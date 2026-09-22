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

    // Gestione del "Remember Me"
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
        // 1. Cerca se il corso base esiste già (es. "Informatica" all'Insubria)
        $stmt = $this->db->prepare("SELECT id FROM courses WHERE name = :name AND university_id = :uni_id");
        $stmt->execute(['name' => $courseName, 'uni_id' => $universityId]);
        $course = $stmt->fetch();
        
        if ($course) {
            $courseId = (int)$course['id'];
        } else {
            // Se non esiste, creiamo il corso base
            $stmtIns = $this->db->prepare("INSERT INTO courses (university_id, name) VALUES (:uni_id, :name)");
            $stmtIns->execute(['uni_id' => $universityId, 'name' => $courseName]);
            $courseId = (int)$this->db->lastInsertId();
        }

        // 2. Crea il curriculum specifico (Anno + Sede + Codici API)
        $stmtCurr = $this->db->prepare("
            INSERT INTO course_curriculums (course_id, campus_location, year, api_config) 
            VALUES (:course_id, :campus, :year, :api_config)
        ");
        $stmtCurr->execute([
            'course_id' => $courseId,
            'campus' => $sede,
            'year' => $anno,
            'api_config' => $apiConfig
        ]);
        $curriculumId = (int)$this->db->lastInsertId();

        // 3. Collega l'utente al corso e al suo curriculum specifico
        $stmtLink = $this->db->prepare("
            INSERT INTO user_academic_profiles (user_id, course_id, curriculum_id, enrollment_year) 
            VALUES (:user_id, :course_id, :curr_id, :year)
        ");
        $stmtLink->execute([
            'user_id' => $userId,
            'course_id' => $courseId,
            'curr_id' => $curriculumId,
            'year' => date('Y')
        ]);
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