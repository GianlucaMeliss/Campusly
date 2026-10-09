<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class UserModel extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password_hash, first_name, last_name) 
            VALUES (:email, :password_hash, :first_name, :last_name)
        ");
        
        $stmt->execute([
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getUserCourses(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                uap.id AS profile_id,
                cc.api_config AS external_course_id, 
                cc.campus_location,
                cc.year,
                c.name AS course_name,
                u.name AS uni_name,
                u.adapter_class 
            FROM user_academic_profiles uap
            JOIN course_curriculums cc ON uap.curriculum_id = cc.id
            JOIN courses c ON uap.course_id = c.id
            JOIN universities u ON c.university_id = u.id
            WHERE uap.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function removeUserCourse(int $userId, int $profileId): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_academic_profiles WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $profileId, 'uid' => $userId]);
    }
    
    public function storeRememberToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_tokens (user_id, token_hash, expires_at) 
            VALUES (:user_id, :token_hash, :expires_at)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt
        ]);
    }

    public function findUserByToken(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.* FROM users u 
            JOIN user_tokens t ON u.id = t.user_id 
            WHERE t.token_hash = :token_hash AND t.expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute(['token_hash' => $tokenHash]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function deleteToken(string $tokenHash): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_tokens WHERE token_hash = :token_hash");
        $stmt->execute(['token_hash' => $tokenHash]);
    }

    /**
     * Collega l'utente a un curriculum ESISTENTE (corso + sede + anno).
     *
     * - Non crea mai nuovi corsi e non modifica mai api_config (è condiviso da tutti gli iscritti).
     * - L'unica eccezione: se il corso non ha ancora nessun curriculum, ne crea uno "AUTO"
     *   (in attesa di configurazione da parte dell'admin), come faceva il fallback del wizard.
     *
     * @return bool false se i dati non corrispondono a niente di esistente
     */
    public function saveAcademicProfile(int $userId, int $universityId, string $courseName, string $sede, int $anno): bool
    {
        $autoConfig = '{"linkCalendarioId":"AUTO","clienteId":"AUTO"}';

        try {
            $this->db->beginTransaction();

            // 1. Il corso deve esistere ed appartenere a un ateneo attivo
            $stmt = $this->db->prepare("
                SELECT c.id
                FROM courses c
                JOIN universities u ON u.id = c.university_id
                WHERE c.name = :name AND c.university_id = :uni_id AND u.is_active = 1
                LIMIT 1
            ");
            $stmt->execute(['name' => $courseName, 'uni_id' => $universityId]);
            $course = $stmt->fetch();

            if (!$course) {
                $this->db->rollBack();
                return false;
            }
            $courseId = (int)$course['id'];

            // 2. Cerca la combinazione Sede + Anno
            $stmtCurr = $this->db->prepare("SELECT id FROM course_curriculums WHERE course_id = :cid AND campus_location = :sede AND year = :anno LIMIT 1");
            $stmtCurr->execute(['cid' => $courseId, 'sede' => $sede, 'anno' => $anno]);
            $curriculum = $stmtCurr->fetch();

            if ($curriculum) {
                $curriculumId = (int)$curriculum['id'];
            } else {
                // Fallback consentito solo se il corso non ha alcun curriculum
                $stmtAny = $this->db->prepare("SELECT COUNT(*) FROM course_curriculums WHERE course_id = :cid");
                $stmtAny->execute(['cid' => $courseId]);

                if ((int)$stmtAny->fetchColumn() > 0) {
                    $this->db->rollBack();
                    return false;
                }

                $stmtInsC = $this->db->prepare("INSERT INTO course_curriculums (course_id, campus_location, year, api_config) VALUES (:cid, :sede, :anno, :conf)");
                $stmtInsC->execute(['cid' => $courseId, 'sede' => $sede, 'anno' => $anno, 'conf' => $autoConfig]);
                $curriculumId = (int)$this->db->lastInsertId();
            }

            // 3. Collegamento utente-curriculum solo se non esiste già
            $stmtCheck = $this->db->prepare("SELECT id FROM user_academic_profiles WHERE user_id = :uid AND curriculum_id = :currid");
            $stmtCheck->execute(['uid' => $userId, 'currid' => $curriculumId]);

            if (!$stmtCheck->fetch()) {
                $insProf = $this->db->prepare("INSERT INTO user_academic_profiles (user_id, course_id, curriculum_id, enrollment_year) VALUES (:uid, :cid, :currid, :year)");
                $insProf->execute(['uid' => $userId, 'cid' => $courseId, 'currid' => $curriculumId, 'year' => date('Y')]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getUserCourseConfig(int $userId): ?array
    {
        // Aggiorniamo la query per leggere dalla nuova tabella course_curriculums (cc)
        $stmt = $this->db->prepare("
            SELECT 
                cc.api_config AS external_course_id, 
                c.name,
                u.adapter_class 
            FROM user_academic_profiles uap
            JOIN course_curriculums cc ON uap.curriculum_id = cc.id
            JOIN courses c ON uap.course_id = c.id
            JOIN universities u ON c.university_id = u.id
            WHERE uap.user_id = :user_id AND uap.is_primary = 1
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
}