<?php
/**
 * Очередь Арены (PvP РБ) + ELO + выдача команд (выданные покемоны).
 * Зависимости:
 *  - Work::$sql (mysqli)
 *  - Info::_parseData / Info::_unParseData
 *  - functions_rb.php (rb_issuePokemons, rb_removePokemons, rb_getIssuedTeam)
 */

if (!class_exists('Work')) { exit('Work class is required'); }
require_once($_SERVER['DOCUMENT_ROOT'].'/rb/functions_rb.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/makasimka/inc/classes/Info.php');

class ArenaQueue {

    /** Константы/настройки */
    const QUEUE_TTL_SEC     = 600;    // 10 минут
    const DEFAULT_ELO       = 1200;
    const K_FACTOR_BASE     = 32;     // базовый K для ELO
    const K_FACTOR_STREAK   = 40;     // повышенный, если серия
    const ARENA_LOCATION_ID = 8009;      // 0 = любая; поставь ID локации арены, если надо фикс
    const RB_POKES_COUNT    = 6;      // сколько выдавать в РБ

    /** Шаги расширения допуска по разнице ELO (диапазон растёт со временем ожидания) */
    const ELO_BASE_RANGE    = 100; // сразу
    const ELO_RANGE_PER_MIN = 50;  // +50 за каждую минуту ожидания

    /** Инициализация таблиц (без friends_news; notification не создаём, используем существующую) */
    public static function migrate(): void {
        $db = Work::$sql;
        // Очередь
        $db->query("CREATE TABLE IF NOT EXISTS `rb_queue` (
            `user_id` INT NOT NULL PRIMARY KEY,
            `location_id` INT NOT NULL DEFAULT 0,
            `elo` INT NOT NULL DEFAULT 1200,
            `enqueued_at` INT NOT NULL,
            `state` ENUM('search','matched','expired') NOT NULL DEFAULT 'search',
            `opponent_id` INT NOT NULL DEFAULT 0,
            `session_id` VARCHAR(64) NOT NULL DEFAULT '',
            `ready` TINYINT(1) NOT NULL DEFAULT 0,
            KEY (`location_id`),
            KEY (`state`),
            KEY (`enqueued_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Рейтинг по арене
        $db->query("CREATE TABLE IF NOT EXISTS `arena_rating` (
            `user_id` INT NOT NULL PRIMARY KEY,
            `elo` INT NOT NULL DEFAULT 1200,
            `wins` INT NOT NULL DEFAULT 0,
            `losses` INT NOT NULL DEFAULT 0,
            `streak` INT NOT NULL DEFAULT 0,
            `last_match_ts` INT NOT NULL DEFAULT 0,
            `location_id` INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Логи матчей
        $db->query("CREATE TABLE IF NOT EXISTS `arena_matches` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `user1` INT NOT NULL,
            `user2` INT NOT NULL,
            `winner` INT NOT NULL,
            `loser` INT NOT NULL,
            `location_id` INT NOT NULL DEFAULT 0,
            `started_at` INT NOT NULL,
            `ended_at` INT NOT NULL,
            `u1_elo_before` INT NOT NULL,
            `u2_elo_before` INT NOT NULL,
            `u1_elo_after` INT NOT NULL,
            `u2_elo_after` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Выданные команды
        $db->query("CREATE TABLE IF NOT EXISTS `rb_user_team` (
            `user_id` INT NOT NULL PRIMARY KEY,
            `team_json` TEXT NOT NULL,
            `created_at` INT NOT NULL,
            `expires_at` INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /** Получить ELO юзера (или дефолт) */
    public static function getElo(int $userId, int $locationId = 0): int {
        $db = Work::$sql;
        $r = $db->query("SELECT elo FROM arena_rating WHERE user_id={$userId} LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) {
            return (int)$row['elo'];
        }
        return self::DEFAULT_ELO;
    }

    /** Обновить ELO по матчу */
    public static function updateEloAfterMatch(int $winnerId, int $loserId, int $locationId = 0): array {
        $Ra = self::getElo($winnerId, $locationId);
        $Rb = self::getElo($loserId,  $locationId);

        $Ea = 1.0 / (1.0 + pow(10.0, ($Rb - $Ra)/400.0));
        $Eb = 1.0 / (1.0 + pow(10.0, ($Ra - $Rb)/400.0));

        $Ka = self::K_FACTOR_BASE;
        $Kb = self::K_FACTOR_BASE;

        $now = time();
        $db  = Work::$sql;

        $streakA = 0; $streakB = 0;
        $ra = $db->query("SELECT streak FROM arena_rating WHERE user_id={$winnerId} LIMIT 1");
        if ($ra && $row = $ra->fetch_assoc()) { $streakA = (int)$row['streak']; }
        $rb = $db->query("SELECT streak FROM arena_rating WHERE user_id={$loserId} LIMIT 1");
        if ($rb && $row = $rb->fetch_assoc()) { $streakB = (int)$row['streak']; }

        if ($streakA >= 3) $Ka = self::K_FACTOR_STREAK;

        $newRa = (int)round($Ra + $Ka * (1 - $Ea));
        $newRb = (int)round($Rb + $Kb * (0 - $Eb));

        $db->query("INSERT INTO arena_rating (user_id, elo, wins, losses, streak, last_match_ts, location_id)
                    VALUES ({$winnerId}, {$newRa}, 1, 0, 1, {$now}, {$locationId})
                    ON DUPLICATE KEY UPDATE
                        elo={$newRa}, wins=wins+1, streak=IF(streak>=0, streak+1, 1), last_match_ts={$now}, location_id={$locationId}");
        $db->query("INSERT INTO arena_rating (user_id, elo, wins, losses, streak, last_match_ts, location_id)
                    VALUES ({$loserId}, {$newRb}, 0, 1, -1, {$now}, {$locationId})
                    ON DUPLICATE KEY UPDATE
                        elo={$newRb}, losses=losses+1, streak=IF(streak<=0, streak-1, -1), last_match_ts={$now}, location_id={$locationId}");

        return [
            'winner_before' => $Ra,
            'loser_before'  => $Rb,
            'winner_after'  => $newRa,
            'loser_after'   => $newRb
        ];
    }

    /** Добавить игрока в очередь. Выдать команду РБ (если её нет/протухла). Вернуть JSON для NPC. */
    public static function enqueue(int $userId, int $locationId = 0, bool $giveTeam = true): array {
        self::migrate();
        $db = Work::$sql;
        $now = time();

        $db->query("DELETE FROM rb_queue WHERE user_id={$userId}");

        if ($giveTeam) {
            rb_issuePokemons($db, $userId, self::RB_POKES_COUNT);
        }

        $elo = self::getElo($userId, $locationId);

        $sessionId = bin2hex(random_bytes(8));
        $db->query("INSERT INTO rb_queue (user_id, location_id, elo, enqueued_at, state, opponent_id, session_id, ready)
                    VALUES ({$userId}, {$locationId}, {$elo}, {$now}, 'search', 0, '".$db->real_escape_string($sessionId)."', 1)");

        $pair = self::matchmake($locationId);

        $resp = [
            'ok' => true,
            'closeDialog' => true,
            'cmd' => '/arena ready',
            'message' => 'Вы встали в очередь на арену. Ожидание соперника...'
        ];

        if ($pair && in_array($userId, [$pair['u1'], $pair['u2']], true)) {
            $resp['message'] = 'Найден соперник! Инициализируем бой...';
            $resp['startBattle'] = true;
            $resp['battleId'] = $pair['battle_id'];
        }

        return $resp;
    }

    /** Удалить из очереди (и команду РБ — опционально) */
    public static function dequeue(int $userId, bool $removeTeam = false): void {
        $db = Work::$sql;
        $db->query("DELETE FROM rb_queue WHERE user_id={$userId}");
        if ($removeTeam) {
            rb_removePokemons($db, $userId);
        }
    }

    /** Периодическая чистка очереди (TTL 10 мин) и нотификация игроку через notification */
    public static function cleanupExpired(): void {
        $db  = Work::$sql;
        $now = time();
        $ttl = $now - self::QUEUE_TTL_SEC;
        $res = $db->query("SELECT user_id FROM rb_queue WHERE state='search' AND enqueued_at < {$ttl}");
        $expired = [];
        while ($res && $row = $res->fetch_assoc()) { $expired[] = (int)$row['user_id']; }

        if (!empty($expired)) {
            foreach ($expired as $uid) {
                self::notify($uid, 'Соперник не найден в течение 10 минут. Напишите в общий чат, например: «Ищу бой на арене!»');
                $db->query("DELETE FROM rb_queue WHERE user_id={$uid}");
                $db->query("UPDATE users SET status='free', status_id=0 WHERE id={$uid}");
            }
        }
    }

    /** Матчмейкинг */
    public static function matchmake(int $locationId = 0): ?array {
        $db = Work::$sql;

        $whereLoc = ($locationId > 0) ? "location_id={$locationId}" : "1=1";
        $q = $db->query("SELECT user_id, elo, enqueued_at FROM rb_queue
                         WHERE state='search' AND {$whereLoc}
                         ORDER BY enqueued_at ASC");

        $queue = [];
        while ($q && $r = $q->fetch_assoc()) {
            $queue[] = [
                'user_id' => (int)$r['user_id'],
                'elo'     => (int)$r['elo'],
                'wait'    => max(0, floor((time() - (int)$r['enqueued_at'])/60))
            ];
        }

        if (count($queue) < 2) {
            return null;
        }

        if (count($queue) == 2) {
            return self::createBattlePair($queue[0]['user_id'], $queue[1]['user_id'], $locationId);
        }

        $best = null;
        for ($i=0; $i<count($queue); $i++) {
            for ($j=$i+1; $j<count($queue); $j++) {
                $u1 = $queue[$i];
                $u2 = $queue[$j];
                $diff = abs($u1['elo'] - $u2['elo']);

                $minutes = max($u1['wait'], $u2['wait']);
                $allowed = self::ELO_BASE_RANGE + $minutes * self::ELO_RANGE_PER_MIN;

                if ($diff <= $allowed) {
                    if ($best === null || $diff < $best['diff']) {
                        $best = ['u1'=>$u1['user_id'], 'u2'=>$u2['user_id'], 'diff'=>$diff];
                    }
                }
            }
        }

        if ($best) {
            return self::createBattlePair($best['u1'], $best['u2'], $locationId);
        }

        if (count($queue) == 2) {
            return self::createBattlePair($queue[0]['user_id'], $queue[1]['user_id'], $locationId);
        }

        return null;
    }

    /** Старт боя РБ между двумя юзерами (используются выданные команды) */
    private static function createBattlePair(int $u1, int $u2, int $locationId = 0): ?array {
        $db  = Work::$sql;
        $now = time();

        $db->query("UPDATE rb_queue SET state='matched', opponent_id={$u2} WHERE user_id={$u1}");
        $db->query("UPDATE rb_queue SET state='matched', opponent_id={$u1} WHERE user_id={$u2}");

        $team1 = rb_getIssuedTeam($db, $u1);
        $team2 = rb_getIssuedTeam($db, $u2);

        if (empty($team1) || empty($team2)) {
            rb_issuePokemons($db, $u1, self::RB_POKES_COUNT);
            rb_issuePokemons($db, $u2, self::RB_POKES_COUNT);
            $team1 = rb_getIssuedTeam($db, $u1);
            $team2 = rb_getIssuedTeam($db, $u2);
        }

        $info1 = [
            'timer' => ['atk' => 0, 'turnStart' => $now],
            'target' => 0,
            'targetAtk' => 0,
            'pokes' => $team1
        ];
        $info2 = [
            'timer' => ['atk' => 0, 'turnStart' => $now],
            'target' => 0,
            'targetAtk' => 0,
            'pokes' => $team2
        ];

        $type = 'pvp';
        $weather = 1; $weather_round = 0;
        $answer  = '';
        $other   = Info::_parseData(['mode'=>'rb_arena']);
        $sql = sprintf(
            "INSERT INTO battle (`type`,`user_1`,`user_2`,`round`,`info_1`,`info_2`,`weather`,`weather_round`,`answer`,`other`,`location`)
             VALUES ('%s', %d, %d, 1, '%s', '%s', %d, %d, '%s', '%s', %d)",
             $db->real_escape_string($type),
             $u1, $u2,
             $db->real_escape_string(Info::_parseData($info1)),
             $db->real_escape_string(Info::_parseData($info2)),
             $weather, $weather_round,
             $db->real_escape_string($answer),
             $db->real_escape_string($other),
             (int)$locationId
        );
        $db->query($sql);
        $battleId = (int)$db->insert_id;

        $db->query("UPDATE users SET status='battle', status_id={$battleId} WHERE id={$u1}");
        $db->query("UPDATE users SET status='battle', status_id={$battleId} WHERE id={$u2}");

        return [
            'battle_id' => $battleId,
            'u1' => $u1,
            'u2' => $u2
        ];
    }

    /**
     * Системная нотификация игроку через таблицу `notification`.
     * Поддерживает разные схемы колонок (user/user_id, text/message/body, date/created_at/time, is_read/read).
     */
    public static function notify(int $userId, string $text): void {
        $db     = Work::$sql;
        $userId = (int)$userId;
        $now    = time();

        // экранируем текст заранее
        $textEsc = $db->real_escape_string($text);

        // определим колонки таблицы notification
        $cols = [];
        $res = $db->query("SHOW COLUMNS FROM `notification`");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $cols[] = $r['Field'];
            }
        }

        // выберем названия колонок
        $userCol = in_array('user', $cols, true)      ? 'user'
                 : (in_array('user_id', $cols, true)  ? 'user_id'
                 : (in_array('uid', $cols, true)      ? 'uid' : 'user'));
        $textCol = in_array('text', $cols, true)      ? 'text'
                 : (in_array('message', $cols, true)  ? 'message'
                 : (in_array('body', $cols, true)     ? 'body' : 'text'));
        $dateCol = in_array('date', $cols, true)      ? 'date'
                 : (in_array('created_at', $cols, true) ? 'created_at'
                 : (in_array('time', $cols, true)     ? 'time' : 'date'));
        $readCol = in_array('is_read', $cols, true)   ? 'is_read'
                 : (in_array('read', $cols, true)     ? 'read' : null);
        $typeCol = in_array('type', $cols, true)      ? 'type' : null;

        // соберём SQL без интерполяции выражений
        $fields = ["`{$userCol}`", "`{$textCol}`", "`{$dateCol}`"];
        $values = ["{$userId}", "'{$textEsc}'", "{$now}"];

        if ($readCol !== null) {
            $fields[] = "`{$readCol}`";
            $values[] = "0";
        }
        if ($typeCol !== null) {
            $fields[] = "`{$typeCol}`";
            $values[] = "'system'"; // пометим тип, если колонка есть
        }

        $sql = "INSERT INTO `notification` (" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ")";
        $db->query($sql);
    }
}
