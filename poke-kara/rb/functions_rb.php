<?php
/**
 * RB (выданные покемоны) – служебные функции (расширенная версия)
 *
 * Что умеет:
 *  - rb_removePokemons($mysqli, $userId[, $alsoQueue=true]) — удалить ВСЕ временные покемоны пользователя (static='rb'), освободить статус и (по умолчанию) убрать запись из rb_queue.
 *  - rb_removePokemonsOnLocationChange($mysqli, $userId, $oldLocation, $newLocation, $allowedLocationId=8009)
 *      — при выходе с разрешённой локации удаляет и покемонов, и запись в очереди; 
 *        также сработает, если новая локация не разрешённая, но RB-состояние активно.
 *  - rb_givePokemons($mysqli, $userId, $count = 6) — выдать набор из rb_pokemons.php, аккуратно посчитать статы и PP, сохранить в user_pokemons как static='rb'.
 *  - rb_getIssuedTeam($mysqli, $userId) — вернуть выданную команду в формате p1..pN из таблицы user_pokemons (для боя).
 *  - rb_issuePokemons($mysqli, $userId, $count = 6) — удобная обёртка: выдать + вернуть готовую команду.
 *
 * Предположения по БД:
 *  - Таблица rb_queue(user_id INT PRIMARY KEY, created_at DATETIME) — запись означает, что игрок стоит в очереди RB.
 *  - В user_pokemons есть поля: user_id, basenum, form, lvl, ability, item_id, active, team_id, trade,
 *    birthday, static, name_new, hp, stats, attacks, pp_attacks, gen
 */

if (!defined('RB_STATIC_FLAG')) {
    define('RB_STATIC_FLAG', 'rb'); // метка временных RB-покемонов
}

/** Лог в файл (на случай ошибок) */
function rb_log($msg) {
    @file_put_contents(__DIR__.'/rb_poke_error.log', '['.date('Y-m-d H:i:s').'] '.$msg."\n", FILE_APPEND);
}

/** Проверка существования таблицы через SHOW TABLES LIKE (быстро и надёжно) */
function rb_table_exists(mysqli $mysqli, string $table): bool {
    $table = $mysqli->real_escape_string($table);
    $res = $mysqli->query("SHOW TABLES LIKE '{$table}'");
    return (bool)($res && $res->fetch_row());
}

/** Жёсткое удаление записи пользователя из rb_queue, если таблица существует. Возвращает число удалённых строк. */
function rb_removeFromRBQueue(mysqli $mysqli, int $userId): int {
    $userId = (int)$userId;
    if (!rb_table_exists($mysqli, 'rb_queue')) {
        return 0;
    }
    $stmt = $mysqli->prepare("DELETE FROM `rb_queue` WHERE `user_id` = ?");
    if (!$stmt) {
        rb_log("[QUEUE] prepare fail: ".$mysqli->error);
        return 0;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $aff = $stmt->affected_rows;
    $stmt->close();
    rb_log("[QUEUE] deleted rows={$aff} for user_id={$userId}");
    return max(0, (int)$aff);
}

/** Есть ли активное RB-состояние: либо выданные покемоны, либо запись в очереди */
function rb_hasActiveRB(mysqli $mysqli, int $userId): bool {
    $userId = (int)$userId;

    // Есть временные покемоны?
    $q1 = $mysqli->query("SELECT 1 FROM `user_pokemons` WHERE `user_id`={$userId} AND `static`='".RB_STATIC_FLAG."' LIMIT 1");
    if ($q1 && $q1->fetch_row()) return true;

    // Есть запись в rb_queue?
    if (rb_table_exists($mysqli, 'rb_queue')) {
        $stmt = $mysqli->prepare("SELECT 1 FROM `rb_queue` WHERE `user_id`=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $has = (bool)$res->fetch_row();
            $stmt->close();
            if ($has) return true;
        }
    }

    return false;
}

/**
 * Полная очистка временных (RB) покемонов пользователя + освобождение статуса.
 * Параметр $alsoQueue=true дополнительно почистит rb_queue, чтобы не оставалось «хвостов».
 */
function rb_removePokemons($mysqli, $userId, bool $alsoQueue = true) {
    $userId = (int)$userId;

    // Удаляем только тех, кто помечен static='rb'
    if (!$mysqli->query("DELETE FROM `user_pokemons` WHERE `user_id`={$userId} AND `static`='".RB_STATIC_FLAG."'")) {
        rb_log('[REMOVE] '.$mysqli->error);
    }

    // Сбрасываем статус (не трогаем, если игрок уже в бою — это решается вышележащей логикой)
    if (!$mysqli->query("UPDATE `users` SET `status`='free', `status_id`=0 WHERE `id`={$userId}")) {
        rb_log('[STATUS] '.$mysqli->error);
    }

    // Дополнительно чистим очередь
    if ($alsoQueue) {
        rb_removeFromRBQueue($mysqli, $userId);
    }

    return true;
}

/**
 * Удаление РБ-состояния при смене локации.
 * Срабатывает в двух случаях:
 *  1) игрок покинул разрешённую локацию (old == allowed, new != allowed)
 *  2) новая локация НЕ разрешённая, но RB-состояние активно (подстраховка, если oldLocation не был корректен)
 */
function rb_removePokemonsOnLocationChange($mysqli, $userId, $oldLocation, $newLocation, $allowedLocation = 8009) {
    $userId          = (int)$userId;
    $oldLocation     = (int)$oldLocation;
    $newLocation     = (int)$newLocation;
    $allowedLocation = (int)$allowedLocation;

    $leftAllowed = ($oldLocation === $allowedLocation && $newLocation !== $allowedLocation);
    $newIsNotAllowedButActive = ($newLocation !== $allowedLocation) && rb_hasActiveRB($mysqli, $userId);

    if ($leftAllowed || $newIsNotAllowedButActive) {
        // 1) снять из очереди
        $deleted = rb_removeFromRBQueue($mysqli, $userId);

        // 2) очистить выданных покемонов и статус
        rb_removePokemons($mysqli, $userId, false); // false: очередь уже почистили

        rb_log("[LOCATION] Purged RB for user={$userId}; reason=".($leftAllowed?'leftAllowed':'newIsNotAllowedButActive')."; queue_deleted={$deleted}");
    }
}

/**
 * Основная выдача РБ-покемонов.
 * Берём список из /rb/rb_pokemons.php, случайно выбираем $count, пересчитываем статы и PP, пишем в user_pokemons.
 */
function rb_givePokemons($mysqli, $userId, $count = 6) {
    $userId = (int)$userId;
    $count  = max(1, (int)$count);

    // На всякий случай — чистим предыдущие РБ-покемоны и хвосты очереди
    rb_removePokemons($mysqli, $userId, true);

    // Подтягиваем пулл покемонов
    $file = $_SERVER['DOCUMENT_ROOT'].'/rb/rb_pokemons.php';
    if (!file_exists($file)) {
        rb_log('[GIVE] rb_pokemons.php not found at '.$file);
        return false;
    }
    $pokemons = include($file);
    if (!is_array($pokemons) || empty($pokemons)) {
        rb_log('[GIVE] rb_pokemons.php did not return array or is empty');
        return false;
    }

    // Перемешиваем и берём нужное количество
    shuffle($pokemons);
    $selected = array_slice($pokemons, 0, $count);

    // Соберём все id покемонов и атак для одной выборки
    $pokeIds   = [];
    $attackIds = [];
    foreach ($selected as $poke) {
        if (!isset($poke['pokemon'], $poke['lvl'], $poke['ability'], $poke['attacks']) || !is_array($poke['attacks'])) {
            rb_log('[GIVE] bad pokemon descriptor: '.print_r($poke, true));
            return false;
        }
        $pokeIds[] = (int)$poke['pokemon'];
        foreach ($poke['attacks'] as $a) {
            $attackIds[] = (int)$a;
        }
    }
    $pokeIds   = array_values(array_unique($pokeIds));
    $attackIds = array_values(array_unique($attackIds));

    // База покемонов
    $pokemonsBase = [];
    if (!empty($pokeIds)) {
        $idStr = implode(',', array_map('intval', $pokeIds));
        $q = $mysqli->query("SELECT * FROM `base_pokemons` WHERE `id` IN ({$idStr})");
        if (!$q) {
            rb_log('[SQL base_pokemons] '.$mysqli->error);
            return false;
        }
        while ($r = $q->fetch_assoc()) {
            $pokemonsBase[(int)$r['id']] = $r;
        }
    }

    // База атак
    $attacksBase = [];
    if (!empty($attackIds)) {
        $idStr = implode(',', array_map('intval', $attackIds));
        $q = $mysqli->query("SELECT * FROM `base_atk` WHERE `id` IN ({$idStr})");
        if (!$q) {
            rb_log('[SQL base_atk] '.$mysqli->error);
            return false;
        }
        while ($r = $q->fetch_assoc()) {
            $attacksBase[(int)$r['id']] = $r;
        }
    }

    // Транзакция на раздачу
    $mysqli->begin_transaction();
    try {
        foreach ($selected as $poke) {
            $baseId  = (int)$poke['pokemon'];
            $lvl     = (int)$poke['lvl'];
            $ability = (int)$poke['ability'];
            $itemId  = isset($poke['item']) ? (int)$poke['item'] : 0;
            $form    = isset($poke['form']) ? (int)$poke['form'] : 0;
            $gen     = isset($poke['gen'])  ? (string)$poke['gen'] : '29,29,28,28,28,29';
            $atkIds  = array_map('intval', (array)$poke['attacks']);

            // Проверка наличия базы
            $base = isset($pokemonsBase[$baseId]) ? $pokemonsBase[$baseId] : null;
            if (!$base) {
                rb_log("[GIVE] base_pokemons entry not found for id={$baseId}");
                throw new Exception('Missing base pokemon');
            }

            // Имя
            $name_new = isset($base['name_rus']) && $base['name_rus'] ? $base['name_rus'] : 'Unknown';

            // Базовые статы
            $base_hp    = isset($base['hp'])    ? (int)$base['hp']    : 50;
            $base_atk   = isset($base['atk'])   ? (int)$base['atk']   : 50;
            $base_def   = isset($base['def'])   ? (int)$base['def']   : 50;
            $base_spatk = isset($base['spatk']) ? (int)$base['spatk'] : 50;
            $base_spdef = isset($base['spdef']) ? (int)$base['spdef'] : 50;
            $base_spd   = isset($base['spd'])   ? (int)$base['spd']   : 50;

            // Итоговые статы
            $hp_max = (int) floor((2 * $base_hp    + 31) * $lvl / 100) + $lvl + 10;
            $atk    = (int) floor((2 * $base_atk   + 31) * $lvl / 100) + 5;
            $def    = (int) floor((2 * $base_def   + 31) * $lvl / 100) + 5;
            $spatk  = (int) floor((2 * $base_spatk + 31) * $lvl / 100) + 5;
            $spdef  = (int) floor((2 * $base_spdef + 31) * $lvl / 100) + 5;
            $spd    = (int) floor((2 * $base_spd   + 31) * $lvl / 100) + 5;

            $stats = implode(',', [$hp_max, $atk, $def, $spatk, $spdef, $spd]);
            $hp    = $hp_max;

            // PP атак: если нет в базе — берём 10 по умолчанию
            $ppList = [];
            foreach ($atkIds as $aId) {
                $ppList[] = isset($attacksBase[$aId]['pp']) ? (int)$attacksBase[$aId]['pp'] : 10;
            }
            // Если атак меньше 4 — добьём нулями PP, чтобы строго 4 позиции
            while (count($ppList) < 4) { $ppList[] = 0; }
            $ppStr   = implode(',', array_slice($ppList, 0, 4));
            $atkList = implode(',', array_slice($atkIds, 0, 4));

            // Доп. поля
            $birthday = json_encode(['user_id' => 4, 'date' => time()], JSON_UNESCAPED_UNICODE);

            // Экранируем текстовые значения
            $nameEsc     = $mysqli->real_escape_string($name_new);
            $statsEsc    = $mysqli->real_escape_string($stats);
            $genEsc      = $mysqli->real_escape_string($gen);
            $birthdayEsc = $mysqli->real_escape_string($birthday);

            // Вставка
            $sql = "
                INSERT INTO `user_pokemons`
                    (`user_id`,`basenum`,`form`,`lvl`,`ability`,`item_id`,`active`,`team_id`,`trade`,
                     `birthday`,`static`,`name_new`,`hp`,`stats`,`attacks`,`pp_attacks`,`gen`)
                VALUES
                    ({$userId}, {$baseId}, {$form}, {$lvl}, {$ability}, {$itemId}, 1, 0, 0,
                     '{$birthdayEsc}','".RB_STATIC_FLAG."','{$nameEsc}', {$hp}, '{$statsEsc}', '{$atkList}', '{$ppStr}', '{$genEsc}')
            ";
            if (!$mysqli->query($sql)) {
                rb_log('[INSERT user_pokemons] '.$mysqli->error.'; SQL: '.$sql);
                throw new Exception('Insert failed');
            }
        }

        $mysqli->commit();
        return true;
    } catch (Throwable $e) {
        $mysqli->rollback();
        rb_log('[TX ROLLBACK] '.$e->getMessage());
        return false;
    }
}

/**
 * Вернуть выданную пользователю RB-команду для боя (формат p1..pN).
 * Возвращает массив: ['p1'=>[row...], 'p2'=>[row...], ...]
 */
function rb_getIssuedTeam($mysqli, $userId) {
    $userId = (int)$userId;
    $team = [];

    $q = $mysqli->query("
        SELECT `id`,`basenum`,`form`,`lvl`,`ability`,`item_id`,`hp`,`stats`,
               `attacks`,`pp_attacks`,`gen`,`name_new`
          FROM `user_pokemons`
         WHERE `user_id`={$userId} AND `static`='".RB_STATIC_FLAG."'
         ORDER BY `id` ASC
         LIMIT 6
    ");
    if (!$q) {
        rb_log('[getIssuedTeam] '.$mysqli->error);
        return $team;
    }

    $i = 1;
    while ($row = $q->fetch_assoc()) {
        // Приводим типы где нужно
        $row['id']         = (int)$row['id'];
        $row['basenum']    = (int)$row['basenum'];
        $row['form']       = (int)$row['form'];
        $row['lvl']        = (int)$row['lvl'];
        $row['ability']    = (int)$row['ability'];
        $row['item_id']    = (int)$row['item_id'];
        $row['hp']         = (int)$row['hp'];
        $row['name_new']   = (string)$row['name_new'];
        $row['gen']        = (string)$row['gen'];
        $row['stats']      = (string)$row['stats'];
        $row['attacks']    = (string)$row['attacks'];
        $row['pp_attacks'] = (string)$row['pp_attacks'];

        // Поля, которые часто ожидает боёвка
        $row['disable_my'] = '0,0,0,0'; // по умолчанию все приёмы доступны
        $row['active']     = 1;
        $row['team_id']    = 0;

        $team['p'.$i] = $row;
        $i++;
    }

    return $team;
}

/**
 * Удобная обёртка: выдать покемонов и вернуть массив команды.
 * Возвращает массив команды (как rb_getIssuedTeam) либо false, если что-то пошло не так.
 */
function rb_issuePokemons($mysqli, $userId, $count = 6) {
    if (!rb_givePokemons($mysqli, $userId, $count)) {
        return false;
    }
    return rb_getIssuedTeam($mysqli, $userId);
}
?>
