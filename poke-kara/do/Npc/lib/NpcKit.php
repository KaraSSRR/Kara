<?php

/**
 * NpcKit
 *
 * A small compatibility-first toolkit for writing NEW NPC logic (quests, storyline, battles)
 * using classes instead of the legacy helper functions.
 *
 * Existing NPC scripts can continue using legacy helpers without modification.
 */
class NpcKit
{
    /** Enable strict mode for NEW scripts to prevent accidental usage of legacy quest helpers. */
    public static function strict()
    {
        if (!defined('NPC_STRICT_LIB')) {
            define('NPC_STRICT_LIB', true);
        }
    }

    public static function userId()
    {
        return isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
    }

    public static function db()
    {
        global $mysqli;
        if (isset($mysqli) && $mysqli) return $mysqli;
        if (class_exists('Work') && isset(Work::$sql) && Work::$sql) return Work::$sql;
        return null;
    }

    public static function loadNpc($npcId)
    {
        $db = self::db();
        if (!$db) return null;
        $npcId = (int)$npcId;
        $row = $db->query("SELECT * FROM `base_npc` WHERE `id` = {$npcId} LIMIT 1");
        return $row ? $row->fetch_assoc() : null;
    }

    /** Parse locations_text into an array of ints. Accepts json arrays or arbitrary digit lists. */
    public static function parseLocations($locationsText)
    {
        if ($locationsText === null) return [];
        $txt = trim((string)$locationsText);
        if ($txt === '') return [];

        // JSON array support
        if ($txt[0] === '[' || $txt[0] === '{') {
            $decoded = json_decode($txt, true);
            if (is_array($decoded)) {
                $vals = [];
                foreach ($decoded as $v) {
                    if (is_numeric($v)) $vals[] = (int)$v;
                }
                return array_values(array_unique($vals));
            }
        }

        // Fallback: extract all numbers
        if (!preg_match_all('/\d+/', $txt, $m)) return [];
        $vals = array_map('intval', $m[0]);
        return array_values(array_unique($vals));
    }

    /** Location gate: loc_id == current OR current is listed in locations_text. */
    public static function isAllowedInLocation(array $npcRow, $userLocationId)
    {
        $userLocationId = (int)$userLocationId;
        $mainLoc = isset($npcRow['loc_id']) ? (int)$npcRow['loc_id'] : 0;
        if ($mainLoc === $userLocationId) return true;

        $extra = self::parseLocations($npcRow['locations_text'] ?? '');
        return in_array($userLocationId, $extra, true);
    }

    /** Build a base response for the frontend dialog. */
    public static function responseBase(array $npcRow)
    {
        $resp = [];
        $resp['npc_id'] = isset($npcRow['id']) ? (int)$npcRow['id'] : 0;
        $resp['name']   = (string)($npcRow['name'] ?? '');
        if (!empty($npcRow['image'])) $resp['image'] = (string)$npcRow['image'];
        if (!empty($npcRow['bg']))    $resp['bg']    = (string)$npcRow['bg'];
        return $resp;
    }

    public static function say(array &$response, $question, $answer = null)
    {
        $response['question'] = (string)$question;
        if ($answer !== null) $response['answer'] = (string)$answer;
    }

    /**
     * Answers are expected by the frontend as an array of pairs:
     *   [[stepOrKey, label], ...]
     */
    public static function answers(array &$response, array $answers)
    {
        $response['answer'] = $answers;
    }

    public static function close(array &$response, $delaySeconds = null)
    {
        $response['closeDialog'] = true;
        if ($delaySeconds !== null) {
            $response['closeDelay'] = (int)$delaySeconds;
        }
    }

    public static function openBattle(array &$response, $battleId)
    {
        $response['battle_id'] = (int)$battleId;
        $response['closeDialog'] = true;
    }

    public static function error(array &$response, $code, $message = null)
    {
        $response['error'] = (int)$code;
        if ($message !== null) {
            $response['question'] = (string)$message;
        }
    }

    /** Safer JSON output. */
    public static function jsonExit(array $response)
    {
        die(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
