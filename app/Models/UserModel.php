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

    // In app/Models/UserModel.php
    public function saveAcademicProfile(int $userId, int $universityId, string $courseName, string $sede, int $anno, string $apiConfig): void
    {
        // 1. Cerca il corso principale
        $stmt = $this->db->prepare("SELECT id FROM courses WHERE name = :name AND university_id = :uni_id");
        $stmt->execute(['name' => $courseName, 'uni_id' => $universityId]);
        $course = $stmt->fetch();
        
        if ($course) {
            $courseId = (int)$course['id'];
        } else {
            $stmtIns = $this->db->prepare("INSERT INTO courses (university_id, name) VALUES (:uni_id, :name)");
            $stmtIns->execute(['uni_id' => $universityId, 'name' => $courseName]);
            $courseId = (int)$this->db->lastInsertId();
        }

        // 2. Cerca se esiste già la combinazione specifica Anno + Sede nel DB
        $stmtCurr = $this->db->prepare("SELECT id FROM course_curriculums WHERE course_id = :cid AND campus_location = :sede AND year = :anno LIMIT 1");
        $stmtCurr->execute(['cid' => $courseId, 'sede' => $sede, 'anno' => $anno]);
        $curriculum = $stmtCurr->fetch();

        if ($curriculum) {
            $curriculumId = (int)$curriculum['id'];
            // Se siamo nello step manuale per smanettoni, sovrascriviamo l'api_config "AUTO" con i dati reali
            if ($apiConfig !== '{"linkCalendarioId":"AUTO","clienteId":"AUTO"}') {
                $upd = $this->db->prepare("UPDATE course_curriculums SET api_config = :conf WHERE id = :id");
                $upd->execute(['conf' => $apiConfig, 'id' => $curriculumId]);
            }
        } else {
            // Se non esiste, lo inseriamo
            $stmtInsC = $this->db->prepare("INSERT INTO course_curriculums (course_id, campus_location, year, api_config) VALUES (:cid, :sede, :anno, :conf)");
            $stmtInsC->execute(['cid' => $courseId, 'sede' => $sede, 'anno' => $anno, 'conf' => $apiConfig]);
            $curriculumId = (int)$this->db->lastInsertId();
        }

        // 3. IL FIX FINALE: Collega l'utente al nuovo curriculum senza sovrascrivere gli altri
        $stmtCheck = $this->db->prepare("SELECT id FROM user_academic_profiles WHERE user_id = :uid AND curriculum_id = :currid");
        $stmtCheck->execute(['uid' => $userId, 'currid' => $curriculumId]);
        
        // Se non è già iscritto esattamente a questa combo, lo inseriamo
        if (!$stmtCheck->fetch()) {
            $insProf = $this->db->prepare("INSERT INTO user_academic_profiles (user_id, course_id, curriculum_id, enrollment_year) VALUES (:uid, :cid, :currid, :year)");
            $insProf->execute(['uid' => $userId, 'cid' => $courseId, 'currid' => $curriculumId, 'year' => date('Y')]);
        }
        if ($profile) {
            // Aggiorna il profilo esistente con il nuovo id
            $updProf = $this->db->prepare("UPDATE user_academic_profiles SET course_id = :cid, curriculum_id = :currid WHERE id = :pid");
            $updProf->execute(['cid' => $courseId, 'currid' => $curriculumId, 'pid' => $profile['id']]);
        } else {
            // Se l'utente non aveva alcun profilo, crealo
            $insProf = $this->db->prepare("INSERT INTO user_academic_profiles (user_id, course_id, curriculum_id, enrollment_year) VALUES (:uid, :cid, :currid, :year)");
            $insProf->execute(['uid' => $userId, 'cid' => $courseId, 'currid' => $curriculumId, 'year' => date('Y')]);
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