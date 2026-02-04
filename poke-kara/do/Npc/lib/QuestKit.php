<?php

/**
 * QuestKit
 *
 * Unified and safe wrapper around `user_quests`.
 *
 * Notes:
 * - `user_quests.step_data` is INT in the provided schema dump.
 * - `user_quests.data` is JSON (optional state blob).
 * - Old NPC scripts can keep using quest_step/quest_update helpers.
 */
class QuestKit
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

    public static function get($questId, $userId = null)
    {
        $db = self::db();
        if (!$db) return null;
        $questId = (int)$questId;
        $uid = self::uid($userId);
        $res = $db->query("SELECT * FROM `user_quests` WHERE `user_id` = {$uid} AND `quest_id` = {$questId} ORDER BY `id` DESC LIMIT 1");
        return $res ? $res->fetch_assoc() : null;
    }

    public static function exists($questId, $userId = null)
    {
        return self::get($questId, $userId) ? true : false;
    }

    /**
     * True if current step matches. If $step=0 => always true (legacy semantics).
     * If $end is not null, end flag must also match.
     */
    public static function isStep($questId, $step, $end = null, $userId = null)
    {
        $step = (int)$step;
        if ($step === 0) return true;
        $row = self::get($questId, $userId);
        if (!$row) return false;
        if ((int)$row['step'] !== $step) return false;
        if ($end !== null && (int)$row['end'] !== (int)$end) return false;
        return true;
    }

    public static function step($questId, $userId = null)
    {
        $row = self::get($questId, $userId);
        return $row ? (int)$row['step'] : 0;
    }

    public static function end($questId, $userId = null)
    {
        $row = self::get($questId, $userId);
        return $row ? (int)$row['end'] : 0;
    }

    public static function stepData($questId, $userId = null)
    {
        $row = self::get($questId, $userId);
        return $row ? ($row['step_data'] === null ? null : (int)$row['step_data']) : null;
    }

    public static function data($questId, $userId = null)
    {
        $row = self::get($questId, $userId);
        if (!$row) return [];
        if (empty($row['data'])) return [];
        $decoded = json_decode($row['data'], true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function dataGet($questId, $key, $default = null, $userId = null)
    {
        $data = self::data($questId, $userId);
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    /**
     * Upsert quest progress.
     *
     * $dataPatch (optional): shallow-merge into existing data json.
     */
    public static function set($questId, $step, $end = 0, $stepData = null, $dataPatch = null, $userId = null)
    {
        $db = self::db();
        if (!$db) return false;
        $questId = (int)$questId;
        $uid = self::uid($userId);
        $step = (int)$step;
        $end = (int)$end;
        $stepData = ($stepData === null) ? null : (int)$stepData;

        $existing = self::get($questId, $uid);
        $dataJson = null;
        if (is_array($dataPatch)) {
            $current = $existing ? (self::data($questId, $uid)) : [];
            foreach ($dataPatch as $k => $v) {
                $current[$k] = $v;
            }
            $dataJson = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif ($existing && isset($existing['data'])) {
            // Preserve existing JSON if no patch.
            $dataJson = $existing['data'];
        }

        if ($existing) {
            if ($dataJson !== null) {
                $stmt = $db->prepare("UPDATE `user_quests` SET `step`=?, `end`=?, `step_data`=?, `data`=? WHERE `user_id`=? AND `quest_id`=?");
                $stmt->bind_param('iiisii', $step, $end, $stepData, $dataJson, $uid, $questId);
            } else {
                $stmt = $db->prepare("UPDATE `user_quests` SET `step`=?, `end`=?, `step_data`=? WHERE `user_id`=? AND `quest_id`=?");
                $stmt->bind_param('iiiii', $step, $end, $stepData, $uid, $questId);
            }
        } else {
            $stmt = $db->prepare("INSERT INTO `user_quests` (`quest_id`,`user_id`,`step`,`end`,`step_data`,`data`) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('iiiiis', $questId, $uid, $step, $end, $stepData, $dataJson);
        }

        $ok = $stmt->execute();
        $stmt->close();
        return (bool)$ok;
    }
}
