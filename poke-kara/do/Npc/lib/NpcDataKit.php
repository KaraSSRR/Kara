<?php

/**
 * NpcDataKit
 *
 * Small wrapper for base_npc_data (cooldowns / visit timers).
 */
class NpcDataKit
{
    private static function db()
    {
        global $mysqli;
        return $mysqli;
    }

    private static function uid($userId)
    {
        if ($userId === null) {
            return isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
        }
        return (int)$userId;
    }

    public static function getUntil($npcId, $userId = null)
    {
        $db = self::db();
        if (!$db) return 0;
        $uid = self::uid($userId);
        $npcId = (int)$npcId;
        $res = $db->query("SELECT `time` FROM `base_npc_data` WHERE `userID` = {$uid} AND `npcID` = {$npcId} ORDER BY `id` DESC LIMIT 1");
        $row = $res ? $res->fetch_assoc() : null;
        return isset($row['time']) ? (int)$row['time'] : 0;
    }

    public static function isActive($npcId, $userId = null)
    {
        return self::getUntil($npcId, $userId) > time();
    }

    public static function setUntil($npcId, $untilTs, $userId = null)
    {
        $db = self::db();
        if (!$db) return false;
        $uid = self::uid($userId);
        $npcId = (int)$npcId;
        $untilTs = (int)$untilTs;

        // Prefer UPSERT if a unique key exists; fallback to insert.
        $sql = "INSERT INTO `base_npc_data` (`userID`,`npcID`,`time`) VALUES (?,?,?)";
        $hasUnique = false;
        $idx = $db->query("SHOW INDEX FROM `base_npc_data` WHERE Key_name = 'ux_user_npc'");
        if ($idx && $idx->num_rows > 0) $hasUnique = true;
        if ($hasUnique) {
            $sql .= " ON DUPLICATE KEY UPDATE `time` = VALUES(`time`)";
        }
        $stmt = $db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('iii', $uid, $npcId, $untilTs);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function setCooldownSeconds($npcId, $seconds, $userId = null)
    {
        $until = time() + (int)$seconds;
        return self::setUntil($npcId, $until, $userId);
    }
}
