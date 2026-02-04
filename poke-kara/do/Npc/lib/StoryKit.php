<?php

/**
 * StoryKit (linear storyline)
 *
 * Stores storyline progress independently from quests, so you can compose complex story arcs
 * without overloading `user_quests`.
 *
 * Default storage table: `user_storylines` (see migrations).
 *
 * NEW NPC scripts should call NpcKit::strict() and then use StoryKit/QuestKit.
 */
class StoryKit
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

    public static function get($code, $userId = null)
    {
        $db = self::db();
        if (!$db) return null;
        $uid = self::uid($userId);
        $code = $db->real_escape_string((string)$code);
        $res = $db->query("SELECT * FROM `user_storylines` WHERE `user_id` = {$uid} AND `code` = '{$code}' LIMIT 1");
        return $res ? $res->fetch_assoc() : null;
    }

    public static function ensure($code, $step = 1, $userId = null)
    {
        $db = self::db();
        if (!$db) return false;
        $uid = self::uid($userId);
        $codeStr = (string)$code;
        $row = self::get($codeStr, $uid);
        if ($row) return true;

        $now = time();
        $stmt = $db->prepare("INSERT INTO `user_storylines` (`user_id`, `code`, `step`, `data`, `started_at`, `updated_at`) VALUES (?,?,?,NULL,?,?)");
        $stmt->bind_param('isiii', $uid, $codeStr, $step, $now, $now);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function step($code, $userId = null)
    {
        $row = self::get($code, $userId);
        return $row ? (int)$row['step'] : 0;
    }

    public static function isStep($code, $step, $userId = null)
    {
        return self::step($code, $userId) === (int)$step;
    }

    public static function setStep($code, $step, $userId = null)
    {
        $db = self::db();
        if (!$db) return false;
        $uid = self::uid($userId);
        $codeStr = (string)$code;
        self::ensure($codeStr, 1, $uid);
        $now = time();
        $stmt = $db->prepare("UPDATE `user_storylines` SET `step` = ?, `updated_at` = ? WHERE `user_id` = ? AND `code` = ?");
        $stmt->bind_param('iiis', $step, $now, $uid, $codeStr);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function advance($code, $by = 1, $userId = null)
    {
        $cur = self::step($code, $userId);
        $next = max(1, $cur + (int)$by);
        return self::setStep($code, $next, $userId);
    }

    public static function data($code, $userId = null)
    {
        $row = self::get($code, $userId);
        if (!$row || empty($row['data'])) return [];
        $decoded = json_decode($row['data'], true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function setData($code, array $patch, $userId = null)
    {
        $db = self::db();
        if (!$db) return false;
        $uid = self::uid($userId);
        $codeStr = (string)$code;
        self::ensure($codeStr, 1, $uid);

        $current = self::data($codeStr, $uid);
        foreach ($patch as $k => $v) {
            $current[$k] = $v;
        }
        $json = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now = time();

        $stmt = $db->prepare("UPDATE `user_storylines` SET `data` = ?, `updated_at` = ? WHERE `user_id` = ? AND `code` = ?");
        $stmt->bind_param('siis', $json, $now, $uid, $codeStr);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Optional: load storyline definition from /do/Npc/story/defs/<code>.json */
    public static function def($code)
    {
        $path = __DIR__ . '/../story/defs/' . basename((string)$code) . '.json';
        if (!file_exists($path)) return null;
        $raw = file_get_contents($path);
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
