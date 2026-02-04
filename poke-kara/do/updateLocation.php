<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
session_start();

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';
$userFunction = $patch_project . '/inc/function/Users.php';

// Подключение глобальных файлов
if (!file_exists($patch_global)) {
    die('The problem with the connection files.');
}
require_once($patch_global);
require_once($userFunction);

// Подключение класса мировых боссов
if (file_exists($patch_project . '/inc/world_boss_manager.php')) {
    require_once($patch_project . '/inc/world_boss_manager.php');
}

/* =============================================================================
   TRAIN: автоматическая высадка по прибытии + утилиты
   ============================================================================= */

// Константы маршрута поезда (при необходимости держите в конфиге)
if (!defined('TRAIN_LOCATION_ID'))  define('TRAIN_LOCATION_ID', 8010); // локация «Поезд/вагон»
if (!defined('TRAIN_STATION_A'))    define('TRAIN_STATION_A', 27);    // Канто
if (!defined('TRAIN_STATION_B'))    define('TRAIN_STATION_B', 70);    // Джотто

/**
 * Высадить пользователя, если его поездка уже прибыла (переводим в to_loc и помечаем поездку завершенной).
 * Таблица train_travel: id,user,from_loc,to_loc,depart_at,arrive_at,active
 */
function train_finish_arrivals(mysqli $db, int $uid): void {
    $now = time();
    $res = $db->query("SELECT * FROM `train_travel` WHERE `user`={$uid} AND `active`=1 ORDER BY `id` DESC LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        if ((int)$row['arrive_at'] <= $now) {
            $db->query("UPDATE `train_travel` SET `active`=0 WHERE `id`=".(int)$row['id']." LIMIT 1");
            $to = (int)$row['to_loc'];
            $db->query("UPDATE `users` SET `location`={$to} WHERE `id`={$uid} LIMIT 1");
        }
    }
}

/** Вызывать на каждом пользовательском запросе (после старта сессии/БД) */
function train_touch(mysqli $db, int $uid): void {
    train_finish_arrivals($db, $uid);
}

/* =============================================================================
   NPC MULTI-LOC: поддержка поля base_npc.locations_text "[1, 8010]" и locations_json
   ============================================================================= */

/** Парсинг текста вида "[1, 8010]" или "(1,8010)" или "1,8010" в массив чисел */
function npc_parse_locations_text($s) {
    if ($s === null || $s === '') return array();
    $s = trim($s);
    $s = str_replace(array('(',')','{','}'), array('[',']','[',']'), $s);
    if (strlen($s) >= 2 && $s[0] === '[' && substr($s,-1) === ']') {
        $arr = json_decode($s, true);
        if (is_array($arr)) {
            $out = array();
            foreach ($arr as $v) if (is_numeric($v)) $out[] = (int)$v;
            return array_values(array_unique($out));
        }
    }
    $parts = preg_split('~\s*,\s*~u', trim($s, "[] \t\n\r\0\x0B"), -1, PREG_SPLIT_NO_EMPTY);
    $out = array();
    foreach ($parts as $p) if (is_numeric($p)) $out[] = (int)$p;
    return array_values(array_unique($out));
}

/** Соответствует ли NPC текущей локации (учет loc_id, locations_text, locations_json) */
function npc_matches_location_row(array $npcRow, int $loc): bool {
    if (isset($npcRow['loc_id']) && (int)$npcRow['loc_id'] === $loc) return true;

    if (!empty($npcRow['locations_json'])) {
        $arr = json_decode($npcRow['locations_json'], true);
        if (is_array($arr)) {
            $arr = array_map('intval', $arr);
            if (in_array($loc, $arr, true)) return true;
        }
    }
    if (!empty($npcRow['locations_text'])) {
        $extra = npc_parse_locations_text($npcRow['locations_text']);
        if (in_array($loc, $extra, true)) return true;
    }
    return false;
}

/* =============================================================================
   === ФУНКЦИЯ ПОДСЧЕТА ВРЕМЕНИ ОНЛАЙН ===
   ============================================================================= */
function updateUserOnlineTime($mysqli, $userId) {
    $now = time();
    $pingInterval = 60; // Интервал обновления в секундах
    
    try {
        // Читаем текущие данные пользователя
        $sql = "SELECT `online`, 
                       IFNULL(`play_seconds`, 0) AS play_seconds,
                       IFNULL(`last_play_at`, 0) AS last_play_at,
                       IFNULL(`hours`, 0) AS hours
                FROM `users`
                WHERE `id` = ?";
        
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false;
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        $prevOnline = (int)$user['online'];
        $lastPlayAt = (int)$user['last_play_at'];
        $playSeconds = (int)$user['play_seconds'];
        
        // Определяем дельту времени для добавления
        $delta = 0;
        $wasOnline = ($prevOnline >= $now);
        
        if ($lastPlayAt > 0) {
            // Пользователь уже играл ранее
            if ($wasOnline) {
                // Был онлайн - считаем время с последнего обновления
                $timeSinceLastUpdate = $now - $lastPlayAt;
                
                // Ограничиваем разумными пределами
                if ($timeSinceLastUpdate > 0 && $timeSinceLastUpdate <= ($pingInterval + 30)) {
                    $delta = min($timeSinceLastUpdate, $pingInterval + 30);
                } else if ($timeSinceLastUpdate > ($pingInterval + 30)) {
                    // Слишком большой перерыв - возможно AFK
                    $delta = $pingInterval;
                }
            }
            // Если был офлайн - начинаем новую сессию, дельта = 0
        }
        // Первый вход - устанавливаем базовую точку, дельта = 0
        
        // Обновляем данные
        $newPlaySeconds = $playSeconds + $delta;
        $newHours = floor($newPlaySeconds / 3600);
        $newOnline = $now + 300; // Онлайн статус на 5 минут
        
        // Сохраняем изменения
        $updateSql = "UPDATE `users`
                      SET `online` = ?, 
                          `last_play_at` = ?, 
                          `play_seconds` = ?, 
                          `hours` = ?
                      WHERE `id` = ?";
        
        $updateStmt = $mysqli->prepare($updateSql);
        if (!$updateStmt) {
            return false;
        }
        
        $updateStmt->bind_param('iiiii', $newOnline, $now, $newPlaySeconds, $newHours, $userId);
        $success = $updateStmt->execute();
        $updateStmt->close();
        
        return $success;
        
    } catch (Exception $e) {
        return false;
    }
}

// --- SOCKET PUSH FUNCTION (здесь пример через Redis pub/sub, настрой под свой push-демон) ---
function socketPush($event, $payload, $userId = null) {
    // Пуши только если событие указано
    if (!empty($event)) {
        $data = [
            'event' => $event,
            'payload' => $payload,
            'userId' => $userId
        ];
        // см. комментарии в вашем исходнике — здесь только заглушка
    }
}

// Получение информации о пользователе
$userId = intval($_SESSION['id']);

// === ОБНОВЛЯЕМ ВРЕМЯ ОНЛАЙН ===
$mysqli->begin_transaction();
try {
    updateUserOnlineTime($mysqli, $userId);
    $mysqli->commit();
} catch (Exception $e) {
    $mysqli->rollback();
}

/* ВАЖНО: СРАЗУ ПОСЛЕ этого — проверяем поезд/прибытие,
   а потом заново читаем пользователя (локация могла поменяться) */
train_touch($mysqli, $userId);

$userQuery = "SELECT `id`,`login`,`user_group`,`region`,`location`,`sex`,`ban`,`status`,`status_id`,`rating`,`rang`,`botID`,`sprite`,`lvl` FROM `users` WHERE `id`='$userId'";
$location_id = $mysqli->query($userQuery)->fetch_assoc();

// Обновление времени онлайн для бота (если есть)
if (!empty($location_id['botID'])) {
    $timeUpdate = time() + 300;
    $mysqli->query('UPDATE `users` SET `online` = ' . $timeUpdate . ' WHERE `id` = ' . intval($location_id['botID']));
}

// Генерация случайной погоды
$time = time();
$r = rand(1, 100);
if ($r < 25) {
    $rand = 1;
} elseif ($r < 50) {
    $rand = 2;
} elseif ($r < 68) {
    $rand = 3;
} elseif ($r < 84) {
    $rand = 4;
} else {
    $rand = 5;
}
$weatherTime = $time + 1800;
$weatherID = $mysqli->query('SELECT id FROM base_region WHERE weather_time < ' . $time)->fetch_assoc();
if ($weatherID) {
    $mysqli->query('UPDATE `base_region` SET weather = ' . $rand . ', weather_time = ' . $weatherTime . ' WHERE id = ' . intval($weatherID['id']));
}

// Обновление ранга пользователя
$ratings = json_decode($location_id['rating']);
$rang = population($ratings->pve) . ' ' . reputation($ratings->pvp, $ratings->battleCount);
if ($rang != $location_id['rang']) {
    $mysqli->query('UPDATE `users` SET `rang` = "' . $mysqli->real_escape_string($rang) . '" WHERE `id` = ' . $userId);
}

$timeOnline = $time - 300;

// Получение уведомлений пользователя
$userNotice = $mysqli->query('SELECT * FROM `user_notice` WHERE `touser_id` = ' . $userId . ' ');
$response = [];
$userNoticeArr = [];
$userNoticeDefArr = [];
$userNoticeDell = [];
$noticeHash = '';

if ($userNotice) {
    while ($userNoticeList = $userNotice->fetch_assoc()) {
        if ($userNoticeList['type'] == 'default') {
            $userNoticeDell[] = $userNoticeList['id'];
            $userNoticeDefArr[] = ['text' => $userNoticeList['info']];
        } else {
            $noticeHash .= 'id' . $userNoticeList['id'];
            if (!isset($userNoticeArr['u' . $userNoticeList['user_id']])) {
                $userNoticeArr['u' . $userNoticeList['user_id']] = [];
            }
            $userNoticeArr['u' . $userNoticeList['user_id']][] = [
                'id' => $userNoticeList['id'],
                'hash' => $userNoticeList['hash'],
                'type' => $userNoticeList['type'],
                'info' => $userNoticeList['info'],
            ];
        }
    }
}
if (!empty($userNoticeDell)) {
    $mysqli->query("DELETE FROM `user_notice` WHERE `id` IN (" . implode(',', $userNoticeDell) . ")");
}
if ($userNoticeArr) {
    $response["usersAtNotice"] = $userNoticeArr;
}
if ($userNoticeDefArr) {
    $response["usersDefNotice"] = $userNoticeDefArr;
}

// Обработка статусов пользователя
if ($location_id['status'] != 'free') {
    switch ($location_id['status']) {
        case 'battle':
            new ActionBattle($location_id, [], $response);
            break;
        case 'trade':
            new Trade($mysqli, $location_id, $response);
            break;
    }
    $_SESSION['battle_refresh'] = time() + mt_rand(1, 3);
} else {
    if (isset($_SESSION['battle_refresh'])) {
        if ($_SESSION['battle_refresh'] <= time()) {
            $getUserLocationInfo = $mysqli->query("SELECT `pve` FROM `base_location` WHERE `id`='" . intval($location_id['location']) . "'")->fetch_assoc();
            if ($getUserLocationInfo['pve'] > 0) {
                if ((!empty($_POST['userAssault']) && $_POST['userAssault'] != 'false') || $getUserLocationInfo['pve'] == 2) {
                    Info::_generatePve($location_id, $location_id['location']);
                }
            }
            $_SESSION['battle_refresh'] = time() + mt_rand(9, 18);
        }
    } else {
        $_SESSION['battle_refresh'] = time() + mt_rand(9, 18);
    }
}

// --- LIVE UPDATE USERS на локации через сокет ---
$liveUpdateUsers = [];
if (isset($_POST['updateUsers'])) {
    $countUsersAtLocation = $mysqli->query("SELECT `sex`,`id`,`user_group`,`login` FROM `users` WHERE `location`='" . intval($location_id['location']) . "' AND `online` >= '" . $timeOnline . "' ORDER BY `user_group`,`id`");
    $response["countUsersAtLocation"] = $countUsersAtLocation->num_rows;
    $usersLoc = [];
    $userHash = '';
    while ($usersInfo = $countUsersAtLocation->fetch_assoc()) {
        $userHash .= 'id' . $usersInfo['id'];
        $userClan = $mysqli->query("SELECT * FROM base_clans_users WHERE user_id = " . intval($usersInfo['id']))->fetch_assoc();
        $userClanQuery = $userClan ? '<div onclick=openClanCard(' . $userClan['clan_id'] . ') class=clanUsers style="background-image:url(/img/world/clans/emblems/' . $userClan['clan_id'] . '.png);"></div>' : '';
        $usersLoc[] = [
            'id' => $usersInfo['id'],
            'login' => $usersInfo['login'],
            'group' => $usersInfo['user_group'],
            'sex' => $usersInfo['sex'],
            'clan' => $userClanQuery,
            'isOffer' => isset($userNoticeArr['u' . $usersInfo['id']])
        ];
        // Для live push
        $liveUpdateUsers[] = [
            'id' => $usersInfo['id'],
            'login' => $usersInfo['login'],
            'group' => $usersInfo['user_group'],
            'sex' => $usersInfo['sex'],
            'clan' => $userClanQuery,
            'isOffer' => isset($userNoticeArr['u' . $usersInfo['id']])
        ];
    }

    // Бафы - ОТДАЁМ МАССИВ ОБЪЕКТОВ!
    $bafsSelect = $mysqli->query("SELECT * FROM `bafs` WHERE `user`=" . $userId);
    $bafsArr = [];
    while ($baf = $bafsSelect->fetch_assoc()) {
        if ($time < $baf['time']) {
            $item = $mysqli->query("SELECT `name` FROM `base_items` WHERE `id` = '" . intval($baf['baf']) . "'")->fetch_assoc();
            $bafsArr[] = [
                'id' => intval($baf['baf']),
                'title' => $item ? $item['name'] : ''
            ];
        }
    }
    if ($bafsArr) $response['bafs'] = $bafsArr;

    // Системные ивенты
    $sys = $mysqli->query("SELECT * FROM `system` WHERE `id` = 1")->fetch_assoc();
    $ev_sys = (in_array(date("l"), ["Saturday", "Sunday", "Wednesday"]) || $sys['skoba'] == 1) ? '<i class="fa fa-bullseye"></i>' : '';
    $money_sys = ($sys['money'] != 1) ? '<i class="fa fa-money-bill"></i>' : '';
    $drop_sys = ($sys['drop'] != 1) ? '<i class="fa fa-radar"></i>' : '';
    $exp_sys = ($sys['exp'] != 1) ? '<i class="fa fa-badge"></i>' : '';
    $shine_sys = ($sys['shine'] != 1) ? '<i class="fa fa-sparkles"></i>' : '';
    $tren_sys = ($sys['tren'] != 1) ? '<i class="fa fa-suitcase"></i>' : '';
    // Админ уведомления
    $pokemonMove = $mysqli->query("SELECT * FROM `adminNotify` WHERE `actual` = 1 ORDER BY `id` DESC LIMIT 1")->fetch_assoc();
    if ($pokemonMove) {
        $check_not = $mysqli->query("SELECT * FROM `adminNotifyCheck` WHERE `user_id`= $userId and `id_notify` = " . intval($pokemonMove['id']))->fetch_assoc();
        if (empty($check_not)) {
            $response['adminNotify'] = $pokemonMove['text'];
            $response['adminNotify_author'] = '<div class="user-link"> <div class="u-1 label">' . $pokemonMove['author'] . '</div></div>';
            $response['adminNotify_date'] = $pokemonMove['date'];
            $mysqli->query("INSERT INTO `adminNotifyCheck` (`id_notify`,`user_id`) VALUES ('" . intval($pokemonMove['id']) . "','$userId')");
        }
    }
    // Данные пользователя
    $us = $mysqli->query('SELECT `lvl`,`exp_lvl`,`exp_lvl_to`,`invaite`,`birthday`,`hash`,`captcha`,`gift`,`gift_online`,`hell_candy`,`hell_team`,`hell`,`status`,`limit_pok` FROM `users` WHERE `id` = ' . $userId)->fetch_assoc();
    $bf = $mysqli->query('SELECT `baf`,`time` FROM `bafs` WHERE `baf` = 448 AND `user` = ' . $userId)->fetch_assoc();
    $ng = $mysqli->query('SELECT `status` FROM `hell` WHERE `id` = 1')->fetch_assoc();
    $server = $sys;
    if ($server['closed'] == 1) {
        $response['tw'] = $server['closed'];
        if ($userId == 4) $response['tw'] = 0;
    }
    $util = achiv_utility(10) ? rand(1, 4025) : rand(1, 3500);
    if ($us['captcha'] == 0 && (!$bf || $bf['time'] < $time) && $util == 500 && $us['gift'] == 0) {
        $r = gen_captcha(6);
        $mysqli->query('UPDATE `users` SET `captcha` = ' . intval($r) . ' WHERE `id` = ' . $userId);
    }
    if ($us['captcha'] != 0) {
        $response["captcha"] = $us['captcha'];
    }
    if (in_array($server['week'], [1, 2, 3])) {
        if (rand(1, 200) <= 3) {
            if ($server['week'] == 1) {
                $ivent = $mysqli->query('SELECT * FROM `a_ivent_week_arheolog` WHERE `user` = ' . $userId)->fetch_assoc();
            } elseif ($server['week'] == 2) {
                $ivent = $mysqli->query('SELECT * FROM `a_ivent_week_labirint` WHERE `user` = ' . $userId)->fetch_assoc();
            } elseif ($server['week'] == 3) {
                $ivent = $mysqli->query('SELECT * FROM `a_ivent_week_playhome` WHERE `user` = ' . $userId)->fetch_assoc();
            }
            if (!empty($ivent)) {
                $mis = $mysqli->query('SELECT `catch` FROM `a_ivent_week_mission` WHERE `user` = ' . $userId)->fetch_assoc();
                if ($mis['catch'] >= 1) {
                    $input = [480, 481, 482, 109, 151, 176, 187, 201, 333, 353, 355, 358, 374, 385, 251, 415, 479, 527, 608, 686, 708, 763, 854, 885];
                    $rand_keys = array_rand($input, 2);
                    $pokemon_catch = $input[$rand_keys[1]];
                    $mysqli->query("INSERT INTO `a_ivent_week_pokemon_catch` (`user`,`pok`) VALUES ($userId,'" . intval($pokemon_catch) . "')");
                    $response["pokemon_catch"] = $mysqli->insert_id;
                    $response["pokemon_catch_num"] = $pokemon_catch;
                }
            }
        }
    }
    $sb = $mysqli->query('SELECT * FROM `snow_ball` WHERE `userto` = ' . $userId . ' AND `active` = 0')->fetch_assoc();
    if (!empty($sb['id'])) {
        $response["snow_ball"] = $sb['id'];
        $mysqli->query('UPDATE `snow_ball` SET `active` = 1 WHERE `id` = ' . intval($sb['id']));
    }
    if ($us['hash'] != $_SESSION['hashcodetest']) $response["hashc"] = 1;
    $response["ver"] = $server['version'];
    $response["lvl"] = $server['online'];
    if ($ev_sys) $response["ev_sys"] = $ev_sys;
    if ($money_sys) $response["money_sys"] = $money_sys;
    if ($drop_sys) $response["drop_sys"] = $drop_sys;
    if ($exp_sys) $response["exp_sys"] = $exp_sys;
    if ($shine_sys) $response["shine_sys"] = $shine_sys;
    if ($tren_sys) $response["tren_sys"] = $tren_sys;
    $response["usersAtLocation"] = $usersLoc;
    $response["usersAtLocationHash"] = md5($userHash . $noticeHash);
    $response["serverTime"] = date('H:i');
    $response["LVLuser"] = $us['lvl'];
    $lvluser = ($us['exp_lvl'] / $us['exp_lvl_to']) * 100;
    $response["WIDTHuser"] = $lvluser;
    $response['ng'] = $ng['status'];
    $myUser = $mysqli->query("SELECT location, id, opros FROM users WHERE id = $userId")->fetch_assoc();
    $locaReg = $mysqli->query("SELECT region FROM base_location WHERE id = " . intval($myUser['location']))->fetch_assoc();
    $weather = $mysqli->query("SELECT * FROM base_region WHERE id = " . intval($locaReg['region']))->fetch_assoc();
    $weatherName = $mysqli->query("SELECT name FROM weather WHERE id = " . intval($weather['weather']))->fetch_assoc();
    $response['WeatherName'] = $weatherName['name'];
    $response['WeatherId'] = $weather['weather'];
    if (!empty($server_ver)) {
        $response['server_ver'] = $server_ver;
    }
    try {
        new GameChat($mysqli, ['type' => 'read'], $response, $location_id);
    } catch (\Exception $e) {
        echo $e->getMessage();
    }
    // --- LIVE PUSH ВСЕМ В ЛОКАЦИИ ---
    if (!empty($liveUpdateUsers)) {
        socketPush(
            'location_users', // имя события для клиента JS
            [
                'location_id' => intval($location_id['location']),
                'users' => $liveUpdateUsers,
                'hash' => md5($userHash . $noticeHash)
            ]
        );
    }
    print(is_array($response) ? Info::_parseData($response) : $response);
    die();
}

// Локации и НПС
$getUserLocationInfo = $mysqli->query("SELECT * FROM `loc_to` WHERE `loc_id`='" . intval($location_id['location']) . "'")->fetch_assoc();
$getLocationRoads = json_decode($getUserLocationInfo["roads"]);
$getUserLocationInfo = $mysqli->query("SELECT * FROM `base_location` WHERE `id`='" . intval($location_id['location']) . "'")->fetch_assoc();

/* === ВАЖНО: загрузка НПС с учётом поля locations_text/locations_json ===
   Берём всех видимых и фильтруем в PHP. */
$getLocationNPC_all = $mysqli->query("SELECT * FROM `base_npc` WHERE `hide` = 0");
$npc = [];
if ($getLocationNPC_all) {
    while ($baseNpc = $getLocationNPC_all->fetch_assoc()) {
        if (!npc_matches_location_row($baseNpc, (int)$location_id['location'])) {
            continue;
        }
        // Спец-правила из вашего исходника
        $data = date('H:i:s');
        $q1 = $mysqli->query("SELECT * FROM `user_quests` WHERE `user_id`='$userId' AND `quest_id` = '6' ")->fetch_assoc();
        if ($baseNpc['id'] == 28 && (($data >= '01:00:00' && $data < '23:59:59') && $q1['step'] <= 5)) $baseNpc['name'] = '';
        if ($baseNpc['test'] == 1 && $userId != 4) $baseNpc['name'] = '';
        if ($baseNpc['id'] == 29 && (item_isset(72, 1) || $q1['step'] < 6 || $q1['end'] == 1)) $baseNpc['name'] = '';

        $npc[] = [
            'id'    => $baseNpc['id'],
            'name'  => $baseNpc['name'],
            'event' => $baseNpc['event']
        ];
    }
}

$locations = [];
if (is_array($getLocationRoads) && count($getLocationRoads) > 0) {
    foreach ($getLocationRoads as $locId) {
        $getLocInfo = $mysqli->query("SELECT * FROM `base_location` WHERE `id`='" . intval($locId) . "'")->fetch_assoc();
        $imgLocMini = file_exists($patch_project . '/img/world/location/' . $getLocInfo['id'] . '.png') ? $getLocInfo['id'] : 0;
        $dopClass = $imgLocMini ? '' : 'background-size: 1000%;background-size: center;';
        // Специальные названия
        if ($location_id['location'] == 11 && $getLocInfo["id"] == 78) $getLocInfo["name"] = "Заброшенный дом";
        if ($location_id['location'] == 78 && $getLocInfo["id"] == 11) $getLocInfo["name"] = "Выход";
        if ($location_id['location'] == 79 && $getLocInfo["id"] == 80) $getLocInfo["name"] = "Лестница";
        if ($location_id['location'] == 80 && $getLocInfo["id"] == 79) $getLocInfo["name"] = "Лестница";
        if ($location_id['location'] == 39 && $getLocInfo["id"] == 88) $getLocInfo["name"] = "Подземелье";
        if ($location_id['location'] == 88 && $getLocInfo["id"] == 39) $getLocInfo["name"] = "Выход в город";
        $locations[] = [
            'id'       => (int)$getLocInfo["id"],
            'img'      => $imgLocMini,
            'dopClass' => $dopClass,
            'name'     => $getLocInfo["name"],
            'event'    => $getLocInfo["event"],
            'tipe'     => ($getLocInfo["tipe"] ?? null),
            'map_x'    => (isset($getLocInfo["map_x"]) && $getLocInfo["map_x"] !== null ? (int)$getLocInfo["map_x"] : null),
            'map_y'    => (isset($getLocInfo["map_y"]) && $getLocInfo["map_y"] !== null ? (int)$getLocInfo["map_y"] : null),
            'is_pc'    => (isset($getLocInfo["tipe"]) && $getLocInfo["tipe"] === "pokecenter") ? 1 : 0
        ];
    }
}

$patch_img = $patch_project . '/img/world/location/' . $location_id['location'] . '.png';
$img = file_exists($patch_img) ? $location_id['location'] : 0;

/* =================== PC (Сестра Джой, Боссы и т.д.) — новый UI =================== */
$pc = '';

/* ----- helpers для локаций/НПС ----- */
$currLocId = (int)($location_id['location'] ?? $getUserLocationInfo['id'] ?? 0);

/** Проверка соответствия локации по имени/типу/слагу (безопасно к различиям в БД) */
function loc_has_keyword(array $loc, array $need): bool {
    $name = mb_strtolower(trim($loc['name'] ?? $loc['title'] ?? ''));
    $slug = mb_strtolower(trim($loc['slug'] ?? ''));
    $type = mb_strtolower(trim($loc['type'] ?? ''));
    foreach ($need as $k) {
        $k = mb_strtolower($k);
        if ($k === $type) return true;
        if ($name !== '' && mb_strpos($name, $k) !== false) return true;
        if ($slug !== '' && mb_strpos($slug, $k) !== false) return true;
    }
    return false;
}

/** Ищем НПС по списку имён, который «привязан» к текущей локации (включая multi-loc) */
function find_npc_by_names_here(mysqli $db, array $names, int $locId): ?array {
    $in = implode(',', array_map(fn($s)=>"'".$db->real_escape_string($s)."'", $names));
    $npc = $db->query("SELECT * FROM `base_npc` WHERE `hide`=0 AND `name` IN ($in)");
    while ($row = $npc->fetch_assoc()) {
        if (function_exists('npc_matches_location_row') && npc_matches_location_row($row, $locId)) {
            return $row;
        }
    }
    return null;
}

/* Текущая локация (постараемся взять всё, что есть) */
$locRow = $getUserLocationInfo ?? $mysqli->query("SELECT * FROM `base_location` WHERE `id`={$currLocId} LIMIT 1")->fetch_assoc() ?? [];

/* Признаки локации по названию/типу */
$isPokeCenter = loc_has_keyword($locRow, ['покецентр','pokecenter','pokeцентр','poke-center','pcenter','pc']);
$isTraining   = loc_has_keyword($locRow, ['поле для тренировок','тренировок','тренировки','training field','dojo']);

/* Ищем «джой-подобного» НПС на этой локации */
$npcJoyHere = find_npc_by_names_here($mysqli, ['Сестра Джой','Медсестра','Нurse Joy'], $currLocId);

/* Плитки показываем если:
   - есть живая Джой в этой точке,
   - ИЛИ мы реально стоим в Покецентре / на Поле для тренировок (даже если НПС не заведён).
*/
$showPcButtons = (bool)$npcJoyHere || $isPokeCenter || $isTraining;

if ($showPcButtons) {
    // Стили добавляем один раз
    if (empty($GLOBALS['__pcBtnCss'])) {
        $pc .= '
        <style id="pc-btn-css">
          .Addon.pcBtn{
            display:inline-flex!important;flex-direction:column;align-items:center;justify-content:center;
            width:132px;height:64px;padding:6px 8px;margin:6px;border:1px solid #cfd6e4;border-radius:6px;
            background:linear-gradient(180deg,#ffffff,#f6f8fc);box-shadow:inset 0 1px 0 rgba(255,255,255,.7);
            cursor:pointer;user-select:none;gap:6px
          }
          .Addon.pcBtn:hover{box-shadow:0 2px 10px rgba(24,38,67,.08)}
          .Addon.pcBtn i{font-size:20px;line-height:1;color:#111}
          .Addon.pcBtn .pcb-lbl{display:block;text-align:center;color:#222;font-weight:300;font-size:13px;line-height:1.1;white-space:normal}
          .Addon.pcBtn.boss-active{background:linear-gradient(180deg,#fff3cd,#ffeaa7);border-color:#ffc107;animation:boss-pulse 2s infinite}
          .Addon.pcBtn.boss-active i{color:#e17055}
          .Addon.pcBtn.boss-nearby{background:linear-gradient(180deg,#e3f2fd,#bbdefb);border-color:#2196f3}
          .Addon.pcBtn.boss-nearby i{color:#1976d2}
          .Addon.pcBtn.boss-inactive{background:linear-gradient(180deg,#f8f9fa,#e9ecef);border-color:#dee2e6}
          .Addon.pcBtn.boss-inactive i{color:#6c757d}
          @keyframes boss-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
          @media (max-width:560px){.Addon.pcBtn{width:43vw;height:62px;margin:6px 4px}.Addon.pcBtn .pcb-lbl{font-size:13.5px}}
        </style>';
        $GLOBALS['__pcBtnCss'] = true;
    }

    /* --- Кнопки сервиса --- */

    // Лечение доступно в Покецентре, на Поле тренировок и при наличии Джой на локации
    $pc .= '
    <span class="Addon pcBtn" onclick="recover();" title="Лечение">
      <i class="fa fa-plus"></i>
      <span class="pcb-lbl">Лечение</span>
    </span>';

    // Питомник/Разведение — только если есть реальный НПС (чтобы NpcDialog сработал корректно)
    if ($npcJoyHere) {
        $joyId = (int)$npcJoyHere['id'];

        $pc .= '
        <span class="Addon pcBtn" onclick="NpcDialog('.$joyId.',2,event);" title="Питомник">
          <i class="fa fa-hospital"></i>
          <span class="pcb-lbl">Питомник</span>
        </span>';

        $pc .= '
        <span class="Addon pcBtn" onclick="NpcDialog('.$joyId.',3,event);" title="Разведение">
          <i class="fa fa-heart"></i>
          <span class="pcb-lbl">Разведение</span>
        </span>';
    }
}

/* ===== Мировые боссы (как было) ===== */
$npcBoss = $mysqli->query("SELECT * FROM `base_boss` WHERE `death`='0' and `time` > '" . $time . "' AND `user` = $userId AND `location` = " . intval($location_id['location']))->fetch_assoc();
if ($npcBoss && $getUserLocationInfo["pve"] == 10) {
    $pc .= '<span class="Addon" onclick="BossBattle(' . (int)$npcBoss['id'] . ');"><i class="fa fa-dragon"></i></span>';
}

if (in_array((int)$getUserLocationInfo['id'], [10, 62, 68], true)) {
    $pc .= '<span class="Addon" onclick="openModal(\'web_offline\');"><i class="far fa-spider-web"></i></span>';
}

if ((int)$getUserLocationInfo['id'] === TRAIN_LOCATION_ID) {
    $pc .= '<span class="Addon" onclick="gym_battle();"><i class="fas fa-money-check-edit"></i></span>';
}

/* ===== Арена РБ (НПС 666.php) — быстрый старт / отмена очереди ===== */
$npcRB_any = $mysqli->query("SELECT * FROM `base_npc` WHERE `id`=666")->fetch_assoc();
$npcRB = ($npcRB_any && function_exists('npc_matches_location_row') && npc_matches_location_row($npcRB_any, $currLocId)) ? $npcRB_any : null;

if ($npcRB) {
    $isInBattle = (!empty($location_id['status']) && $location_id['status'] === 'battle');
    if (!$isInBattle) {
        $inQueue = 0;
        if ($q = $mysqli->query("SELECT 1 FROM `rb_queue` WHERE `user_id`=".(int)$userId." LIMIT 1")) {
            $inQueue = (int)$q->num_rows;
        }
        if ($inQueue) {
            $pc .= '<span id="rbArenaBtn" class="Addon" title="Вы в очереди РБ (нажмите, чтобы отменить)" onclick="NpcDialog(666,3,event);"><i class="fas fa-hourglass-half"></i></span>';
        } else {
            $pc .= '<span id="rbArenaBtn" class="Addon" title="Арена РБ: быстрый старт" onclick="NpcDialog(666,2,event);"><i class="fas fa-bolt"></i></span>';
        }
    }
}

// =================== /PC ===================

if ($getUserLocationInfo['control'] != "0,0") {
    $clan = explode(',', $getUserLocationInfo['control']);
    $baseClans = $mysqli->query("SELECT `info` FROM `base_clans` WHERE `id` = '" . intval($clan[0]) . "'")->fetch_assoc();
    $info = json_decode($baseClans['info']);
    $reborn = time() + $clan[1];
    $reborn = downcountermin($reborn);
    $text_control = 'Локация находится под контролем клана ' . $info->name . ' еще ' . $reborn . '!';
    $control = $clan[0];
    $response["control"] = $control;
    $response["control_text"] = $text_control;
}

$response['pc']                = $pc;
$response["location_id"]      = (int)$location_id['location'];
$response["player"] = [
    "user_id"      => (int)$userId,
    "location_id"  => (int)$location_id['location'],
    "map_x"        => (isset($getUserLocationInfo["map_x"]) && $getUserLocationInfo["map_x"] !== null ? (int)$getUserLocationInfo["map_x"] : null),
    "map_y"        => (isset($getUserLocationInfo["map_y"]) && $getUserLocationInfo["map_y"] !== null ? (int)$getUserLocationInfo["map_y"] : null)
];

$response["description"]       = $getUserLocationInfo["description"];
$response["name"]              = $getUserLocationInfo["name"];
$response["pokAtLocation"]     = ($getUserLocationInfo["pve"] == 0 ? 0 : 1);
if (!empty($locations)) $response["roads"] = $locations;
if (!empty($npc))       $response["npc"]   = $npc;
$response["img"]              = $img;

/* Пользователи на локации */
$countUsersAtLocation = $mysqli->query("SELECT `sex`,`id`,`user_group`,`login` FROM `users` WHERE `location`='" . intval($location_id['location']) . "' AND `online` >= '" . $timeOnline . "' ORDER BY `id`");
$response["countUsersAtLocation"] = $countUsersAtLocation->num_rows;
$usersLoc = [];
$userHash = '';
while ($usersInfo = $countUsersAtLocation->fetch_assoc()) {
    $userHash .= 'id' . $usersInfo['id'];
    $userClan = $mysqli->query("SELECT * FROM base_clans_users WHERE user_id = " . intval($usersInfo['id']))->fetch_assoc();
    $userClanQuery = $userClan ? '<div onclick=openClanCard(' . $userClan['clan_id'] . ') class=clanUsers style="background-image:url(/img/world/clans/emblems/' . $userClan['clan_id'] . '.png);"></div>' : '';
    $usersLoc[] = [
        'id'      => $usersInfo['id'],
        'login'   => $usersInfo['login'],
        'group'   => $usersInfo['user_group'],
        'sex'     => $usersInfo['sex'],
        'clan'    => $userClanQuery,
        'isOffer' => isset($userNoticeArr['u' . $usersInfo['id']])
    ];
}
// LIVE PUSH при каждом page load
if (!empty($usersLoc)) {
    socketPush(
        'location_users',
        [
            'location_id' => intval($location_id['location']),
            'users'       => $usersLoc,
            'hash'        => md5($userHash . $noticeHash)
        ]
    );
}
$response["usersAtLocation"] = $usersLoc;

echo (is_array($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : $response);
?>
