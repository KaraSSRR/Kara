<?php
// NPC: 9906 — Зимний Дух (Winter Wonder turn-in + обменник + босс)
// Поместите файл рядом с другими NPC-скриптами (по аналогии с 78.php/79.php) с именем 9906.php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once($_SERVER['DOCUMENT_ROOT']."/inc/function/Functions.php");

// -------------------- Helpers / DB --------------------
$mysqli = $GLOBALS['mysqli'] ?? (isset($mysqli) ? $mysqli : null);
if (!$mysqli && class_exists('Work') && isset(Work::$sql)) {
    $mysqli = Work::$sql;
}

$user_id = 0;
if (isset($_SESSION['id'])) {
    $user_id = (int)$_SESSION['id'];
} elseif (isset($user) && is_array($user) && isset($user['id'])) {
    $user_id = (int)$user['id'];
} elseif (isset($user_id) && $user_id) {
    $user_id = (int)$user_id;
}


function ww_now(){ return time(); }

function ww_q1($mysqli, $sql){
    if (!$mysqli) return null;
    $q = $mysqli->query($sql);
    if ($q && $q->num_rows) return $q->fetch_assoc();
    return null;
}

function ww_ensure_tables($mysqli){
    if (!$mysqli) return;

    $mysqli->query("CREATE TABLE IF NOT EXISTS `aa_event_winterwonder_state` (
        `id` INT NOT NULL,
        `active` TINYINT(1) NOT NULL DEFAULT 0,
        `name` VARCHAR(64) NOT NULL DEFAULT 'Winter Wonder',
        `season_tag` VARCHAR(32) NOT NULL DEFAULT 'winter',
        `start_ts` INT NOT NULL DEFAULT 0,
        `end_ts` INT NOT NULL DEFAULT 0,
        `updated_at` INT NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $mysqli->query("CREATE TABLE IF NOT EXISTS `aa_event_winterwonder_users` (
        `user_id` INT NOT NULL,
        `started_at` INT NOT NULL DEFAULT 0,
        `step1_done` TINYINT(1) NOT NULL DEFAULT 0,
        `step2_done` TINYINT(1) NOT NULL DEFAULT 0,
        `step3_done` TINYINT(1) NOT NULL DEFAULT 0,
        `boss_done`  TINYINT(1) NOT NULL DEFAULT 0,
        `completed`  TINYINT(1) NOT NULL DEFAULT 0,
        `daily_last_key` INT NOT NULL DEFAULT 0,
        PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $row = ww_q1($mysqli, "SELECT * FROM `aa_event_winterwonder_state` WHERE `id`=1 LIMIT 1");
    if (!$row) {
        $start = ww_now();
        $end   = $start + 14*24*3600; // дефолт 14 дней (можно править в админке /do/event_winterwonder.php)
        $mysqli->query("INSERT INTO `aa_event_winterwonder_state` (`id`,`active`,`name`,`season_tag`,`start_ts`,`end_ts`,`updated_at`)
            VALUES (1,1,'Winter Wonder','winter',".(int)$start.",".(int)$end.",".(int)ww_now().")");
    }
}

function ww_get_state($mysqli){
    $row = ww_q1($mysqli, "SELECT * FROM `aa_event_winterwonder_state` WHERE `id`=1 LIMIT 1");
    return $row ?: array('active'=>0,'start_ts'=>0,'end_ts'=>0,'name'=>'Winter Wonder');
}
function ww_active_now($state){
    if (!isset($state['active']) || (int)$state['active'] !== 1) return false;
    $now = ww_now();
    $st = isset($state['start_ts']) ? (int)$state['start_ts'] : 0;
    $en = isset($state['end_ts']) ? (int)$state['end_ts'] : 0;
    if ($st && $now < $st) return false;
    if ($en && $now > $en) return false;
    return true;
}

function ww_get_user_row($mysqli, $uid){
    if (!$mysqli) return array();
    $uid = (int)$uid;
    $row = ww_q1($mysqli, "SELECT * FROM `aa_event_winterwonder_users` WHERE `user_id`=".$uid." LIMIT 1");
    if (!$row) {
        $mysqli->query("INSERT INTO `aa_event_winterwonder_users` (`user_id`) VALUES (".$uid.")");
        $row = ww_q1($mysqli, "SELECT * FROM `aa_event_winterwonder_users` WHERE `user_id`=".$uid." LIMIT 1");
    }
    return $row ?: array();
}
function ww_user_set($mysqli, $uid, $fields){
    if (!$mysqli) return;
    $uid = (int)$uid;
    $set = array();
    foreach ($fields as $k=>$v){
        $k = preg_replace('/[^a-z0-9_]/i','', (string)$k);
        $set[] = "`".$k."`=".(int)$v;
    }
    if (!$set) return;
    $mysqli->query("UPDATE `aa_event_winterwonder_users` SET ".implode(',', $set)." WHERE `user_id`=".$uid." LIMIT 1");
}

function ww_item_count($mysqli, $item_id, $uid){
    if (!$mysqli) return 0;
    $q = $mysqli->query("SELECT SUM(`count`) AS c FROM `items_users` WHERE `item_id`=".(int)$item_id." AND `user`=".(int)$uid);
    if ($q && $q->num_rows){
        $r = $q->fetch_assoc();
        return isset($r['c']) ? (int)$r['c'] : 0;
    }
    return 0;
}

function ww_need($have, $need){
    $have = (int)$have; $need=(int)$need;
    return $have >= $need ? "<span style='color:#41d97b'>".$have."/".$need."</span>" : "<span style='color:#ff7070'>".$have."/".$need."</span>";
}

// -------------------- Event constants --------------------
define('WW_ITEM_BELL', 1203);
define('WW_ITEM_ICE_LEAF', 1204);
define('WW_ITEM_GHOST_SPARK', 1205);
define('WW_ITEM_WARM_COAL', 1206);
define('WW_ITEM_ORNAMENT', 1207);
define('WW_ITEM_GRASS_MARK', 1208);
define('WW_ITEM_POISON_MARK', 1209);
define('WW_ITEM_BOSS_HEART', 1210);

define('WW_MONEY_ITEM', 1);
define('WW_STEP1_MONEY', 25000);

define('WW_TRAINING_ITEM_ID', 197);
define('WW_GRASS_CANDY_ITEM', 28);
define('WW_POISON_CANDY_ITEM', 29);

define('WW_BOSS_QUEST_ID', 12010);
define('WW_BOSS_LEVEL', 50);

// Ген-пул для обменника (покемоны региона 7 с зимней тематикой)
$WW_GEN_POOL = array(215,221,225,361,473,478,37,77,43,92,273,274,275,152,153,154,252,253,254,722,723,724,756);

function ww_grant_pokemon_simple($user_id, $basenum, $form, $lvl, $gen_min, $gen_max){
    global $mysqli;
    $level = (int)$lvl; if ($level < 1) $level = 1;

    // attacks
    $atk_list = array();
    if (class_exists('Info') && method_exists('Info','_generateAtkListPoke')) {
        $a = Info::_generateAtkListPoke((int)$basenum, $level);
        if (is_array($a) && !empty($a)) $atk_list = array_values($a);
    }
    while (count($atk_list) < 4) { $atk_list[] = rand(1, 750); }
    $atk_list = array_slice($atk_list, 0, 4);

    // genes
    $ivs = array();
    for ($i=0; $i<6; $i++) { $ivs[] = rand((int)$gen_min, (int)$gen_max); }
    $gen_str = implode(',', $ivs);

    if (function_exists('addPokemonToUser')) {
        $pid = addPokemonToUser((int)$user_id, (int)$basenum, (int)$level, $atk_list, $gen_str);
        if ($pid) {
            if ($form !== '' && $form !== '0') {
                // try Info::addForm, else direct update
                if (class_exists('Info') && method_exists('Info','addForm')) {
                    Info::addForm((int)$pid, (string)$form);
                } elseif ($mysqli) {
                    $mysqli->query("UPDATE user_pokemons SET form='". $mysqli->real_escape_string((string)$form) ."' WHERE id=".(int)$pid);
                }
            }
            return array('ok'=>1,'pokemon_id'=>(int)$pid);
        }
        return array('ok'=>0,'error'=>'addPokemonToUser failed');
    }

    return array('ok'=>0,'error'=>'addPokemonToUser missing');
}

// -------------------- Init --------------------
ww_ensure_tables($mysqli);
$state = ww_get_state($mysqli);
$active = ww_active_now($state);
$userRow = ww_get_user_row($mysqli, $user_id);

// -------------------- Response base --------------------
$response = array();
$response['name']  = 'Зимний Дух';
$response['image'] = '/img/default-npc.png';

$step = isset($npcStep) ? (int)$npcStep : 0;

// -------------------- Steps --------------------
if (!$user_id) {
    $response['question'] = "Ошибка: не удалось определить пользователя.";
    $response['answer'] = array(0 => "Закрыть");
    return;
}

$started = isset($userRow['started_at']) && (int)$userRow['started_at'] > 0;
$step1 = isset($userRow['step1_done']) ? (int)$userRow['step1_done'] : 0;
$step2 = isset($userRow['step2_done']) ? (int)$userRow['step2_done'] : 0;
$step3 = isset($userRow['step3_done']) ? (int)$userRow['step3_done'] : 0;
$boss  = isset($userRow['boss_done']) ? (int)$userRow['boss_done'] : 0;

$bell = ww_item_count($mysqli, WW_ITEM_BELL, $user_id);

$endTxt = '';
if (isset($state['end_ts']) && (int)$state['end_ts'] > 0) {
    $endTxt = date('d.m.Y H:i', (int)$state['end_ts']);
}

if ($step === 0) {
    if (!$active) {
        $response['question'] = "<b>Праздник сейчас не активен.</b><br>Если администратор включит ивент — я сразу начну выдавать задания.";
        $response['answer'] = array(0=>'Закрыть');
        return;
    }

    $q = "<div style='line-height:1.25'>
        <b>Новогодний ивент «Зимнее Чудо»</b><br>
        <small>Колокольчики: <b>".$bell."</b> · Конец: <b>".($endTxt ?: '—')."</b></small><br><br>";

    if (!$started) {
        $q .= "Я — Зимний Дух. Помоги собрать ингредиенты, украсить ёлку и победить Морозного стража.";
        $response['question'] = $q."</div>";
        $response['answer'] = array(
            1   => "Начать ивент",
            200 => "Как это работает?",
            100 => "Ивент-обменник",
            0   => "Закрыть"
        );
        return;
    }

    // Progress blocks
    $ice = ww_item_count($mysqli, WW_ITEM_ICE_LEAF, $user_id);
    $spark = ww_item_count($mysqli, WW_ITEM_GHOST_SPARK, $user_id);
    $coal = ww_item_count($mysqli, WW_ITEM_WARM_COAL, $user_id);
    $orn  = ww_item_count($mysqli, WW_ITEM_ORNAMENT, $user_id);
    $gmk  = ww_item_count($mysqli, WW_ITEM_GRASS_MARK, $user_id);
    $pmk  = ww_item_count($mysqli, WW_ITEM_POISON_MARK, $user_id);
    $heart= ww_item_count($mysqli, WW_ITEM_BOSS_HEART, $user_id);

    $q .= "<b>Текущий прогресс</b><br>";

    if (!$step1) {
        $q .= "<br><b>Этап 1 — Ингредиенты</b><br>
        Ледяной Лист: ".ww_need($ice,3)."<br>
        Призрачная Искра: ".ww_need($spark,2)."<br>
        Тёплый Уголь: ".ww_need($coal,2)."<br>";
    } elseif (!$step2) {
        $q .= "<br><b>Этап 2 — Озорники</b><br>
        Травяные метки: ".ww_need($gmk,200)."<br>
        Ядовитые метки: ".ww_need($pmk,200)."<br>";
    } elseif (!$step3) {
        $q .= "<br><b>Этап 3 — Укрась ёлку</b><br>
        Украшения: ".ww_need($orn,15)."<br>";
    } elseif (!$boss) {
        $q .= "<br><b>Этап 4 — Морозный страж</b><br>
        Сердце стража: ".ww_need($heart,1)."<br>";
    } else {
        $q .= "<br><b>Ивент завершён.</b><br>Спасибо! Если будут новые этапы — я сообщу.";
    }

    $response['question'] = $q."</div>";

    $ans = array();

    if (!$step1) $ans[11] = "Сдать этап 1";
    else if (!$step2) $ans[12] = "Сдать этап 2";
    else if (!$step3) $ans[13] = "Сдать этап 3";
    else if (!$boss) {
        $ans[15] = "Начать бой с Морозным стражем";
        $ans[14] = "Сдать этап 4";
    }

    $ans[100] = "Ивент-обменник";
    $ans[0]   = "Закрыть";

    $response['answer'] = $ans;
    return;
}

if ($step === 200) {
    $response['question'] = "<b>Как участвовать</b><br>
    1) Этап 1: собери Ледяной Лист x3 (Oddish), Призрачную Искру x2 (Gastly), Тёплый Уголь x2 (Vulpix/Ponyta).<br>
    2) Этап 2: собери Травяные метки x200 и Ядовитые метки x200 (засчитывается по <b>первому типу</b>).<br>
    3) Этап 3: собери Украшения x15 (20% с любых диких).<br>
    4) Этап 4: начни бой с Морозным стражем, получи Сердце и сдай мне.<br><br>
    Награды выдаются при сдаче этапов. Колокольчики трать в обменнике.";
    $response['answer'] = array(0=>"Назад");
    return;
}

if ($step === 1) {
    if (!$active) {
        $response['question'] = "Ивент сейчас выключен.";
        $response['answer'] = array(0=>'Закрыть');
        return;
    }
    if (!$started) {
        ww_user_set($mysqli, $user_id, array('started_at'=>ww_now()));
    }
    $response['question'] = "Ивент начат. Возвращайся с предметами — я приму их и выдам награды.";
    $response['answer'] = array(0=>"Хорошо");
    return;
}

// -------- Turn-ins --------
if ($step === 11) {
    if (!$active) { $response['question']="Ивент выключен."; $response['answer']=array(0=>"Закрыть"); return; }
    $userRow = ww_get_user_row($mysqli, $user_id);
    if (!(isset($userRow['started_at']) && (int)$userRow['started_at'] > 0)) { $response['question']="Сначала начни ивент."; $response['answer']=array(0=>"Ок"); return; }
    if ((int)$userRow['step1_done'] === 1) { $response['question']="Этап 1 уже выполнен."; $response['answer']=array(0=>"Ок"); return; }

    if (ww_item_count($mysqli, WW_ITEM_ICE_LEAF, $user_id) < 3) { $response['question']="Не хватает: Ледяной Лист x3"; $response['answer']=array(0=>"Ок"); return; }
    if (ww_item_count($mysqli, WW_ITEM_GHOST_SPARK, $user_id) < 2) { $response['question']="Не хватает: Призрачная Искра x2"; $response['answer']=array(0=>"Ок"); return; }
    if (ww_item_count($mysqli, WW_ITEM_WARM_COAL, $user_id) < 2) { $response['question']="Не хватает: Тёплый Уголь x2"; $response['answer']=array(0=>"Ок"); return; }

    if (function_exists('minus_item')) {
        minus_item(WW_ITEM_ICE_LEAF, 3, $user_id);
        minus_item(WW_ITEM_GHOST_SPARK, 2, $user_id);
        minus_item(WW_ITEM_WARM_COAL, 2, $user_id);
    }
    if (function_exists('itemAdd')) {
        itemAdd(WW_MONEY_ITEM, WW_STEP1_MONEY, $user_id);
        itemAdd(WW_ITEM_BELL, 5, $user_id);
    }
    ww_user_set($mysqli, $user_id, array('step1_done'=>1));

    $response['question'] = "<b>Этап 1 завершён.</b><br>Награда: 25 000 + Колокольчики x5";
    $response['answer'] = array(0=>"Дальше");
    return;
}

if ($step === 12) {
    if (!$active) { $response['question']="Ивент выключен."; $response['answer']=array(0=>"Закрыть"); return; }
    $userRow = ww_get_user_row($mysqli, $user_id);
    if ((int)$userRow['step1_done'] !== 1) { $response['question']="Сначала заверши этап 1."; $response['answer']=array(0=>"Ок"); return; }
    if ((int)$userRow['step2_done'] === 1) { $response['question']="Этап 2 уже выполнен."; $response['answer']=array(0=>"Ок"); return; }

    if (ww_item_count($mysqli, WW_ITEM_GRASS_MARK, $user_id) < 200) { $response['question']="Не хватает: Травяная метка x200"; $response['answer']=array(0=>"Ок"); return; }
    if (ww_item_count($mysqli, WW_ITEM_POISON_MARK, $user_id) < 200) { $response['question']="Не хватает: Ядовитая метка x200"; $response['answer']=array(0=>"Ок"); return; }

    if (function_exists('minus_item')) {
        minus_item(WW_ITEM_GRASS_MARK, 200, $user_id);
        minus_item(WW_ITEM_POISON_MARK, 200, $user_id);
    }
    if (function_exists('itemAdd')) {
        itemAdd(WW_GRASS_CANDY_ITEM, 10, $user_id);
        itemAdd(WW_POISON_CANDY_ITEM, 10, $user_id);
        itemAdd(WW_ITEM_BELL, 5, $user_id);
    }
    ww_user_set($mysqli, $user_id, array('step2_done'=>1));

    $response['question'] = "<b>Этап 2 завершён.</b><br>Награда: Травяная конфета x10 + Ядовитая конфета x10 + Колокольчики x5";
    $response['answer'] = array(0=>"Дальше");
    return;
}

if ($step === 13) {
    if (!$active) { $response['question']="Ивент выключен."; $response['answer']=array(0=>"Закрыть"); return; }
    $userRow = ww_get_user_row($mysqli, $user_id);
    if ((int)$userRow['step2_done'] !== 1) { $response['question']="Сначала заверши этап 2."; $response['answer']=array(0=>"Ок"); return; }
    if ((int)$userRow['step3_done'] === 1) { $response['question']="Этап 3 уже выполнен."; $response['answer']=array(0=>"Ок"); return; }

    if (ww_item_count($mysqli, WW_ITEM_ORNAMENT, $user_id) < 15) { $response['question']="Не хватает: Новогоднее украшение x15"; $response['answer']=array(0=>"Ок"); return; }

    if (function_exists('minus_item')) {
        minus_item(WW_ITEM_ORNAMENT, 15, $user_id);
    }
    if (function_exists('itemAdd')) {
        itemAdd(WW_ITEM_BELL, 10, $user_id);
    }
    ww_user_set($mysqli, $user_id, array('step3_done'=>1));

    $response['question'] = "<b>Этап 3 завершён.</b><br>Награда: Колокольчики x10";
    $response['answer'] = array(0=>"Дальше");
    return;
}

if ($step === 15) {
    // Start boss battle
    if (!$active) { $response['question']="Ивент выключен."; $response['answer']=array(0=>"Закрыть"); return; }
    $userRow = ww_get_user_row($mysqli, $user_id);
    if ((int)$userRow['step3_done'] !== 1) { $response['question']="Сначала заверши этап 3."; $response['answer']=array(0=>"Ок"); return; }

    if (!class_exists('Info')) {
        // пытаемся подключить Info
        @require_once($_SERVER['DOCUMENT_ROOT']."/do/Info.php");
    }
    if (!class_exists('Info') || !method_exists('Info','_generatePve')) {
        $response['question'] = "Не найден Info::_generatePve. Проверь файл /do/Info.php.";
        $response['answer'] = array(0=>"Закрыть");
        return;
    }

    if (!$mysqli) { $response['question']="Нет подключения к БД."; $response['answer']=array(0=>"Закрыть"); return; }
    $u = $mysqli->query("SELECT * FROM `users` WHERE `id`=".(int)$user_id." LIMIT 1");
    if (!$u || !$u->num_rows) { $response['question']="Пользователь не найден."; $response['answer']=array(0=>"Закрыть"); return; }
    $user_info = $u->fetch_assoc();

    if (isset($user_info['status']) && (string)$user_info['status'] === 'battle') {
        $response['question'] = "Ты уже находишься в бою.";
        $response['answer'] = array(0=>"Ок");
        return;
    }

    $loc = isset($user_info['location']) ? (int)$user_info['location'] : 0;
    Info::_generatePve($user_info, $loc, null, WW_BOSS_QUEST_ID, null, WW_BOSS_LEVEL);

    $response['question'] = "<b>Бой начат!</b><br>Победи Морозного стража и принеси мне «Сердце стража».";
    $response['answer'] = array(0=>"Ок");
    return;
}

if ($step === 14) {
    if (!$active) { $response['question']="Ивент выключен."; $response['answer']=array(0=>"Закрыть"); return; }
    $userRow = ww_get_user_row($mysqli, $user_id);
    if ((int)$userRow['step3_done'] !== 1) { $response['question']="Сначала заверши этап 3."; $response['answer']=array(0=>"Ок"); return; }
    if ((int)$userRow['boss_done'] === 1) { $response['question']="Этап 4 уже выполнен."; $response['answer']=array(0=>"Ок"); return; }

    if (ww_item_count($mysqli, WW_ITEM_BOSS_HEART, $user_id) < 1) {
        $response['question']="Сначала победи Морозного стража и получи «Сердце стража».";
        $response['answer']=array(0=>"Ок");
        return;
    }

    if (function_exists('minus_item')) minus_item(WW_ITEM_BOSS_HEART, 1, $user_id);
    if (function_exists('itemAdd')) {
        itemAdd(WW_TRAINING_ITEM_ID, 1, $user_id);
        itemAdd(WW_ITEM_BELL, 15, $user_id);
    }

    // Final reward: Alola Vulpix
    $pokeRes = ww_grant_pokemon_simple($user_id, 37, 'alola', 5, 18, 28);

    ww_user_set($mysqli, $user_id, array('boss_done'=>1, 'completed'=>1));

    if (!$pokeRes || !isset($pokeRes['ok']) || (int)$pokeRes['ok'] !== 1) {
        $err = isset($pokeRes['error']) ? $pokeRes['error'] : 'unknown';
        $response['question'] = "<b>Этап 4 завершён.</b><br>Награда: Тренировка x1 + Колокольчики x15.<br><br><b>ВНИМАНИЕ:</b> Vulpix (Alola) не выдан из-за ошибки: ".$err;
        $response['answer'] = array(0=>"Ок");
        return;
    }

    $response['question'] = "<b>Ивент завершён!</b><br>Награда: Тренировка x1 + Колокольчики x15 + <b>Vulpix (Alola)</b>.";
    $response['answer'] = array(0=>"Ура!");
    return;
}

// -------- Exchange shop --------
if ($step === 100) {
    if (!$active) { $response['question']="Ивент выключен."; $response['answer']=array(0=>"Закрыть"); return; }
    if (!$started) { $response['question']="Сначала начни ивент."; $response['answer']=array(0=>"Ок"); return; }

    $bell = ww_item_count($mysqli, WW_ITEM_BELL, $user_id);
    $response['question'] = "<b>Ивент-обменник</b><br><small>Колокольчики: <b>".$bell."</b></small><br><br>
    1) Конфета (травяная) — 1 колокольчик<br>
    2) Конфета (ядовитая) — 1 колокольчик<br>
    3) Тренировка (ID 197) — 5 колокольчиков<br>
    4) Покемон с генами 16–21 — 15 колокольчиков<br><small>10% шанс: Vulpix (Alola) как бонус в паке</small>";

    $response['answer'] = array(
        101 => "Купить травяную конфету (1)",
        102 => "Купить ядовитую конфету (1)",
        103 => "Купить тренировку (5)",
        104 => "Купить ген-покемона (15)",
        0   => "Назад"
    );
    return;
}

function ww_shop_buy_item($item_id, $price, $user_id){
    global $mysqli;
    if (ww_item_count($mysqli, WW_ITEM_BELL, $user_id) < $price) return array('ok'=>0,'error'=>'Не хватает колокольчиков (нужно '.$price.').');
    if (function_exists('minus_item')) minus_item(WW_ITEM_BELL, $price, $user_id);
    if (function_exists('itemAdd')) itemAdd((int)$item_id, 1, $user_id);
    return array('ok'=>1);
}

if ($step === 101) {
    $r = ww_shop_buy_item(WW_GRASS_CANDY_ITEM, 1, $user_id);
    $response['question'] = $r['ok'] ? "Покупка успешна: травяная конфета x1" : ("Ошибка: ".$r['error']);
    $response['answer'] = array(100=>"В обменник", 0=>"Назад");
    return;
}

if ($step === 102) {
    $r = ww_shop_buy_item(WW_POISON_CANDY_ITEM, 1, $user_id);
    $response['question'] = $r['ok'] ? "Покупка успешна: ядовитая конфета x1" : ("Ошибка: ".$r['error']);
    $response['answer'] = array(100=>"В обменник", 0=>"Назад");
    return;
}

if ($step === 103) {
    $r = ww_shop_buy_item(WW_TRAINING_ITEM_ID, 5, $user_id);
    $response['question'] = $r['ok'] ? "Покупка успешна: тренировка x1" : ("Ошибка: ".$r['error']);
    $response['answer'] = array(100=>"В обменник", 0=>"Назад");
    return;
}

if ($step === 104) {
    if (ww_item_count($mysqli, WW_ITEM_BELL, $user_id) < 15) {
        $response['question'] = "Ошибка: не хватает колокольчиков (нужно 15).";
        $response['answer'] = array(100=>"В обменник", 0=>"Назад");
        return;
    }
    if (function_exists('minus_item')) minus_item(WW_ITEM_BELL, 15, $user_id);

    $pick = $WW_GEN_POOL[array_rand($WW_GEN_POOL)];
    $form = '';
    if (rand(1,100) <= 10) { $pick = 37; $form = 'alola'; }

    $pokeRes = ww_grant_pokemon_simple($user_id, $pick, $form, 5, 16, 21);
    if (!$pokeRes || !isset($pokeRes['ok']) || (int)$pokeRes['ok'] !== 1) {
        $err = isset($pokeRes['error']) ? $pokeRes['error'] : 'unknown';
        $response['question'] = "Ошибка: не удалось выдать покемона: ".$err;
        $response['answer'] = array(100=>"В обменник", 0=>"Назад");
        return;
    }
    $response['question'] = "Покупка успешна: покемон с генами 16–21 выдан.";
    $response['answer'] = array(100=>"В обменник", 0=>"Назад");
    return;
}

$response['question'] = "Неизвестный шаг диалога.";
$response['answer'] = array(0=>"Закрыть");
