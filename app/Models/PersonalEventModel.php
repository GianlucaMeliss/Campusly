<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PersonalEventModel extends Model
{
    public function getUserEvents(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM personal_events WHERE user_id = :user_id ORDER BY start_time ASC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function createEvent(int $userId, array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO personal_events (user_id, title, start_time, end_time, location) 
            VALUES (:user_id, :title, :start_time, :end_time, :location)
        ");
        
        $stmt->execute([
            'user_id'    => $userId,
            'title'      => $data['title'],
            'start_time' => $data['start_time'],
            'end_time'   => $data['end_time'],
            'location'   => $data['location'] ?? 'Non specificato'
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function deleteEvent(int $userId, int $eventId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM personal_events WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([
            'id' => $eventId,
            'user_id' => $userId
        ]);
    }
}