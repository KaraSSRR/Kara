<?php
// Новый Боевой Пропуск. PUBG/LoL механика. Все категории + статистика + магазин + награды + миссии + история.
// Обновлено: улучшен раздел "Миссии" — корректная выборка, группировка Daily/Weekly/Special,
// отображение прогресса и времени до истечения, пустые слоты, кнопки действий.
// Автор: Copilot, 2025

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project.'/inc/conf/global.php';
$patch_func    = $patch_project.'/inc/function/Functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/battlepass_progress.php';
if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        die('The problem with the connection files.');
    } else {
        require_once($patch_global);
        require_once($patch_func);
    }
}

session_start();
header('Content-Type: application/json; charset=utf-8');
$user_id = intval($_SESSION['id'] ?? 0);
$response = [];

if (!$user_id) {
    echo json_encode(['error' => 'Ошибка авторизации']); exit;
}

// Константы
define('BP_EXP_PER_LEVEL', 800);
define('BP_MIN_LEVEL', 7);
define('BP_FULL_PASS_COST_ITEM', 25); // драгоценный камень
define('BP_FULL_PASS_COST_COUNT', 100);
// миссии не повторяются чаще, чем раз в N дней
if (!defined('BP_MISSION_COOLDOWN_DAYS')) {
    define('BP_MISSION_COOLDOWN_DAYS', 3);
}


// ================== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==================
function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function getActiveSeason($mysqli) {
    return $mysqli->query("SELECT * FROM `aa_battle_pass_season` WHERE `is_active` = 1 LIMIT 1")->fetch_assoc();
}
function getUserBP($mysqli, $user_id, $season_id) {
    return $mysqli->query("SELECT * FROM `aa_battle_pass_user` WHERE `user` = ".(int)$user_id." AND `season_id` = ".(int)$season_id)->fetch_assoc();
}

/**
 * Стоимость следующего уровня.
 * Если нужна прогрессия, меняйте формулу здесь (например, $base + ($lvl-1)*100).
 */

/* =============================================================================
 * КЕШИРОВАНИЕ (ускорение)
 * -----------------------------------------------------------------------------
 * Используем APCu (если доступен) либо короткий fallback на $_SESSION.
 * Кешируем только безопасные данные: справочники, топы, списки магазинов.
 * ============================================================================= */
if (!function_exists('bp_cache_support')) {
    function bp_cache_support(): bool {
        if (function_exists('apcu_fetch') && function_exists('apcu_store')) {
            // apc.enabled может быть выключен в CLI, но включён под FPM
            $enabled = ini_get('apc.enabled');
            if ($enabled === false || $enabled === '' || $enabled === '0') {
                // на некоторых конфигурациях ini_get может быть недоступен/пустой — считаем что APCu есть
                return true;
            }
            return (bool)$enabled;
        }
        return false;
    }
}
if (!function_exists('bp_cache_get')) {
    function bp_cache_get(string $key, int $ttl, $default = null) {
        static $runtime = [];
        $now = time();

        // runtime cache (в рамках запроса)
        if (isset($runtime[$key])) {
            $it = $runtime[$key];
            if (($now - (int)$it['t']) <= $ttl) return $it['v'];
        }

        // APCu
        if (bp_cache_support()) {
            $v = apcu_fetch($key, $ok);
            if ($ok) { $runtime[$key] = ['t'=>$now,'v'=>$v]; return $v; }
        }

        // SESSION fallback
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['bp_cache'])) $_SESSION['bp_cache'] = [];
            if (isset($_SESSION['bp_cache'][$key])) {
                $it = $_SESSION['bp_cache'][$key];
                if (is_array($it) && isset($it['t']) && (($now - (int)$it['t']) <= $ttl)) {
                    $runtime[$key] = $it;
                    return $it['v'];
                }
            }
        }

        return $default;
    }
}
if (!function_exists('bp_cache_set')) {
    function bp_cache_set(string $key, $val, int $ttl = 10): void {
        $now = time();
        // APCu
        if (bp_cache_support()) {
            @apcu_store($key, $val, max(1,$ttl));
        }
        // SESSION
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['bp_cache'])) $_SESSION['bp_cache'] = [];
            $_SESSION['bp_cache'][$key] = ['t'=>$now,'v'=>$val];
        }
        // runtime
        static $runtime = [];
        $runtime[$key] = ['t'=>$now,'v'=>$val];
    }
}
if (!function_exists('bp_cache_del')) {
    function bp_cache_del(string $key): void {
        if (bp_cache_support()) { @apcu_delete($key); }
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['bp_cache'][$key])) unset($_SESSION['bp_cache'][$key]);
    }
}

function bpGetLevelCost(int $lvl): int {
    $base = defined('BP_EXP_PER_LEVEL') ? (int)BP_EXP_PER_LEVEL : 800;
    return max(1, $base);
}

/**
 * Добавить EXP с автоповышением уровня (атомарно).
 * Возвращает новое состояние уровня.
 */
function bpAddExp(mysqli $mysqli, int $user_id, int $season_id, int $addExp): array {
    $out = ['lvl'=>null,'exp_me'=>null,'exp_to'=>null,'gained_levels'=>0,'added'=>$addExp];
    if ($addExp <= 0) return $out;

    try {
        $mysqli->begin_transaction();

        $row = $mysqli->query("
            SELECT lvl, exp_me, exp_to
            FROM aa_battle_pass_user
            WHERE user = {$user_id} AND season_id = {$season_id}
            FOR UPDATE
        ")->fetch_assoc();
        if (!$row) { $mysqli->rollback(); return $out; }

        $lvl = (int)$row['lvl'];
        $me  = (int)$row['exp_me'] + (int)$addExp;
        $to  = (int)$row['exp_to'];
        if ($to <= 0) $to = bpGetLevelCost($lvl);

        $gained = 0;
        while ($me >= $to) {
            $me -= $to;
            $lvl++;
            $to = bpGetLevelCost($lvl);
            $gained++;
        }

        $mysqli->query("
            UPDATE aa_battle_pass_user
            SET lvl = {$lvl}, exp_me = {$me}, exp_to = {$to}
            WHERE user = {$user_id} AND season_id = {$season_id}
            LIMIT 1
        ");

        $mysqli->commit();
        $out['lvl'] = $lvl; $out['exp_me'] = $me; $out['exp_to'] = $to; $out['gained_levels'] = $gained;
        return $out;
    } catch (Throwable $e) {
        if ($mysqli->errno) $mysqli->rollback();
        return $out;
    }
}

/**
 * Автоповышение уровня, если exp_me >= exp_to (без добавления опыта).
 * Используйте при открытии разделов БП, чтобы синхронизировать уровень.
 */
function bpEnsureLevelUp(mysqli $mysqli, int $user_id, int $season_id): void {
    try {
        $mysqli->begin_transaction();

        $row = $mysqli->query("
            SELECT lvl, exp_me, exp_to
            FROM aa_battle_pass_user
            WHERE user = {$user_id} AND season_id = {$season_id}
            FOR UPDATE
        ")->fetch_assoc();
        if (!$row) { $mysqli->rollback(); return; }

        $lvl = (int)$row['lvl'];
        $me  = (int)$row['exp_me'];
        $to  = (int)$row['exp_to'];
        if ($to <= 0) $to = bpGetLevelCost($lvl);

        $changed = false;
        while ($me >= $to) {
            $me -= $to;
            $lvl++;
            $to = bpGetLevelCost($lvl);
            $changed = true;
        }

        if ($changed) {
            $mysqli->query("
                UPDATE aa_battle_pass_user
                SET lvl = {$lvl}, exp_me = {$me}, exp_to = {$to}
                WHERE user = {$user_id} AND season_id = {$season_id}
                LIMIT 1
            ");
        }

        $mysqli->commit();
    } catch (Throwable $e) {
        if ($mysqli->errno) $mysqli->rollback();
    }
}

/**
 * Жёсткое ограничение количества одновременно активных миссий по слотам.
 * Лишние (сверх слотов) активные миссии помечаются expired=1.
 * Слот считается занятым любой неистёкшей миссией данного типа (done=0/1 не важно).
 */
function enforceBpMissionSlots(mysqli $mysqli, int $user_id, int $season_id): void {
    $slotsRow = $mysqli->query("
        SELECT
          COALESCE(daily_slots,2)   AS d,
          COALESCE(weekly_slots,1)  AS w,
          COALESCE(special_slots,1) AS s
        FROM aa_battle_pass_user
        WHERE user = ".(int)$user_id." AND season_id = ".(int)$season_id."
        LIMIT 1
    ")->fetch_assoc();

    $limits = [
        'daily'   => (int)($slotsRow['d'] ?? 2),
        'weekly'  => (int)($slotsRow['w'] ?? 1),
        'special' => (int)($slotsRow['s'] ?? 1),
    ];

    foreach (['daily','weekly','special'] as $type) {
        $limit = max(0, (int)$limits[$type]);
        if ($limit === 0) {
            $mysqli->query("
                UPDATE aa_battle_pass_user_mission um
                JOIN aa_battle_pass_mission m ON m.id=um.mission_id
                SET um.expired = 1
                WHERE um.user_id = ".(int)$user_id."
                  AND um.season_id = ".(int)$season_id."
                  AND um.expired = 0
                  AND m.type = '".$mysqli->real_escape_string($type)."'
            ");
            continue;
        }

        $res = $mysqli->query("
            SELECT um.id
            FROM aa_battle_pass_user_mission um
            JOIN aa_battle_pass_mission m ON m.id = um.mission_id
            WHERE um.user_id = ".(int)$user_id."
              AND um.season_id = ".(int)$season_id."
              AND um.expired = 0
              AND m.type = '".$mysqli->real_escape_string($type)."'
            ORDER BY um.assigned_at ASC, um.id ASC
        ");
        if (!$res) continue;

        $idsToExpire = [];
        $i = 0;
        while ($row = $res->fetch_assoc()) {
            $i++;
            if ($i > $limit) $idsToExpire[] = (int)$row['id'];
        }
        if ($idsToExpire) {
            $in = implode(',', $idsToExpire);
            $mysqli->query("
                UPDATE aa_battle_pass_user_mission
                SET expired = 1
                WHERE user_id = ".(int)$user_id."
                  AND season_id = ".(int)$season_id."
                  AND id IN ($in)
            ");
        }
    }
}

/**
 * Активные (не истёкшие) миссии пользователя текущего сезона с нужными полями.
 */
function getUserMissionsActive($mysqli, $user_id, $season_id) {
    enforceBpMissionSlots($mysqli, (int)$user_id, (int)$season_id);

    $sql = "
        SELECT
            um.id              AS user_mission_id,
            um.user_id,
            um.progress,
            um.done,
            um.assigned_at,
            um.expires_at,
            um.expired,
            m.id               AS mission_id,
            m.type             AS mission_type,
            m.text,
            m.exp,
            m.count_max,
            m.target_type,
            m.target_id
        FROM aa_battle_pass_user_mission um
        INNER JOIN aa_battle_pass_mission m ON m.id = um.mission_id
        WHERE um.user_id = ".(int)$user_id."
          AND um.season_id = ".(int)$season_id."
          AND um.expired = 0
        ORDER BY FIELD(m.type,'daily','weekly','special'), um.done ASC,
                 COALESCE(um.expires_at,'2099-12-31 23:59:59') ASC, um.id DESC
    ";
    $res = $mysqli->query($sql);
    $byType = ['daily'=>[], 'weekly'=>[], 'special'=>[]];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $t = $row['mission_type'];
            if (!isset($byType[$t])) $byType[$t] = [];
            $byType[$t][] = $row;
        }
    }
    return $byType;
}

function getPrizes($mysqli, $season_id) {
    $res = $mysqli->query("SELECT * FROM `aa_battle_pass_prize` WHERE `season_id` = ".(int)$season_id." ORDER BY `level`, `type`");
    $list = [];
    while ($row = $res->fetch_assoc()) $list[] = $row;
    return $list;
}
function getUserPrizes($mysqli, $user_id, $season_id) {
    $res = $mysqli->query("SELECT * FROM `aa_battle_pass_userprize` WHERE `user_id` = ".(int)$user_id." AND `season_id` = ".(int)$season_id);
    $out = [];
    while ($row = $res->fetch_assoc()) $out[$row['prize_id']] = true;
    return $out;
}
function canBuyFullPass($user, $items) {
    return $user['type'] == 1 && (isset($items[BP_FULL_PASS_COST_ITEM]) && $items[BP_FULL_PASS_COST_ITEM] >= BP_FULL_PASS_COST_COUNT);
}
function buyFullPass($mysqli, $user_id, $season_id) {
    $mysqli->query("UPDATE `aa_battle_pass_user` SET `type`=2, `point`=`point`+10 WHERE `user` = ".(int)$user_id." AND `season_id` = ".(int)$season_id);
    minus_item(BP_FULL_PASS_COST_ITEM, BP_FULL_PASS_COST_COUNT);
}

/**
 * ВЫДАЧА МИССИЙ — вручную (для диагностики). Не используется в UI.
 */
function issueMissionsToUser($mysqli, $user_id, $season_id) {
    $missions = $mysqli->query("SELECT id FROM aa_battle_pass_mission WHERE season_id = ".(int)$season_id." AND visible = 1");
    while ($m = $missions->fetch_assoc()) {
        $mid = intval($m['id']);
        $exists = $mysqli->query("
            SELECT 1
            FROM aa_battle_pass_user_mission
            WHERE user_id = ".(int)$user_id."
              AND season_id = ".(int)$season_id."
              AND mission_id = ".$mid."
            LIMIT 1
        ")->fetch_row();
        if (!$exists) {
            $mysqli->query("
                INSERT INTO aa_battle_pass_user_mission
                (user_id, season_id, mission_id, progress, done, assigned_at, expires_at, expired)
                VALUES (".(int)$user_id.", ".(int)$season_id.", ".$mid.", 0, 0, NOW(), NULL, 0)
            ");
        }
    }
    enforceBpMissionSlots($mysqli, (int)$user_id, (int)$season_id);
}

function claimPrize($mysqli, $user_id, $season_id, $prize_id, $user_bp, $userprizes) {
    $prize = $mysqli->query("SELECT * FROM `aa_battle_pass_prize` WHERE `id` = ".(int)$prize_id." AND `season_id` = ".(int)$season_id)->fetch_assoc();
    if (!$prize) return ['error'=>'Приз не найден!'];
    if (isset($userprizes[$prize_id])) return ['error'=>'Приз уже получен!'];
    if ($user_bp['lvl'] < $prize['level']) return ['error'=>'Недостаточный уровень!'];
    if ($user_bp['type'] < $prize['type']) return ['error'=>'Нет доступа к этому призу!'];

    $mysqli->query("INSERT INTO aa_battle_pass_userprize (user_id, season_id, prize_id, claimed_at) VALUES (".(int)$user_id.", ".(int)$season_id.", ".(int)$prize_id.", NOW())");

    if (!empty($prize['point'])) {
        $mysqli->query("UPDATE aa_battle_pass_user SET point = point + ".intval($prize['point'])." WHERE user = ".(int)$user_id." AND season_id = ".(int)$season_id);
    }

    $response = [];

    if ((int)$prize['type_pr'] === 1) {
        // Выдача покемона ТОЧНО так же, как в emblemshop
        $pok_row = $mysqli->query("SELECT `name_rus` FROM base_pokemons WHERE id = ".(int)$prize['pok'])->fetch_assoc();
        $tm   = time() + (3600 * 24 * 6);
        $gens = "25,25,25,25,25,25";
        $form = isset($prize['form']) ? (int)$prize['form'] : 0; // в призах столбца form может не быть — тогда 0

        // plusEgg($gens, false, false, true, $tm, pok_id, false, user_id, form)
        plusEgg($gens, false, false, true, $tm, (int)$prize['pok'], false, (int)$user_id, $form);

        $response['plus'] = "<img src='/img/world/items/little/151.png' class='item'> Яйцо ".h($pok_row['name_rus'] ?? 'Покемон');
    } else {
        if (!empty($prize['item'])) {
            $response['plus'] = '';
            $item_l = explode(';', (string)$prize['item']);
            foreach ($item_l as $item_data) {
                if (!$item_data) continue;
                list($item_id, $item_qty) = array_map('intval', explode(',', $item_data));
                if ($item_id > 0 && $item_qty > 0) {
                    itemAdd($item_id, $item_qty);
                    $response['plus'] .= "<img src='/img/world/items/little/".$item_id.".png' class='item'> <b>x".$item_qty."</b><br>";
                }
            }
        }
    }

    $response['html']  = 'Приз успешно получен!';
    $response['prize'] = $prize;
    return $response;
}
function getMissionStat($mysqli, $user_id, $season_id) {
    $out = [];
    $res = $mysqli->query("
        SELECT m.type, COUNT(*) as cnt, SUM(um.done) as done
        FROM aa_battle_pass_user_mission um
        INNER JOIN aa_battle_pass_mission m ON um.mission_id = m.id
        WHERE um.user_id = ".(int)$user_id." AND um.season_id = ".(int)$season_id."
        GROUP BY m.type
    ");
    while ($row = $res->fetch_assoc()) {
        $out[$row['type']] = ['total' => (int)$row['cnt'], 'done' => (int)$row['done']];
    }
    return $out;
}
function getHistory($mysqli, $user_id, $season_id, $limit=40) {
    $res = $mysqli->query("SELECT * FROM `aa_battle_pass_history` WHERE user_id = ".(int)$user_id." AND season_id = ".(int)$season_id." ORDER BY id DESC LIMIT ".(int)$limit);
    $list = [];
    while ($row = $res->fetch_assoc()) $list[] = $row;
    return $list;
}

function secondsToShortHuman($sec) {
    if ($sec <= 0) return '0с';
    $d = floor($sec / 86400); $sec %= 86400;
    $h = floor($sec / 3600);  $sec %= 3600;
    $m = floor($sec / 60);
    if ($d > 0) return $d.'д '.$h.'ч';
    if ($h > 0) return $h.'ч '.$m.'м';
    return $m.'м';
}
if (!function_exists('bpGetItemNames')) {
    function bpGetItemNames(mysqli $db, array $ids): array {
        static $cache = [];
        $out = [];
        $need = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;
            if (isset($cache[$id])) {
                $out[$id] = $cache[$id];
            } else {
                $need[$id] = true;
            }
        }
        if ($need) {
            $in = implode(',', array_keys($need));
            $res = $db->query("SELECT id, name FROM base_items WHERE id IN ($in)");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $iid = (int)$r['id'];
                    $nm  = (string)$r['name'];
                    $cache[$iid] = $nm;
                    $out[$iid] = $nm;
                }
            }
            // Для тех, что не нашлись — заполним плейсхолдером
            foreach ($need as $iid => $_) {
                if (!isset($out[$iid])) {
                    $cache[$iid] = 'Предмет #'.$iid;
                    $out[$iid] = $cache[$iid];
                }
            }
        }
        return $out;
    }
}
// =============== END FUNCTIONS ===============

// ================== АВТОСИНХРОНИЗАЦИЯ УРОВНЯ ==================
$season = getActiveSeason($mysqli);
if (!$season) {
    $response['error'] = 'Сезон не найден!';
    echo json_encode($response); exit;
}
$season_id = (int)$season['id'];

// Берём строку пользователя и СРАЗУ синхронизируем уровень по накопленному EXP
$user_bp = getUserBP($mysqli, $user_id, $season_id);
if ($user_bp) {
    bpEnsureLevelUp($mysqli, $user_id, $season_id);
    // перечитываем после возможного апа уровня
    $user_bp = getUserBP($mysqli, $user_id, $season_id);
}

if (!$user_bp && empty($_POST['type'])) {
    echo json_encode(['error' => 'Сначала активируйте участие в пропуске']); exit;
}

if (!empty($_POST['type'])) {
    $type = $_POST['type'];
    switch ($type) {
        // Запуск участия
        case 'activatedfree':
            $user = $mysqli->query("SELECT `id`,`user_group`,`lvl`,`login` FROM `users` WHERE `id`= ".(int)$user_id." ")->fetch_assoc();
            if ($user['user_group'] <= 6 || $user['user_group'] == 100) {
                if ($user['lvl'] >= BP_MIN_LEVEL) {
                    if (!$user_bp) {
                        $mysqli->query("
                            INSERT INTO `aa_battle_pass_user`
                            (`user`, `season_id`, `lvl`, `exp_me`, `exp_to`, `type`, `point`)
                            VALUES (".(int)$user_id.", ".$season_id.", 1, 0, ".BP_EXP_PER_LEVEL.", 1, 0)
                        ");
                        $mysqli->query("INSERT INTO aa_battle_pass_history (user_id, season_id, event, info) VALUES (".(int)$user_id.", ".$season_id.", 'activatedfree', 'Старт участия')");
                        $response['html'] = "Поздравляем, ".h($user['login'])."! Вы успешно начали участие в боевом пропуске!";
                    } else {
                        $response['error'] = "Вы уже участвуете в пропуске!";
                    }
                } else {
                    $response['error'] = "Вы должны иметь минимум ".BP_MIN_LEVEL." уровень для участия в пропуске!";
                }
            } else {
                $response['error'] = "Нельзя начать пропуск, находясь в тюрьме!";
            }
        break;

       case 'activatedfull':

    // --- Константы стоимости ---
    $costItemId  = defined('BP_FULL_PASS_COST_ITEM')  ? (int)BP_FULL_PASS_COST_ITEM  : 0;   // напр. 25 (камень)
    $costItemQty = defined('BP_FULL_PASS_COST_COUNT') ? (int)BP_FULL_PASS_COST_COUNT : 0;   // напр. 100

    // --- Пользователь и активный сезон ---
    $user = $mysqli->query("SELECT `id`,`user_group`,`lvl`,`login` FROM `users` WHERE `id`=".(int)$user_id." LIMIT 1")->fetch_assoc();
    $season = $mysqli->query("SELECT * FROM `aa_battle_pass_season` WHERE `is_active` = 1 LIMIT 1")->fetch_assoc();
    $season_id = $season ? (int)$season['id'] : 0;

    if (!$user) { $response['error'] = "Ошибка: пользователь не найден."; break; }
    if ((int)$user['user_group'] == 7) { $response['error'] = "Нельзя активировать пропуск, находясь в тюрьме."; break; }
    if ((int)$user['lvl'] < (defined('BP_MIN_LEVEL') ? (int)BP_MIN_LEVEL : 1)) {
        $response['error'] = "Минимальный уровень для участия: ".(defined('BP_MIN_LEVEL')?(int)BP_MIN_LEVEL:1)."."; break;
    }
    if ($season_id === 0) { $response['error'] = "Сезон боевого пропуска не найден."; break; }

    // --- Проверяем, что бесплатный пропуск уже активирован ---
    $user_bp = $mysqli->query("
        SELECT * FROM `aa_battle_pass_user`
        WHERE `user`=".(int)$user_id." AND `season_id`=".$season_id."
        LIMIT 1
    ")->fetch_assoc();
    if (!$user_bp) { $response['error'] = "Сначала активируйте бесплатную версию пропуска."; break; }
    if ((int)$user_bp['type'] == 2) { $response['error'] = "Полная версия уже активна."; break; }

    // --- Валидация оплаты ---
    if ($costItemId <= 0 || $costItemQty <= 0) { $response['error'] = "Неверно настроена стоимость полной версии."; break; }
    if (!function_exists('item_isset') || !item_isset($costItemId, $costItemQty)) {
        $response['error'] = "Недостаточно камней для покупки."; break;
    }
    if (!function_exists('minus_item')) {
        $response['error'] = "Сервис списания предметов недоступен."; break;
    }

    try {
        // ---------- ТРАНЗАКЦИЯ ----------
        $mysqli->begin_transaction();

        // Блокируем строку БП пользователя на время апгрейда
        $lock = $mysqli->query("
            SELECT `id`,`type`,`point`
            FROM `aa_battle_pass_user`
            WHERE `user`=".(int)$user_id." AND `season_id`=".$season_id."
            FOR UPDATE
        ")->fetch_assoc();
        if (!$lock) { throw new Exception('Строка пропуска не найдена.'); }
        if ((int)$lock['type'] == 2) { throw new Exception('Полная версия уже активна.'); }

        // Повторная проверка наличия предмета перед списанием
        if (!item_isset($costItemId, $costItemQty)) {
            throw new Exception('Недостаточно камней для покупки.');
        }

        // Списываем оплату
        minus_item($costItemId, $costItemQty);

        // Переводим в полную версию (type=2). ВАЖНО: поинты НЕ меняем.
        $mysqli->query("
            UPDATE `aa_battle_pass_user`
               SET `type` = 2
             WHERE `user`=".(int)$user_id." AND `season_id`=".$season_id."
             LIMIT 1
        ");
        if ($mysqli->affected_rows !== 1) {
            throw new Exception('Не удалось обновить статус пропуска.');
        }

        // Лог в историю (без упоминания жетонов/поинтов)
        $mysqli->query("
            INSERT INTO `aa_battle_pass_history` (`user_id`,`season_id`,`event`,`info`)
            VALUES (".(int)$user_id.", ".$season_id.", 'activatedfull', 'Покупка полной версии (без доп. жетонов)')
        ");

        $mysqli->commit();
        // ---------- /ТРАНЗАКЦИЯ ----------

        // ===== После успешной активации: выдаём СПЕЦ-миссии до лимита =====
        // Эти функции определены в battlepass_cron.php. Если их нет — просто пропустим блок.
        if (function_exists('bp_count_occupied') && function_exists('bp_assign_missions_for_user') && function_exists('bp_next_special_dt')) {

            // Узнаём лимит слотов спец-миссий пользователя
            $bp_row = $mysqli->query("
                SELECT COALESCE(`special_slots`,1) AS special_slots
                FROM `aa_battle_pass_user`
                WHERE `user`=".(int)$user_id." AND `season_id`=".$season_id."
                LIMIT 1
            ")->fetch_assoc();

            $specialSlots = (int)($bp_row['special_slots'] ?? 1);
            if ($specialSlots < 0) $specialSlots = 0;

            // Сколько уже занято активными (не expired) спец-миссиями
            $occupied = bp_count_occupied($mysqli, (int)$user_id, (int)$season_id, 'special');
            $need = max(0, $specialSlots - (int)$occupied);

            if ($need > 0) {
                // При активации премиума — выдаём немедленно, игнорируя 3-дневный кулдаун,
                // но всё равно без одновременных дублей (это обеспечивает сам ассайнер).
                bp_assign_missions_for_user(
                    $mysqli,
                    (int)$user_id,
                    (int)$season_id,
                    'special',
                    (int)$need,
                    bp_next_special_dt(),
                    ['ignore_cooldown' => true]
                );
            }
        }

        // Ответ клиенту
        $nm = function_exists('h') ? h($user['login']) : htmlspecialchars($user['login'], ENT_QUOTES, 'UTF-8');
        $response['html']  = "Поздравляем, {$nm}! Полная версия пропуска активирована.";
        $response['minus'] = "<img src='/img/world/items/little/{$costItemId}.png' class='item'> Драгоценный камень <b>x{$costItemQty}</b>";
        $response['plus']  = ""; // никаких доп. жетонов/поинтов

    } catch (Throwable $e) {
        if ($mysqli->errno) { $mysqli->rollback(); }
        $response['error'] = "Ошибка активации: ".$e->getMessage();
    }

break;


// Категории интерфейса
case 'category':
    $category = $_POST['category'] ?? 'info';
    ob_start();
    switch ($category) {

        case 'mission':

    /* ---------- Утилиты ---------- */
    if (!function_exists('h')) {
        function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    }
    if (!function_exists('bp_normalize_type')) {
        function bp_normalize_type($raw){
            $t = strtolower(trim((string)$raw));
            if (in_array($t,['daily','day','d','ежедневное','ежедневная']))       return 'daily';
            if (in_array($t,['weekly','week','w','еженедельное','еженедельная'])) return 'weekly';
            if (in_array($t,['special','spec','s','специальное','спец']))          return 'special';
            return 'daily';
        }
    }
    if (!function_exists('secondsToShortHuman')) {
        function secondsToShortHuman($sec){
            $sec = (int)$sec; if ($sec<=0) return '0с';
            $d=floor($sec/86400); $sec%=86400; $h=floor($sec/3600); $sec%=3600; $m=floor($sec/60);
            if ($d>0) return $d.'д '.$h.'ч'; if ($h>0) return $h.'ч '.$m.'м'; return $m.'м';
        }
    }

    // Синхронизация слотов
    if (function_exists('enforceBpMissionSlots')) {
        enforceBpMissionSlots($mysqli, (int)$user_id, (int)$season_id);
    }

    $user_bp = function_exists('getUserBP') ? getUserBP($mysqli, $user_id, $season_id) : null;
    if (!$user_bp){ echo '<div style="padding:12px">Боевой пропуск не активирован.</div>'; break; }

    $lvl  = (int)($user_bp['lvl'] ?? 1);
    $me   = (int)($user_bp['exp_me'] ?? 0);
    $to   = (int)($user_bp['exp_to'] ?? 0);
    $perc = $to>0 ? max(0,min(100,round($me/$to*100,1))) : 0;

    $slots = [
        'daily'   => (int)($user_bp['daily_slots']   ?? 2),
        'weekly'  => (int)($user_bp['weekly_slots']  ?? 1),
        'special' => (int)($user_bp['special_slots'] ?? 1),
    ];

    $missionsByType = function_exists('getUserMissionsActive')
        ? getUserMissionsActive($mysqli, $user_id, $season_id)
        : ['daily'=>[], 'weekly'=>[], 'special'=>[]];

    // НПС-приёмщик (лайтовая подсказка)
    $giverName='Персонаж'; $giverImg='/img/default-npc.png'; $giverLoc='';
    $npc = $mysqli->query("
        SELECT n.name, n.image, COALESCE(l.name,'') AS loc_name
        FROM base_npc n
        LEFT JOIN base_location l ON l.id=n.loc_id
        WHERE n.id=55 LIMIT 1
    ")->fetch_assoc();
    if ($npc){ $giverName=(string)$npc['name']; $giverImg=(string)$npc['image']; $giverLoc=(string)$npc['loc_name']; }

    /* ---------- Стили (вставка один раз) ---------- */
    if (!defined('BP_MISSION_SKIN_V13')) {
        define('BP_MISSION_SKIN_V13',1);
        echo '
        <style>
          :root{
            --bp-b:#e6eafe; --bp-bg:#f7f9ff; --bp-card:#ffffff; --bp-t:#1b2540; --bp-sub:#6f7b95;
            --bp-ac:#0e55b6; --bp-ok:#16b574;
            --bp-d:#5b7cff; --bp-w:#f0b400; --bp-s:#ff6b9d;
            --bp-shadow:0 14px 40px rgba(23,35,74,.12);
          }
          .bpBox{border:1px solid var(--bp-b);border-radius:18px;background:var(--bp-card);box-shadow:var(--bp-shadow);padding:14px}

          /* Header */
          .bpHeader{display:grid;grid-template-columns:auto 1fr auto;gap:12px;align-items:center}
          .bpLvl{
            width:60px;height:60px;border-radius:16px;border:1px solid var(--bp-b);
            background:linear-gradient(180deg,#fff 0%,#f2f6ff 100%);display:grid;place-items:center;
            font:900 20px Nunito,Arial;color:var(--bp-ac)
          }
          .bpBarWrap{min-width:0}
          .bpBarTop{display:flex;align-items:center;gap:10px}
          .bpTitle{font:900 15px Nunito,Arial;color:var(--bp-t)}
          .bpInfo{width:28px;height:28px;border-radius:10px;border:1px solid var(--bp-b);background:#eef3ff;color:var(--bp-ac);
                  display:grid;place-items:center;cursor:pointer}
          .bpBar{height:12px;border:1px solid var(--bp-b);border-radius:999px;background:#edf1ff;overflow:hidden}
          .bpBar>div{height:100%;width:0;background:linear-gradient(90deg,#6d8bff,#7ee0ff);transition:width .25s ease}
          .bpBarTxt{font:800 12px Nunito,Arial;color:#6f7b95;margin-top:4px}
          .bpHelp{display:none;margin-top:8px;border:1px dashed var(--bp-b);border-radius:12px;padding:10px;background:#fff}
          .bpLegend{display:flex;gap:12px;flex-wrap:wrap;margin:10px 0 4px;color:#8896c2;font:800 12.5px Nunito,Arial}
          .bpLegend .dot{width:10px;height:10px;border-radius:2px;border:1px solid var(--bp-b);display:inline-block;margin-right:6px}
          .dot.daily{background:#eaf0ff}.dot.weekly{background:#fff2cc}.dot.special{background:#ffe7f0}

          /* Section titles */
          .bpSecT{font:900 14px Nunito,Arial;color:#2d3b66;margin:12px 6px 8px}

          /* Card */
          .bpCard{
            position:relative;padding:18px;border-radius:16px;background:var(--bp-card);border:1px solid var(--bp-b);
            box-shadow:0 10px 24px rgba(23,35,74,.08);margin:8px 0;overflow:hidden;transition:transform .12s ease, box-shadow .12s ease
          }
          .bpCard:hover{transform:translateY(-2px);box-shadow:0 16px 28px rgba(23,35,74,.12)}
          .bpCard::before{
            content:"";position:absolute;left:0;top:0;right:0;height:3px;
            background:linear-gradient(90deg, rgba(93,124,255,.95), rgba(126,224,255,.95));opacity:.85
          }
          .bpCard.t-weekly::before{background:linear-gradient(90deg, #f0b400, #ffd76b)}
          .bpCard.t-special::before{background:linear-gradient(90deg, #ff6b9d, #ffd0de)}

          .bpCard.done{background:linear-gradient(180deg,#ffffff 0%,#f8fff9 100%);border-color:#bfead3}
          .bpCard.done::before{background:linear-gradient(90deg,#16b574,#83f3c1)}

          /* time pill (top-right) */
          .bpTime{
            position:absolute;right:12px;top:10px;display:inline-flex;align-items:center;gap:6px;
            background:#ffffff;border:1px solid var(--bp-b);border-radius:999px;padding:6px 10px;
            font:900 12px Nunito,Arial;color:#34486c;box-shadow:0 2px 10px rgba(23,35,74,.06)
          }
          .bpTime.ok{background:#eafff3;border-color:#bfead3;color:#138d56}

          /* Head row */
          .bpHead{display:flex;align-items:flex-start;gap:10px;padding-right:110px}
          .bpChip{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:12px;border:1px solid var(--bp-b);
            font:900 12px Nunito,Arial;color:#324261;background:#f7faff}
          .bpChip.daily{background:#eaf0ff}.bpChip.weekly{background:#fff2cc}.bpChip.special{background:#ffe7f0}
          .bpTitle2{font:900 17px/1.25 Nunito,Arial;color:#24345c;margin-top:2px;word-break:break-word}
          .bpTitle2 img{height:22px;vertical-align:middle;margin-right:8px}

          /* Body grid */
          .bpBody{display:grid;grid-template-columns:1fr 280px;gap:16px;margin-top:12px}
          @media(max-width:900px){ .bpBody{grid-template-columns:1fr} }

          /* Progress & meta */
          .bpProg{height:12px;border-radius:999px;border:1px solid var(--bp-b);background:#eef2ff;overflow:hidden}
          .bpProg>div{height:100%;background:linear-gradient(90deg,#6d8bff,#7ee0ff);transition:width .25s ease}
          .bpMeta{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}
          .bpTag{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--bp-b);background:#fff;border-radius:999px;padding:6px 10px;
                 font:900 12px Nunito,Arial;color:#6b7c97}
          .bpTag i{opacity:.85}

          /* Right column */
          .bpRight{display:flex;flex-direction:column;gap:8px;justify-content:center}
          .bpHint{display:flex;align-items:center;gap:10px;background:#f7fbff;border:1px dashed #cfe0ff;color:#35507a;border-radius:12px;padding:10px}
          .bpHint img{width:28px;height:28px;border-radius:8px;object-fit:cover;background:#eef}
          .bpState{font:900 12px Nunito,Arial}
          .bpState.ok{color:var(--bp-ok)} .bpState.wait{color:#7d8fb8}

          /* Empty slot */
          .bpEmpty{padding:12px;border:1px dashed #cfe0ff;border-radius:14px;background:#fbfdff;color:#7a8cc1;font:800 12.5px Nunito,Arial;margin:8px 0}

          @media(max-width:560px){
            .bpLvl{width:52px;height:52px}
            .bpLegend{gap:10px}
            .bpHead{padding-right:96px}
            .bpTitle2{font-size:16px}
            .bpTime{padding:5px 8px}
          }
        </style>';
    }

    /* ---------- Шапка ---------- */
    echo '<div class="bpBox">';
      echo '<div class="bpHeader">
              <div class="bpLvl">'.$lvl.'</div>
              <div class="bpBarWrap">
                <div class="bpBarTop">
                  <div class="bpTitle">Опыт пропуска</div>
                  <button id="bpExpInfo" class="bpInfo" type="button" aria-expanded="false" aria-controls="bpExpHelp"><i class="fas fa-info"></i></button>
                </div>
                <div class="bpBar"><div style="width:'.$perc.'%"></div></div>
                <div class="bpBarTxt">'.$me.' / '.$to.' ('.$perc.'%)</div>
                <div class="bpHelp" id="bpExpHelp">
                  <div style="font-weight:900;margin-bottom:6px">Как получать EXP</div>
                  <ul style="margin:0 0 6px 18px;padding:0">
                    <li>Выполняйте <b>ежедневные</b> и <b>еженедельные</b> задания</li>
                    <li>Проходите <b>специальные</b> задания (для полной версии)</li>
                  </ul>
                  <div style="opacity:.9">Награда в EXP указана на карточке каждого задания.</div>
                </div>
              </div>
            </div>';

      echo '<div class="bpLegend">
              <div><span class="dot daily"></span>Ежедневные</div>
              <div><span class="dot weekly"></span>Еженедельные</div>
              <div><span class="dot special"></span>Специальные</div>
            </div>';

    /* ---------- Рендер карточки ---------- */
    $renderCard = function(array $mis) use ($giverName,$giverImg,$giverLoc){
        $type   = bp_normalize_type($mis['mission_type'] ?? 'daily');
        $label  = $type==='daily'?'Ежедневное':($type==='weekly'?'Еженедельное':'Специальное');
        $max    = max(1,(int)$mis['count_max']);
        $now    = (int)$mis['progress'];
        $pct    = min(100, round($now/$max*100,1));
        $done   = (int)$mis['done'] === 1;
        $exp    = (int)$mis['exp'];
        $expires= !empty($mis['expires_at']) ? strtotime($mis['expires_at']) : null;
        $left   = $expires ? secondsToShortHuman($expires - time()) : '—';

        $tt = (string)($mis['target_type'] ?? '');
        $title = h($mis['text'] ?? '');
        if ($tt==='item' && (int)$mis['target_id']>0)   $title = '<img src="/img/world/items/little/'.(int)$mis['target_id'].'.png" alt="">'.$title;
        if ($tt==='coins')                              $title = '<img src="/img/ui/coin.png" alt="">'.$title;
        if ($tt==='kills')                              $title = '<img src="/img/ui/swords.png" alt="">'.$title;
        if ($tt==='poke' && (int)$mis['target_id']>0)   $title = '<img src="/img/pokemons/animation/'.(int)$mis['target_id'].'.png" alt="">'.$title;

        echo '<div class="bpCard t-'.$type.($done?' done':'').'">';

        // time / status (top-right)
        if ($done) {
            echo '<div class="bpTime ok"><i class="fas fa-check"></i> Готово</div>';
        } else {
            echo '<div class="bpTime"><i class="far fa-clock"></i> '.$left.'</div>';
        }

        // head row
        echo '  <div class="bpHead">
                  <div class="bpChip '.$type.'">'.$label.'</div>
                  <div class="bpTitle2">'.$title.'</div>
                </div>';

        // body
        echo '  <div class="bpBody">';
        echo '    <div>';
        echo '      <div class="bpProg"><div style="width:'.$pct.'%"></div></div>';
        echo '      <div class="bpMeta">
                      <span class="bpTag"><i class="fas fa-chart-line"></i> '.$now.' / '.$max.'</span>
                      <span class="bpTag"><i class="fas fa-star"></i> Награда: <b>'.$exp.' EXP</b></span>
                    </div>';
        echo '    </div>';

        echo '    <div class="bpRight">';
        if (!$done) {
            if ($tt==='poke') {
                echo '  <div class="bpHint"><img src="'.h($giverImg).'" alt=""><div>Сдайте покемона персонажу <b>'.h($giverName).'</b>'.($giverLoc?' — '.h($giverLoc):'').'.</div></div>';
            } elseif ($tt==='item') {
                echo '  <div class="bpHint"><img src="/img/world/items/little/'.(int)$mis['target_id'].'.png" alt=""><div>Сдайте необходимые предметы приёмщику.</div></div>';
            } else {
                echo '  <div class="bpState wait">Прогресс начисляется автоматически</div>';
            }
        } else {
            echo '  <div class="bpState ok"><i class="fas fa-trophy"></i> Выполнено</div>';
        }
        echo '    </div>';

        echo '  </div>'; // body
        echo '</div>';   // card
    };

    $renderEmpty = function($label){
        echo '<div class="bpEmpty">Слот свободен — новое '.$label.' задание будет выдано автоматически при сбросе.</div>';
    };
    $slice = function(array $arr, int $limit){ return array_slice($arr, 0, max(0,$limit)); };

    /* ---------- Секции ---------- */
    echo '<div class="bpSecT">Ежедневные</div>';
    $daily = $slice($missionsByType['daily'] ?? [], $slots['daily']);
    foreach ($daily as $m) $renderCard($m);
    for ($i=count($daily); $i<$slots['daily']; $i++) $renderEmpty('ежедневное');

    echo '<div class="bpSecT">Еженедельные</div>';
    $weekly = $slice($missionsByType['weekly'] ?? [], $slots['weekly']);
    foreach ($weekly as $m) $renderCard($m);
    for ($i=count($weekly); $i<$slots['weekly']; $i++) $renderEmpty('еженедельное');

    echo '<div class="bpSecT">Специальные</div>';
    $special = $slice($missionsByType['special'] ?? [], $slots['special']);
    foreach ($special as $m) $renderCard($m);
    for ($i=count($special); $i<$slots['special']; $i++) $renderEmpty('специальное');

    echo '</div>'; // .bpBox

    /* ---------- JS: только кнопка i (без Tipped) ---------- */
    echo '<script>
      (function(){
        var btn=document.getElementById("bpExpInfo");
        var help=document.getElementById("bpExpHelp");
        if(btn&&help){
          btn.addEventListener("click",function(e){
            e.preventDefault();
            var opened=getComputedStyle(help).display!=="none";
            help.style.display=opened?"none":"block";
            btn.setAttribute("aria-expanded",opened?"false":"true");
          },false);
        }
      })();
    </script>';

    break;

case 'track':
    // Данные
    $prizes     = getPrizes($mysqli, $season_id);               // все призы сезона
    $userprizes = getUserPrizes($mysqli, $user_id, $season_id); // уже полученные призы (по id)
    $user_bp    = getUserBP($mysqli, $user_id, $season_id);     // прогресс пользователя

    $userLvl  = (int)($user_bp['lvl']  ?? 0);
    $userType = (int)($user_bp['type'] ?? 1); // 1=обычный, 2=премиум

    // Метаданные сезона (краткий хедер без большого заголовка)
    $season_subtitle   = $season_subtitle   ?? 'Выполняйте задания и получайте награды';
    $season_desc       = $season_desc       ?? '';
    $season_banner     = $season_banner     ?? '/img/battlepass/season-banner-default.png';
    $season_ends_at_ts = isset($season_ends_at_ts) ? (int)$season_ends_at_ts : 0;

    // Максимальный уровень
    $max_level = 0;
    foreach ($prizes as $prize) {
        $lvl = (int)$prize['level'];
        if ($lvl > $max_level) $max_level = $lvl;
    }

    // Группировка по уровню/типу и сбор ID для пакетной загрузки
    $track   = [];   // $track[level][type] = prize
    $itemIds = [];   // предметы
    $pokIds  = [];   // покемоны

    foreach ($prizes as $prize) {
        $level = (int)$prize['level'];
        $type  = (int)$prize['type']; // 1=free, 2=premium
        $track[$level][$type] = $prize;

        if ((int)$prize['type_pr'] === 1) {
            $pid = (int)($prize['pok'] ?? 0);
            if ($pid > 0) $pokIds[$pid] = true;
        } else {
            $item_l = array_filter(explode(';', (string)$prize['item']));
            foreach ($item_l as $item_data) {
                $parts = array_map('intval', explode(',', $item_data));
                $iid = $parts[0] ?? 0;
                $qty = $parts[1] ?? 0;
                if ($iid > 0 && $qty > 0) $itemIds[$iid] = true;
            }
        }
    }

    // Пакетная загрузка названий предметов и покемонов
    $itemsMap = []; // id => name
    if (!empty($itemIds)) {
        $in = implode(',', array_map('intval', array_keys($itemIds)));
        $res = $mysqli->query("SELECT id, name FROM base_items WHERE id IN ($in)");
        if ($res) while ($r = $res->fetch_assoc()) $itemsMap[(int)$r['id']] = (string)$r['name'];
    }
    $poksMap = []; // id => name_rus
    if (!empty($pokIds)) {
        $in = implode(',', array_map('intval', array_keys($pokIds)));
        $res = $mysqli->query("SELECT id, name_rus FROM base_pokemons WHERE id IN ($in)");
        if ($res) while ($r = $res->fetch_assoc()) $poksMap[(int)$r['id']] = (string)$r['name_rus'];
    }

    // Рендер компактной карточки приза
    $renderPrizeCell = function(array $p, int $userLvl, int $userType, array $userprizes, array $itemsMap, array $poksMap, string $laneClass): string {
        $id        = (int)$p['id'];
        $level     = (int)$p['level'];
        $trackType = (int)$p['type'];     // 1=free, 2=premium
        $typePr    = (int)$p['type_pr'];  // 1=pokemon, 2=items
        $claimed   = isset($userprizes[$id]);

        $needsLevel = $userLvl < $level;
        $needsFull  = ($trackType === 2 && $userType < 2);
        $canClaim   = (!$claimed && !$needsLevel && !$needsFull);

        $titleHtml  = '';
        $iconsHtml  = '';

        if ($typePr === 1) {
            $pid   = (int)($p['pok'] ?? 0);
            $pname = $poksMap[$pid] ?? 'Покемон';
            $iconsHtml .= '<div class="bpIcon"><img loading="lazy" src="/img/pokemons/animation/'.$pid.'.png" alt="'.h($pname).'"></div>';
            $titleHtml  = '<div class="bpTitle">'.h($pname).'</div>';
        } else {
            $item_l     = array_filter(explode(';', (string)$p['item']));
            $titleParts = [];
            foreach ($item_l as $item_data) {
                $parts = array_map('intval', explode(',', $item_data));
                $iid = $parts[0] ?? 0;
                $qty = $parts[1] ?? 0;
                if ($iid <= 0 || $qty <= 0) continue;
                $iname = $itemsMap[$iid] ?? ('Предмет #'.$iid);
                $iconsHtml .= '<div class="bpIcon"><img loading="lazy" src="/img/world/items/little/'.$iid.'.png" alt="'.h($iname).'"><div class="bpQty">x'.$qty.'</div></div>';
                $titleParts[] = h($iname).' × '.$qty;
            }
            $titleHtml = '<div class="bpTitle">'.implode(', ', $titleParts).'</div>';
        }

        $cellClass = trim(($trackType === 2 ? 'premium' : 'free').' '.$laneClass);
        if ($claimed)   $cellClass .= ' claimed';
        if ($canClaim)  $cellClass .= ' can-claim';
        if ($needsFull) $cellClass .= ' locked-prem';
        if ($needsLevel)$cellClass .= ' locked-lvl';

        $lockedReason = '';
        if ($needsLevel) $lockedReason = 'Нужен уровень '.$level;
        elseif ($needsFull) $lockedReason = 'Требуется полный пропуск';

        ob_start();
        echo '<div class="bpPrizeCell '.$cellClass.'" data-prize-id="'.$id.'" data-level="'.$level.'" data-track="'.$trackType.'">';
        echo '  <div class="bpRibbon '.($trackType===2?'prem':'free').'">'.($trackType===2?'Премиум':'Free').'</div>';
        echo '  <div class="bpIcons">'.$iconsHtml.'</div>';
        echo '  '.$titleHtml;
        if ($claimed) {
            echo '  <div class="bpClaimed">Забрано</div>';
        } elseif ($canClaim) {
            echo '  <button class="bpClaimBtn" onclick="battlepass_giveprize('.$id.')">Забрать</button>';
        } else {
            echo '  <div class="bpLocked"><i class="fa fa-lock"></i> '.h($lockedReason).'</div>';
        }
        echo '</div>';
        return ob_get_clean();
    };

    // Подсчёт строки "до окончания"
    $season_left_str = '';
    if ($season_ends_at_ts > 0) {
        $left = max(0, $season_ends_at_ts - time());
        $days = (int)floor($left / 86400);
        $hours = (int)floor(($left % 86400) / 3600);
        $mins = (int)floor(($left % 3600) / 60);
        if ($days > 0)      $season_left_str = "До окончания: {$days} д. {$hours} ч.";
        elseif ($hours > 0) $season_left_str = "До окончания: {$hours} ч. {$mins} мин.";
        else                $season_left_str = "До окончания: {$mins} мин.";
    }

    // CSS + макет (новая компактная колонковая лента: на один уровень — две карточки под ним)
    ob_start();
    ?>
    <style>
      .bpTrackViewport{
        --colW: 145px;            /* ширина колонки уровня */
        --gapX: 10px;             /* горизонтальный зазор */
        --gapY: 10px;             /* вертикальный зазор в колонке (между free/prem) */
        --cardH: 120px;           /* высота карточки награды */
        --brand: #6b6ef6;
        --brand2:#6dd5ed;
        --gold:#c79a1b;
        --gold-bg:#fff3cd;
        --gold-br:#ffe08a;
        --muted:#e7eaff;
      }
      .bpContainer{background:linear-gradient(180deg,#f6f8ff 0,#fafbff 100%);border:1px solid #e8ecff;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(35,40,105,.05)}

      /* ——— Заголовок ——— */
      .bpHero{display:grid;grid-template-columns:1fr minmax(220px,320px);gap:14px;padding:12px 14px;background:linear-gradient(180deg,#f7f9ff 0,#eef3ff 100%);border-bottom:1px solid #e9edff}
      .bpSeasonSubtitle{font:800 13px/1.1 Inter;color:#595ed6}
      .bpSeasonDesc{font:500 12px/1.45 Inter;color:#6e74a0;margin-top:4px}
      .bpHeroMeta{margin-top:6px;display:flex;flex-wrap:wrap;gap:8px}
      .bpMetaChip{display:inline-flex;gap:6px;align-items:center;background:#fff;border:1px solid #e7eaff;border-radius:999px;padding:5px 9px;font-weight:800;color:#6e72a8}
      .bpHeroActions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
      .bpHeroActions .btn{background:#fff;border:1px solid #e7eaff;border-radius:10px;padding:7px 10px;font-weight:800;color:#5a5ab0;cursor:pointer}
      .bpHeroActions .btn.primary{background:linear-gradient(90deg,#7d7cf8,#6dd5ed);border:0;color:#fff;box-shadow:0 4px 12px rgba(109,213,237,.25)}
      .bpHeroBanner{position:relative;border-radius:12px;overflow:hidden;border:1px solid #e8ecff;background:#fff;min-height:110px}
      .bpHeroBanner img{width:100%;height:100%;object-fit:cover}

      /* ——— Основная лента ——— */
      .bpLanesWrap{padding:10px;background:#fbfcff}
      .trackTop{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
      .trackTabs{display:flex;gap:8px}
      .trackTabs .tab{padding:6px 9px;border:1px solid #e4e7ff;border-radius:999px;font-weight:800;font-size:12px;color:#6e72a8;background:#fff;cursor:pointer}
      .trackTabs .tab.active{background:#eef0ff;color:#4f54d8;border-color:#dfe3ff}
      .navArrows{display:flex;gap:6px}
      .navArrows .arrow{width:28px;height:28px;border-radius:999px;background:#fff;border:1px solid #e6e9ff;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#6f73a5}
      .navArrows .arrow:hover{background:#f6f7ff}

      /* Хор. скролл + гибкая колонковая сетка */
      .bpScrollX{overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;scroll-behavior:smooth;padding-bottom:6px}
      .levelGrid{display:flex;gap:var(--gapX)}
      .levelCol{flex:0 0 var(--colW);min-width:var(--colW);position:relative}
      .levelCol::after{content:'';position:absolute;top:48px;left:calc(50% - 1px);width:2px;height:calc(var(--gapY) + var(--cardH) + var(--gapY) + var(--cardH));background:linear-gradient(180deg,#edf0ff 0,#e6e9ff 50%,#edf0ff 100%);opacity:.6;border-radius:2px}
      .lvlChip{width:30px;height:30px;border-radius:999px;background:#fff;border:1px solid #e2e6ff;color:#6266e8;font:900 12px/30px Inter;text-align:center;margin:0 auto 8px;position:relative;box-shadow:0 2px 6px rgba(35,40,105,.06);cursor:pointer}
      .lvlChip.current{border-color:#cfd6ff;box-shadow:0 6px 14px rgba(87,91,227,.16), 0 0 0 4px rgba(109,213,237,.14)}
      .lvlChip.key::after{content:'★';position:absolute;right:-7px;top:-7px;width:16px;height:16px;border-radius:50%;background:#fff1d0;border:1px solid #ffe08a;color:#c79a1b;font:900 10px/16px Inter}
      .stack{display:flex;flex-direction:column;gap:var(--gapY);position:relative;z-index:1}

      /* Карточки наград (компакт) */
      .bpPrizeCell{position:relative;background:#fff;border:1px solid #eceeff;border-radius:12px;min-height:var(--cardH);padding:10px;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;box-shadow:0 1px 6px rgba(24,32,84,.05);transition:transform .1s ease, box-shadow .15s ease, border-color .15s ease}
      .bpPrizeCell:hover{transform:translateY(-1px);box-shadow:0 8px 16px rgba(35,40,105,.08)}
      .bpPrizeCell.premium{background:linear-gradient(180deg,#fffaf0 0,#fff 30px);border-color:#ffe8ae}
      .bpPrizeCell.claimed{opacity:.92}
      .bpPrizeCell.can-claim{border-color:#b9e1ff;box-shadow:0 0 0 2px rgba(109,213,237,.2) inset}
      .bpRibbon{position:absolute;left:8px;top:-10px;padding:4px 8px;border-radius:999px;border:1px solid;font-weight:900;font-size:10px;background:#fff}
      .bpRibbon.free{border-color:#e4e7ff;color:#6b6ef6}
      .bpRibbon.prem{border-color:#ffe08a;color:#8b6a00;background:#fff8db}
      .bpIcons{display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin:6px 0 8px}
      .bpIcon{position:relative;width:42px;height:42px;border-radius:10px;background:#f6f7ff;border:1px solid #e3e6ff;display:flex;align-items:center;justify-content:center}
      .bpIcon img{max-width:100%;max-height:100%;object-fit:contain}
      .bpQty{position:absolute;right:4px;bottom:4px;background:#5958e8;color:#fff;font-size:10px;font-weight:800;border-radius:6px;padding:1px 5px}
      .bpTitle{font-size:12px;color:#5a5ab0;text-align:center;min-height:16px;margin-bottom:6px;max-width:100%;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
      .bpClaimBtn{padding:7px 10px;border:none;border-radius:10px;font-weight:900;cursor:pointer;background:linear-gradient(90deg,#7d7cf8,#6dd5ed);color:#fff;font-size:12px}
      .bpClaimed{position:absolute;top:6px;right:6px;background:#e9f9f0;color:#1bbb70;border:1px solid #b9f0d3;font-weight:800;font-size:10px;border-radius:999px;padding:2px 7px}
      .bpLocked{margin-top:auto;font-size:11px;color:#9aa0d0;background:#f6f7ff;border:1px dashed #c7c9ff;border-radius:8px;padding:4px 7px;display:flex;gap:6px;align-items:center}

      /* Легенда и режимы отображения */
      .bpLegend{margin:8px 12px 10px;display:flex;gap:14px;justify-content:center;font-size:12px;color:#a6a6c6}
      .bpLegend .chip{display:inline-block;width:12px;height:12px;border-radius:4px;margin-right:6px}
      .bpChipFree{background:#e4e6fc}
      .bpChipPrem{background:#fff1d0}

      /* Мобильный: переключатель дорожек (показываем по одной) */
      .mode-free .stack .premium{display:none}
      .mode-prem .stack .free{display:none}

      @media (max-width: 860px){
        .bpHero{grid-template-columns:1fr}
        .bpHeroBanner{min-height:120px}
        .trackTabs{display:flex}
        .bpTrackViewport{ --colW: 128px; --cardH: 100px; }
      }
      @media (max-width: 480px){
        .bpTrackViewport{ --colW: 120px; --cardH: 96px; }
        .bpIcon{width:38px;height:38px}
        .lvlChip{width:28px;height:28px;line-height:28px;font-size:11px}
      }
    </style>

    <div class="bpTrackViewport" id="bpViewport">
      <div class="bpContainer">
        <!-- HERO -->
        <div class="bpHero">
          <div>
            <div class="bpSeasonSubtitle"><?= h($season_subtitle) ?></div>
            <?php if ($season_desc): ?>
              <div class="bpSeasonDesc"><?= nl2br(h($season_desc)) ?></div>
            <?php endif; ?>
            <div class="bpHeroMeta">
              <?php
              $meta = [];
              if ($season_ends_at_ts > 0) $meta[] = '<div class="bpMetaChip"><i class="fa fa-clock-o"></i> '.h($season_left_str).'</div>';
              $meta[] = '<div class="bpMetaChip"><i class="fa fa-user"></i> Уровень <strong style="margin-left:4px;">'.(int)$userLvl.'</strong></div>';
              $meta[] = '<div class="bpMetaChip"><i class="fa fa-trophy"></i> Макс. <strong style="margin-left:4px;">'.(int)$max_level.'</strong></div>';
              echo implode('', $meta);
              ?>
            </div>
            <div class="bpHeroActions">
              <button type="button" class="btn" id="bpGoToMyLevel"><i class="fa fa-location-arrow"></i> К моему уровню</button>
              <button type="button" class="btn primary" id="bpClaimAll"><i class="fa fa-gift"></i> Забрать все</button>
              <button type="button" class="btn" id="bpNextKeyReward"><i class="fa fa-star"></i> Следующая ключевая</button>
            </div>
          </div>
          <div class="bpHeroBanner" aria-hidden="true">
            <img src="<?= h($season_banner) ?>" alt="">
          </div>
        </div>

        <!-- TRACK -->
        <div class="bpLanesWrap">
          <div class="trackTop">
            <div class="trackTabs" id="trackTabs">
              <div class="tab active" data-mode="both">Обе дорожки</div>
              <div class="tab" data-mode="free">Обычные</div>
              <div class="tab" data-mode="prem">Премиум</div>
            </div>
            <div class="navArrows">
              <div class="arrow" id="arrowL" title="Влево"><i class="fa fa-angle-left"></i></div>
              <div class="arrow" id="arrowR" title="Вправо"><i class="fa fa-angle-right"></i></div>
            </div>
          </div>

          <div class="bpScrollX" id="bpScrollX" tabindex="0" aria-label="Лента наград по уровням">
            <div class="levelGrid" id="levelGrid">
              <?php
              // Комбинированная колонка: чип уровня + free + premium
              for ($level = 1; $level <= $max_level; $level++) {
                  $isKey = !empty($track[$level][2]) && (!empty($track[$level][2]['is_key']) || ($level % 10 === 0));
                  echo '<div class="levelCol" id="bp-col-'.$level.'" data-level="'.$level.'" data-key="'.($isKey?1:0).'">';
                  echo '  <div class="lvlChip'.($level==$userLvl?' current':'').($isKey?' key':'').'" data-jump="'.$level.'">'.$level.'</div>';
                  echo '  <div class="stack">';
                  // FREE
                  echo '    <div class="free">';
                  if (!empty($track[$level][1])) {
                      echo $renderPrizeCell($track[$level][1], $userLvl, $userType, $userprizes, $itemsMap, $poksMap, 'free');
                  } else {
                      echo '<div class="bpPrizeCell free" style="visibility:hidden;min-height:var(--cardH)"></div>';
                  }
                  echo '    </div>';
                  // PREMIUM
                  echo '    <div class="premium">';
                  if (!empty($track[$level][2])) {
                      echo $renderPrizeCell($track[$level][2], $userLvl, $userType, $userprizes, $itemsMap, $poksMap, 'premium');
                  } else {
                      echo '<div class="bpPrizeCell premium" style="visibility:hidden;min-height:var(--cardH)"></div>';
                  }
                  echo '    </div>';
                  echo '  </div>';
                  echo '</div>';
              }
              ?>
            </div>
          </div>

          <div class="bpLegend">
            <div><span class="chip bpChipFree"></span>Обычные</div>
            <div><span class="chip bpChipPrem"></span>Премиум</div>
          </div>
        </div>
      </div>
    </div>

    <script>
      (function(){
        var vp        = document.getElementById('bpViewport');
        var grid      = document.getElementById('levelGrid');
        var scrollX   = document.getElementById('bpScrollX');
        var tabs      = document.getElementById('trackTabs');
        var arrowL    = document.getElementById('arrowL');
        var arrowR    = document.getElementById('arrowR');
        var maxLevel  = <?= (int)$max_level ?>;
        var userLvl   = <?= (int)$userLvl ?>;

        function clamp(n,min,max){ return Math.max(min, Math.min(max, n)); }
        function colByLevel(level){ return document.getElementById('bp-col-'+level); }

        function centerToLevel(level, smooth){
          var col = colByLevel(level);
          if (!col) return;
          var target = col.offsetLeft + (col.clientWidth/2) - (scrollX.clientWidth/2);
          var max = scrollX.scrollWidth - scrollX.clientWidth;
          if (target < 0) target = 0;
          if (target > max) target = max;
          scrollX.scrollTo({ left: target, behavior: smooth ? 'smooth' : 'auto' });
        }

        function highlightLevel(level){
          grid.querySelectorAll('.lvlChip').forEach(function(el){
            el.classList.toggle('current', parseInt(el.dataset.jump,10) === level);
          });
        }

        // Инициализация
        var initLv = clamp(userLvl || 1, 1, maxLevel);
        centerToLevel(initLv, false);
        highlightLevel(initLv);

        // Публичный API
        window.bpUpdateUserLevel = function(newLvl){
          var lv = clamp(parseInt(newLvl,10)||1, 1, maxLevel);
          centerToLevel(lv, true);
          highlightLevel(lv);
        };

        // Клик по чипу уровня
        grid.addEventListener('click', function(e){
          var chip = e.target.closest('.lvlChip'); if (!chip) return;
          var lv = parseInt(chip.dataset.jump,10) || 1;
          centerToLevel(lv, true);
          highlightLevel(lv);
        });

        // Стрелки
        function scrollByPage(dir){
          var dx = dir * Math.max(220, Math.floor(scrollX.clientWidth * 0.8));
          scrollX.scrollBy({ left: dx, behavior: 'smooth' });
        }
        arrowL && arrowL.addEventListener('click', function(){ scrollByPage(-1); });
        arrowR && arrowR.addEventListener('click', function(){ scrollByPage(+1); });

        // Табы (мобильный режим: обе/только free/только прем)
        function setMode(mode){
          vp.classList.remove('mode-free','mode-prem');
          if (mode === 'free') vp.classList.add('mode-free');
          if (mode === 'prem') vp.classList.add('mode-prem');
        }
        if (tabs){
          tabs.addEventListener('click', function(e){
            var t = e.target.closest('.tab'); if (!t) return;
            tabs.querySelectorAll('.tab').forEach(function(x){ x.classList.remove('active'); });
            t.classList.add('active');
            setMode(t.getAttribute('data-mode'));
          });
          // На узких экранах по умолчанию показываем обе (видно часть), юзер может сузить
          if (window.matchMedia('(max-width: 860px)').matches) setMode('free');
        }

        // К моему уровню
        var btnMy = document.getElementById('bpGoToMyLevel');
        btnMy && btnMy.addEventListener('click', function(){
          var lv = clamp(userLvl || 1, 1, maxLevel);
          centerToLevel(lv, true);
          highlightLevel(lv);
        });

        // Следующая ключевая
        var btnKey = document.getElementById('bpNextKeyReward');
        function nextKeyFrom(leftPos){
          var cols = grid.querySelectorAll('.levelCol[data-key="1"]');
          var cur  = (typeof leftPos === 'number') ? leftPos : scrollX.scrollLeft;
          var best = null, bestLv = 0, minDx = Infinity;
          cols.forEach(function(col){
            var left = col.offsetLeft;
            var dx   = left - cur;
            var lvl  = parseInt(col.getAttribute('data-level'),10)||0;
            if (dx > 16 && dx < minDx){ minDx = dx; best = col; bestLv = lvl; }
          });
          return {el:best, level:bestLv};
        }
        function refreshKeyBtn(){
          if (!btnKey) return;
          var n = nextKeyFrom();
          if (n.level){ btnKey.disabled=false; btnKey.innerHTML='<i class="fa fa-star"></i> Следующая ключевая: '+n.level; }
          else { btnKey.disabled=true; btnKey.innerHTML='<i class="fa fa-star"></i> Ключевые получены'; }
        }
        if (btnKey){
          btnKey.addEventListener('click', function(){
            var n = nextKeyFrom();
            if (n.el) { centerToLevel(n.level, true); highlightLevel(n.level); }
          });
          scrollX.addEventListener('scroll', function(){ clearTimeout(scrollX.__k); scrollX.__k=setTimeout(refreshKeyBtn,120); }, {passive:true});
          setTimeout(refreshKeyBtn, 0);
        }

        // Забрать все
        var btnAll = document.getElementById('bpClaimAll');
        function updateAllCounter(){
          if (!btnAll) return;
          var cnt = scrollX.querySelectorAll('.bpPrizeCell.can-claim .bpClaimBtn').length;
          var __old = btnAll.querySelector('.__cnt');
          if (__old && __old.parentNode) __old.parentNode.removeChild(__old);
          if (cnt){
            var s=document.createElement('span'); s.className='__cnt'; s.style.marginLeft='6px'; s.textContent='('+cnt+')';
            btnAll.appendChild(s);
          }
        }
        if (btnAll){
          btnAll.addEventListener('click', function(){
            var claimBtns = scrollX.querySelectorAll('.bpPrizeCell.can-claim .bpClaimBtn');
            if (!claimBtns.length){ try{ Game.notifications.main('Нет доступных наград для получения','info'); }catch(_){ } return; }
            btnAll.disabled = true;
            var i=0;(function step(){
              if (i>=claimBtns.length){ btnAll.disabled=false; updateAllCounter(); try{ Game.notifications.main('Все доступные награды забраны','success'); }catch(_){ } return; }
              var id = claimBtns[i++].closest('.bpPrizeCell').getAttribute('data-prize-id');
              try{ battlepass_giveprize(parseInt(id,10)); }catch(_){}
              setTimeout(step, 110);
            })();
          });
          setTimeout(updateAllCounter, 0);
        }

        // Клавиатура
        scrollX && scrollX.addEventListener('keydown', function(e){
          if (e.key === 'ArrowLeft')  { e.preventDefault(); scrollByPage(-1); }
          if (e.key === 'ArrowRight') { e.preventDefault(); scrollByPage(+1); }
        });
      })();
    </script>
    <?php
    echo ob_get_clean();
break;
                // Магазин жетонов (как было)
                case 'shop':
    $user_bp     = getUserBP($mysqli, $user_id, $season_id);
    $user_points = $user_bp ? (int)$user_bp['point'] : 0;
    $is_full     = ($user_bp && (int)$user_bp['type'] == 2);

    // Достаём лоты магазина текущего сезона
    // Достаём лоты магазина текущего сезона (кеш 20 сек: снижает нагрузку при онлайне)
    $cacheShopKey = 'bp_shop_'.$season_id;
    $prizes = bp_cache_get($cacheShopKey, 20, null);
    if (!is_array($prizes) || !$prizes) {
        $prizes = [];
        $prizeList = $mysqli->query("SELECT * FROM `aa_battle_pass_shop` WHERE `season_id` = ".$season_id." ORDER BY `point` ASC, `id` ASC");
        if ($prizeList) {
            while ($row = $prizeList->fetch_assoc()) $prizes[] = $row;
        }
        bp_cache_set($cacheShopKey, $prizes, 20);
    }


    ob_start();
?>
<style>
  .bpShopWrap{display:flex;flex-direction:column;gap:14px}
  .bpShopHeader{display:flex;align-items:center;justify-content:space-between;gap:12px}
  .bpShopTitle{font-weight:900;color:#5958e8;font-size:20px}
  .bpShopPoints{display:inline-flex;align-items:center;gap:8px;background:#f4f5ff;border:1px solid #e2e4fb;color:#5958e8;border-radius:12px;padding:8px 12px;font-weight:800}
  .bpShopPoints .val{font-family:'JetBrains Mono',monospace;font-size:16px}
  .bpShopGrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}
  .bpShopCard{position:relative;border-radius:14px;background:#fff;border:1px solid #eceeff;box-shadow:0 1px 6px rgba(0,0,0,.05);display:flex;flex-direction:column;overflow:hidden}
  .bpShopCard.locked:before{content:"";position:absolute;inset:0;background:repeating-linear-gradient(45deg,rgba(0,0,0,.04) 0 8px, rgba(0,0,0,.06) 8px 16px)}
  .bpShopHead{padding:10px 12px;border-bottom:1px solid #f0f1ff;display:flex;align-items:center;justify-content:space-between}
  .bpBadge{font-size:11px;font-weight:800;padding:4px 8px;border-radius:999px}
  .bpBadge.free{color:#6c6cdf;background:#eef0ff;border:1px solid #e1e4ff}
  .bpBadge.premium{color:#856404;background:#fff3cd;border:1px solid #ffe49a}
  .bpShopBody{padding:12px 12px 6px 12px;display:flex;flex-direction:column;gap:8px;min-height:96px}
  .bpShopIcons{display:flex;flex-wrap:wrap;gap:8px}
  .bpIcon{position:relative;width:48px;height:48px;background:#f6f7ff;border:1px solid #e3e6ff;border-radius:10px;display:flex;align-items:center;justify-content:center;overflow:hidden}
  .bpIcon img{max-width:100%;max-height:100%;object-fit:contain}
  .bpQty{position:absolute;right:4px;bottom:4px;background:#5958e8;color:#fff;font-size:11px;font-weight:800;border-radius:6px;padding:1px 5px}
  .bpTitle{font-size:13px;color:#5a5ab0;min-height:18px}
  .bpShopFoot{padding:10px 12px;border-top:1px solid #f0f1ff;display:flex;align-items:center;justify-content:space-between;gap:8px}
  .bpPrice{display:flex;align-items:center;gap:6px;color:#4a4a9b;font-weight:800}
  .bpPrice .cost{font-family:'JetBrains Mono',monospace}
  .bpBuyBtn{padding:7px 12px;border:none;border-radius:10px;font-weight:800;cursor:pointer;background:linear-gradient(90deg,#7d7cf8,#6dd5ed);color:#fff}
  .bpBuyBtn[disabled]{opacity:.55;cursor:not-allowed;background:#e9eafc;color:#a0a2d5}
  .bpEmpty{padding:12px;border:1px dashed #c7c9ff;border-radius:12px;color:#8e8eda;background:#f7f8ff}
</style>

<div class="bpShopWrap">
  <div class="bpShopHeader">
    <div class="bpShopTitle">Магазин жетонов</div>
    <div class="bpShopPoints" title="Ваш баланс жетонов">
      <span class="val"><?= (int)$user_points ?></span>
      <span class="ico"><i class="fab fa-galactic-republic"></i></span>
    </div>
  </div>

  <div class="bpShopGrid">
    <?php
    if (!$prizeList || !$prizeList->num_rows) {
        echo '<div class="bpEmpty">В магазине пока нет товаров этого сезона.</div>';
    } else {
        foreach ($prizes as $pr) {
            $id          = (int)$pr['id'];
            $cost        = (int)$pr['point'];
            $type_pr     = (int)$pr['type_pr']; // 1 = покемон, 2 = предметы
            $requireFull = (int)($pr['require_full'] ?? 0); // НОВОЕ поле (см. миграцию ниже)
            $canAfford   = $user_points >= $cost;

            $badgeClass = $requireFull ? 'premium' : 'free';
            $badgeText  = $requireFull ? 'Только полный пропуск' : 'Лот магазина';

            echo '<div class="bpShopCard'.($requireFull && !$is_full ? ' locked' : '').'">';

            // Header
            echo '  <div class="bpShopHead">';
            echo '    <div class="bpBadge '.$badgeClass.'">'.h($badgeText).'</div>';
            echo '    <div style="color:#9aa0d0;font-size:12px;">#'.(int)$id.'</div>';
            echo '  </div>';

            // Body
            echo '  <div class="bpShopBody">';
            echo '    <div class="bpShopIcons">';

            if ($type_pr === 1) {
                // Покемон
                $pokId = (int)($pr['pok'] ?? 0);
                $pok = $pokId ? $mysqli->query("SELECT `name_rus` FROM `base_pokemons` WHERE `id`=".$pokId)->fetch_assoc() : null;
                $pokName = $pok['name_rus'] ?? 'Покемон';
                echo '      <div class="bpIcon"><img loading="lazy" src="/img/pokemons/animation/'.$pokId.'.png" alt="'.h($pokName).'"></div>';
                echo '      <div class="bpTitle">'.h($pokName).'</div>';
            } else {
                // Предметы: показываем ИМЕНА из base_items
                $items = array_filter(explode(';', (string)$pr['item']));
                $ids = [];
                $parsed = [];
                foreach ($items as $item_data) {
                    $parts = array_map('intval', explode(',', $item_data));
                    $item_id = $parts[0] ?? 0;
                    $item_qty = $parts[1] ?? 0;
                    if ($item_id <= 0 || $item_qty <= 0) continue;
                    $ids[$item_id] = true;
                    $parsed[] = [$item_id, $item_qty];
                }
                $names = $ids ? bpGetItemNames($mysqli, array_keys($ids)) : [];
                $titleParts = [];
                foreach ($parsed as [$item_id, $item_qty]) {
                    $nm = $names[$item_id] ?? ('Предмет #'.$item_id);
                    echo '  <div class="bpIcon"><img loading="lazy" src="/img/world/items/little/'.$item_id.'.png" alt="'.h($nm).'"><div class="bpQty">x'.$item_qty.'</div></div>';
                    $titleParts[] = h($nm).' × '.$item_qty;
                }
                echo '      <div class="bpTitle">'.implode(', ', $titleParts).'</div>';
            }

            echo '    </div>'; // icons
            echo '  </div>'; // body

            // Footer
            echo '  <div class="bpShopFoot">';
            echo '    <div class="bpPrice"><span class="cost">'.$cost.'</span> <i class="fab fa-galactic-republic"></i></div>';

            // Кнопка: магазин доступен всем; запрет — только если не хватает жетонов, либо лот требует полный пропуск и он не активирован
            $disabledReason = '';
            if ($requireFull && !$is_full) {
                $disabledReason = 'Только полный пропуск';
            } elseif (!$canAfford) {
                $disabledReason = 'Не хватает '.($cost - $user_points);
            }

            if ($disabledReason) {
                echo '    <button class="bpBuyBtn" disabled aria-disabled="true" title="'.h($disabledReason).'">'.$disabledReason.'</button>';
            } else {
                echo '    <button class="bpBuyBtn" onclick="battlepass_emblemshop('.$id.')">Купить</button>';
            }

            echo '  </div>'; // foot

            echo '</div>'; // card
        }
    }
    ?>
  </div>
</div>
<?php
    echo ob_get_clean();
break;

                case 'stat':
    $stat    = getMissionStat($mysqli, $user_id, $season_id);
    $user_bp = getUserBP($mysqli, $user_id, $season_id);

    $lvl        = (int)($user_bp['lvl'] ?? 0);
    $expTotal   = (int)($user_bp['exp_me'] ?? 0);
    $points     = (int)($user_bp['point'] ?? 0);
    $passType   = (int)($user_bp['type'] ?? 1); // 1=Базовый, 2=Полный
    $passLabel  = $passType === 2 ? 'Полный' : 'Базовый';

    // Агрегация по миссиям
    $total_missions = 0; 
    $done_missions  = 0;
    if ($stat) {
        foreach ($stat as $row) {
            $done_missions  += (int)$row['done'];
            $total_missions += (int)$row['total'];
        }
    }
    $missionPct = $total_missions > 0 ? round($done_missions / max(1, $total_missions) * 100, 1) : 0.0;

    // Получено наград
    $prizesQ  = $mysqli->query("SELECT COUNT(*) as cnt FROM `aa_battle_pass_userprize` WHERE user_id = ".(int)$user_id." AND season_id = ".$season_id);
    $prizes   = $prizesQ ? $prizesQ->fetch_assoc() : ['cnt' => 0];
    $prizeCnt = (int)($prizes['cnt'] ?? 0);

    // Дни активности
    $daysQ  = $mysqli->query("SELECT COUNT(DISTINCT DATE(`created_at`)) as d FROM `aa_battle_pass_history` WHERE user_id = ".(int)$user_id." AND season_id = ".$season_id);
    $days   = $daysQ ? $daysQ->fetch_assoc() : ['d' => 0];
    $activeDays = (int)($days['d'] ?? 0);
    $avgExpPerDay = $activeDays > 0 ? floor($expTotal / $activeDays) : 0;

    // Топ-20 по сезону (уровень/EXP) — кешируем на короткий TTL, чтобы снизить нагрузку при онлайне
    $cacheTopLvlKey = 'bp_top_lvl_'.$season_id;
    $top = bp_cache_get($cacheTopLvlKey, 10, null);
    $top = is_array($top) ? $top : [];

    if (!$top) {
        $topQ = $mysqli->query("SELECT user, lvl, exp_me, point FROM `aa_battle_pass_user` WHERE `season_id` = ".$season_id." ORDER BY lvl DESC, exp_me DESC LIMIT 20");
        if ($topQ) {
            while ($row = $topQ->fetch_assoc()) {
                $top[] = $row;
            }
        }
        bp_cache_set($cacheTopLvlKey, $top, 10);
    }

    $user_ids = [];
    $my_place = null;
    $place = 1;
    foreach ($top as $row) {
        $user_ids[] = (int)$row['user'];
        if ((int)$row['user'] === (int)$user_id) $my_place = $place;
        $place++;
    }

    // Место пользователя (уровни/EXP)
    if ($my_place === null) {
        $res = $mysqli->query("SELECT COUNT(*) as cnt FROM `aa_battle_pass_user` WHERE `season_id`=".$season_id." AND (lvl > ".$lvl." OR (lvl = ".$lvl." AND exp_me > ".$expTotal."))");
        $row = $res ? $res->fetch_assoc() : ['cnt' => 0];
        $my_place = ((int)$row['cnt']) + 1;
    }

    // Сезонный рейтинг (по поинтам) — отдельный лидерборд
    $cacheTopPointsKey = 'bp_top_points_'.$season_id;
    $top_points = bp_cache_get($cacheTopPointsKey, 10, null);
    $top_points = is_array($top_points) ? $top_points : [];

    if (!$top_points) {
        $topPQ = $mysqli->query("SELECT user, point, lvl, exp_me FROM `aa_battle_pass_user` WHERE `season_id` = ".$season_id." ORDER BY point DESC, lvl DESC, exp_me DESC LIMIT 20");
        if ($topPQ) {
            while ($row = $topPQ->fetch_assoc()) {
                $top_points[] = $row;
            }
        }
        bp_cache_set($cacheTopPointsKey, $top_points, 10);
    }

    $my_place_points = null;
    $place = 1;
    foreach ($top_points as $row) {
        $user_ids[] = (int)$row['user'];
        if ((int)$row['user'] === (int)$user_id) $my_place_points = $place;
        $place++;
    }

    if ($my_place_points === null) {
        $res = $mysqli->query("SELECT COUNT(*) as cnt FROM `aa_battle_pass_user` WHERE `season_id`=".$season_id." AND (point > ".$points." OR (point = ".$points." AND (lvl > ".$lvl." OR (lvl = ".$lvl." AND exp_me > ".$expTotal."))))");
        $row = $res ? $res->fetch_assoc() : ['cnt' => 0];
        $my_place_points = ((int)$row['cnt']) + 1;
    }

    // Уникальные id пользователей (для пакета users)
    $user_ids = array_values(array_unique(array_map('intval', $user_ids)));

// Подгружаем данные пользователей топа
    $user_data = [];
    if ($user_ids) {
        $ids_str = implode(",", array_map('intval', $user_ids));
        $res = $mysqli->query("SELECT id, login, user_group, rang, online FROM `users` WHERE id IN ($ids_str)");
        if ($res) while ($row = $res->fetch_assoc()) $user_data[(int)$row['id']] = $row;
    }

    // Рендер
    ob_start();
?>
<style>
  .bpStatWrap{display:flex;flex-direction:column;gap:16px}
  .bpTitle{margin-bottom:4px;font-size:22px;color:#5958e8;font-weight:900;letter-spacing:.4px}

  .bpCards{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}
  .bpCard{background:#fff;border:1px solid #eceeff;border-radius:14px;padding:12px;box-shadow:0 1px 6px rgba(0,0,0,.04);display:flex;gap:10px;align-items:center}
  .bpCard .ico{width:40px;height:40px;border-radius:10px;background:#f6f7ff;border:1px solid #e3e6ff;display:flex;align-items:center;justify-content:center;color:#7d7cf8;font-size:18px}
  .bpCard .meta{display:flex;flex-direction:column;gap:2px}
  .bpCard .meta .label{font-size:12px;color:#9aa0d0}
  .bpCard .meta .val{font-size:18px;font-weight:900;color:#4a4a9b}
  .bpChip{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:4px 10px;font-weight:800;font-size:12px;border:1px solid transparent}
  .bpChip.free{color:#6c6cdf;background:#eef0ff;border-color:#e1e4ff}
  .bpChip.full{color:#856404;background:#fff3cd;border-color:#ffe49a}

  .bpProg{margin-top:6px;background:#f2f3ff;border:1px solid #e3e6ff;height:10px;border-radius:999px;overflow:hidden}
  .bpProg > div{height:100%;background:linear-gradient(90deg,#7d7cf8,#6dd5ed);width:0%}

  .bpRow{display:flex;gap:16px;align-items:flex-start}
  .bpCol{flex:1 1 50%}
  .bpBox{background:#fff;border:1px solid #eceeff;border-radius:14px;padding:12px;box-shadow:0 1px 6px rgba(0,0,0,.04)}
  .bpBoxTitle{font-size:16px;color:#5958e8;font-weight:800;margin-bottom:8px}

  .bpList{display:flex;flex-direction:column;gap:6px}
  .bpListItem{display:flex;align-items:center;gap:9px;padding:8px;border-radius:10px;background:#fff}
  .bpListItem.mytop{background:#e9eaff;font-weight:900}
  .bpPlace{width:22px;text-align:right;font-size:14px;color:#7d7cf8;font-weight:800}
  .bpAvatar{width:36px;height:36px;border-radius:50%;background:#dedcff;background-size:cover;background-position:center;box-shadow:0 1px 3px #b9b9f425;cursor:pointer;position:relative}
  .bpStatus{position:absolute;bottom:2px;right:2px;width:9px;height:9px;border-radius:50%;border:1.5px solid #fff}
  .bpStatus.onl{background:#40c572}
  .bpStatus.ofl{background:#ccc}
  .bpUser{flex:1 1 auto}
  .bpUserLogin{font-size:15px;font-weight:800;color:#5958e8}
  .bpUserRang{color:#9f9fd8;font-size:12px}
  .bpStatCols{display:flex;gap:14px;min-width:120px}
  .bpStatCell{text-align:center;background:#f6f7ff;border:1px solid #e3e6ff;border-radius:10px;padding:6px 8px}
  .bpStatCell .lbl{font-size:10px;color:#8e8eda}
  .bpStatCell .val{font-size:14px;color:#4a4a9b;font-weight:800}

  .bpKpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px}
  .bpKpi{display:flex;align-items:center;gap:8px;background:#fafbff;border:1px dashed #dfe3ff;border-radius:12px;padding:10px}
  .bpKpi .tag{font-size:12px;color:#8e8eda}
  .bpKpi .strong{font-weight:900;color:#4a4a9b}

  .bpHint{background:#fffaf0;border:1px solid #ffe8ae;color:#856404;border-radius:10px;padding:10px 12px;font-size:13px}
  .bpTopToggle{margin-top:8px;text-align:center}
  .bpBtn{display:inline-block;padding:8px 12px;border-radius:10px;border:1px solid #e3e6ff;background:#fff;color:#5958e8;font-weight:800;cursor:pointer}

  .bpTopTabs{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:10px 0 8px}
  .bpBtnSecondary{display:inline-block;padding:8px 12px;border-radius:10px;border:1px solid #e2e4fb;background:#f6f7ff;color:#4a4a9b;font-weight:900;cursor:pointer}
  .bpBtnSecondary.active{background:#5958e8;border-color:#5958e8;color:#fff}
</style>

<div class="bpStatWrap">
  <div class="bpTitle">Статистика сезона</div>

  <!-- Основные карточки -->
  <div class="bpCards">
    <div class="bpCard">
      <div class="ico"><i class="fa fa-level-up-alt"></i></div>
      <div class="meta">
        <div class="label">Текущий уровень</div>
        <div class="val"><?= (int)$lvl ?></div>
      </div>
    </div>
    <div class="bpCard">
      <div class="ico"><i class="fa fa-bolt"></i></div>
      <div class="meta">
        <div class="label">Получено опыта</div>
        <div class="val"><?= (int)$expTotal ?></div>
      </div>
    </div>
    <div class="bpCard">
      <div class="ico"><i class="fab fa-galactic-republic"></i></div>
      <div class="meta">
        <div class="label">Жетонов заработано</div>
        <div class="val"><?= (int)$points ?></div>
      </div>
    </div>
    <div class="bpCard">
      <div class="ico"><i class="fa fa-id-badge"></i></div>
      <div class="meta">
        <div class="label">Тип пропуска</div>
        <div class="val">
          <?php if ($passType === 2): ?>
            <span class="bpChip full"><?= h($passLabel) ?></span>
          <?php else: ?>
            <span class="bpChip free"><?= h($passLabel) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI и прогресс миссий -->
  <div class="bpRow">
    <div class="bpCol">
      <div class="bpBox">
        <div class="bpBoxTitle">Прогресс миссий</div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
          <div style="font-weight:900;color:#4a4a9b"><?= (int)$done_missions ?> / <?= (int)$total_missions ?></div>
          <div style="color:#8e8eda;font-size:12px">(<?= $missionPct ?>%)</div>
        </div>
        <div class="bpProg"><div style="width:<?= min(100,$missionPct) ?>%"></div></div>

        <?php if ($stat): ?>
          <div class="bpKpis" style="margin-top:10px;">
            <?php foreach ($stat as $type => $row):
              $tDone = (int)$row['done'];
              $tTotal= (int)$row['total'];
              $tPct  = $tTotal>0 ? round($tDone/$tTotal*100) : 0; ?>
              <div class="bpKpi">
                <div>
                  <div class="tag">Миссии: <?= h($type) ?></div>
                  <div class="strong"><?= $tDone ?> / <?= $tTotal ?> (<?= $tPct ?>%)</div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="bpCol">
      <div class="bpBox">
        <div class="bpBoxTitle">Активность</div>
        <div class="bpKpis">
          <div class="bpKpi">
            <div>
              <div class="tag">Рейтинг (уровень/EXP)</div>
              <div class="strong">#<?= (int)$my_place ?></div>
            </div>
          </div>
          <div class="bpKpi">
            <div>
              <div class="tag">Рейтинг (поинты)</div>
              <div class="strong">#<?= (int)$my_place_points ?></div>
            </div>
          </div>
          <div class="bpKpi">
            <div>
              <div class="tag">Дней активности</div>
              <div class="strong"><?= (int)$activeDays ?></div>
            </div>
          </div>
          <div class="bpKpi">
            <div>
              <div class="tag">Средний EXP/день</div>
              <div class="strong"><?= (int)$avgExpPerDay ?></div>
            </div>
          </div>
          <div class="bpKpi">
            <div>
              <div class="tag">Получено наград</div>
              <div class="strong"><?= (int)$prizeCnt ?></div>
            </div>
          </div>
        </div>

        <?php if ($passType !== 2): ?>
          <div class="bpHint" style="margin-top:10px;">
            Для дополнительной полки наград активируйте полный пропуск. Некоторые награды доступны только владельцам полного пропуска.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Топ сезона -->
  <div class="bpBox">
    <div class="bpBoxTitle">Рейтинг сезона</div>

    <div class="bpTopTabs">
      <button class="bpBtnSecondary active" type="button" id="bpTopTabLvl">Уровни / EXP</button>
      <button class="bpBtnSecondary" type="button" id="bpTopTabPoints">Поинты</button>
    </div>

    <!-- Лидерборд: уровень/EXP -->
    <div class="bpList" id="bpTopListLvl">
      <?php
      $place = 1;
      $now   = time();
      foreach ($top as $row):
          $uid = (int)$row['user'];
          $u   = $user_data[$uid] ?? ['login'=>'?', 'user_group'=>1, 'rang'=>'', 'online'=>0];
          $is_me = ($uid === (int)$user_id);
          $is_online = ($u['online'] >= $now - 300) ? 'onl' : 'ofl';
          $hiddenAttr = ($place > 10) ? ' style="display:none" data-more="1"' : '';
      ?>
      <div class="bpListItem<?= $is_me ? ' mytop':'' ?>"<?= $hiddenAttr ?>>
        <div class="bpPlace"><?= $place ?></div>
        <div class="bpAvatar" onclick="showUserTooltip(<?= $uid ?>)" style="background-image:url(/img/avatars/mini/<?= $uid ?>.png);">
          <div class="bpStatus <?= $is_online ?>"></div>
        </div>
        <div class="bpUser">
          <div class="bpUserLogin">
            <span class="u-<?= (int)$u['user_group'] ?> label" style="cursor:pointer" onclick="user_to_chat_add(<?= $uid ?>)"><?= h($u['login']) ?></span>
          </div>
          <?php if (!empty($u['rang'])): ?>
            <div class="bpUserRang"><?= h($u['rang']) ?></div>
          <?php endif; ?>
        </div>
        <div class="bpStatCols">
          <div class="bpStatCell">
            <div class="lbl">LVL</div>
            <div class="val"><?= (int)$row['lvl'] ?></div>
          </div>
          <div class="bpStatCell">
            <div class="lbl">EXP</div>
            <div class="val"><?= (int)$row['exp_me'] ?></div>
          </div>
        </div>
      </div>
      <?php
          $place++;
      endforeach;
      ?>
    </div>

    <?php if (count($top) > 10): ?>
      <div class="bpTopToggle">
        <button class="bpBtn" id="bpTopToggleBtnLvl" type="button" aria-expanded="false">Показать топ 11–20</button>
      </div>
    <?php endif; ?>

    <!-- Лидерборд: поинты -->
    <div class="bpList" id="bpTopListPoints" style="display:none">
      <?php
      $place = 1;
      $now   = time();
      foreach ($top_points as $row):
          $uid = (int)$row['user'];
          $u   = $user_data[$uid] ?? ['login'=>'?', 'user_group'=>1, 'rang'=>'', 'online'=>0];
          $is_me = ($uid === (int)$user_id);
          $is_online = ($u['online'] >= $now - 300) ? 'onl' : 'ofl';
          $hiddenAttr = ($place > 10) ? ' style="display:none" data-more="1"' : '';
      ?>
      <div class="bpListItem<?= $is_me ? ' mytop':'' ?>"<?= $hiddenAttr ?>>
        <div class="bpPlace"><?= $place ?></div>
        <div class="bpAvatar" onclick="showUserTooltip(<?= $uid ?>)" style="background-image:url(/img/avatars/mini/<?= $uid ?>.png);">
          <div class="bpStatus <?= $is_online ?>"></div>
        </div>
        <div class="bpUser">
          <div class="bpUserLogin">
            <span class="u-<?= (int)$u['user_group'] ?> label" style="cursor:pointer" onclick="user_to_chat_add(<?= $uid ?>)"><?= h($u['login']) ?></span>
          </div>
          <?php if (!empty($u['rang'])): ?>
            <div class="bpUserRang"><?= h($u['rang']) ?></div>
          <?php endif; ?>
        </div>
        <div class="bpStatCols">
          <div class="bpStatCell">
            <div class="lbl">PTS</div>
            <div class="val"><?= (int)$row['point'] ?></div>
          </div>
          <div class="bpStatCell">
            <div class="lbl">LVL</div>
            <div class="val"><?= (int)$row['lvl'] ?></div>
          </div>
        </div>
      </div>
      <?php
          $place++;
      endforeach;
      ?>
    </div>

    <?php if (count($top_points) > 10): ?>
      <div class="bpTopToggle" id="bpTopTogglePointsWrap" style="display:none">
        <button class="bpBtn" id="bpTopToggleBtnPoints" type="button" aria-expanded="false">Показать топ 11–20</button>
      </div>
    <?php endif; ?>
  </div>

<script>
(function(){
  var tabLvl    = document.getElementById('bpTopTabLvl');
  var tabPoints = document.getElementById('bpTopTabPoints');
  var listLvl   = document.getElementById('bpTopListLvl');
  var listPts   = document.getElementById('bpTopListPoints');
  var wrapPts   = document.getElementById('bpTopTogglePointsWrap');

  function setTab(mode){
    if(!tabLvl || !tabPoints || !listLvl || !listPts) return;
    if(mode === 'points'){
      tabLvl.classList.remove('active');
      tabPoints.classList.add('active');
      listLvl.style.display = 'none';
      listPts.style.display = 'block';
      if (wrapPts) wrapPts.style.display = 'block';
      var wrapLvl = document.getElementById('bpTopToggleBtnLvl');
      if (wrapLvl && wrapLvl.parentNode) wrapLvl.parentNode.style.display = 'none';
    } else {
      tabPoints.classList.remove('active');
      tabLvl.classList.add('active');
      listPts.style.display = 'none';
      listLvl.style.display = 'block';
      if (wrapPts) wrapPts.style.display = 'none';
      var wrapLvl2 = document.getElementById('bpTopToggleBtnLvl');
      if (wrapLvl2 && wrapLvl2.parentNode) wrapLvl2.parentNode.style.display = 'block';
    }
  }

  tabLvl && tabLvl.addEventListener('click', function(){ setTab('lvl'); });
  tabPoints && tabPoints.addEventListener('click', function(){ setTab('points'); });

  function setupToggle(listId, btnId){
    var btn  = document.getElementById(btnId);
    if(!btn) return;
    var list = document.getElementById(listId);
    if(!list) return;
    var more = list.querySelectorAll('[data-more="1"]');

    function anyHidden(){
      for (var i=0;i<more.length;i++){
        if (more[i].style.display === 'none') return true;
      }
      return false;
    }
    function apply(show){
      for (var i=0;i<more.length;i++){
        more[i].style.display = show ? '' : 'none';
      }
      btn.textContent = show ? 'Скрыть топ 11–20' : 'Показать топ 11–20';
      btn.setAttribute('aria-expanded', show ? 'true' : 'false');
    }

    // синхронизация по текущему состоянию
    apply(false);

    btn.addEventListener('click', function(){
      var show = anyHidden(); // если скрыто — показать, иначе скрыть
      apply(show);
    });
  }

  setupToggle('bpTopListLvl', 'bpTopToggleBtnLvl');
  setupToggle('bpTopListPoints', 'bpTopToggleBtnPoints');
})();
</script>


  </div>
</div>
<?php
    echo ob_get_clean();
break;
                                // История (современный таймлайн, группировка по дням, фильтры по типам; строка поиска удалена)
                case 'history':
                    // Загружаем последние записи
                    $history = getHistory($mysqli, $user_id, $season_id, 40);

                    // Карта событий: иконка (SVG), заголовок, цвет бейджа
                    $event_map = [
                        'activatedfree'    => [
                            'title' => 'Старт участия',
                            'color' => '#36b37e',
                            'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10" fill="#36b37e"/><polygon points="8,6 15,10 8,14" fill="#fff"/></svg>'
                        ],
                        'activatedfull'    => [
                            'title' => 'Куплена полная версия',
                            'color' => '#ffd700',
                            'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10" fill="#ffd700"/><polygon points="10,4 12,9 18,9 13,12 15,17 10,14 5,17 7,12 2,9 8,9" fill="#fff"/></svg>'
                        ],
                        'claim_prize'      => [
                            'title' => 'Получена награда',
                            'color' => '#7d7cf8',
                            'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20"><rect width="20" height="20" rx="6" fill="#7d7cf8"/><rect x="4" y="7" width="12" height="7" rx="2" fill="#fff"/><rect x="7" y="3" width="6" height="7" rx="3" fill="#fff" opacity="0.7"/></svg>'
                        ],
                        // Поддерживаем оба варианта названия для магазина
                        'emblem_shop'      => [
                            'title' => 'Покупка в магазине',
                            'color' => '#4fc3f7',
                            'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20"><rect width="20" height="20" rx="6" fill="#4fc3f7"/><circle cx="10" cy="10" r="5" fill="#fff"/></svg>'
                        ],
                        'emblemshop_buy'   => [
                            'title' => 'Покупка в магазине',
                            'color' => '#4fc3f7',
                            'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20"><rect width="20" height="20" rx="6" fill="#4fc3f7"/><circle cx="10" cy="10" r="5" fill="#fff"/></svg>'
                        ],
                        'mission_complete' => [
                            'title' => 'Завершено задание',
                            'color' => '#6d4aff',
                            'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10" fill="#6d4aff"/><polyline points="6,11 9,14 15,7" stroke="#fff" stroke-width="2" fill="none"/></svg>'
                        ],
                        'switch'           => [
                            'title' => 'Смена задания',
                            'color' => '#e67e22',
                            'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10" fill="#e67e22"/><path d="M5 10h10M10 5l5 5-5 5" stroke="#fff" stroke-width="2" fill="none"/></svg>'
                        ],
                        'switchactiv'      => [
                            'title' => 'Выдано новое задание',
                            'color' => '#36b37e',
                            'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10" fill="#36b37e"/><path d="M6 14l8-8M6 6h8v8" stroke="#fff" stroke-width="2" fill="none"/></svg>'
                        ],
                    ];
                    // Фолбэк для неизвестных событий
                    $default_event = [
                        'title' => 'Событие',
                        'color' => '#c5c8ff',
                        'icon'  => '<svg width="18" height="18" viewBox="0 0 20 20"><rect width="20" height="20" rx="6" fill="#c5c8ff"/><path d="M4 10h12" stroke="#fff" stroke-width="2"/></svg>'
                    ];

                    // Группировка по дате
                    $byDate = [];
                    $counts = []; // счётчики по типам событий
                    if ($history) {
                        foreach ($history as $row) {
                            $ts = strtotime($row['created_at']);
                            $dateKey = date('Y-m-d', $ts);
                            if (!isset($byDate[$dateKey])) $byDate[$dateKey] = [];
                            $byDate[$dateKey][] = $row;

                            $ev = (string)$row['event'];
                            $counts[$ev] = ($counts[$ev] ?? 0) + 1;
                        }
                    }

                    // Утилита "сегодня/вчера/дата"
                    $humanDate = function(string $dateYmd): string {
                        $ts = strtotime($dateYmd);
                        $today = strtotime(date('Y-m-d'));
                        $yest  = $today - 86400;
                        if ($ts === $today) return 'Сегодня';
                        if ($ts === $yest)  return 'Вчера';
                        return date('d.m.Y', $ts);
                    };

                    ob_start();
                    ?>
                    <style>
                      .bpHistWrap{display:flex;flex-direction:column;gap:12px}
                      .bpTitle{margin-bottom:4px;font-size:22px;color:#5958e8;font-weight:900;letter-spacing:.4px}

                      .bpHistHeader{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
                      .bpTag{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;border:1px solid #e3e6ff;background:#fff;font-size:12px;color:#5958e8;font-weight:800}
                      .bpTag .dot{width:8px;height:8px;border-radius:50%}

                      .bpHistFilter{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:4px}
                      .bpToggle{display:flex;align-items:center;gap:6px;border:1px solid #e3e6ff;border-radius:999px;padding:5px 8px;background:#fff;font-size:12px;color:#5958e8;font-weight:800;cursor:pointer}
                      .bpToggle input{accent-color:#7d7cf8}

                      .bpTimeline{display:flex;flex-direction:column;gap:10px;margin-top:6px}
                      .bpDay{background:#fff;border:1px solid #eceeff;border-radius:12px;overflow:hidden}
                      .bpDayHeader{display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:#f7f8ff;border-bottom:1px solid #eceeff}
                      .bpDayTitle{font-weight:900;color:#4a4a9b}
                      .bpDayDate{font-size:12px;color:#8e8eda}
                      .bpEvents{display:flex;flex-direction:column}
                      .bpEvent{display:grid;grid-template-columns:32px 1fr auto;gap:10px;align-items:center;padding:8px 12px;border-top:1px solid #f2f3ff}
                      .bpEvent:first-child{border-top:none}
                      .bpEvIcon{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;border:1px solid #e3e6ff;background:#fff}
                      .bpEvBody{display:flex;flex-direction:column;gap:2px;min-width:0}
                      .bpEvTitle{color:#5958e8;font-weight:800;font-size:14px}
                      .bpEvInfo{color:#7a7ab8;font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
                      .bpEvTime{font-family:'JetBrains Mono',monospace;color:#7d7cf8;font-size:12px;min-width:56px;text-align:right}
                      .bpEmpty{padding:12px;border:1px dashed #c7c9ff;border-radius:12px;background:#f7f8ff;color:#8e8eda}

                      .bpMore{text-align:center;margin-top:8px}
                      .bpBtn{display:inline-block;padding:8px 12px;border-radius:10px;border:1px solid #e3e6ff;background:#fff;color:#5958e8;font-weight:800;cursor:pointer}
                    </style>

                    <div class="bpHistWrap">
                      <div class="bpTitle">История действий</div>

                      <?php if (!$history): ?>
                        <div class="bpEmpty">Нет записей.</div>
                      <?php else: ?>
                        <!-- Сводка по типам -->
                        <div class="bpHistHeader">
                          <?php
                          // Сортируем по количеству по убыванию
                          arsort($counts);
                          foreach ($counts as $ev => $cnt):
                              $meta = $event_map[$ev] ?? $default_event;
                              $color = $meta['color'];
                          ?>
                            <div class="bpTag" data-filter-tag="<?= h($ev) ?>">
                              <span class="dot" style="background:<?= h($color) ?>"></span>
                              <span><?= h($meta['title']) ?></span>
                              <span>· <?= (int)$cnt ?></span>
                            </div>
                          <?php endforeach; ?>
                        </div>

                        <!-- Фильтры по типам (без строки поиска) -->
                        <div class="bpHistFilter" id="bpHistFilter">
                          <?php
                          $evTypes = array_keys($counts);
                          foreach ($evTypes as $ev):
                              $meta = $event_map[$ev] ?? $default_event;
                          ?>
                            <label class="bpToggle">
                              <input type="checkbox" class="bpHistType" value="<?= h($ev) ?>" checked>
                              <span><?= h($meta['title']) ?></span>
                            </label>
                          <?php endforeach; ?>
                        </div>

                        <!-- Таймлайн -->
                        <div class="bpTimeline" id="bpTimeline">
                          <?php
                          // Показываем максимум 40 записей, но скрываем сверх первых 25 (кнопкой "Показать ещё")
                          $shown = 0;
                          foreach ($byDate as $dateYmd => $items):
                              $dateTitle = $humanDate($dateYmd);
                              $dateHumanFull = date('d.m.Y', strtotime($dateYmd));
                          ?>
                            <div class="bpDay">
                              <div class="bpDayHeader">
                                <div class="bpDayTitle"><?= h($dateTitle) ?></div>
                                <div class="bpDayDate"><?= h($dateHumanFull) ?></div>
                              </div>
                              <div class="bpEvents">
                                <?php foreach ($items as $row):
                                    $time  = date('H:i', strtotime($row['created_at']));
                                    $event = (string)$row['event'];
                                    $info  = trim((string)$row['info']);
                                    $meta  = $event_map[$event] ?? $default_event;
                                    $icon  = $meta['icon'];
                                    $title = $meta['title'];
                                    $color = $meta['color'];
                                    $shown++;
                                    $hiddenAttr = $shown > 25 ? ' data-more="1" style="display:none"' : '';
                                ?>
                                  <div class="bpEvent"<?= $hiddenAttr ?> data-ev="<?= h($event) ?>">
                                    <div class="bpEvIcon" style="border-color: <?= h($color) ?>33">
                                      <?= $icon ?>
                                    </div>
                                    <div class="bpEvBody">
                                      <div class="bpEvTitle"><?= h($title) ?></div>
                                      <?php if ($info !== ''): ?>
                                        <div class="bpEvInfo">— <?= h($info) ?></div>
                                      <?php endif; ?>
                                    </div>
                                    <div class="bpEvTime"><?= h($time) ?></div>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>

                        <?php if ($shown > 25): ?>
                          <div class="bpMore">
                            <button class="bpBtn" id="bpHistMoreBtn" onclick="(function(btn){
                              var more = document.querySelectorAll('#bpTimeline [data-more=\"1\"]');
                              var hidden = 0;
                              more.forEach(function(el){ if (el.style.display === 'none') hidden++; });
                              var show = hidden > 0;
                              more.forEach(function(el){ el.style.display = show ? '' : 'none'; });
                              btn.textContent = show ? 'Скрыть' : 'Показать ещё';
                            })(this)">Показать ещё</button>
                          </div>
                        <?php endif; ?>

                        <script>
                          (function(){
                            var typeBoxes = [].slice.call(document.querySelectorAll('.bpHistType'));
                            var timeline = document.getElementById('bpTimeline');

                            function applyTypeFilter(){
                              var allowed = {};
                              typeBoxes.forEach(function(cb){ if (cb.checked) allowed[cb.value] = true; });

                              [].forEach.call(timeline.querySelectorAll('.bpEvent'), function(ev){
                                var evType = ev.getAttribute('data-ev') || '';
                                var show = !!allowed[evType];
                                ev.style.display = show ? '' : 'none';
                              });
                            }

                            // Клик по сводным тегам — переключает соответствующий чекбокс
                            document.querySelectorAll('[data-filter-tag]').forEach(function(tag){
                              tag.addEventListener('click', function(){
                                var ev = tag.getAttribute('data-filter-tag');
                                var cb = document.querySelector('.bpHistType[value="'+ev+'"]');
                                if (cb) { cb.checked = !cb.checked; applyTypeFilter(); }
                              });
                            });

                            typeBoxes.forEach(function(cb){ cb.addEventListener('change', applyTypeFilter); });
                          })();
                        </script>
                      <?php endif; ?>
                    </div>
                    <?php
                    echo ob_get_clean();
                break;

                                // Инфо (обновлено: современный UI, SVG-значок сезона в бейдже и хедере)
case 'info':
default:
    // Переходим на Font Awesome: никакого SVG/require не используем

    // Данные пользователя по текущему сезону
    $bd        = $mysqli->query("SELECT * FROM `aa_battle_pass_user` WHERE `user` = ".(int)$user_id." AND `season_id` = ".$season_id)->fetch_assoc();
    $user_bp   = function_exists('getUserBP') ? (getUserBP($mysqli, $user_id, $season_id) ?: []) : [];
    $user_lvl  = (int)($user_bp['lvl'] ?? 0);
    $user_exp  = (int)($user_bp['exp_me'] ?? 0);
    $user_point= (int)($user_bp['point'] ?? 0);
    $user_type = (int)($user_bp['type'] ?? (int)($bd['type'] ?? 1)); // 1=базовый, 2=полный

    // Сезон
    $season_name = h($season['name'] ?? 'Сезон');
    $season_desc = trim((string)($season['description'] ?? ''));
    $start_ts    = !empty($season['start']) ? strtotime($season['start']) : 0;
    $end_ts      = !empty($season['end'])   ? strtotime($season['end'])   : 0;
    $now_ts      = time();
    $period_str  = ($start_ts && $end_ts) ? date('d.m.Y', $start_ts).' — '.date('d.m.Y', $end_ts) : '—';
    $days_left   = ($end_ts > $now_ts) ? max(0, (int)ceil(($end_ts - $now_ts) / 86400)) : 0;
    $is_active   = ($start_ts && $end_ts && $now_ts >= $start_ts && $now_ts <= $end_ts);

    // Акцентный цвет интерфейса (HEX). Можно переопределить через GET: &bp_accent=%237d7cf8
    $accent = !empty($_GET['bp_accent'])
        ? (string)$_GET['bp_accent']
        : ($season['accent'] ?? ($season['color1'] ?? '#7d7cf8'));

    // Хелпер: HEX → rgba()
    $toRgba = function (string $hex, float $a) {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
        $r = hexdec(substr($h,0,2));
        $g = hexdec(substr($h,2,2));
        $b = hexdec(substr($h,4,2));
        return "rgba($r,$g,$b,$a)";
    };
    $accent_bg = $toRgba($accent, 0.08);
    $accent_bd = $toRgba($accent, 0.22);

    // Подбор иконки Font Awesome по названию сезона
    $detectFaIcon = function (string $name): string {
        $s = mb_strtolower($name, 'UTF-8');
        $map = [
            'fa-fire'        => ['огонь','плам','жар','инферн','lava','магма','inferno','fire','жаркое'],
            'fa-water'       => ['вода','океан','море','волна','буря','шторм','прилив','wave','ocean','sea'],
            'fa-snowflake'   => ['лед','снег','иней','frost','ice','зима','ледник','стуж'],
            'fa-leaf'        => ['лист','лес','зелень','grove','nature','spring','весн'],
            'fa-seedling'    => ['цвет','сакура','вишн','blossom','petal','лепест'],
            'fa-star'        => ['звез','звёзд','star','созвезд','астер'],
            'fa-sun'         => ['солн','day','sun','корона','лучезар'],
            'fa-moon'        => ['луна','ноч','eclipse','тень','полумрак'],
            'fa-bolt'        => ['гром','молни','thunder','lightning','разряд'],
            'fa-shield-alt'  => ['щит','guard','защит','страж'],
            'fa-crown'       => ['корона','imper','импер','royal','crown'],
            'fa-meteor'      => ['комет','метеор','meteor','падающ'],
            'fa-atom'        => ['галак','galaxy','космос','небул','nebula','вселен'],
            'fa-rainbow'     => ['аврора','сиян','поляр','aurora'],
            'fa-scroll'      => ['руна','rune','glyph','мистик','тайн','свиток'],
            'fa-feather'     => ['феникс','phoenix','перерожд','пепел','перо'],
            'fa-gem'         => ['крист','crystal','diamond','кристалл'],
        ];
        foreach ($map as $icon => $keys) {
            foreach ($keys as $kw) {
                if ($kw !== '' && mb_strpos($s, $kw) !== false) {
                    return $icon;
                }
            }
        }
        return 'fa-gem'; // дефолт
    };
    $fa_icon_class = $detectFaIcon($season['name'] ?? '');

    // Константы/стоимость
    $bp_item_id    = (int)BP_FULL_PASS_COST_ITEM;
    $bp_item_count = (int)BP_FULL_PASS_COST_COUNT;
    $bp_item_name  = 'Драгоценный камень';
    $bp_item_img   = '/img/world/items/little/'.(int)BP_FULL_PASS_COST_ITEM.'.png';

    // Бонусы полного пропуска
    $bonuses = [
        "Доступ к премиум-наградам на каждом уровне",
        "Получение дополнительных жетонов для магазина наград",
        "Эксклюзивные задания и призы",
        "Премиум-иконка в профиле",
    ];

    // Условия участия
    $minLevelNote = defined('BP_MIN_LEVEL') ? 'Минимальный уровень участия: '.(int)BP_MIN_LEVEL : '';

    ob_start();
    ?>
    <style>
      .bpInfoWrap{margin:0 auto;max-width:880px;display:flex;flex-direction:column;gap:14px}
      .bpHero{background:linear-gradient(180deg,#fff7ee,#fff);border:1px solid #ffe3c2;border-radius:16px;box-shadow:0 2px 10px #ffeac655;padding:18px}
      .bpHeroTop, .bpTopHero{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center}
      .bpTitle{font-size:22px;font-weight:900;color:#6d4aff;letter-spacing:.4px;display:flex;gap:8px;align-items:center}
      .bpHeroIcon{width:56px;height:56px;display:flex;align-items:center;justify-content:center;border-radius:14px;background:#ffffffcc;border:1px solid #eceeff;color:<?= h($accent) ?>}
      .bpHeroIcon i{font-size:28px;line-height:1}
      .bpBadges{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
      .bpBadge{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:800;border:1px solid transparent;background:#fff;color:#5958e8}
      .bpBadge.season{border-color:<?= h($accent_bd) ?>;background:<?= h($accent_bg) ?>;color:<?= h($accent) ?>}
      .bpBadge.period{border-color:#ffe8ae;background:#fffaf0;color:#856404}
      .bpBadge.days{border-color:#e3e6ff}
      .bpBadge .ico{display:inline-flex;align-items:center;justify-content:center;line-height:0}
      .bpBadge .ico i{font-size:16px;line-height:1}
      .bpDesc{font-size:14px;color:#555;margin-top:8px;white-space:pre-line}
      .bpUserChips{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
      .bpChip{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:10px;border:1px solid #e3e6ff;background:#fff;font-size:12px;color:#5958e8;font-weight:800}
      .bpChip .v{font-size:14px;color:#4a4a9b}

      
      @media(max-width: 860px){ .bpGrid, .bpTopGrid{grid-template-columns:1fr} .bpHeroTop, .bpTopHero{grid-template-columns:1fr} .bpHeroIcon{display:none} }

      .bpCard{background:#fff;border:1px solid #eceeff;border-radius:14px;padding:14px;box-shadow:0 1px 6px rgba(0,0,0,.04)}
      .bpCardTitle{font-size:16px;color:#5958e8;font-weight:800;margin-bottom:8px}
      .bpList{margin:0;padding:0 0 0 18px;color:#463ca3;font-size:14px;word-break:break-word}
      .bpList li{margin:5px 0}

      .bpCompare{display:grid;grid-template-columns:1fr 1fr;gap:10px}
      .bpCompareCol{border:1px solid #e3e6ff;border-radius:12px;padding:12px;background:#f9faff}
      .bpCompareCol.premium{background:linear-gradient(180deg,#fffaf0,#fff);border-color:#ffe8ae}
      .bpCompareHead{font-weight:900;margin-bottom:6px}
      .bpCompareRow{display:flex;gap:8px;align-items:center;margin:6px 0}
      .ok{color:#1bbb70}
      .no{color:#c1c1d9}

      .bpCTA{text-align:center;display:flex;flex-direction:column;gap:8px;align-items:center;justify-content:center}
      .bpBtn{font-size:16px;padding:10px 18px;cursor:pointer;background:linear-gradient(90deg,#7d7cf8 0,#1bbb70 100%);color:#fff;font-weight:800;border:none;border-radius:10px}
      .bpBtn:disabled{opacity:.6;cursor:not-allowed}
      .bpBtnSecondary{font-size:14px;padding:8px 12px;border-radius:10px;background:#fff;border:1px solid #e3e6ff;color:#5958e8;font-weight:800;cursor:pointer}
      .bpPrice{color:#888;font-size:13px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
      .bpPrice img{vertical-align:middle;width:20px;height:20px}
      .bpNote{margin-top:6px;color:#9a9ad8;font-size:12px}

      .bpSections{display:grid;grid-template-columns:1fr;gap:10px}
      .bpSection{background:#fff;border:1px solid #eceeff;border-radius:14px;padding:12px}
      .bpSectionTitle{font-size:16px;color:#5958e8;font-weight:800;margin-bottom:6px}
      .bpSection ul{margin:0;padding:0 0 0 18px;color:#463ca3;font-size:14px}
      .bpOwned{display:inline-flex;gap:8px;align-items:center;color:#1bbb70;font-weight:900;font-size:16px}

      /* ——— Мобильная адаптация: для ПК ничего не меняем, только узкие экраны ——— */
      @media (max-width: 600px){
        .bpInfoWrap{padding:0 4px}
        .bpHero{padding:14px;border-radius:12px}
        .bpTitle{font-size:clamp(16px,5vw,22px)}
        .bpBadges{gap:6px;row-gap:6px}
        .bpBadge{padding:5px 8px;font-size:11px;max-width:100%}
        .bpBadge .ico i{font-size:14px}
        .bpDesc{font-size:13px}
        .bpUserChips{gap:6px}
        .bpChip{padding:5px 8px;font-size:11px}
        .bpChip .v{font-size:13px}
        .bpCompare{grid-template-columns:1fr}              /* сравнение — в одну колонку */
        .bpCard{padding:12px}
        .bpCardTitle{font-size:15px;margin-bottom:6px}
        .bpList{font-size:13px}
        .bpCTA{gap:6px}
        .bpBtn{width:100%;font-size:15px;padding:10px 14px} /* кнопки на всю ширину */
        .bpBtnSecondary{width:100%}
        .bpPrice{font-size:12px}
        .bpSections{gap:8px}
        .bpSection{padding:10px}
        .bpSectionTitle{font-size:15px}
      }
      /* Если совсем узко — бейджи в горизонтальный скролл без переносов, чтобы не расползались карточки */
      @media (max-width: 420px){
        .bpBadges{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;padding-bottom:4px}
        .bpBadges::-webkit-scrollbar{height:5px}
        .bpBadges::-webkit-scrollbar-thumb{background:#e3e6ff;border-radius:10px}
        .bpBadge{white-space:nowrap}
      }
    </style>

    <div class="bpInfoWrap">
      <div class="bpHero">
        <div class="bpHeroTop">
          <div>
            <div class="bpTitle">О сезоне</div>
            <div class="bpBadges">
              <!-- Бейдж сезона: Font Awesome вместо SVG -->
              <div class="bpBadge season">
                <span class="ico"><i class="fa <?= h($fa_icon_class) ?>" aria-hidden="true"></i></span>
                <?= $season_name ?>
              </div>
              <div class="bpBadge period"><i class="fa fa-calendar-alt"></i> <?= h($period_str) ?></div>
              <?php if ($is_active): ?>
                <div class="bpBadge days"><i class="fa fa-hourglass-half"></i> Осталось: <?= (int)$days_left ?> дн.</div>
              <?php else: ?>
                <div class="bpBadge days"><i class="fa fa-hourglass-end"></i> Не активен</div>
              <?php endif; ?>
            </div>
          </div>
          <!-- Крупная эмблема сезона: Font Awesome -->
          <div class="bpHeroIcon" style="background: <?= h($accent_bg) ?>;border-color: <?= h($accent_bd) ?>;">
            <i class="fa <?= h($fa_icon_class) ?>" aria-hidden="true"></i>
          </div>
        </div>

        <?php if ($season_desc !== ''): ?>
          <div class="bpDesc"><?= nl2br(h($season_desc)) ?></div>
        <?php endif; ?>

        <div class="bpUserChips">
          <div class="bpChip"><span>Ваш уровень</span> <span class="v"><?= (int)$user_lvl ?></span></div>
          <div class="bpChip"><span>Опыт</span> <span class="v"><?= (int)$user_exp ?></span></div>
          <div class="bpChip"><span>Жетонов</span> <span class="v"><?= (int)$user_point ?></span></div>
          <div class="bpChip">
            <span>Тип</span>
            <span class="v"><?= $user_type === 2 ? 'Полный' : 'Базовый' ?></span>
          </div>
        </div>
      </div>

      <div class="bpGrid">
        <div class="bpCard">
          <div class="bpCardTitle">Преимущества полной версии</div>
          <ul class="bpList">
            <?php foreach ($bonuses as $b): ?>
              <li><?= h($b) ?></li>
            <?php endforeach; ?>
          </ul>

          <div class="bpCardTitle" style="margin-top:12px;">Сравнение</div>
          <div class="bpCompare">
            <div class="bpCompareCol">
              <div class="bpCompareHead">Базовый</div>
              <div class="bpCompareRow"><span class="ok"><i class="fa fa-check-circle"></i></span> Награды обычной дорожки</div>
              <div class="bpCompareRow"><span class="no"><i class="fa fa-times-circle"></i></span> Премиум-награды</div>
              <div class="bpCompareRow"><span class="no"><i class="fa fa-times-circle"></i></span> Эксклюзивные задания</div>
              <div class="bpCompareRow"><span class="no"><i class="fa fa-times-circle"></i></span> Премиум-иконка</div>
            </div>
            <div class="bpCompareCol premium">
              <div class="bpCompareHead">Полный</div>
              <div class="bpCompareRow"><span class="ok"><i class="fa fa-check-circle"></i></span> Награды обеих дорожек</div>
              <div class="bpCompareRow"><span class="ok"><i class="fa fa-check-circle"></i></span> Доп. жетоны и призы</div>
              <div class="bpCompareRow"><span class="ok"><i class="fa fa-check-circle"></i></span> Эксклюзивные задания</div>
              <div class="bpCompareRow"><span class="ok"><i class="fa fa-check-circle"></i></span> Премиум-иконка</div>
            </div>
          </div>
        </div>

        <div class="bpCard">
          <div class="bpCardTitle">Покупка полного пропуска</div>
          <div class="bpCTA">
            <?php if ($bd && (int)$bd['type'] === 2): ?>
              <div class="bpOwned"><i class="fas fa-check-circle"></i> У вас уже полная версия пропуска</div>
            <?php else: ?>
              <?php if (!$bd): ?>
                <button class="bpBtnSecondary" onclick="confirmBPFree()">Активировать бесплатный пропуск</button>
              <?php endif; ?>
              <button class="bpBtn" id="bpFullPassBtn" onclick="confirmBPFullPass()" <?= !$is_active ? 'disabled' : '' ?>>Купить полный пропуск</button>
              <div class="bpPrice">
                <span>Стоимость:</span>
                <img src="<?= h($bp_item_img) ?>" alt="<?= h($bp_item_name) ?>">
                <b style="color:#7d7cf8;"><?= h($bp_item_name) ?> × <?= (int)$bp_item_count ?></b>
              </div>
              <?php if ($minLevelNote): ?>
                <div class="bpNote"><i class="fa fa-info-circle"></i> <?= h($minLevelNote) ?></div>
              <?php endif; ?>
              <?php if (!$is_active): ?>
                <div class="bpNote"><i class="fa fa-exclamation-triangle"></i> Сезон вне активности — покупка временно недоступна</div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        
        <div class="bpCard">
          <div class="bpCardTitle">Подарок полной версии</div>
          <div class="bpCTA">
            <div class="bpGiftRow">
              <input id="bpGiftTo" class="bpGiftInput" type="text" placeholder="Логин или ID игрока">
              <button class="bpBtn" onclick="confirmBPGiftFullPass()" <?= !$is_active ? 'disabled' : '' ?>>Подарить премиум</button>
            </div>
            <div class="bpGiftHint">
              Стоимость подарка: <b><?= (int)$bp_item_count ?> <?= h($bp_item_name) ?></b>
            </div>
            <div class="bpGiftMsg" id="bpGiftMsg" style="display:none"></div>
            <?php if (!$is_active): ?>
              <div class="bpNote"><i class="fa fa-exclamation-triangle"></i> Сезон вне активности — подарки временно недоступны</div>
            <?php endif; ?>
          </div>
        </div>

        <style>
          .bpGiftRow{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:8px}
          .bpGiftInput{flex:1 1 220px;min-width:180px;border:1px solid #e2e4fb;border-radius:12px;padding:10px 12px;font-weight:800;color:#4a4a9b;outline:none;background:#fff}
          .bpGiftInput:focus{border-color:#cfd3ff;box-shadow:0 0 0 4px rgba(125,124,248,.08)}
          .bpGiftHint{font-size:12px;color:#8e8eda;margin-bottom:6px}
          .bpGiftMsg{margin-top:8px;background:#f6f7ff;border:1px solid #e3e6ff;border-radius:12px;padding:10px 12px;font-weight:800;color:#4a4a9b}
          .bpGiftMsg.ok{background:#e9f7ef;border-color:#bde5c8;color:#1a6b3b}
          .bpGiftMsg.bad{background:#fff0f0;border-color:#ffd1d1;color:#a61b1b}
        </style>

</div>
      </div>

      <div class="bpSections">
        <div class="bpSection">
          <div class="bpSectionTitle">Состав боевого пропуска</div>
          <ul>
            <li>Лента наград <b>(Track)</b> — награды за каждый уровень</li>
            <li>Задания <b>(Mission)</b> — ежедневные, недельные, специальные</li>
            <li>Магазин жетонов <b>(Shop)</b> — обмен жетонов на ценные призы</li>
            <li>Ваша статистика <b>(Stat)</b> — подробный прогресс по сезону</li>
            <li>История событий <b>(History)</b> — все ключевые действия</li>
          </ul>
        </div>
      </div>
    </div>

    <script>
      function confirmBPFullPass() {
        if (confirm("Вы уверены, что хотите купить полный боевой пропуск за <?= (int)$bp_item_count ?> <?= addslashes($bp_item_name) ?>?")) {
          if (typeof activatedfull === 'function') {
            activatedfull();
          } else {
            console.error('activatedfull() не определена');
          }
        }
      }
      function confirmBPFree() {
        if (confirm("Активировать бесплатный боевой пропуск?")) {
          if (typeof activatedfree === 'function') {
            activatedfree();
          } else {
            console.error('activatedfree() не определена');
          }
        }
      }
    
      function confirmBPGiftFullPass() {
        try {
          var inp = document.getElementById('bpGiftTo');
          var to  = inp ? (inp.value || '').trim() : '';
          if (!to) { alert('Укажите логин или ID игрока.'); return; }
          if (!confirm('Подарить полную версию пропуска игроку: '+to+' ?')) return;
          bpGiftPremium(to);
        } catch (e) {
          console.error(e);
          alert('Ошибка при подготовке подарка.');
        }
      }

      function bpGiftPremium(to) {
        var box = document.getElementById('bpGiftMsg');
        function showMsg(text, ok){
          if (!box) return;
          box.className = 'bpGiftMsg ' + (ok ? 'ok' : 'bad');
          box.style.display = 'block';
          box.innerHTML = text;
        }

        var url = window.location.pathname || '/do/battlepass.php';
        if (url.indexOf('battlepass') === -1) url = '/do/battlepass.php';

        var body = 'type=giftpremium&to=' + encodeURIComponent(to);

        // jQuery (если есть)
        if (window.jQuery && typeof jQuery.post === 'function') {
          jQuery.post(url, body, function(resp){
            try {
              if (typeof resp === 'string') resp = JSON.parse(resp);
            } catch(e) {}
            if (resp && resp.ok) {
              showMsg(resp.msg ? resp.msg : 'Подарок отправлен.', true);
              // Обновление интерфейса, если есть загрузчик вкладок
              if (typeof bpLoadCategory === 'function') bpLoadCategory('info');
              else if (typeof battle_pass === 'function') battle_pass('info');
              else if (typeof battlepass === 'function') battlepass('info');
            } else {
              showMsg((resp && resp.error) ? resp.error : 'Ошибка при отправке подарка.', false);
            }
          }).fail(function(){
            showMsg('Ошибка соединения. Повторите позже.', false);
          });
          return;
        }

        // fetch fallback
        fetch(url, {
          method: 'POST',
          headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
          body: body,
          credentials: 'same-origin'
        }).then(function(r){ return r.text(); })
          .then(function(t){
            var resp = null;
            try { resp = JSON.parse(t); } catch(e) {}
            if (resp && resp.ok) {
              showMsg(resp.msg ? resp.msg : 'Подарок отправлен.', true);
              if (typeof bpLoadCategory === 'function') bpLoadCategory('info');
              else if (typeof battle_pass === 'function') battle_pass('info');
              else if (typeof battlepass === 'function') battlepass('info');
            } else {
              showMsg((resp && resp.error) ? resp.error : 'Ошибка при отправке подарка.', false);
            }
          }).catch(function(){
            showMsg('Ошибка соединения. Повторите позже.', false);
          });
      }

</script>
    <?php
    echo ob_get_clean();
break;
            }
            $response['html'] = ob_get_clean();
        break;

        // Получение награды
        
        // Подарок полной версии (премиум)
        case 'giftpremium':

            // --- Константы стоимости ---
            $costItemId  = defined('BP_FULL_PASS_COST_ITEM')  ? (int)BP_FULL_PASS_COST_ITEM  : 0;
            $costItemQty = defined('BP_FULL_PASS_COST_COUNT') ? (int)BP_FULL_PASS_COST_COUNT : 0;

            $toRaw = trim((string)($_POST['to'] ?? $_POST['login'] ?? $_POST['user'] ?? ''));
            if ($toRaw === '') { $response['error'] = "Укажите игрока (логин или ID)."; break; }

            // Проверяем активный сезон
            $season = getActiveSeason($mysqli);
            $season_id = $season ? (int)$season['id'] : 0;
            if (!$season_id) { $response['error'] = "Активный сезон не найден."; break; }

            // Пользователь-даритель
            $meUser = $mysqli->query("SELECT `id`,`login`,`user_group`,`lvl` FROM `users` WHERE `id`=".(int)$user_id." LIMIT 1")->fetch_assoc();
            if (!$meUser) { $response['error'] = "Ошибка: пользователь не найден."; break; }
            if ((int)$meUser['user_group'] == 7) { $response['error'] = "Нельзя дарить пропуск, находясь в тюрьме."; break; }

            // Получатель
            if (ctype_digit($toRaw)) {
                $toUser = $mysqli->query("SELECT `id`,`login`,`user_group`,`lvl` FROM `users` WHERE `id`=".(int)$toRaw." LIMIT 1")->fetch_assoc();
            } else {
                $safeLogin = $mysqli->real_escape_string($toRaw);
                $toUser = $mysqli->query("SELECT `id`,`login`,`user_group`,`lvl` FROM `users` WHERE `login`='".$safeLogin."' LIMIT 1")->fetch_assoc();
            }

            if (!$toUser) { $response['error'] = "Игрок не найден."; break; }
            if ((int)$toUser['id'] === (int)$user_id) { $response['error'] = "Нельзя подарить премиум самому себе."; break; }
            if ((int)$toUser['user_group'] == 7) { $response['error'] = "Нельзя подарить пропуск игроку, который находится в тюрьме."; break; }

            $minLvl = defined('BP_MIN_LEVEL') ? (int)BP_MIN_LEVEL : 1;
            if ((int)$toUser['lvl'] < $minLvl) { $response['error'] = "Получатель должен иметь минимум ".$minLvl." уровень."; break; }

            if ($costItemId <= 0 || $costItemQty <= 0) {
                $response['error'] = "Покупка/подарок полной версии сейчас недоступны (не задана стоимость)."; break;
            }

            // Проверяем наличие оплаты у дарителя
            if (!item_isset($costItemId, $costItemQty)) {
                $response['error'] = "Недостаточно ресурсов для подарка премиума."; break;
            }

            try {
                $mysqli->begin_transaction();

                // Блокируем строку пропуска получателя
                $lock = $mysqli->query("
                    SELECT `id`,`type`,`lvl`,`exp_me`,`exp_to`,`point`
                    FROM `aa_battle_pass_user`
                    WHERE `user`=".(int)$toUser['id']." AND `season_id`=".$season_id."
                    FOR UPDATE
                ")->fetch_assoc();

                if ($lock) {
                    if ((int)$lock['type'] === 2) {
                        throw new Exception('У игрока уже есть полная версия.');
                    }
                    // Апгрейд до полной версии (поинты/уровень не трогаем)
                    $mysqli->query("
                        UPDATE `aa_battle_pass_user`
                           SET `type`=2
                         WHERE `user`=".(int)$toUser['id']." AND `season_id`=".$season_id."
                         LIMIT 1
                    ");
                    if ($mysqli->affected_rows !== 1) {
                        throw new Exception('Не удалось обновить статус пропуска у получателя.');
                    }
                } else {
                    // Если у получателя нет записи — создаём сразу полную версию
                    $mysqli->query("
                        INSERT INTO `aa_battle_pass_user`
                        (`user`,`season_id`,`lvl`,`exp_me`,`exp_to`,`type`,`point`)
                        VALUES (".(int)$toUser['id'].", ".$season_id.", 1, 0, ".(defined('BP_EXP_PER_LEVEL')?(int)BP_EXP_PER_LEVEL:800).", 2, 0)
                    ");
                    if ($mysqli->insert_id <= 0) {
                        throw new Exception('Не удалось создать запись пропуска у получателя.');
                    }
                }

                // Списываем оплату у дарителя (повторная проверка)
                if (!item_isset($costItemId, $costItemQty)) {
                    throw new Exception('Недостаточно ресурсов для подарка премиума.');
                }
                minus_item($costItemId, $costItemQty);

                // История: получатель и даритель
                $mysqli->query("
                    INSERT INTO `aa_battle_pass_history` (`user_id`,`season_id`,`event`,`info`)
                    VALUES (".(int)$toUser['id'].", ".$season_id.", 'giftpremium_recv', 'Подарок полной версии от ".addslashes((string)$meUser['login'])."')
                ");
                $mysqli->query("
                    INSERT INTO `aa_battle_pass_history` (`user_id`,`season_id`,`event`,`info`)
                    VALUES (".(int)$user_id.", ".$season_id.", 'giftpremium_send', 'Подарили полную версию игроку ".addslashes((string)$toUser['login'])." (ID: ".(int)$toUser['id'].")')
                ");

                $mysqli->commit();

                // Обновляем кеши топов (на всякий случай)
                bp_cache_del('bp_top_lvl_'.$season_id);
                bp_cache_del('bp_top_points_'.$season_id);

                $response['ok'] = 1;
                $response['msg'] = "Премиум успешно подарен игроку ".htmlspecialchars((string)$toUser['login'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').".";
            } catch (Exception $e) {
                $mysqli->rollback();
                $response['error'] = $e->getMessage();
            }

        break;

case 'giveprize':
            $prize_id   = intval($_POST['id'] ?? 0);
            $userprizes = getUserPrizes($mysqli, $user_id, $season_id);
            $user_bp    = getUserBP($mysqli, $user_id, $season_id);
            $result     = claimPrize($mysqli, $user_id, $season_id, $prize_id, $user_bp, $userprizes);
            $mysqli->query("INSERT INTO aa_battle_pass_history (user_id, season_id, event, event_id, info) VALUES (".(int)$user_id.", ".$season_id.", 'claim_prize', ".(int)$prize_id.", 'Получена награда')");
            $response = array_merge($response, $result);
        break;

        // Покупка в магазине
        case 'emblemshop':
            $id     = intval($_POST['id'] ?? 0);
            $prize  = $mysqli->query("SELECT * FROM aa_battle_pass_shop WHERE id = ".$id." AND season_id = ".$season_id)->fetch_assoc();
            $user_bp = getUserBP($mysqli, $user_id, $season_id);
            if ($user_bp && (int)$user_bp['type'] == 2) {
                if ((int)$user_bp['point'] >= (int)$prize['point']) {
                    if ($prize['type_pr'] == 1) {
                        $pok = $mysqli->query("SELECT `name_rus` FROM base_pokemons WHERE id = ".(int)$prize['pok'])->fetch_assoc();
                        $tm = time() + (3600 * 24 * 6);
                        $gens = "25,25,25,25,25,25";
                        plusEgg($gens, false, false, true, $tm, (int)$prize['pok'], false, $user_id, $prize['form']);
                        $response['plus'] = "<img src='/img/world/items/little/151.png' class='item'> Яйцо ".h($pok['name_rus'] ?? 'Покемон');
                    } else {
                        $response['plus'] = '';
                        $item_l = explode(';', (string)$prize['item']);
                        foreach ($item_l as $item_data) {
                            if (!$item_data) continue;
                            list($item_id, $item_qty) = array_map('intval', explode(',', $item_data));
                            itemAdd($item_id, $item_qty);
                            $response['plus'] .= "<img src='/img/world/items/little/".$item_id.".png' class='item'> <b>x".$item_qty."</b><br>";
                        }
                    }
                    $new_points = (int)$user_bp['point'] - (int)$prize['point'];
                    $mysqli->query("UPDATE aa_battle_pass_user SET point = ".$new_points." WHERE user = ".(int)$user_id." AND season_id = ".$season_id);
                    $mysqli->query("INSERT INTO aa_battle_pass_history (user_id, season_id, event, event_id, info) VALUES (".(int)$user_id.", ".$season_id.", 'emblem_shop', ".$id.", 'Покупка в магазине')");
                    $response['html'] = 'Товар успешно получен!';
                } else {
                    $response['error'] = "У вас недостаточно жетонов!";
                }
            } else {
                $response['error'] = "Вам нужна полная версия для магазина!";
            }
        break;

        default:
            $response['error'] = "Данный тип запроса не поддерживается!";
    }
} else {
    $response['error'] = "Данный тип запроса не поддерживается!";
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);