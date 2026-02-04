<?php
/**
 * Общие функции и константы «Квест электростанции»
 * Подключай в каждом NPC-файле:
 *   require_once($_SERVER['DOCUMENT_ROOT'].'/do/Npc/powerplant/helpers.php');
 */

if (!isset($mysqli)) {
    require_once($_SERVER['DOCUMENT_ROOT'].'/inc/conf/global.php');
}

/** ===== ЛОКАЦИИ (ПОДСТАВЬ СВОИ ID) ===== */
define('PP_LOC_GATE',       201); // КПП (охрана, вход)
define('PP_LOC_OPERATOR',   202); // Операторская (диспетчер)
define('PP_LOC_HALL_N',     203); // Зал «Север»
define('PP_LOC_HALL_W',     204); // Зал «Запад»
define('PP_LOC_HALL_E',     205); // Зал «Восток»
define('PP_LOC_TECH',       206); // Тех.этаж/щитовая (инженер Барнс)
define('PP_LOC_SWITCH',     207); // Главный рубильник
define('PP_LOC_OFFICE',     208); // Кабинет директора

/** ===== ПРОЧЕЕ ===== */
define('PP_QUEST_CODE',   'powerplant');
define('PP_BADGE_ITEM',   9501); // Бейдж-допуск
define('PP_FUSE_ITEM',    9502); // Предохранитель
define('PP_REWARD_ITEM',  9503); // Ящик с инструментом
define('PP_REWARD_COINS', 250);

/**
 * step:
 * 0 — не начинал
 * 1 — получил допуск у охранника
 * 2 — задание Лины: перезапустить 3 терминала (N/W/E)
 * 3 — вернулся к Лине → к Барнсу (найти 3 предохранителя)
 * 4 — предохранители установлены Барнсом, идти к рубильнику
 * 5 — рубильник перезапущен
 * 6 — награда у директора получена
 */
function pp_get($uid) {
    global $mysqli;
    $uid = (int)$uid;
    $row = $mysqli->query("SELECT * FROM quest_powerplant WHERE user_id={$uid}")->fetch_assoc();
    if (!$row) {
        $now = time();
        $mysqli->query("INSERT INTO quest_powerplant (user_id, step, flags, started_at, updated_at)
                        VALUES ({$uid}, 0, '{}', {$now}, {$now})");
        $row = $mysqli->query("SELECT * FROM quest_powerplant WHERE user_id={$uid}")->fetch_assoc();
    }
    $row['flags'] = json_decode($row['flags'] ?: "{}", true);
    if (!is_array($row['flags'])) $row['flags'] = [];
    return $row;
}
function pp_set_step($uid, $step, $flagsPatch = []) {
    global $mysqli;
    $uid  = (int)$uid;
    $step = (int)$step;
    $row  = pp_get($uid);
    $flags = array_merge($row['flags'], (array)$flagsPatch);
    $flagsJson = $mysqli->real_escape_string(json_encode($flags, JSON_UNESCAPED_UNICODE));
    $now = time();
    $mysqli->query("UPDATE quest_powerplant
                    SET step={$step}, flags='{$flagsJson}', updated_at={$now}
                    WHERE user_id={$uid} LIMIT 1");
    return ['step'=>$step, 'flags'=>$flags];
}
function pp_flag($uid, $key, $val = 1) {
    $row = pp_get($uid);
    return pp_set_step($uid, (int)$row['step'], [$key=>$val]);
}

/** Уведомление в таблицу notification */
function pp_notify($uid, $text) {
    global $mysqli;
    $uid = (int)$uid;
    $text = $mysqli->real_escape_string($text);
    $now = time();
    $mysqli->query("INSERT INTO notification (user_id, text, type, is_read, created_at)
                    VALUES ({$uid}, '{$text}', 'system', 0, {$now})");
}

/** Проверка: игрок на одной из разрешённых локаций */
function pp_on_locations($uid, array $locIds) {
    global $mysqli;
    $uid = (int)$uid;
    if (!$locIds) return false;
    $row = $mysqli->query("SELECT location FROM users WHERE id={$uid}")->fetch_assoc();
    if (!$row) return false;
    $cur = (int)$row['location'];
    foreach ($locIds as $id) if ((int)$id === $cur) return true;
    return false;
}

/** Единый JSON-ответ и выход */
function pp_json($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Обёртки предметов под ваши хелперы */
function pp_item_add($itemId, $count, $uid) {
    if (function_exists('itemAdd')) { itemAdd((int)$itemId,(int)$count,(int)$uid); return true; }
    return false;
}
function pp_item_take($itemId, $count, $uid) {
    if (function_exists('minus_item')) { minus_item((int)$itemId,(int)$count,(int)$uid); return true; }
    return false;
}
function pp_item_has($itemId, $count, $uid) {
    if (function_exists('item_isset')) return (bool)item_isset((int)$itemId,(int)$count,(int)$uid);
    global $mysqli;
    $uid = (int)$uid; $itemId = (int)$itemId;
    $row = $mysqli->query("SELECT SUM(count) AS c FROM items WHERE user={$uid} AND item={$itemId}")->fetch_assoc();
    return ($row && (int)$row['c'] >= (int)$count);
}

/** Ответ, если игрок не на нужной локации */
function pp_fail_location_response($who = 'Персонал') {
    return [
        'name'        => $who,
        'question'    => 'Вы находитесь не в том секторе. Подойдите к нужной части электростанции.',
        'answer'      => [ 0 => 'Понял' ],
        'closeDialog' => true
    ];
}
