<?php
/**
 * admin_unified.php
 * Единая админ-панель в одном файле:
 * - Единый центр логов (admin_logs/log_pokemon/log_game/battle_end/antibot + recharge.log)
 * - Выдача: яйца/предметы/покемоны/атаки
 * - Поиск пользователя + быстрые действия (mute/ban/unban/unmute, setGroup) — реализовано максимально безопасно, с фоллбэками
 *
 * Требования окружения:
 * - /inc/conf/global.php (как в проекте)
 * - желательно /inc/function/Functions.php и /inc/function/Items.php (если есть; подключаем если найдём)
 *
 * Доступ:
 * - id=4 ИЛИ user_group >= 10 и user_group != 100
 */

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project . '/inc/conf/global.php';
$patch_func    = $patch_project . '/inc/function/Functions.php';
$patch_items   = $patch_project . '/inc/function/Items.php';

if (!file_exists($patch_global)) {
    http_response_code(500);
    die('The problem with the connection files.');
}
require_once $patch_global;
if (file_exists($patch_func)) { require_once $patch_func; }
if (file_exists($patch_items)) { require_once $patch_items; }

if (session_status() === PHP_SESSION_NONE) { @session_start(); }

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

if (!function_exists('escapeMe')) {
    function escapeMe($s) { return htmlspecialchars(trim((string)$s), ENT_QUOTES, 'UTF-8'); }
}
function json_out($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}
function is_assoc_array($a) { return is_array($a) && array_keys($a) !== range(0, count($a)-1); }

function table_exists($mysqli, $table) {
    $t = $mysqli->real_escape_string($table);
    $q = $mysqli->query("SHOW TABLES LIKE '{$t}'");
    return $q && $q->num_rows > 0;
}
function table_columns($mysqli, $table) {
    $cols = [];
    $t = $mysqli->real_escape_string($table);
    $q = $mysqli->query("SHOW COLUMNS FROM `{$t}`");
    if ($q) { while ($r = $q->fetch_assoc()) { $cols[] = $r['Field']; } }
    return $cols;
}
function pick_first_existing($cols, $candidates) {
    foreach ($candidates as $c) { if (in_array($c, $cols, true)) return $c; }
    return null;
}
function csrf_init() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
function csrf_check() {
    $tok = $_POST['csrf_token'] ?? '';
    return is_string($tok) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $tok);
}

csrf_init();

// --- Аутентификация и права ---
$user_id = (int)($_SESSION['id'] ?? 0);
if ($user_id <= 0) {
    http_response_code(403);
    die('Доступ запрещён: нет сессии.');
}

$userRow = null;
if (isset($mysqli)) {
    $res = $mysqli->query("SELECT * FROM users WHERE id = {$user_id} LIMIT 1");
    if ($res) { $userRow = $res->fetch_assoc(); }
}
if (!$userRow) {
    http_response_code(403);
    die('Доступ запрещён: пользователь не найден.');
}
$user_group = (int)($userRow['user_group'] ?? 0);

$allowed = ($user_id === 4) || ($user_group >= 10 && $user_group != 100);
if (!$allowed) {
    http_response_code(403);
    die('Доступ запрещён: недостаточно прав.');
}

// --- Настройки ---
define('RECHARGE_LOG_PATH_OVERRIDE', ''); // при необходимости: абсолютный путь к recharge.log

function resolve_recharge_log_path($patch_project) {
    if (RECHARGE_LOG_PATH_OVERRIDE) return RECHARGE_LOG_PATH_OVERRIDE;

    $candidates = [
        $patch_project . '/inc/log/recharge.log',
        $patch_project . '/logs/recharge.log',
        $patch_project . '/recharge.log',
        dirname(__FILE__) . '/recharge.log',
        dirname(__FILE__) . '/../recharge.log',
    ];
    foreach ($candidates as $p) {
        if (is_string($p) && file_exists($p) && is_readable($p)) return $p;
    }
    return null;
}

// --- Вспомогательные: разбор "ban/mute" сериализованных полей ---
function safe_unserialize_any($v) {
    if ($v === null) return [];
    if (is_array($v)) return $v;
    $v = (string)$v;
    $v = trim($v);
    if ($v === '' || $v === '0') return [];

    // JSON?
    $j = json_decode($v, true);
    if (is_array($j)) return $j;

    // PHP serialize?
    $tmp = @unserialize($v);
    if (is_array($tmp)) return $tmp;

    return [];
}
function safe_serialize_back($arr, $original) {
    // Если исходник выглядел как JSON — вернём JSON, иначе serialize.
    $o = (string)$original;
    $o = trim($o);
    $looksJson = (strlen($o) > 0 && ($o[0] === '{' || $o[0] === '['));
    if ($looksJson) return json_encode($arr, JSON_UNESCAPED_UNICODE);
    return serialize($arr);
}
function now_ts() { return time(); }

// --- POST API (AJAX) ---
$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
if ($action !== '') {
    if (!csrf_check()) {
        json_out(['ok'=>false,'error'=>'csrf','message'=>'Ошибка безопасности: неверный CSRF токен.']);
    }

    // helpers
    $db = $mysqli;

    if ($action === 'user_lookup') {
        $q = trim((string)($_POST['q'] ?? ''));
        if ($q === '') json_out(['ok'=>false,'message'=>'Пустой запрос.']);
        $qEsc = $db->real_escape_string($q);

        $where = "id=".(int)$q;
        if (!ctype_digit($q)) {
            $where = "login LIKE '%{$qEsc}%'";
        }

        $r = $db->query("SELECT id, login, user_group, status, rang, ban, mute FROM users WHERE {$where} ORDER BY id DESC LIMIT 50");
        $rows = [];
        if ($r) while($row=$r->fetch_assoc()) {
            $row['ban_parsed'] = safe_unserialize_any($row['ban']);
            $row['mute_parsed'] = safe_unserialize_any($row['mute']);
            $rows[] = $row;
        }
        json_out(['ok'=>true,'rows'=>$rows]);
    }

    if ($action === 'moderation_update') {
        $target = (int)($_POST['user_id'] ?? 0);
        $mode = (string)($_POST['mode'] ?? '');
        $minutes = (int)($_POST['minutes'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));

        if ($target<=0) json_out(['ok'=>false,'message'=>'Некорректный user_id.']);
        $u = $db->query("SELECT * FROM users WHERE id={$target} LIMIT 1")->fetch_assoc();
        if (!$u) json_out(['ok'=>false,'message'=>'Пользователь не найден.']);

        $ban = safe_unserialize_any($u['ban'] ?? '');
        $mute = safe_unserialize_any($u['mute'] ?? '');

        $ts = now_ts();

        if ($mode === 'mute') {
            if ($minutes<=0) $minutes = 60;
            $mute['chat'] = $ts + $minutes*60;
        } elseif ($mode === 'unmute') {
            $mute['chat'] = 0;
        } elseif ($mode === 'ban') {
            if ($minutes<=0) $minutes = 24*60;
            $ban['game'] = $ts + $minutes*60;
            // статус (если поле существует) — мягко
        } elseif ($mode === 'unban') {
            $ban['game'] = 0;
            if (isset($ban['chat'])) $ban['chat'] = 0;
        } elseif ($mode === 'set_group') {
            $newGroup = (int)($_POST['new_group'] ?? 0);
            if ($newGroup <= 0) json_out(['ok'=>false,'message'=>'Некорректная группа.']);
            $db->query("UPDATE users SET user_group={$newGroup} WHERE id={$target} LIMIT 1");
            // audit
            if (table_exists($db,'admin_logs')) {
                $adminId = (int)($_SESSION['id'] ?? 0);
                $act = $db->real_escape_string('set_group');
                $det = $db->real_escape_string("user={$target}; new_group={$newGroup}; reason={$reason}");
                $db->query("INSERT INTO admin_logs (admin_id, action, target_id, details, created_at) VALUES ({$adminId}, '{$act}', {$target}, '{$det}', NOW())");
            }
            json_out(['ok'=>true,'message'=>'Группа обновлена.']);
        } else {
            json_out(['ok'=>false,'message'=>'Неизвестный режим.']);
        }

        $banStr = safe_serialize_back($ban, $u['ban'] ?? '');
        $muteStr = safe_serialize_back($mute, $u['mute'] ?? '');

        $banStrEsc = $db->real_escape_string($banStr);
        $muteStrEsc = $db->real_escape_string($muteStr);

        $db->query("UPDATE users SET ban='{$banStrEsc}', mute='{$muteStrEsc}' WHERE id={$target} LIMIT 1");

        // audit
        if (table_exists($db,'admin_logs')) {
            $adminId = (int)($_SESSION['id'] ?? 0);
            $act = $db->real_escape_string($mode);
            $det = $db->real_escape_string("user={$target}; minutes={$minutes}; reason={$reason}");
            // гибкие поля
            $cols = table_columns($db,'admin_logs');
            $has_details = in_array('details',$cols,true);
            $has_created_at = in_array('created_at',$cols,true);
            $has_timestamp = in_array('timestamp',$cols,true);

            if ($has_details && $has_created_at) {
                $db->query("INSERT INTO admin_logs (admin_id, action, target_id, details, created_at) VALUES ({$adminId}, '{$act}', {$target}, '{$det}', NOW())");
            } elseif ($has_timestamp) {
                $db->query("INSERT INTO admin_logs (admin_id, action, target_id, timestamp) VALUES ({$adminId}, '{$act}', {$target}, NOW())");
            } else {
                // best-effort
                $db->query("INSERT INTO admin_logs (admin_id, action, target_id) VALUES ({$adminId}, '{$act}', {$target})");
            }
        }

        json_out(['ok'=>true,'message'=>'Готово.']);
    }

    if ($action === 'give_egg') {
        $targetMode = (string)($_POST['target_mode'] ?? 'one'); // one|many|all
        $userIdsRaw = trim((string)($_POST['user_ids'] ?? ''));
        $countPerUser = max(1, (int)($_POST['count'] ?? 1));

        $pokemon = trim((string)($_POST['pokemon'] ?? '')); // id or name
        $form = trim((string)($_POST['form'] ?? ''));
        $shineMode = (string)($_POST['shine'] ?? 'random'); // 0|1|random
        $trade = (int)($_POST['trade'] ?? 0);
        $sparka = (int)($_POST['sparka'] ?? 0);
        $days = (int)($_POST['days'] ?? 0);
        $gens = trim((string)($_POST['gens'] ?? ''));
        $character = trim((string)($_POST['character'] ?? ''));

        // resolve recipients
        $recipients = [];
        if ($targetMode === 'all') {
            $r = $db->query("SELECT id FROM users");
            if ($r) while($row=$r->fetch_assoc()) $recipients[] = (int)$row['id'];
        } else {
            $raw = preg_split('/[,\s]+/', $userIdsRaw, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($raw as $x) {
                $id = (int)$x;
                if ($id>0) $recipients[] = $id;
            }
            $recipients = array_values(array_unique($recipients));
        }
        if (!$recipients) json_out(['ok'=>false,'message'=>'Не выбраны получатели.']);

        // resolve basenum
        $basenum = false;
        if ($pokemon !== '') {
            if (ctype_digit($pokemon)) {
                $basenum = (int)$pokemon;
            } else {
                // try base_pokemons by name_rus/name
                if (table_exists($db,'base_pokemons')) {
                    $pEsc = $db->real_escape_string($pokemon);
                    $q = $db->query("SELECT num FROM base_pokemons WHERE name_rus='{$pEsc}' OR name='{$pEsc}' LIMIT 1");
                    if ($q && $q->num_rows>0) { $basenum = (int)$q->fetch_assoc()['num']; }
                }
            }
        }

        $ok=0; $fail=0; $errors=[];
        foreach ($recipients as $uid) {
            for ($i=0; $i<$countPerUser; $i++) {
                $shine = false;
                if ($shineMode === '1') $shine = 1;
                elseif ($shineMode === '0') $shine = 0;
                else $shine = false;

                $formVal = ($form === '' ? false : $form);
                $gensVal = ($gens === '' ? false : $gens);
                $charVal = ($character === '' ? false : $character);

                $daysVal = ($days>0 ? $days : false);
                // prefer plusEgg() if exists
                try {
                    if (function_exists('plusEgg') && ($shineMode !== '0') && ($daysVal === false)) {
                        // plusEgg($gens=false,$character=false,$shine=false,$trade=false,$basenum=false,$sparka=false,$userEgg=false,$form=false)
                        plusEgg($gensVal, $charVal, ($shine===0?false:$shine), ($trade?1:0), ($basenum?$basenum:false), ($sparka?1:0), $uid, $formVal);
                        $ok++;
                    } else {
                        // fallback direct insert
                        if (!table_exists($db,'user_egg')) throw new Exception('Нет user_egg и нет plusEgg().');
                        $t = now_ts();
                        $time_end = $t + (($daysVal!==false ? (int)$daysVal : rand(5,11)) * 24 * 3600);
                        $gensIns = $gensVal ? $gensVal : '10,10,10,10,10,10';
                        $charIns = $charVal ? $charVal : 1;
                        $shineIns = ($shine===1?1:0);
                        $tradeIns = ($trade?1:0);
                        $sparkaIns = ($sparka?1:0);
                        $basenumIns = ($basenum?$basenum:0);
                        $eggBasenumIns = $basenumIns;
                        if (table_exists($db,'base_pokemons')) {
                            $bpCols = table_columns($db,'base_pokemons');
                            if (in_array('eggBasenum', $bpCols, true)) {
                                if ($basenumIns > 0) {
                                    $qq = $db->query("SELECT eggBasenum FROM base_pokemons WHERE num=".(int)$basenumIns." LIMIT 1");
                                    if ($qq && $qq->num_rows > 0) { $eggBasenumIns = (int)$qq->fetch_assoc()['eggBasenum']; }
                                } else {
                                    $qq = $db->query("SELECT eggBasenum FROM base_pokemons WHERE eggBasenum IS NOT NULL AND eggBasenum>0 ORDER BY RAND() LIMIT 1");
                                    if ($qq && $qq->num_rows > 0) { $eggBasenumIns = (int)$qq->fetch_assoc()['eggBasenum']; }
                                }
                            }
                        }
                        $formIns = ($formVal!==false ? $formVal : 0);

                        $stmt = $db->prepare("INSERT INTO user_egg (gens, character, shine, time_start, time_end, trade, user_id, eggBasenum, sparka, form) VALUES (?,?,?,?,?,?,?,?,?,?)");
                        $stmt->bind_param("siiiiiiiii", $gensIns, $charIns, $shineIns, $t, $time_end, $tradeIns, $uid, $eggBasenumIns, $sparkaIns, $formIns);
                        $stmt->execute();
                        $ok++;
                    }
                } catch (Throwable $e) {
                    $fail++;
                    $errors[] = "uid={$uid}: ".$e->getMessage();
                }
            }
        }

        json_out(['ok'=>($fail===0),'message'=>"Готово. Успешно: {$ok}; Ошибок: {$fail}", 'errors'=>$errors]);
    }

    if ($action === 'give_item') {
        $targetMode = (string)($_POST['target_mode'] ?? 'one'); // one|many|all
        $userIdsRaw = trim((string)($_POST['user_ids'] ?? ''));
        $item = trim((string)($_POST['item'] ?? '')); // id or name
        $count = max(1, (int)($_POST['count'] ?? 1));
        $trophy = (int)($_POST['trophy'] ?? 0);

        // resolve recipients
        $recipients = [];
        if ($targetMode === 'all') {
            $r = $db->query("SELECT id FROM users");
            if ($r) while($row=$r->fetch_assoc()) $recipients[] = (int)$row['id'];
        } else {
            $raw = preg_split('/[,\s]+/', $userIdsRaw, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($raw as $x) { $id=(int)$x; if($id>0) $recipients[]=$id; }
            $recipients = array_values(array_unique($recipients));
        }
        if (!$recipients) json_out(['ok'=>false,'message'=>'Не выбраны получатели.']);

        // resolve item_id
        $itemId = 0;
        if (ctype_digit($item)) {
            $itemId = (int)$item;
        } else {
            if (table_exists($db,'base_items')) {
                $iEsc = $db->real_escape_string($item);
                $q = $db->query("SELECT id FROM base_items WHERE name='{$iEsc}' OR name_rus='{$iEsc}' LIMIT 1");
                if ($q && $q->num_rows>0) { $itemId = (int)$q->fetch_assoc()['id']; }
            }
        }
        if ($itemId<=0) json_out(['ok'=>false,'message'=>'Предмет не найден (id/name).']);

        $ok=0; $fail=0; $errors=[];
        foreach ($recipients as $uid) {
            try {
                if ($trophy && table_exists($db,'items_users')) {
                    $stmt=$db->prepare("INSERT INTO items_users (item_id, count, user, trophy) VALUES (?,?,?,1)");
                    $stmt->bind_param("iii", $itemId, $count, $uid);
                    $stmt->execute();
                } else {
                    if (function_exists('itemAdd')) {
                        itemAdd($itemId, $count, $uid);
                    } elseif (table_exists($db,'items_users')) {
                        $stmt=$db->prepare("INSERT INTO items_users (item_id, count, user, trophy) VALUES (?,?,?,0)");
                        $stmt->bind_param("iii", $itemId, $count, $uid);
                        $stmt->execute();
                    } else {
                        throw new Exception('Нет itemAdd() и нет таблицы items_users.');
                    }
                }

                if (table_exists($db,'log_pokemon')) {
                    $name = (string)$item;
                    $nameEsc = $db->real_escape_string($name . ' x'.$count);
                    $date = $db->real_escape_string(date('d.m H:i'));
                    $db->query("INSERT INTO log_pokemon (`date`,`pok`,`pok_id`,`type`) VALUES ('{$date}','{$nameEsc}','{$uid}','items')");
                }
                $ok++;
            } catch (Throwable $e) {
                $fail++; $errors[]="uid={$uid}: ".$e->getMessage();
            }
        }

        json_out(['ok'=>($fail===0),'message'=>"Готово. Успешно: {$ok}; Ошибок: {$fail}", 'errors'=>$errors]);
    }

    if ($action === 'give_attack') {
        $pokeId = (int)($_POST['poke_id'] ?? 0);
        $attack = trim((string)($_POST['attack'] ?? '')); // id or name
        if ($pokeId<=0) json_out(['ok'=>false,'message'=>'Некорректный ID покемона.']);

        if (!table_exists($db,'user_pokemons')) json_out(['ok'=>false,'message'=>'Нет таблицы user_pokemons.']);
        $p = $db->query("SELECT id FROM user_pokemons WHERE id={$pokeId} LIMIT 1")->fetch_assoc();
        if (!$p) json_out(['ok'=>false,'message'=>'Покемон не найден.']);

        $attackId=0;
        if (ctype_digit($attack)) {
            $attackId=(int)$attack;
        } else {
            if (table_exists($db,'base_atk')) {
                $aEsc=$db->real_escape_string($attack);
                $q=$db->query("SELECT id FROM base_atk WHERE name='{$aEsc}' OR name_rus='{$aEsc}' LIMIT 1");
                if ($q && $q->num_rows>0) $attackId=(int)$q->fetch_assoc()['id'];
            }
        }
        if ($attackId<=0) json_out(['ok'=>false,'message'=>'Атака не найдена.']);

        if (!table_exists($db,'user_pokemons_tm')) json_out(['ok'=>false,'message'=>'Нет таблицы user_pokemons_tm.']);
        $stmt=$db->prepare("INSERT INTO user_pokemons_tm (`pok`,`attacks`) VALUES (?,?)");
        $stmt->bind_param("ii", $pokeId, $attackId);
        $stmt->execute();

        if (table_exists($db,'log_pokemon')) {
            $date = $db->real_escape_string(date('d.m H:i'));
            $nm = $db->real_escape_string($attack);
            $db->query("INSERT INTO log_pokemon (`date`,`pok`,`pok_id`,`type`) VALUES ('{$date}','{$nm}','{$pokeId}','attack')");
        }

        json_out(['ok'=>true,'message'=>'Атака добавлена.']);
    }

    if ($action === 'give_pokemon') {
        $targetMode = (string)($_POST['target_mode'] ?? 'one'); // one|many|all
        $userIdsRaw = trim((string)($_POST['user_ids'] ?? ''));
        $pokemon = trim((string)($_POST['pokemon'] ?? '')); // id or name
        $lvl = max(1, (int)($_POST['lvl'] ?? 1));
        $attacksRaw = trim((string)($_POST['attacks'] ?? '')); // "1,2,3,4"
        $genRaw = trim((string)($_POST['gen'] ?? '')); // "10,10,10,10,10,10"

        // recipients
        $recipients = [];
        if ($targetMode === 'all') {
            $r = $db->query("SELECT id FROM users");
            if ($r) while($row=$r->fetch_assoc()) $recipients[] = (int)$row['id'];
        } else {
            $raw = preg_split('/[,\s]+/', $userIdsRaw, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($raw as $x) { $id=(int)$x; if($id>0) $recipients[]=$id; }
            $recipients = array_values(array_unique($recipients));
        }
        if (!$recipients) json_out(['ok'=>false,'message'=>'Не выбраны получатели.']);

        // basenum
        $basenum = 0;
        if (ctype_digit($pokemon)) $basenum=(int)$pokemon;
        else if (table_exists($db,'base_pokemons')) {
            $pEsc=$db->real_escape_string($pokemon);
            $q=$db->query("SELECT num FROM base_pokemons WHERE name_rus='{$pEsc}' OR name='{$pEsc}' LIMIT 1");
            if ($q && $q->num_rows>0) $basenum=(int)$q->fetch_assoc()['num'];
        }
        if ($basenum<=0) json_out(['ok'=>false,'message'=>'Покемон не найден (id/name).']);

        // attacks list
        $attacks = [];
        if ($attacksRaw !== '') {
            foreach (preg_split('/[,\s]+/', $attacksRaw, -1, PREG_SPLIT_NO_EMPTY) as $a) {
                if (ctype_digit($a)) $attacks[] = (int)$a;
            }
        }
        while (count($attacks) < 4) $attacks[] = 0;
        $attacks = array_slice($attacks, 0, 4);

        $gen = ($genRaw !== '' ? $genRaw : '10,10,10,10,10,10');

        if (!table_exists($db,'user_pokemons')) json_out(['ok'=>false,'message'=>'Нет таблицы user_pokemons.']);

        $ok=0; $fail=0; $errors=[];
        foreach ($recipients as $uid) {
            try {
                $newId = 0;
                if (function_exists('addPokemonToUser')) {
                    $newId = (int)addPokemonToUser($uid, $basenum, $lvl, $attacks, $gen);
                } else {
                    $attStr = implode(',', $attacks);
                    // best-effort insert с минимальными полями — рассчитываем на DEFAULTS
                    $stmt = $db->prepare("INSERT INTO user_pokemons (user_id, basenum, lvl, attacks, gen) VALUES (?,?,?,?,?)");
                    if (!$stmt) throw new Exception('Не удалось подготовить INSERT (проверьте схему user_pokemons).');
                    $stmt->bind_param("iiiss", $uid, $basenum, $lvl, $attStr, $gen);
                    $stmt->execute();
                    $newId = (int)$db->insert_id;
                }

                if (table_exists($db,'log_pokemon')) {
                    $date = $db->real_escape_string(date('d.m H:i'));
                    $nm = $db->real_escape_string($pokemon);
                    $db->query("INSERT INTO log_pokemon (`date`,`pok`,`pok_id`,`type`) VALUES ('{$date}','{$nm}','{$newId}','pok')");
                }
                $ok++;
            } catch (Throwable $e) {
                $fail++; $errors[]="uid={$uid}: ".$e->getMessage();
            }
        }
        json_out(['ok'=>($fail===0),'message'=>"Готово. Успешно: {$ok}; Ошибок: {$fail}", 'errors'=>$errors]);
    }

    if ($action === 'get_logs') {
        $source = trim((string)($_POST['source'] ?? 'unified'));
        $q = trim((string)($_POST['q'] ?? ''));
        $limit = min(500, max(20, (int)($_POST['limit'] ?? 200)));
        $offset = max(0, (int)($_POST['offset'] ?? 0));

        $rows = [];
        $meta = ['source'=>$source,'limit'=>$limit,'offset'=>$offset];

        $qEsc = $db->real_escape_string($q);

        // unified feed
        if ($source === 'unified') {
            $parts = [];

            // log_pokemon
            if (table_exists($db,'log_pokemon')) {
                $cols = table_columns($db,'log_pokemon');
                $dateCol = pick_first_existing($cols, ['date','dt','created_at','time']);
                $pokCol = pick_first_existing($cols, ['pok','text','info','message']);
                $typeCol = pick_first_existing($cols, ['type','action']);
                $idCol = pick_first_existing($cols, ['id','pok_id']);
                $where = "1=1";
                if ($q !== '') {
                    $where .= " AND (".($pokCol?$pokCol:'pok')." LIKE '%{$qEsc}%' OR ".($typeCol?$typeCol:'type')." LIKE '%{$qEsc}%')";
                }
                $parts[] = "SELECT ".
                    ($dateCol?"{$dateCol}":"''")." AS ts, ".
                    "'log_pokemon' AS src, ".
                    "NULL AS actor_id, ".
                    ($idCol?"{$idCol}":"NULL")." AS ref_id, ".
                    ($typeCol?"{$typeCol}":"''")." AS action, ".
                    ($pokCol?"{$pokCol}":"''")." AS message ".
                    "FROM log_pokemon WHERE {$where}";
            }

            // log_game
            if (table_exists($db,'log_game')) {
                $cols = table_columns($db,'log_game');
                $dateCol = pick_first_existing($cols, ['date','dt','created_at','time']);
                $typeCol = pick_first_existing($cols, ['type','action']);
                $infoCol = pick_first_existing($cols, ['info','text','message','data']);
                $userCol = pick_first_existing($cols, ['user_id','user']);
                $idCol   = pick_first_existing($cols, ['id']);
                $where = "1=1";
                if ($q !== '') {
                    $where .= " AND (".($infoCol?$infoCol:'info')." LIKE '%{$qEsc}%' OR ".($typeCol?$typeCol:'type')." LIKE '%{$qEsc}%')";
                }
                $parts[] = "SELECT ".
                    ($dateCol?"{$dateCol}":"''")." AS ts, ".
                    "'log_game' AS src, ".
                    ($userCol?"{$userCol}":"NULL")." AS actor_id, ".
                    ($idCol?"{$idCol}":"NULL")." AS ref_id, ".
                    ($typeCol?"{$typeCol}":"''")." AS action, ".
                    ($infoCol?"{$infoCol}":"''")." AS message ".
                    "FROM log_game WHERE {$where}";
            }

            // battle_end
            if (table_exists($db,'battle_end')) {
                $cols = table_columns($db,'battle_end');
                $dateCol = pick_first_existing($cols, ['date','dt','created_at','time','end_time']);
                $userCol = pick_first_existing($cols, ['user_id','user']);
                $idCol   = pick_first_existing($cols, ['id']);
                $infoCol = pick_first_existing($cols, ['info','result','data','log']);
                $where = "1=1";
                if ($q !== '') {
                    if ($infoCol) $where .= " AND {$infoCol} LIKE '%{$qEsc}%'";
                }
                $parts[] = "SELECT ".
                    ($dateCol?"{$dateCol}":"''")." AS ts, ".
                    "'battle_end' AS src, ".
                    ($userCol?"{$userCol}":"NULL")." AS actor_id, ".
                    ($idCol?"{$idCol}":"NULL")." AS ref_id, ".
                    "'battle' AS action, ".
                    ($infoCol?"{$infoCol}":"''")." AS message ".
                    "FROM battle_end WHERE {$where}";
            }

            // antibot_events
            if (table_exists($db,'antibot_events')) {
                $cols = table_columns($db,'antibot_events');
                $dateCol = pick_first_existing($cols, ['created_at','time','date','dt']);
                $userCol = pick_first_existing($cols, ['user_id','user']);
                $typeCol = pick_first_existing($cols, ['type','action','event']);
                $msgCol  = pick_first_existing($cols, ['message','info','data']);
                $idCol   = pick_first_existing($cols, ['id']);
                $where = "1=1";
                if ($q !== '') {
                    $where .= " AND (".($msgCol?$msgCol:'message')." LIKE '%{$qEsc}%' OR ".($typeCol?$typeCol:'type')." LIKE '%{$qEsc}%')";
                }
                $parts[] = "SELECT ".
                    ($dateCol?"{$dateCol}":"''")." AS ts, ".
                    "'antibot_events' AS src, ".
                    ($userCol?"{$userCol}":"NULL")." AS actor_id, ".
                    ($idCol?"{$idCol}":"NULL")." AS ref_id, ".
                    ($typeCol?"{$typeCol}":"''")." AS action, ".
                    ($msgCol?"{$msgCol}":"''")." AS message ".
                    "FROM antibot_events WHERE {$where}";
            }

            // admin_logs
            if (table_exists($db,'admin_logs')) {
                $cols = table_columns($db,'admin_logs');
                $dateCol = pick_first_existing($cols, ['created_at','timestamp','date','dt','time']);
                $adminCol = pick_first_existing($cols, ['admin_id','user_id','admin']);
                $actCol = pick_first_existing($cols, ['action','type']);
                $tgtCol = pick_first_existing($cols, ['target_id','target','user']);
                $detCol = pick_first_existing($cols, ['details','info','message']);
                $where = "1=1";
                if ($q !== '') {
                    $where .= " AND (".($detCol?$detCol:$actCol)." LIKE '%{$qEsc}%' OR ".($actCol?$actCol:'action')." LIKE '%{$qEsc}%')";
                }
                $parts[] = "SELECT ".
                    ($dateCol?"{$dateCol}":"''")." AS ts, ".
                    "'admin_logs' AS src, ".
                    ($adminCol?"{$adminCol}":"NULL")." AS actor_id, ".
                    ($tgtCol?"{$tgtCol}":"NULL")." AS ref_id, ".
                    ($actCol?"{$actCol}":"''")." AS action, ".
                    ($detCol?$detCol:"''")." AS message ".
                    "FROM admin_logs WHERE {$where}";
            }

            if (!$parts) json_out(['ok'=>true,'rows'=>[], 'meta'=>$meta, 'message'=>'Нет доступных таблиц логов в БД.']);

            $sql = "(".implode(") UNION ALL (", $parts).") ORDER BY ts DESC LIMIT {$limit} OFFSET {$offset}";
            $r = $db->query($sql);
            if ($r) while($row=$r->fetch_assoc()) $rows[]=$row;

            json_out(['ok'=>true,'rows'=>$rows,'meta'=>$meta]);
        }

        // single table mode
        $allowedSources = ['admin_logs','log_pokemon','log_game','battle_end','antibot_events'];
        if (!in_array($source, $allowedSources, true)) {
            json_out(['ok'=>false,'message'=>'Неизвестный источник.']);
        }
        if (!table_exists($db,$source)) json_out(['ok'=>true,'rows'=>[],'meta'=>$meta,'message'=>'Таблица не найдена.']);

        $cols = table_columns($db,$source);
        $textCols = array_values(array_filter($cols, function($c){
            return in_array($c, ['info','message','text','data','result','pok','type','action','details','login'], true) || stripos($c,'info')!==false;
        }));
        if (!$textCols) $textCols = $cols;

        $where = "1=1";
        if ($q !== '') {
            $likeParts = [];
            foreach (array_slice($textCols,0,8) as $c) {
                $cEsc = preg_replace('/[^a-zA-Z0-9_]/','', $c);
                $likeParts[] = "`{$cEsc}` LIKE '%{$qEsc}%'";
            }
            $where .= " AND (".implode(" OR ", $likeParts).")";
        }

        $sql = "SELECT * FROM `{$source}` WHERE {$where} ORDER BY 1 DESC LIMIT {$limit} OFFSET {$offset}";
        $r = $db->query($sql);
        if ($r) while($row=$r->fetch_assoc()) $rows[]=$row;

        json_out(['ok'=>true,'rows'=>$rows,'meta'=>$meta]);
    }

    if ($action === 'get_recharge_log') {
        $path = resolve_recharge_log_path($patch_project);
        if (!$path) json_out(['ok'=>false,'message'=>'recharge.log не найден. Укажите RECHARGE_LOG_PATH_OVERRIDE в файле.']);
        $tail = min(2000, max(50, (int)($_POST['tail'] ?? 400)));
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) json_out(['ok'=>false,'message'=>'Не удалось прочитать файл лога.']);
        $slice = array_slice($lines, -$tail);
        json_out(['ok'=>true,'path'=>$path,'text'=>implode("\n",$slice)]);
    }

    json_out(['ok'=>false,'message'=>'Неизвестное действие.']);
}

// --- HTML ---
$csrf = $_SESSION['csrf_token'];
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Единая админ-панель</title>
<style>
:root{--bg:#0f1220;--card:#171a2b;--card2:#101324;--text:#eef1ff;--muted:#aab0d6;--line:#2a2f52;--acc:#7c5cff;--bad:#ff5c7a;--good:#44d18f;}
*{box-sizing:border-box}
body{margin:0;background:linear-gradient(180deg,#0b0e1a, #0f1220);color:var(--text);font:14px/1.35 system-ui,-apple-system,Segoe UI,Roboto,Arial}
a{color:inherit}
.wrap{max-width:1200px;margin:0 auto;padding:18px}
.top{display:flex;align-items:center;gap:12px;justify-content:space-between;margin-bottom:14px}
.brand{font-weight:700;letter-spacing:.2px}
.badge{font-size:12px;color:var(--muted)}
.tabs{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0}
.tabbtn{border:1px solid var(--line);background:transparent;color:var(--text);padding:9px 12px;border-radius:10px;cursor:pointer}
.tabbtn.active{background:rgba(124,92,255,.18);border-color:rgba(124,92,255,.55)}
.card{background:linear-gradient(180deg,var(--card),var(--card2));border:1px solid var(--line);border-radius:14px;padding:14px;margin:12px 0}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:10px}
.col-12{grid-column:span 12}
.col-6{grid-column:span 6}
.col-4{grid-column:span 4}
.col-3{grid-column:span 3}
.col-2{grid-column:span 2}
@media(max-width:900px){.col-6,.col-4,.col-3,.col-2{grid-column:span 12}}
label{display:block;font-size:12px;color:var(--muted);margin:0 0 6px}
input,select,textarea{width:100%;background:#0c0f1e;color:var(--text);border:1px solid var(--line);border-radius:10px;padding:10px}
textarea{min-height:84px;resize:vertical}
.btn{display:inline-flex;align-items:center;gap:8px;background:var(--acc);border:0;color:#fff;padding:10px 12px;border-radius:10px;cursor:pointer;font-weight:650}
.btn.secondary{background:transparent;border:1px solid var(--line);color:var(--text)}
.btn.danger{background:var(--bad)}
.btn.good{background:var(--good);color:#08110b}
.row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.small{font-size:12px;color:var(--muted)}
.hr{height:1px;background:var(--line);margin:12px 0}
.table{width:100%;border-collapse:collapse;border-radius:12px;overflow:hidden}
.table th,.table td{border-bottom:1px solid var(--line);padding:8px 10px;vertical-align:top}
.table th{font-size:12px;color:var(--muted);text-align:left;background:rgba(255,255,255,.03)}
.pill{display:inline-block;padding:2px 8px;border-radius:999px;border:1px solid var(--line);font-size:12px;color:var(--muted)}
.ok{color:var(--good)}
.err{color:var(--bad)}
.hidden{display:none}
pre{white-space:pre-wrap;word-break:break-word;background:#0c0f1e;border:1px solid var(--line);padding:10px;border-radius:12px;max-height:520px;overflow:auto}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div>
      <div class="brand">Единая админ-панель</div>
      <div class="badge">Вы вошли как <span class="pill"><?php echo escapeMe($userRow['login'] ?? ('id='.$user_id)); ?></span> · group <span class="pill"><?php echo (int)$user_group; ?></span></div>
    </div>
    <div class="small">Файл: <span class="pill"><?php echo escapeMe(basename(__FILE__)); ?></span></div>
  </div>

  <div class="tabs">
    <button class="tabbtn active" data-tab="logs">Логи</button>
    <button class="tabbtn" data-tab="give">Выдача</button>
    <button class="tabbtn" data-tab="users">Пользователь</button>
    <button class="tabbtn" data-tab="moder">Модерация</button>
    <button class="tabbtn" data-tab="recharge">recharge.log</button>
  </div>

  <div id="tab-logs" class="tab card">
    <div class="grid">
      <div class="col-3">
        <label>Источник</label>
        <select id="logs_source">
          <option value="unified">Unified (все таблицы)</option>
          <option value="admin_logs">admin_logs</option>
          <option value="log_pokemon">log_pokemon</option>
          <option value="log_game">log_game</option>
          <option value="battle_end">battle_end</option>
          <option value="antibot_events">antibot_events</option>
        </select>
      </div>
      <div class="col-6">
        <label>Поиск</label>
        <input id="logs_q" placeholder="login / id / текст / type / действие">
      </div>
      <div class="col-3">
        <label>Лимит</label>
        <select id="logs_limit">
          <option>50</option><option selected>200</option><option>500</option>
        </select>
      </div>
      <div class="col-12 row">
        <button class="btn" onclick="loadLogs(0)">Показать</button>
        <button class="btn secondary" onclick="downloadCSV()">Экспорт CSV (текущая выдача)</button>
        <span class="small" id="logs_status"></span>
      </div>
      <div class="col-12">
        <div class="hr"></div>
        <div id="logs_table_wrap" class="small">Нажмите “Показать”.</div>
      </div>
    </div>
  </div>

  <div id="tab-give" class="tab card hidden">
    <div class="grid">
      <div class="col-12"><div class="pill">Выдача яиц</div></div>

      <div class="col-3">
        <label>Получатели</label>
        <select id="egg_target_mode">
          <option value="one">Указанные ID</option>
          <option value="all">Всем</option>
        </select>
      </div>
      <div class="col-6">
        <label>ID пользователей (через запятую/пробел)</label>
        <input id="egg_user_ids" placeholder="Например: 12, 34, 56">
      </div>
      <div class="col-3">
        <label>Кол-во яиц на пользователя</label>
        <input id="egg_count" type="number" value="1" min="1">
      </div>

      <div class="col-4">
        <label>Покемон (ID или имя/имя_rus; пусто = случайное)</label>
        <input id="egg_pokemon" placeholder="25 или Пикачу">
      </div>
      <div class="col-2">
        <label>Форма</label>
        <input id="egg_form" placeholder="0/1/2...">
      </div>
      <div class="col-2">
        <label>Shine</label>
        <select id="egg_shine">
          <option value="random" selected>random</option>
          <option value="0">0</option>
          <option value="1">1</option>
        </select>
      </div>
      <div class="col-2">
        <label>trade</label>
        <select id="egg_trade"><option value="0" selected>false</option><option value="1">true</option></select>
      </div>
      <div class="col-2">
        <label>sparka</label>
        <select id="egg_sparka"><option value="0" selected>0</option><option value="1">1</option></select>
      </div>

      <div class="col-3">
        <label>Срок вылупления (дней; 0=рандом 5–11)</label>
        <input id="egg_days" type="number" value="0" min="0">
      </div>
      <div class="col-3">
        <label>gens (опц.)</label>
        <input id="egg_gens" placeholder="10,10,10,10,10,10">
      </div>
      <div class="col-3">
        <label>character (опц.)</label>
        <input id="egg_character" placeholder="1..26">
      </div>
      <div class="col-12 row">
        <button class="btn good" onclick="giveEgg()">Выдать яйца</button>
        <span class="small" id="egg_status"></span>
      </div>

      <div class="col-12"><div class="hr"></div></div>
      <div class="col-12"><div class="pill">Выдача предметов</div></div>

      <div class="col-3">
        <label>Получатели</label>
        <select id="item_target_mode">
          <option value="one">Указанные ID</option>
          <option value="all">Всем</option>
        </select>
      </div>
      <div class="col-6">
        <label>ID пользователей</label>
        <input id="item_user_ids" placeholder="12, 34, 56">
      </div>
      <div class="col-3">
        <label>Кол-во</label>
        <input id="item_count" type="number" value="1" min="1">
      </div>
      <div class="col-6">
        <label>Предмет (ID или name/name_rus)</label>
        <input id="item_item" placeholder="1 или 'Покебол'">
      </div>
      <div class="col-3">
        <label>trophy</label>
        <select id="item_trophy"><option value="0" selected>0</option><option value="1">1</option></select>
      </div>
      <div class="col-12 row">
        <button class="btn good" onclick="giveItem()">Выдать предметы</button>
        <span class="small" id="item_status"></span>
      </div>

      <div class="col-12"><div class="hr"></div></div>
      <div class="col-12"><div class="pill">Выдача покемонов</div></div>

      <div class="col-3">
        <label>Получатели</label>
        <select id="pok_target_mode">
          <option value="one">Указанные ID</option>
          <option value="all">Всем</option>
        </select>
      </div>
      <div class="col-6">
        <label>ID пользователей</label>
        <input id="pok_user_ids" placeholder="12, 34, 56">
      </div>
      <div class="col-3">
        <label>Уровень</label>
        <input id="pok_lvl" type="number" value="1" min="1" max="100">
      </div>

      <div class="col-6">
        <label>Покемон (ID или имя/имя_rus)</label>
        <input id="pok_pokemon" placeholder="25 или Пикачу">
      </div>
      <div class="col-6">
        <label>Атаки (ID через запятую; опц.)</label>
        <input id="pok_attacks" placeholder="33, 85, 98, 0">
      </div>
      <div class="col-6">
        <label>Gen (опц.)</label>
        <input id="pok_gen" placeholder="10,10,10,10,10,10">
      </div>
      <div class="col-12 row">
        <button class="btn good" onclick="givePokemon()">Выдать покемонов</button>
        <span class="small" id="pok_status"></span>
      </div>

      <div class="col-12"><div class="hr"></div></div>
      <div class="col-12"><div class="pill">Выдача атаки (TM) конкретному покемону</div></div>

      <div class="col-4">
        <label>ID покемона (user_pokemons.id)</label>
        <input id="tm_poke_id" type="number" min="1" placeholder="12345">
      </div>
      <div class="col-8">
        <label>Атака (ID или name/name_rus)</label>
        <input id="tm_attack" placeholder="15 или 'Thunderbolt'">
      </div>
      <div class="col-12 row">
        <button class="btn good" onclick="giveAttack()">Добавить атаку</button>
        <span class="small" id="tm_status"></span>
      </div>
    </div>
  </div>

  <div id="tab-users" class="tab card hidden">
    <div class="grid">
      <div class="col-8">
        <label>Поиск пользователя (ID или login)</label>
        <input id="u_q" placeholder="Например: 123 или ash">
      </div>
      <div class="col-4 row" style="align-items:flex-end">
        <button class="btn" onclick="userLookup()">Искать</button>
        <span class="small" id="u_status"></span>
      </div>
      <div class="col-12"><div id="u_results" class="small"></div></div>
    </div>
  </div>

  <div id="tab-moder" class="tab card hidden">
    <div class="grid">
      <div class="col-3">
        <label>ID пользователя</label>
        <input id="m_user_id" type="number" min="1" placeholder="123">
      </div>
      <div class="col-3">
        <label>Действие</label>
        <select id="m_mode">
          <option value="mute">mute (chat)</option>
          <option value="unmute">unmute</option>
          <option value="ban">ban (game)</option>
          <option value="unban">unban</option>
          <option value="set_group">setGroup</option>
        </select>
      </div>
      <div class="col-3">
        <label>Минуты (для mute/ban)</label>
        <input id="m_minutes" type="number" min="0" value="60">
      </div>
      <div class="col-3">
        <label>Новая группа (для setGroup)</label>
        <input id="m_new_group" type="number" min="1" placeholder="10">
      </div>
      <div class="col-12">
        <label>Причина (лог)</label>
        <input id="m_reason" placeholder="Коротко: почему">
      </div>
      <div class="col-12 row">
        <button class="btn danger" onclick="moderationUpdate()">Применить</button>
        <span class="small" id="m_status"></span>
      </div>
      <div class="col-12 small">
        Примечание: поля ban/mute в проекте часто сериализованы. Панель обновляет только ключи <span class="pill">chat</span> и <span class="pill">game</span>, не трогая остальные.
      </div>
    </div>
  </div>

  <div id="tab-recharge" class="tab card hidden">
    <div class="row">
      <button class="btn" onclick="loadRecharge()">Показать последние строки</button>
      <select id="recharge_tail" style="max-width:160px">
        <option>200</option><option selected>400</option><option>800</option><option>1500</option>
      </select>
      <span class="small" id="recharge_status"></span>
    </div>
    <div class="hr"></div>
    <pre id="recharge_pre">Нажмите “Показать последние строки”.</pre>
  </div>

</div>

<script>
const CSRF = <?php echo json_encode($csrf); ?>;

function qs(id){return document.getElementById(id);}
function setTab(tab){
  document.querySelectorAll('.tabbtn').forEach(b=>b.classList.toggle('active', b.dataset.tab===tab));
  document.querySelectorAll('.tab').forEach(el=>el.classList.add('hidden'));
  qs('tab-'+tab).classList.remove('hidden');
}
document.querySelectorAll('.tabbtn').forEach(b=>b.addEventListener('click',()=>setTab(b.dataset.tab)));

async function post(action, payload){
  const fd = new FormData();
  fd.append('action', action);
  fd.append('csrf_token', CSRF);
  for (const [k,v] of Object.entries(payload||{})){
    fd.append(k, v);
  }
  const r = await fetch(location.href, {method:'POST', body: fd, credentials:'same-origin'});
  return await r.json();
}

let lastLogs = [];

function renderTable(rows){
  if (!rows || rows.length===0) return '<div class="small">Нет данных.</div>';
  const cols = Object.keys(rows[0]);
  let html = '<table class="table"><thead><tr>';
  for (const c of cols) html += '<th>'+escapeHtml(c)+'</th>';
  html += '</tr></thead><tbody>';
  for (const row of rows){
    html += '<tr>';
    for (const c of cols){
      let v = row[c];
      if (v === null || v === undefined) v = '';
      const s = String(v);
      html += '<td>'+escapeHtml(s)+'</td>';
    }
    html += '</tr>';
  }
  html += '</tbody></table>';
  return html;
}
function escapeHtml(s){
  return s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

async function loadLogs(offset){
  qs('logs_status').textContent = 'Загрузка...';
  const source = qs('logs_source').value;
  const q = qs('logs_q').value;
  const limit = qs('logs_limit').value;
  const res = await post('get_logs', {source, q, limit, offset});
  if (!res.ok){
    qs('logs_status').textContent = res.message || 'Ошибка';
    return;
  }
  lastLogs = res.rows || [];
  qs('logs_table_wrap').innerHTML = renderTable(lastLogs);
  qs('logs_status').textContent = 'Показано: '+lastLogs.length;
}

function downloadCSV(){
  if (!lastLogs || lastLogs.length===0){ alert('Нет данных для экспорта.'); return; }
  const cols = Object.keys(lastLogs[0]);
  const lines = [];
  lines.push(cols.join(';'));
  for (const r of lastLogs){
    const row = cols.map(c => {
      let v = r[c];
      if (v===null||v===undefined) v='';
      v = String(v).replaceAll('"','""');
      return '"'+v+'"';
    }).join(';');
    lines.push(row);
  }
  const blob = new Blob([lines.join('\n')], {type:'text/csv;charset=utf-8'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'logs_'+new Date().toISOString().slice(0,19).replaceAll(':','-')+'.csv';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}

async function userLookup(){
  qs('u_status').textContent='Поиск...';
  const q = qs('u_q').value;
  const res = await post('user_lookup', {q});
  if(!res.ok){ qs('u_status').textContent=res.message||'Ошибка'; return; }
  qs('u_status').textContent = 'Найдено: '+(res.rows?res.rows.length:0);
  const rows = res.rows || [];
  if (!rows.length){ qs('u_results').innerHTML = '<div class="small">Нет совпадений.</div>'; return; }
  let html = '<table class="table"><thead><tr><th>id</th><th>login</th><th>group</th><th>status</th><th>rang</th><th>mute(chat)</th><th>ban(game)</th></tr></thead><tbody>';
  for (const u of rows){
    const mute = (u.mute_parsed && u.mute_parsed.chat) ? u.mute_parsed.chat : '';
    const ban = (u.ban_parsed && u.ban_parsed.game) ? u.ban_parsed.game : '';
    html += `<tr>
      <td>${escapeHtml(String(u.id))}</td>
      <td>${escapeHtml(String(u.login))}</td>
      <td>${escapeHtml(String(u.user_group))}</td>
      <td>${escapeHtml(String(u.status||''))}</td>
      <td>${escapeHtml(String(u.rang||''))}</td>
      <td>${escapeHtml(String(mute))}</td>
      <td>${escapeHtml(String(ban))}</td>
    </tr>`;
  }
  html += '</tbody></table>';
  qs('u_results').innerHTML = html;
}

async function moderationUpdate(){
  qs('m_status').textContent='Выполняю...';
  const user_id = qs('m_user_id').value;
  const mode = qs('m_mode').value;
  const minutes = qs('m_minutes').value;
  const reason = qs('m_reason').value;
  const new_group = qs('m_new_group').value;
  const res = await post('moderation_update', {user_id, mode, minutes, reason, new_group});
  qs('m_status').textContent = res.ok ? res.message : (res.message||'Ошибка');
}

async function giveEgg(){
  qs('egg_status').textContent='Выполняю...';
  const payload = {
    target_mode: qs('egg_target_mode').value,
    user_ids: qs('egg_user_ids').value,
    count: qs('egg_count').value,
    pokemon: qs('egg_pokemon').value,
    form: qs('egg_form').value,
    shine: qs('egg_shine').value,
    trade: qs('egg_trade').value,
    sparka: qs('egg_sparka').value,
    days: qs('egg_days').value,
    gens: qs('egg_gens').value,
    character: qs('egg_character').value
  };
  const res = await post('give_egg', payload);
  qs('egg_status').textContent = res.ok ? res.message : (res.message||'Ошибка');
  if (res.errors && res.errors.length) console.warn(res.errors);
}

async function giveItem(){
  qs('item_status').textContent='Выполняю...';
  const payload = {
    target_mode: qs('item_target_mode').value,
    user_ids: qs('item_user_ids').value,
    item: qs('item_item').value,
    count: qs('item_count').value,
    trophy: qs('item_trophy').value
  };
  const res = await post('give_item', payload);
  qs('item_status').textContent = res.ok ? res.message : (res.message||'Ошибка');
  if (res.errors && res.errors.length) console.warn(res.errors);
}

async function givePokemon(){
  qs('pok_status').textContent='Выполняю...';
  const payload = {
    target_mode: qs('pok_target_mode').value,
    user_ids: qs('pok_user_ids').value,
    pokemon: qs('pok_pokemon').value,
    lvl: qs('pok_lvl').value,
    attacks: qs('pok_attacks').value,
    gen: qs('pok_gen').value
  };
  const res = await post('give_pokemon', payload);
  qs('pok_status').textContent = res.ok ? res.message : (res.message||'Ошибка');
  if (res.errors && res.errors.length) console.warn(res.errors);
}

async function giveAttack(){
  qs('tm_status').textContent='Выполняю...';
  const payload = { poke_id: qs('tm_poke_id').value, attack: qs('tm_attack').value };
  const res = await post('give_attack', payload);
  qs('tm_status').textContent = res.ok ? res.message : (res.message||'Ошибка');
}

async function loadRecharge(){
  qs('recharge_status').textContent='Загрузка...';
  const tail = qs('recharge_tail').value;
  const res = await post('get_recharge_log', {tail});
  if (!res.ok){ qs('recharge_status').textContent = res.message || 'Ошибка'; return; }
  qs('recharge_pre').textContent = res.text || '';
  qs('recharge_status').textContent = res.path ? ('Источник: '+res.path) : 'OK';
}

// preload logs
loadLogs(0);
</script>
</body>
</html>
