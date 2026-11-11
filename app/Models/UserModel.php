<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;
use Throwable;

class UserModel extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, email, first_name, last_name, role, created_at 
            FROM users 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findAuthByEmail(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, password_hash, password_changed_at, first_name, last_name, role 
                FROM users 
                WHERE email = :email 
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (PDOException) {
            $stmt = $this->db->prepare("
                SELECT id, email, password_hash, first_name, last_name, role 
                FROM users 
                WHERE email = :email 
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            return $user ?: null;
        }
    }

    public function getPasswordChangedAt(int $userId): ?string
    {
        try {
            $stmt = $this->db->prepare("SELECT password_changed_at FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $userId]);
            $val = $stmt->fetchColumn();
            return is_string($val) && $val !== '' ? $val : null;
        } catch (PDOException) {
            return null;
        }
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password_hash, first_name, last_name) 
            VALUES (:email, :password_hash, :first_name, :last_name)
        ");
        $stmt->execute([
            'email'         => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name']
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
                c.university_id,
                u.name AS uni_name,
                u.adapter_class 
            FROM user_academic_profiles uap
            JOIN course_curriculums cc ON uap.curriculum_id = cc.id
            JOIN courses c ON uap.course_id = c.id
            JOIN universities u ON c.university_id = u.id
            WHERE uap.user_id = :user_id
            ORDER BY uap.is_primary DESC, uap.id ASC
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
        $now = gmdate('Y-m-d H:i:s');
        $stmtDel = $this->db->prepare("DELETE FROM user_tokens WHERE expires_at < :now");
        $stmtDel->execute(['now' => $now]);

        $stmtOld = $this->db->prepare("
            SELECT id FROM user_tokens WHERE user_id = :uid ORDER BY expires_at DESC LIMIT 100 OFFSET 4
        ");
        $stmtOld->execute(['uid' => $userId]);
        $oldIds = $stmtOld->fetchAll(\PDO::FETCH_COLUMN);

        if (!empty($oldIds)) {
            $placeholders = implode(',', array_fill(0, count($oldIds), '?'));
            $delOld = $this->db->prepare("DELETE FROM user_tokens WHERE id IN ($placeholders)");
            $delOld->execute(array_map('intval', $oldIds));
        }

        $stmt = $this->db->prepare("
            INSERT INTO user_tokens (user_id, token_hash, expires_at) 
            VALUES (:user_id, :token_hash, :expires_at)
        ");
        $stmt->execute([
            'user_id'    => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt
        ]);
    }

    public function findUserByToken(string $tokenHash): ?array
    {
        $now = gmdate('Y-m-d H:i:s');
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.first_name, u.password_changed_at 
                FROM users u 
                JOIN user_tokens t ON u.id = t.user_id 
                WHERE t.token_hash = :token_hash AND t.expires_at > :now 
                LIMIT 1
            ");
            $stmt->execute(['token_hash' => $tokenHash, 'now' => $now]);
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (PDOException) {
            $stmt = $this->db->prepare("
                SELECT u.id, u.first_name 
                FROM users u 
                JOIN user_tokens t ON u.id = t.user_id 
                WHERE t.token_hash = :token_hash AND t.expires_at > :now 
                LIMIT 1
            ");
            $stmt->execute(['token_hash' => $tokenHash, 'now' => $now]);
            $user = $stmt->fetch();
            return $user ?: null;
        }
    }

    public function deleteToken(string $tokenHash): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_tokens WHERE token_hash = :token_hash");
        $stmt->execute(['token_hash' => $tokenHash]);
    }

    public function deleteAllTokensForUser(int $userId): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_tokens WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
    }

    public function createPasswordReset(int $userId, string $tokenHash, string $expiresAt): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->prepare("DELETE FROM password_resets WHERE user_id = :uid OR expires_at < :now")
                 ->execute(['uid' => $userId, 'now' => $now]);

        $stmt = $this->db->prepare("
            INSERT INTO password_resets (user_id, token_hash, expires_at) 
            VALUES (:uid, :hash, :exp)
        ");
        $stmt->execute(['uid' => $userId, 'hash' => $tokenHash, 'exp' => $expiresAt]);
    }

    public function findValidPasswordReset(string $tokenHash): ?array
    {
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            SELECT id, user_id, token_hash, expires_at 
            FROM password_resets 
            WHERE token_hash = :hash AND used_at IS NULL AND expires_at > :now 
            LIMIT 1
        ");
        $stmt->execute(['hash' => $tokenHash, 'now' => $now]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function consumePasswordReset(int $resetId, int $userId, string $newPassword): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->beginTransaction();
        try {
            try {
                $updUser = $this->db->prepare("
                    UPDATE users 
                    SET password_hash = :hash, password_changed_at = :changed_at 
                    WHERE id = :uid
                ");
                $updUser->execute([
                    'hash'       => password_hash($newPassword, PASSWORD_DEFAULT),
                    'changed_at' => $now,
                    'uid'        => $userId,
                ]);
            } catch (PDOException) {
                $updUser = $this->db->prepare("UPDATE users SET password_hash = :hash WHERE id = :uid");
                $updUser->execute([
                    'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'uid'  => $userId,
                ]);
            }

            $updReset = $this->db->prepare("UPDATE password_resets SET used_at = :now WHERE id = :id");
            $updReset->execute(['now' => $now, 'id' => $resetId]);

            $this->deleteAllTokensForUser($userId);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function saveAcademicProfile(int $userId, int $universityId, string $courseName, string $sede, int $anno): bool
    {
        $autoConfig = '{"linkCalendarioId":"AUTO","clienteId":"AUTO"}';

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT c.id FROM courses c
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

            $stmtCurr = $this->db->prepare("SELECT id FROM course_curriculums WHERE course_id = :cid AND campus_location = :sede AND year = :anno LIMIT 1");
            $stmtCurr->execute(['cid' => $courseId, 'sede' => $sede, 'anno' => $anno]);
            $curriculum = $stmtCurr->fetch();

            if ($curriculum) {
                $curriculumId = (int)$curriculum['id'];
            } else {
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

            // C4: INSERT IGNORE atomico per evitare race condition da doppio click
            $insProf = $this->db->prepare("
                INSERT IGNORE INTO user_academic_profiles (user_id, course_id, curriculum_id, enrollment_year) 
                VALUES (:uid, :cid, :currid, :year)
            ");
            $insProf->execute([
                'uid'    => $userId,
                'cid'    => $courseId,
                'currid' => $curriculumId,
                'year'   => gmdate('Y'),
            ]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getUserCourseConfig(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT cc.api_config AS external_course_id, c.name, c.university_id, u.adapter_class 
            FROM user_academic_profiles uap
            JOIN course_curriculums cc ON uap.curriculum_id = cc.id
            JOIN courses c ON uap.course_id = c.id
            JOIN universities u ON c.university_id = u.id
            WHERE uap.user_id = :user_id
            ORDER BY uap.is_primary DESC, uap.id ASC
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
