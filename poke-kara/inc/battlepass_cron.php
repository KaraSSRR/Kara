<?php
/**
 * battlepass_cron.php
 * PHP 5.6+ совместим.
 *
 * $force:
 *   null      — обычный режим по времени
 *   'daily'   — принудительный сброс/выдача DAILY
 *   'weekly'  — принудительный сброс/выдача WEEKLY
 *   'both'    — daily + weekly
 *   'special' — довыдача SPECIAL до лимита (без сброса), ТОЛЬКО type=2
 *   'all'     — both + довыдача SPECIAL (ТОЛЬКО type=2)
 */

//////////////////////////// ВСПОМОГАТЕЛЬНОЕ ////////////////////////////

function bp_type_predicate($db, $type) {
    $map = [
        'daily'   => ['daily','day','d'],
        'weekly'  => ['weekly','week','w'],
        'special' => ['special','spec','s'],
    ];
    $key  = strtolower((string)$type);
    $vals = isset($map[$key]) ? $map[$key] : [$key];

    $valsEsc = [];
    foreach ($vals as $v) $valsEsc[] = "'" . $db->real_escape_string(strtolower($v)) . "'";
    return 'LOWER(m.type) IN ('.implode(',', $valsEsc).')';
}

/** Помечаем просроченные миссии expired=1. */
function bp_expire_outdated(mysqli $db) {
    $db->query("
        UPDATE aa_battle_pass_user_mission
        SET expired = 1
        WHERE expired = 0
          AND expires_at IS NOT NULL
          AND expires_at <= NOW()
    ");
}

function bp_today_reset_ts() { return strtotime('today'); }
function bp_week_reset_ts() {
    $today0 = bp_today_reset_ts();
    $dow = (int)date('N', $today0); // 1..7 (1=Пн)
    return $today0 - ($dow - 1) * 86400;
}
function bp_next_daily_dt()  { return date('Y-m-d H:i:s', bp_today_reset_ts() + 86400); }
function bp_next_weekly_dt() { return date('Y-m-d H:i:s', bp_week_reset_ts() + 7*86400); }
function bp_next_special_dt(){ return date('Y-m-d H:i:s', bp_today_reset_ts() + 14*86400); }

/** Сколько активных миссий типа у пользователя/сезона. */
function bp_count_occupied(mysqli $db, $userId, $seasonId, $type) {
    $userId   = (int)$userId;
    $seasonId = (int)$seasonId;
    $typeSql  = bp_type_predicate($db, $type);

    $sql = "
        SELECT COUNT(*) AS c
        FROM aa_battle_pass_user_mission um
        JOIN aa_battle_pass_mission m ON m.id = um.mission_id
        WHERE um.user_id   = {$userId}
          AND um.season_id = {$seasonId}
          AND um.expired   = 0
          AND {$typeSql}
    ";
    $q = $db->query($sql);
    if (!$q) return 0;
    $r = $q->fetch_assoc();
    return (int)($r['c'] ?? 0);
}

/** ВЫЧИСЛЯЕМАЯ «подпись» миссии (нормализованный смысл): текст+тип цели+id цели. */
function bp_mission_signature_expr($alias='m'){
    return "LOWER(CONCAT_WS(':', TRIM({$alias}.text), COALESCE({$alias}.target_type,''), COALESCE({$alias}.target_id,0)))";
}

/** Подпись последней назначенной миссии данного типа (любой статус) — чтобы не давать подряд ту же. */
function bp_last_assigned_signature(mysqli $db, $userId, $seasonId, $type): ?string {
    $userId   = (int)$userId;
    $seasonId = (int)$seasonId;
    $typeSql  = bp_type_predicate($db, $type);
    $sql = "
        SELECT ".bp_mission_signature_expr('m')." AS sig
        FROM aa_battle_pass_user_mission um
        JOIN aa_battle_pass_mission m ON m.id = um.mission_id
        WHERE um.user_id   = {$userId}
          AND um.season_id = {$seasonId}
          AND {$typeSql}
        ORDER BY um.assigned_at DESC, um.id DESC
        LIMIT 1
    ";
    $q = $db->query($sql);
    if ($q && ($r = $q->fetch_assoc())) return (string)$r['sig'];
    return null;
}

/** «Сжечь» активные миссии указанного типа. */
function bp_expire_active_by_type(mysqli $db, $userId, $seasonId, $type) {
    $userId   = (int)$userId;
    $seasonId = (int)$seasonId;
    $db->query("
        UPDATE aa_battle_pass_user_mission um
        JOIN aa_battle_pass_mission m ON m.id = um.mission_id
        SET um.expired = 1
        WHERE um.user_id   = {$userId}
          AND um.season_id = {$seasonId}
          AND um.expired   = 0
          AND ".bp_type_predicate($db, $type)."
    ");
}

/**
 * Выдать пользователю $need миссий типа $type.
 * Гарантии:
 *  - одновременно НЕ будет двух миссий с одинаковой «подписью» (даже если разные m.id)
 *  - повтор одной и той же подписи запрещён 3 дня (если ignore_cooldown=false)
 *  - при форсе (ignore_cooldown=true) кулдаун игнорируется, но моментальные дубли всё равно запрещены
 */
function bp_assign_missions_for_user(
    mysqli $db, $userId, $seasonId, $type, $need, $expiresAt, array $opts = []
){
    $userId   = (int)$userId;
    $seasonId = (int)$seasonId;
    $need     = max(0, (int)$need);
    if ($need <= 0) return;

    // Поведение:
    //  - не дублируем активные миссии по "подписи"
    //  - не повторяем подряд ту же "подпись"
    //  - cooldown (по умолчанию 3 дня) на повтор подписи (можно игнорировать при форсе)
    //  - ВАЖНО: стараемся не выдавать пачку миссий одного типа: сначала собираем разные target_type,
    //           затем, если не хватает, добиваем любыми.
    $ignoreCooldown = !empty($opts['ignore_cooldown']); // при форсе передавать true
    $cooldownDays   = isset($opts['cooldown_days']) ? max(0, (int)$opts['cooldown_days']) : 3;

    $typeSql        = bp_type_predicate($db, $type);

    // Используем NOW() на стороне MySQL — меньше проблем с TZ
    $nowRow = $db->query("SELECT NOW() AS now_dt")->fetch_assoc();
    $nowEsc = $db->real_escape_string($nowRow ? $nowRow['now_dt'] : date('Y-m-d H:i:s'));
    $expiresEsc = $db->real_escape_string($expiresAt);

    // не давать подряд ту же подпись
    $lastSig = bp_last_assigned_signature($db, $userId, $seasonId, $type);
    $lastSigCond = $lastSig ? "AND ".bp_mission_signature_expr('m')." <> '".$db->real_escape_string($lastSig)."'" : "";

    // запрет одновременного дубля по подписи
    $notExistsActiveSameSig = "
        NOT EXISTS (
          SELECT 1
          FROM aa_battle_pass_user_mission um2
          JOIN aa_battle_pass_mission m2 ON m2.id = um2.mission_id
          WHERE um2.user_id   = {$userId}
            AND um2.season_id = {$seasonId}
            AND um2.expired   = 0
            AND ".bp_mission_signature_expr('m2')." = ".bp_mission_signature_expr('m')."
        )
    ";

    // cooldown на повтор подписи
    if ($ignoreCooldown || $cooldownDays <= 0) {
        $notExistsCooldown = "1=1";
    } else {
        $notExistsCooldown = "
            NOT EXISTS (
              SELECT 1
              FROM aa_battle_pass_user_mission um3
              JOIN aa_battle_pass_mission m3 ON m3.id = um3.mission_id
              WHERE um3.user_id   = {$userId}
                AND um3.season_id = {$seasonId}
                AND um3.assigned_at >= DATE_SUB('{$nowEsc}', INTERVAL {$cooldownDays} DAY)
                AND ".bp_mission_signature_expr('m3')." = ".bp_mission_signature_expr('m')."
            )
        ";
    }

    // берём с запасом, чтобы после фильтров хватило
    $limitFetch = max(12, $need * 20);

    // общий сборщик SQL; $fallback=0 — строго по сезону, 1 — +NULL/0 сезон
    $buildSql = function($fallback) use ($seasonId, $typeSql, $nowEsc, $lastSigCond, $notExistsActiveSameSig, $notExistsCooldown, $limitFetch){
        $seasonFilter = $fallback
            ? "(m.season_id = {$seasonId} OR m.season_id IS NULL OR m.season_id = 0)"
            : "m.season_id = {$seasonId}";
        // поле visible может отсутствовать в старых схемах — на такой схеме запрос упадёт.
        // Если вы уверены, что visible есть — оставляем. Если нет — нужно убрать из схемы/кода.
        $visibleCond = "(m.visible IS NULL OR m.visible = 1)";

        return "
            SELECT m.id,
                   m.target_type,
                   ".bp_mission_signature_expr('m')." AS sig
            FROM aa_battle_pass_mission m
            WHERE {$seasonFilter}
              AND {$typeSql}
              AND {$visibleCond}
              {$lastSigCond}
              AND (m.active_from IS NULL OR m.active_from <= '{$nowEsc}')
              AND (m.active_to   IS NULL OR m.active_to   >= '{$nowEsc}')
              AND {$notExistsActiveSameSig}
              AND {$notExistsCooldown}
            ORDER BY RAND()
            LIMIT {$limitFetch}
        ";
    };

    // Собираем кандидатов (1) сезонные (2) сезонные+универсальные
    $rows = [];
    foreach ([0,1] as $fb) {
        $sql = $buildSql($fb);
        if ($res = $db->query($sql)) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    'id'         => (int)$r['id'],
                    'target_type'=> (string)$r['target_type'],
                    'sig'        => (string)$r['sig'],
                ];
            }
        }
        if (count($rows) >= $limitFetch) break;
    }

    if (!$rows) return;

    // 1) Первый проход: уникальные подписи + максимально разные target_type
    $pickedIds = [];
    $pickedSig = [];
    $pickedTypes = [];

    // сгруппируем по target_type
    $byType = [];
    foreach ($rows as $r) {
        $t = $r['target_type'] !== '' ? $r['target_type'] : 'unknown';
        $byType[$t][] = $r;
    }

    // перемешиваем порядок типов, чтобы не было bias
    $types = array_keys($byType);
    shuffle($types);

    foreach ($types as $t) {
        // перемешиваем внутри типа
        shuffle($byType[$t]);
        foreach ($byType[$t] as $r) {
            if (count($pickedIds) >= $need) break 2;
            if (isset($pickedSig[$r['sig']])) continue;
            $pickedIds[] = $r['id'];
            $pickedSig[$r['sig']] = true;
            $pickedTypes[$t] = true;
            break; // 1 миссия на тип на первом проходе
        }
    }

    // 2) Добиваем остаток: любые типы, но без дубля подписи
    if (count($pickedIds) < $need) {
        shuffle($rows);
        foreach ($rows as $r) {
            if (count($pickedIds) >= $need) break;
            if (isset($pickedSig[$r['sig']])) continue;
            $pickedIds[] = $r['id'];
            $pickedSig[$r['sig']] = true;
        }
    }

    foreach ($pickedIds as $mid) {
        $db->query("
            INSERT INTO aa_battle_pass_user_mission
                (user_id, season_id, mission_id, progress, done, assigned_at, expires_at, expired)
            VALUES
                ({$userId}, {$seasonId}, {$mid}, 0, 0, '{$nowEsc}', '{$expiresEsc}', 0)
        ");
    }
}


//////////////////////////// ОСНОВНОЙ ПРОХОД ////////////////////////////

function bp_run_cron(mysqli $db, $force = null) {
    $force = $force ? strtolower((string)$force) : null;
    $forceDaily   = in_array($force, ['daily','both','all'], true);
    $forceWeekly  = in_array($force, ['weekly','both','all'], true);
    $forceSpecial = in_array($force, ['special','both','all'], true);

    // Просрочки по времени переводим в expired=1
    bp_expire_outdated($db);

    $usersRes = $db->query("
        SELECT
            user                       AS user_id,
            season_id,
            COALESCE(daily_slots,  2) AS daily_slots,
            COALESCE(weekly_slots, 1) AS weekly_slots,
            COALESCE(special_slots,1) AS special_slots,
            last_daily_reset,
            last_weekly_reset,
            COALESCE(type, 0)         AS user_type
        FROM aa_battle_pass_user
    ");
    if (!$usersRes) return;

    $today0 = bp_today_reset_ts();   // полночь сегодня
    $week0  = bp_week_reset_ts();    // понедельник 00:00

    while ($u = $usersRes->fetch_assoc()) {
        $uid          = (int)$u['user_id'];
        $sid          = (int)$u['season_id'];
        $dailySlots   = max(0, (int)$u['daily_slots']);
        $weeklySlots  = max(0, (int)$u['weekly_slots']);
        $specialSlots = max(0, (int)$u['special_slots']);
        $userType     = (int)$u['user_type'];

        /* -------- DAILY -------- */
        $needDailyReset = $forceDaily;
        if (!$needDailyReset) {
            $last = !empty($u['last_daily_reset']) ? strtotime($u['last_daily_reset']) : 0;
            // обычный дневной ресет по полуночи
            $needDailyReset = ($last < $today0);
            // первый запуск игрока (слоты есть, активных миссий нет)
            if (!$needDailyReset) {
                $occupiedDaily = bp_count_occupied($db, $uid, $sid, 'daily');
                if ($occupiedDaily <= 0 && $last === 0) $needDailyReset = true;
            }
        }

        if ($needDailyReset) {
            bp_expire_active_by_type($db, $uid, $sid, 'daily');
            bp_assign_missions_for_user(
                $db, $uid, $sid, 'daily',
                $dailySlots,
                bp_next_daily_dt(),
                ['ignore_cooldown' => $forceDaily] // при форсе игнорим 3-дн. кулдаун
            );
            $db->query("UPDATE aa_battle_pass_user SET last_daily_reset = NOW() WHERE user = {$uid} AND season_id = {$sid}");
        } elseif ($forceDaily) {
            // Форс без календарного ресета: довыдаём недостающее
            $occupiedDaily = bp_count_occupied($db, $uid, $sid, 'daily');
            $need = $dailySlots - $occupiedDaily;
            if ($need > 0) {
                bp_assign_missions_for_user(
                    $db, $uid, $sid, 'daily',
                    $need,
                    bp_next_daily_dt(),
                    ['ignore_cooldown' => true]
                );
            }
        }

        /* -------- WEEKLY -------- */
        $needWeeklyReset = $forceWeekly;
        if (!$needWeeklyReset) {
            $lastW = !empty($u['last_weekly_reset']) ? strtotime($u['last_weekly_reset']) : 0;
            $needWeeklyReset = ($lastW < $week0); // ресет по понедельникам
            if (!$needWeeklyReset) {
                $occupiedWeekly = bp_count_occupied($db, $uid, $sid, 'weekly');
                if ($occupiedWeekly <= 0 && $lastW === 0) $needWeeklyReset = true;
            }
        }

        if ($needWeeklyReset) {
            bp_expire_active_by_type($db, $uid, $sid, 'weekly');
            bp_assign_missions_for_user(
                $db, $uid, $sid, 'weekly',
                $weeklySlots,
                bp_next_weekly_dt(),
                ['ignore_cooldown' => $forceWeekly]
            );
            $db->query("UPDATE aa_battle_pass_user SET last_weekly_reset = NOW() WHERE user = {$uid} AND season_id = {$sid}");
        } elseif ($forceWeekly) {
            $occupiedWeekly = bp_count_occupied($db, $uid, $sid, 'weekly');
            $need = $weeklySlots - $occupiedWeekly;
            if ($need > 0) {
                bp_assign_missions_for_user(
                    $db, $uid, $sid, 'weekly',
                    $need,
                    bp_next_weekly_dt(),
                    ['ignore_cooldown' => true]
                );
            }
        }

        /* -------- SPECIAL (только премиум) -------- */
        if ($userType === 2) {
            $occupiedSpecial = bp_count_occupied($db, $uid, $sid, 'special');
            $need = $specialSlots - $occupiedSpecial;
            if ($need > 0) {
                bp_assign_missions_for_user(
                    $db, $uid, $sid, 'special',
                    $need,
                    bp_next_special_dt(),
                    ['ignore_cooldown' => $forceSpecial] // при форсе снимаем кулдаун
                );
            } elseif ($forceSpecial) {
                // Если слоты заполнены, ничего не делаем (не перезаписываем активные)
            }
        } else {
            // у непремиумов special миссий быть не должно
            bp_expire_active_by_type($db, $uid, $sid, 'special');
        }
    }
}
