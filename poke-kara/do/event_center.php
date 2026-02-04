<?php
/**
 * /do/event_winterwonder.php
 * Winter Wonder (Region 7) — Event Center
 * PHP 5.6 compatible.
 *
 * Provides:
 * - Quest chain (4 steps)
 * - Exchange shop (bells)
 * - Boss start (Aggron)
 * - Server statistics
 * - Minimal admin controls (user id = 4)
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$docroot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
$path_global = $docroot.'/inc/conf/global.php';
$path_func   = $docroot.'/inc/function/Functions.php';
$path_info   = $docroot.'/do/Info.php';

if (!file_exists($path_global) || !file_exists($path_func)) {
  echo json_encode(array('ok'=>0,'error'=>'Missing include files: global.php / Functions.php')); exit;
}

require_once $path_global;
require_once $path_func;
if (file_exists($path_info)) {
  require_once $path_info;
}

if (function_exists('session_status')) {
  if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
} else {
  if (session_id() === '') { @session_start(); }
}

$user_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
if ($user_id <= 0) {
  echo json_encode(array('ok'=>0,'error'=>'Ошибка авторизации')); exit;
}

// ---------- Constants (IDs reserved for this event) ----------
// Event currency
define('WW_ITEM_BELL', 1203);
// Quest items
define('WW_ITEM_ICE_LEAF', 1204);
define('WW_ITEM_GHOST_SPARK', 1205);
define('WW_ITEM_WARM_COAL', 1206);
define('WW_ITEM_ORNAMENT', 1207);
// Kill marks (first type only enforced by species list in drop config)
define('WW_ITEM_GRASS_MARK', 1208);
define('WW_ITEM_POISON_MARK', 1209);
// Boss proof
define('WW_ITEM_BOSS_HEART', 1210);

// Boss
define('WW_BOSS_QUEST_ID', 12010);
define('WW_BOSS_LEVEL', 50);
define('WW_BOSS_BASENUM', 306); // Aggron

// Rewards
define('WW_MONEY_ITEM', 1);
define('WW_STEP1_MONEY', 25000);
define('WW_TRAINING_ITEM_ID', 197); // confirmed by user

define('WW_GRASS_CANDY_ITEM', 28);
define('WW_POISON_CANDY_ITEM', 29);

define('WW_DAILY_BELLS', 3);

// Exchange pool (region 7 themed); final reward is always Alola Vulpix
$WW_GEN_POOL = array(215,221,225,361,473,478,37,77,43,92,273,274,275,152,153,154,252,253,254,722,723,724,756);

// ---------- Helpers ----------
function ww_json($arr){ echo json_encode($arr); exit; }

function ww_is_admin($user_id){ return ($user_id === 4); }

function ww_time_human($ts){
  if (!$ts) return '—';
  return date('d.m.Y H:i', (int)$ts);
}

function ww_now_day_key(){ return (int)date('Ymd'); }

function ww_item_count($item_id, $user_id){
  if (function_exists('item_isset')) {
    $c = item_isset((int)$item_id, 1, (int)$user_id);
    return ($c === false) ? 0 : (int)$c;
  }
  return 0;
}

function ww_sum_item_server($mysqli, $item_id){
  if (!$mysqli) return 0;
  // In this project items_users uses column `user` (not user_id). For SUM we only need item_id.
  $q = $mysqli->query("SELECT SUM(`count`) AS c FROM `items_users` WHERE `item_id`=".(int)$item_id);
  if ($q && $q->num_rows) {
    $r = $q->fetch_assoc();
    return isset($r['c']) ? (int)$r['c'] : 0;
  }
  return 0;
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
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

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
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

  $q = $mysqli->query("SELECT * FROM `aa_event_winterwonder_state` WHERE `id`=1 LIMIT 1");
  if (!$q || !$q->num_rows) {
    $now = time();
    $start = $now - 3600; // active from "today"
    $end = $now + 14*86400; // 14 days default
    $mysqli->query("INSERT INTO `aa_event_winterwonder_state` (`id`,`active`,`name`,`season_tag`,`start_ts`,`end_ts`,`updated_at`)
      VALUES (1,1,'Зимнее Чудо','winter',$start,$end,$now)");
  }
}

function ww_get_state($mysqli){
  if (!$mysqli) return array();
  $q = $mysqli->query("SELECT * FROM `aa_event_winterwonder_state` WHERE `id`=1 LIMIT 1");
  if ($q && $q->num_rows) return $q->fetch_assoc();
  return array();
}

function ww_active_now($state){
  $now = time();
  $active = isset($state['active']) ? (int)$state['active'] : 0;
  $start = isset($state['start_ts']) ? (int)$state['start_ts'] : 0;
  $end   = isset($state['end_ts']) ? (int)$state['end_ts'] : 0;
  if ($active !== 1) return false;
  if ($start > 0 && $now < $start) return false;
  if ($end > 0 && $now > $end) return false;
  return true;
}

function ww_get_user_row($mysqli, $user_id){
  if (!$mysqli) return array();
  $q = $mysqli->query("SELECT * FROM `aa_event_winterwonder_users` WHERE `user_id`=".(int)$user_id." LIMIT 1");
  if ($q && $q->num_rows) return $q->fetch_assoc();
  $mysqli->query("INSERT INTO `aa_event_winterwonder_users` (`user_id`) VALUES (".(int)$user_id.")");
  $q = $mysqli->query("SELECT * FROM `aa_event_winterwonder_users` WHERE `user_id`=".(int)$user_id." LIMIT 1");
  return ($q && $q->num_rows) ? $q->fetch_assoc() : array();
}

function ww_user_set($mysqli, $user_id, $fields){
  if (!$mysqli) return;
  $parts = array();
  foreach($fields as $k=>$v){
    $k = preg_replace('/[^a-z0-9_]/i','', $k);
    if (is_int($v) || ctype_digit((string)$v)) {
      $parts[] = "`$k`=".(int)$v;
    } else {
      $parts[] = "`$k`='".$mysqli->real_escape_string((string)$v)."'";
    }
  }
  if (empty($parts)) return;
  $mysqli->query("UPDATE `aa_event_winterwonder_users` SET ".implode(',', $parts)." WHERE `user_id`=".(int)$user_id." LIMIT 1");
}

function ww_require_active($active){
  if (!$active) ww_json(array('ok'=>0,'error'=>'Ивент сейчас не активен.'));
}

function ww_user_started($userRow){
  return (isset($userRow['started_at']) && (int)$userRow['started_at'] > 0);
}

function ww_reload_payload($mysqli, $user_id){
  $state = ww_get_state($mysqli);
  $active = ww_active_now($state);
  $u = ww_get_user_row($mysqli, $user_id);

  $counts = array(
    'bells'       => ww_item_count(WW_ITEM_BELL, $user_id),
    'ice_leaf'    => ww_item_count(WW_ITEM_ICE_LEAF, $user_id),
    'ghost_spark' => ww_item_count(WW_ITEM_GHOST_SPARK, $user_id),
    'warm_coal'   => ww_item_count(WW_ITEM_WARM_COAL, $user_id),
    'ornament'    => ww_item_count(WW_ITEM_ORNAMENT, $user_id),
    'grass_mark'  => ww_item_count(WW_ITEM_GRASS_MARK, $user_id),
    'poison_mark' => ww_item_count(WW_ITEM_POISON_MARK, $user_id),
    'boss_heart'  => ww_item_count(WW_ITEM_BOSS_HEART, $user_id),
  );

  $participants = 0; $completed = 0;
  if ($mysqli) {
    $q1 = $mysqli->query("SELECT COUNT(*) c FROM `aa_event_winterwonder_users` WHERE `started_at`>0");
    if ($q1 && $q1->num_rows) { $r = $q1->fetch_assoc(); $participants = isset($r['c']) ? (int)$r['c'] : 0; }
    $q2 = $mysqli->query("SELECT COUNT(*) c FROM `aa_event_winterwonder_users` WHERE `completed`=1");
    if ($q2 && $q2->num_rows) { $r = $q2->fetch_assoc(); $completed = isset($r['c']) ? (int)$r['c'] : 0; }
  }

  $server = array(
    'participants' => $participants,
    'completed'    => $completed,
    'bells_total'  => ww_sum_item_server($mysqli, WW_ITEM_BELL),
    'ornaments_total' => ww_sum_item_server($mysqli, WW_ITEM_ORNAMENT),
  );

  $state_human = array(
    'start' => ww_time_human(isset($state['start_ts']) ? $state['start_ts'] : 0),
    'end'   => ww_time_human(isset($state['end_ts']) ? $state['end_ts'] : 0),
  );

  return array(
    'ok' => 1,
    'active' => $active,
    'is_admin' => ww_is_admin($user_id),
    'state' => $state,
    'state_human' => $state_human,
    'user' => $u,
    'counts' => $counts,
    'server' => $server,
  );
}

function ww_grant_pokemon_simple($user_id, $basenum, $form, $lvl, $gen_min, $gen_max){
  global $mysqli;

  $level = (int)$lvl;
  if ($level < 1) $level = 1;

  // attacks
  $atk_list = array();
  if (class_exists('Info') && method_exists('Info','_generateAtkListPoke')) {
    $a = Info::_generateAtkListPoke((int)$basenum, $level);
    if (is_array($a) && !empty($a)) {
      $atk_list = array_values($a);
    }
  }
  while (count($atk_list) < 4) { $atk_list[] = rand(1, 750); }
  $atk_list = array_slice($atk_list, 0, 4);

  // genes
  $ivs = array();
  for ($i=0; $i<6; $i++) { $ivs[] = rand((int)$gen_min, (int)$gen_max); }
  $gen_str = implode(',', $ivs);

  // addPokemonToUser exists in Functions.php
  if (function_exists('addPokemonToUser')) {
    $pid = addPokemonToUser((int)$user_id, (int)$basenum, (int)$level, $atk_list, $gen_str);
    if ($pid) {
      if ($form !== '' && $form !== '0') {
        if (class_exists('Info') && method_exists('Info','addForm')) {
          Info::addForm((int)$pid, (string)$form);
        } elseif (class_exists('Work') && isset(Work::$sql)) {
          Work::$sql->query("UPDATE user_pokemons SET form='".Work::$sql->real_escape_string((string)$form)."' WHERE id=".(int)$pid);
        }
      }
      return array('ok'=>1,'pokemon_id'=>(int)$pid);
    }
    return array('ok'=>0,'error'=>'addPokemonToUser failed');
  }

  // Fallback minimal insert
  if (!$mysqli) return array('ok'=>0,'error'=>'No DB connection');
  $attacksStr = implode(',', $atk_list);
  $stmt = $mysqli->prepare("INSERT INTO user_pokemons (user_id, basenum, lvl, attacks, gen) VALUES (?,?,?,?,?)");
  if (!$stmt) return array('ok'=>0,'error'=>'prepare failed');
  $stmt->bind_param('iiiss', $user_id, $basenum, $level, $attacksStr, $gen_str);
  if (!$stmt->execute()) { $e = $stmt->error; $stmt->close(); return array('ok'=>0,'error'=>$e); }
  $pid = (int)$stmt->insert_id;
  $stmt->close();

  if ($pid && $form !== '' && $form !== '0') {
    if (class_exists('Info') && method_exists('Info','addForm')) {
      Info::addForm($pid, (string)$form);
    }
  }
  return array('ok'=>1,'pokemon_id'=>$pid);
}

// ---------- Boot ----------
$mysqli = isset($mysqli) ? $mysqli : (isset($GLOBALS['mysqli']) ? $GLOBALS['mysqli'] : null);
ww_ensure_tables($mysqli);

$state = ww_get_state($mysqli);
$active = ww_active_now($state);
$userRow = ww_get_user_row($mysqli, $user_id);

$action = 'open';
if (isset($_POST['action'])) $action = $_POST['action'];
elseif (isset($_GET['action'])) $action = $_GET['action'];
$action = preg_replace('/[^a-z0-9_]/i','', (string)$action);

// ---------- Actions ----------
if ($action === 'open') {
  ww_json(ww_reload_payload($mysqli, $user_id));
}

if ($action === 'start') {
  ww_require_active($active);
  if (!ww_user_started($userRow)) {
    ww_user_set($mysqli, $user_id, array('started_at'=>time()));
  }
  ww_json(array('ok'=>1,'msg'=>'Ивент начат! Выполняй задания и собирай колокольчики.','data'=>ww_reload_payload($mysqli,$user_id)));
}

if ($action === 'daily') {
  ww_require_active($active);
  if (!ww_user_started($userRow)) ww_json(array('ok'=>0,'error'=>'Сначала начни ивент.'));

  $today = ww_now_day_key();
  $last  = isset($userRow['daily_last_key']) ? (int)$userRow['daily_last_key'] : 0;
  if ($last === $today) {
    ww_json(array('ok'=>0,'error'=>'Ежедневная награда уже получена сегодня.'));
  }

  if (function_exists('itemAdd')) {
    itemAdd(WW_ITEM_BELL, WW_DAILY_BELLS, $user_id);
  }
  ww_user_set($mysqli, $user_id, array('daily_last_key'=>$today));
  ww_json(array('ok'=>1,'msg'=>'Ежедневно: +Колокольчик x'.WW_DAILY_BELLS,'data'=>ww_reload_payload($mysqli,$user_id)));
}

if ($action === 'turnin_step1') {
  ww_require_active($active);
  if (!ww_user_started($userRow)) ww_json(array('ok'=>0,'error'=>'Сначала начни ивент.'));
  if (isset($userRow['step1_done']) && (int)$userRow['step1_done'] === 1) ww_json(array('ok'=>0,'error'=>'Этап 1 уже выполнен.'));

  if (ww_item_count(WW_ITEM_ICE_LEAF,$user_id) < 3) ww_json(array('ok'=>0,'error'=>'Не хватает: Ледяной Лист x3'));
  if (ww_item_count(WW_ITEM_GHOST_SPARK,$user_id) < 2) ww_json(array('ok'=>0,'error'=>'Не хватает: Призрачная Искра x2'));
  if (ww_item_count(WW_ITEM_WARM_COAL,$user_id) < 2) ww_json(array('ok'=>0,'error'=>'Не хватает: Тёплый Уголь x2'));

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
  ww_json(array('ok'=>1,'msg'=>'Этап 1 завершён: +25 000, +Колокольчик x5','data'=>ww_reload_payload($mysqli,$user_id)));
}

if ($action === 'turnin_step2') {
  ww_require_active($active);
  if (!ww_user_started($userRow)) ww_json(array('ok'=>0,'error'=>'Сначала начни ивент.'));
  if (!isset($userRow['step1_done']) || (int)$userRow['step1_done'] !== 1) ww_json(array('ok'=>0,'error'=>'Сначала заверши этап 1.'));
  if (isset($userRow['step2_done']) && (int)$userRow['step2_done'] === 1) ww_json(array('ok'=>0,'error'=>'Этап 2 уже выполнен.'));

  if (ww_item_count(WW_ITEM_GRASS_MARK,$user_id) < 200) ww_json(array('ok'=>0,'error'=>'Не хватает: Травяная метка x200'));
  if (ww_item_count(WW_ITEM_POISON_MARK,$user_id) < 200) ww_json(array('ok'=>0,'error'=>'Не хватает: Ядовитая метка x200'));

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
  ww_json(array('ok'=>1,'msg'=>'Этап 2 завершён: +Зелёная конфета x10, +Фиолетовая конфета x10, +Колокольчик x5','data'=>ww_reload_payload($mysqli,$user_id)));
}

if ($action === 'turnin_step3') {
  ww_require_active($active);
  if (!ww_user_started($userRow)) ww_json(array('ok'=>0,'error'=>'Сначала начни ивент.'));
  if (!isset($userRow['step2_done']) || (int)$userRow['step2_done'] !== 1) ww_json(array('ok'=>0,'error'=>'Сначала заверши этап 2.'));
  if (isset($userRow['step3_done']) && (int)$userRow['step3_done'] === 1) ww_json(array('ok'=>0,'error'=>'Этап 3 уже выполнен.'));

  if (ww_item_count(WW_ITEM_ORNAMENT,$user_id) < 15) ww_json(array('ok'=>0,'error'=>'Не хватает: Новогоднее украшение x15'));

  if (function_exists('minus_item')) {
    minus_item(WW_ITEM_ORNAMENT, 15, $user_id);
  }
  if (function_exists('itemAdd')) {
    itemAdd(WW_ITEM_BELL, 10, $user_id);
  }
  ww_user_set($mysqli, $user_id, array('step3_done'=>1));
  ww_json(array('ok'=>1,'msg'=>'Этап 3 завершён: +Колокольчик x10','data'=>ww_reload_payload($mysqli,$user_id)));
}

if ($action === 'boss_start') {
  ww_require_active($active);
  if (!ww_user_started($userRow)) ww_json(array('ok'=>0,'error'=>'Сначала начни ивент.'));
  if (!isset($userRow['step3_done']) || (int)$userRow['step3_done'] !== 1) ww_json(array('ok'=>0,'error'=>'Сначала заверши этап 3.'));

  if (!class_exists('Info') || !method_exists('Info','_generatePve')) {
    ww_json(array('ok'=>0,'error'=>'Info::_generatePve не найден. Проверь подключение /do/Info.php.'));
  }

  if (!$mysqli) ww_json(array('ok'=>0,'error'=>'No DB connection'));
  $u = $mysqli->query("SELECT * FROM `users` WHERE `id`=".(int)$user_id." LIMIT 1");
  if (!$u || !$u->num_rows) ww_json(array('ok'=>0,'error'=>'User not found'));
  $user_info = $u->fetch_assoc();

  if (isset($user_info['status']) && (string)$user_info['status'] === 'battle') {
    ww_json(array('ok'=>0,'error'=>'Ты уже находишься в бою.'));
  }

  $loc = isset($user_info['location']) ? (int)$user_info['location'] : 0;
  Info::_generatePve($user_info, $loc, null, WW_BOSS_QUEST_ID, null, WW_BOSS_LEVEL);

  ww_json(array('ok'=>1,'msg'=>'Бой с Морозным стражем начат! Победи босса и получи Сердце стража.','data'=>ww_reload_payload($mysqli,$user_id)));
}

if ($action === 'turnin_step4') {
  ww_require_active($active);
  if (!ww_user_started($userRow)) ww_json(array('ok'=>0,'error'=>'Сначала начни ивент.'));
  if (!isset($userRow['step3_done']) || (int)$userRow['step3_done'] !== 1) ww_json(array('ok'=>0,'error'=>'Сначала заверши этап 3.'));
  if (isset($userRow['boss_done']) && (int)$userRow['boss_done'] === 1) ww_json(array('ok'=>0,'error'=>'Этап 4 уже выполнен.'));

  if (ww_item_count(WW_ITEM_BOSS_HEART,$user_id) < 1) {
    ww_json(array('ok'=>0,'error'=>'Сначала победи Морозного стража и получи «Сердце Морозного стража».'));
  }

  if (function_exists('minus_item')) {
    minus_item(WW_ITEM_BOSS_HEART, 1, $user_id);
  }

  if (function_exists('itemAdd')) {
    itemAdd(WW_TRAINING_ITEM_ID, 1, $user_id);
    itemAdd(WW_ITEM_BELL, 15, $user_id);
  }

  // Final reward: Alola Vulpix (form = "alola")
  $pokeRes = ww_grant_pokemon_simple($user_id, 37, 'alola', 5, 18, 28);
  if (!$pokeRes || !isset($pokeRes['ok']) || (int)$pokeRes['ok'] !== 1) {
    $err = isset($pokeRes['error']) ? $pokeRes['error'] : 'unknown';
    // Still mark boss done so the user does not lose progress; admin can compensate.
    ww_user_set($mysqli, $user_id, array('boss_done'=>1, 'completed'=>0));
    ww_json(array('ok'=>0,'error'=>'Босс сдан, награды выданы, но покемон не добавлен: '.$err,'data'=>ww_reload_payload($mysqli,$user_id)));
  }

  ww_user_set($mysqli, $user_id, array('boss_done'=>1,'completed'=>1));
  ww_json(array('ok'=>1,'msg'=>'Ивент завершён: +Набор тренировки x1, +Колокольчик x15, +Вульпикс (Алола)!','data'=>ww_reload_payload($mysqli,$user_id)));
}

if ($action === 'exchange_buy') {
  ww_require_active($active);
  if (!ww_user_started($userRow)) ww_json(array('ok'=>0,'error'=>'Сначала начни ивент.'));

  $sku = '';
  if (isset($_POST['sku'])) $sku = (string)$_POST['sku'];
  $sku = preg_replace('/[^a-z0-9_]/i','', $sku);

  $price = 0;
  $reward_type = '';
  $reward_item = 0;
  $reward_count = 0;

  if ($sku === 'candy_green') { $price=1; $reward_type='item'; $reward_item=WW_GRASS_CANDY_ITEM; $reward_count=1; }
  else if ($sku === 'candy_purple') { $price=1; $reward_type='item'; $reward_item=WW_POISON_CANDY_ITEM; $reward_count=1; }
  else if ($sku === 'training') { $price=5; $reward_type='item'; $reward_item=WW_TRAINING_ITEM_ID; $reward_count=1; }
  else if ($sku === 'gen_poke') { $price=15; $reward_type='pokemon'; }
  else { ww_json(array('ok'=>0,'error'=>'Неизвестный товар.')); }

  if (ww_item_count(WW_ITEM_BELL,$user_id) < $price) {
    ww_json(array('ok'=>0,'error'=>'Не хватает колокольчиков. Нужно: '.$price));
  }

  if (function_exists('minus_item')) {
    minus_item(WW_ITEM_BELL, $price, $user_id);
  }

  if ($reward_type === 'item') {
    if (function_exists('itemAdd')) itemAdd($reward_item, $reward_count, $user_id);
    ww_json(array('ok'=>1,'msg'=>'Покупка успешна.','data'=>ww_reload_payload($mysqli,$user_id)));
  }

  // Pokemon with genes 16–21
  $pick = $WW_GEN_POOL[array_rand($WW_GEN_POOL)];
  $form = '';
  // 10% chance: Alola Vulpix as a bonus in shop
  if (rand(1,100) <= 10) { $pick = 37; $form = 'alola'; }

  $pokeRes = ww_grant_pokemon_simple($user_id, $pick, $form, 5, 16, 21);
  if (!$pokeRes || !isset($pokeRes['ok']) || (int)$pokeRes['ok'] !== 1) {
    $err = isset($pokeRes['error']) ? $pokeRes['error'] : 'unknown';
    ww_json(array('ok'=>0,'error'=>'Покупка списала колокольчики, но покемон не добавлен: '.$err,'data'=>ww_reload_payload($mysqli,$user_id)));
  }

  ww_json(array('ok'=>1,'msg'=>'Покупка успешна: покемон с генами 16–21.','data'=>ww_reload_payload($mysqli,$user_id)));
}

// ---------- Admin ----------
if ($action === 'admin_get') {
  if (!ww_is_admin($user_id)) ww_json(array('ok'=>0,'error'=>'Нет доступа'));
  $st = ww_get_state($mysqli);
  $payload = ww_reload_payload($mysqli, $user_id);
  $payload['admin'] = array('state'=>$st);
  ww_json($payload);
}

if ($action === 'admin_set') {
  if (!ww_is_admin($user_id)) ww_json(array('ok'=>0,'error'=>'Нет доступа'));
  if (!$mysqli) ww_json(array('ok'=>0,'error'=>'No DB connection'));

  $active_v = isset($_POST['active']) ? (int)$_POST['active'] : null;
  $start_ts = isset($_POST['start_ts']) ? (int)$_POST['start_ts'] : null;
  $end_ts   = isset($_POST['end_ts']) ? (int)$_POST['end_ts'] : null;
  $name     = isset($_POST['name']) ? (string)$_POST['name'] : null;

  $parts = array();
  if ($active_v !== null) $parts[] = "`active`=".(int)$active_v;
  if ($start_ts !== null) $parts[] = "`start_ts`=".(int)$start_ts;
  if ($end_ts !== null) $parts[] = "`end_ts`=".(int)$end_ts;
  if ($name !== null) $parts[] = "`name`='".$mysqli->real_escape_string($name)."'";
  $parts[] = "`updated_at`=".time();

  $mysqli->query("UPDATE `aa_event_winterwonder_state` SET ".implode(',', $parts)." WHERE `id`=1 LIMIT 1");
  ww_json(array('ok'=>1,'msg'=>'Сохранено','data'=>ww_reload_payload($mysqli,$user_id)));
}

ww_json(array('ok'=>0,'error'=>'Unknown action'));
