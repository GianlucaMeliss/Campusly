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
    public function saveAcademicProfile(int $userId, int $universityId, string $courseName, string $externalId, string $department = ''): void
    {
        // 1. Inseriamo il corso "personalizzato" nel database
        $stmt = $this->db->prepare("
            INSERT INTO courses (university_id, name, external_course_id, department) 
            VALUES (:uni_id, :name, :ext_id, :dept)
        ");
        $stmt->execute([
            'uni_id' => $universityId,
            'name' => $courseName,
            'ext_id' => $externalId, // Qui salveremo un JSON con clienteId e linkCalendarioId
            'dept' => $department
        ]);
        
        $courseId = (int)$this->db->lastInsertId();

        // 2. Colleghiamo l'utente al corso
        $stmt2 = $this->db->prepare("
            INSERT INTO user_academic_profiles (user_id, course_id, enrollment_year) 
            VALUES (:user_id, :course_id, :year)
        ");
        $stmt2->execute([
            'user_id' => $userId,
            'course_id' => $courseId,
            'year' => date('Y')
        ]);
    }

    public function getUserCourseConfig(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                c.external_course_id, 
                u.adapter_class 
            FROM user_academic_profiles uap
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