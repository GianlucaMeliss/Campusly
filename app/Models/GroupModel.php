<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class GroupModel extends Model
{
    // Crea un nuovo gruppo e vi aggiunge il creatore
    public function createGroup(int $userId, string $groupName, string $privacyLevel = 'transparent'): array
    {
        // Genera un codice di invito univoco di 8 caratteri
        $inviteCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO study_groups (name, invite_code, created_by) VALUES (:name, :code, :user_id)");
            $stmt->execute(['name' => $groupName, 'code' => $inviteCode, 'user_id' => $userId]);
            $groupId = (int)$this->db->lastInsertId();

            $this->joinGroup($userId, $inviteCode, $privacyLevel);

            $this->db->commit();
            return ['status' => 'success', 'group_id' => $groupId, 'invite_code' => $inviteCode];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Aggiunge un utente a un gruppo tramite codice
    public function joinGroup(int $userId, string $inviteCode, string $privacyLevel = 'logistical'): array
    {
        $stmt = $this->db->prepare("SELECT id FROM study_groups WHERE invite_code = :code");
        $stmt->execute(['code' => $inviteCode]);
        $group = $stmt->fetch();

        if (!$group) {
            return ['status' => 'error', 'message' => 'Codice invito non valido'];
        }

        $groupId = $group['id'];

        $stmtIns = $this->db->prepare("
            INSERT IGNORE INTO group_members (group_id, user_id, privacy_level) 
            VALUES (:group_id, :user_id, :privacy)
            ON DUPLICATE KEY UPDATE privacy_level = :privacy
        ");
        
        $stmtIns->execute([
            'group_id' => $groupId,
            'user_id' => $userId,
            'privacy' => $privacyLevel
        ]);

        return ['status' => 'success', 'group_id' => $groupId];
    }

    // Recupera i gruppi di cui fa parte l'utente
    public function getUserGroups(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT sg.id, sg.name, sg.invite_code, gm.privacy_level 
            FROM study_groups sg
            JOIN group_members gm ON sg.id = gm.group_id
            WHERE gm.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    // Recupera tutti i membri di un gruppo (necessario per il merge degli orari)
    public function getGroupMembers(int $groupId): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.first_name, u.last_name, gm.privacy_level 
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = :group_id
        ");
        $stmt->execute(['group_id' => $groupId]);
        return $stmt->fetchAll() ?: [];
    }
}