<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class HiddenCourseModel extends Model
{
    public function getHiddenCourses(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT course_name FROM hidden_courses WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        
        // Restituiamo un array semplice (piatto) di stringhe: ['MATEMATICA', 'FISICA']
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    public function toggleCourse(int $userId, string $courseName): array
    {
        // Controlla se il corso è già nascosto
        $stmt = $this->db->prepare("SELECT id FROM hidden_courses WHERE user_id = :user_id AND course_name = :course_name");
        $stmt->execute(['user_id' => $userId, 'course_name' => $courseName]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Se esiste, lo rimuoviamo (lo rendiamo di nuovo visibile)
            $delStmt = $this->db->prepare("DELETE FROM hidden_courses WHERE id = :id");
            $delStmt->execute(['id' => $exists['id']]);
            return ['status' => 'success', 'action' => 'unhidden'];
        } else {
            // Se non esiste, lo nascondiamo
            $insStmt = $this->db->prepare("INSERT INTO hidden_courses (user_id, course_name) VALUES (:user_id, :course_name)");
            $insStmt->execute(['user_id' => $userId, 'course_name' => $courseName]);
            return ['status' => 'success', 'action' => 'hidden'];
        }
    }
}