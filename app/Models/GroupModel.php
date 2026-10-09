<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class GroupModel extends Model
{
    public function createGroup(int $userId, string $groupName, string $privacyLevel = 'transparent'): array
    {
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

    public function joinGroup(int $userId, string $inviteCode, string $privacyLevel = 'logistical'): array
    {
        $stmt = $this->db->prepare("SELECT id FROM study_groups WHERE invite_code = :code");
        $stmt->execute(['code' => $inviteCode]);
        $group = $stmt->fetch();

        if (!$group) {
            return ['status' => 'error', 'message' => 'Codice invito non valido'];
        }

        $groupId = $group['id'];

        // Controllo esplicito per evitare errori PDO
        $check = $this->db->prepare("SELECT user_id FROM group_members WHERE group_id = :gid AND user_id = :uid");
        $check->execute(['gid' => $groupId, 'uid' => $userId]);

        if ($check->fetch()) {
            $upd = $this->db->prepare("UPDATE group_members SET privacy_level = :priv WHERE group_id = :gid AND user_id = :uid");
            $upd->execute(['priv' => $privacyLevel, 'gid' => $groupId, 'uid' => $userId]);
        } else {
            $ins = $this->db->prepare("INSERT INTO group_members (group_id, user_id, privacy_level) VALUES (:gid, :uid, :priv)");
            $ins->execute(['gid' => $groupId, 'uid' => $userId, 'priv' => $privacyLevel]);
        }

        return ['status' => 'success', 'group_id' => $groupId];
    }

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