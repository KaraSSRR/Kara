<?php
/**
 * NpcBattle — запуск PvE боя из NPC-скриптов (прямая вставка в battle), без изменения HP.
 *
 * ВАЖНО:
 * - НЕ лечит/не поднимает HP. Берёт фактическое hp из user_pokemons.
 * - Если нет ни одного покемона с hp>0 — бой не стартует (это корректно).
 * - Гарантирует, что есть активный покемон с hp>0: если активного нет — выбирает живого с max hp и ставит active=1.
 *
 * После вставки:
 * - users.status='battle'
 * - users.status_id=battle_id
 * - battle_log starter
 *
 * Логирование в error_log: [NpcBattle] ...
 */
class NpcBattle
{
    private static function db()
    {
        if (class_exists('Work') && isset(Work::$sql) && Work::$sql) {
            return Work::$sql;
        }
        global $mysqli;
        if (isset($mysqli) && $mysqli) {
            return $mysqli;
        }
        return null;
    }

    private static function ensureInfoLoaded()
    {
        if (class_exists('Info')) return true;
        $root = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
        $candidates = array(
            $root.'/inc/function/Info.php',
            $root.'/inc/function/Functions.php',
        );
        foreach ($candidates as $p) {
            if ($p && file_exists($p)) {
                require_once $p;
                if (class_exists('Info')) return true;
            }
        }
        return class_exists('Info');
    }

    private static function logMsg($msg)
    {
        error_log('[NpcBattle] '.$msg);
    }

    private static function getUser($userId)
    {
        $db = self::db();
        if (!$db) return array();
        $res = $db->query('SELECT * FROM `users` WHERE `id`='.(int)$userId);
        $row = $res ? $res->fetch_assoc() : array();
        return is_array($row) ? $row : array();
    }

    private static function ensureActiveAlive($userId)
    {
        $db = self::db();
        if (!$db) return false;

        // Уже есть active и живой?
        $has = $db->query('SELECT `id` FROM `user_pokemons` WHERE `user_id`='.(int)$userId.' AND `active`=1 AND `hp`>0 LIMIT 1')->fetch_assoc();
        if (!empty($has)) return true;

        // Выбираем живого с максимальным hp и делаем активным (HP НЕ меняем!)
        $best = $db->query('SELECT `id` FROM `user_pokemons` WHERE `user_id`='.(int)$userId.' AND `hp`>0 ORDER BY `hp` DESC, `id` DESC LIMIT 1')->fetch_assoc();
        if (empty($best['id'])) {
            self::logMsg('No alive pokemons (hp>0) for user_id='.(int)$userId);
            return false;
        }
        $pid = (int)$best['id'];

        $db->query('UPDATE `user_pokemons` SET `active`=0 WHERE `user_id`='.(int)$userId);
        $db->query('UPDATE `user_pokemons` SET `active`=1 WHERE `id`='.$pid.' AND `user_id`='.(int)$userId);

        $has2 = $db->query('SELECT `id` FROM `user_pokemons` WHERE `user_id`='.(int)$userId.' AND `active`=1 AND `hp`>0 LIMIT 1')->fetch_assoc();
        return !empty($has2);
    }

    private static function getLocationCtx($locationId)
    {
        $db = self::db();
        $locationId = (int)$locationId;
        if ($locationId <= 0) $locationId = 1;

        $weather = 1;
        $img = 0;

        if ($db) {
            $loc = $db->query('SELECT `region`,`img_fight`,`weather` FROM `base_location` WHERE `id`='.$locationId)->fetch_assoc();
            if (!empty($loc['weather'])) $weather = (int)$loc['weather'];
            $img = (int)(isset($loc['img_fight']) ? $loc['img_fight'] : 0);

            if ($weather <= 0 && !empty($loc['region'])) {
                $reg = $db->query('SELECT `weather` FROM `base_region` WHERE `id`='.(int)$loc['region'])->fetch_assoc();
                if (!empty($reg['weather'])) $weather = (int)$reg['weather'];
            }
        }

        return array(
            'weather' => ($weather > 0 ? $weather : 1),
            'weather_round' => 10,
            'img' => $img,
            'arena' => $locationId,
            'location' => $locationId,
        );
    }

    public static function start($npcPokes, $env = array())
    {
        if (!self::ensureInfoLoaded()) {
            self::logMsg('Info class not loaded');
            return 0;
        }
        $db = self::db();
        if (!$db) {
            self::logMsg('DB handler not found');
            return 0;
        }

        $userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
        if ($userId <= 0) return 0;

        $u = self::getUser($userId);
        if (!empty($u) && isset($u['status']) && $u['status'] === 'battle' && !empty($u['status_id'])) {
            return (int)$u['status_id'];
        }

        if (!self::ensureActiveAlive($userId)) {
            return 0;
        }

        $battleType = isset($env['battle_type']) ? (string)$env['battle_type'] : 'pve';
        if ($battleType !== 'pve' && $battleType !== 'npc' && $battleType !== 'boss') $battleType = 'pve';

        $locationId = isset($env['location']) ? (int)$env['location'] : (isset($u['location']) ? (int)$u['location'] : 1);
        $ctx = self::getLocationCtx($locationId);

        $weather = isset($env['weather']) ? (int)$env['weather'] : (int)$ctx['weather'];
        $weatherRound = isset($env['weather_round']) ? (int)$env['weather_round'] : (int)$ctx['weather_round'];
        $img = isset($env['img']) ? (int)$env['img'] : (int)$ctx['img'];
        $arena = isset($env['arena']) ? (int)$env['arena'] : (int)$ctx['arena'];

        // Стороны боя как в движке
        $infoUserArr = Info::_userInfoBattle($userId, 'pve', array('uinfo' => $u));
        $catchable = 0;
        if (is_array($npcPokes)) {
            foreach ($npcPokes as $p) {
                if (isset($p['catch']) && (int)$p['catch'] === 1) { $catchable = 1; break; }
            }
        }
        $enemyUinfo = array('user_group' => ($catchable ? 6 : 1), 'catch' => ($catchable ? 1 : 0));
        $infoEnemyArr = Info::_userInfoBattle(0, 'pve', array('npc' => $npcPokes, 'uinfo' => $enemyUinfo));

        if (empty($infoUserArr) || empty($infoEnemyArr)) {
            self::logMsg('Info::_userInfoBattle returned empty (player='.(!empty($infoUserArr)?'ok':'empty').', enemy='.(!empty($infoEnemyArr)?'ok':'empty').')');
            return 0;
        }

        $info1 = Info::_parseData($infoUserArr);
        $info2 = Info::_parseData($infoEnemyArr);

        $info1Sql = $db->real_escape_string($info1);
        $info2Sql = $db->real_escape_string($info2);
        $typeSql  = $db->real_escape_string($battleType);

        $sql = 'INSERT INTO `battle`
            (`round`,`user_1`,`user_2`,`info_1`,`info_2`,`type`,`weather`,`weather_round`,`img`,`arena`)
            VALUES (
                1,
                '.$userId.',
                0,
                "'.$info1Sql.'",
                "'.$info2Sql.'",
                "'.$typeSql.'",
                '.(int)$weather.',
                '.(int)$weatherRound.',
                '.(int)$img.',
                '.(int)$arena.'
            )';

        if (!$db->query($sql)) {
            self::logMsg('Battle insert failed: '.$db->error);
            return 0;
        }

        $battleId = (int)$db->insert_id;
        if ($battleId <= 0) {
            self::logMsg('insert_id=0 after battle insert');
            return 0;
        }

        $db->query('UPDATE `users` SET `status`="battle", `status_id`='.$battleId.' WHERE `id`='.$userId);

        if (function_exists('limit_pok_plus')) {
            limit_pok_plus();
        }

        // Лог старта (как в _generatePve: text пустой)
        $db->query('INSERT INTO `battle_log` (`battle`,`round`,`text`,`end`,`user`,`starter`)
                   VALUES ('.$battleId.', 0, "", 0, '.$userId.', 1)');

        return $battleId;
    }
}
