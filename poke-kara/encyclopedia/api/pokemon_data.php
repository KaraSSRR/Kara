<?php
require_once __DIR__ . '/../_inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$id = enc_int((isset($_GET['id']) ? $_GET['id'] : 0), 0);
$formReq = enc_sanitize_form((isset($_GET['form']) ? $_GET['form'] : ''));

if ($id <= 0) {
  echo json_encode(['ok'=>false,'error'=>'id'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Helpers
function enc_csv_int_list($s) {
  $s = trim((string)$s);
  if ($s === '') return [];
  $parts = preg_split('~\s*,\s*~', $s);
  $out = [];
  foreach ($parts as $p) {
    $p = trim((string)$p);
    if ($p === '') continue;
    if (!preg_match('~^\d+$~', $p)) continue;
    $n = (int)$p;
    if ($n > 0) $out[] = $n;
  }
  return $out;
}

// Base pokemon
$stmt = $mysqli->prepare("SELECT id,name,name_rus,type,type_two,hp,atk,def,satk,sdef,spd FROM base_pokemons WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$base = enc_stmt_fetch_assoc($stmt);
$stmt->close();

if (!$base) {
  echo json_encode(['ok'=>false,'error'=>'not_found'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Forms (base + optional)
$forms = [];
$baseTypes = [ (string)$base['type'] ];
if ((string)$base['type_two'] !== '' && (string)$base['type_two'] !== 'not') $baseTypes[] = (string)$base['type_two'];

$forms[] = [
  'form' => '',
  'start' => 1,
  'label' => 'Обычная',
  'types' => $baseTypes,
  'base_stats' => [
    'hp'=>(int)$base['hp'],
    'atk'=>(int)$base['atk'],
    'def'=>(int)$base['def'],
    'satk'=>(int)$base['satk'],
    'sdef'=>(int)$base['sdef'],
    'spd'=>(int)$base['spd'],
  ],
];

$stmt = $mysqli->prepare("SELECT id_form,name,type,type_two,hp,atk,def,satk,sdef,spd,start FROM base_pokemon_forms WHERE pokemons=? ORDER BY start DESC, id ASC");
$stmt->bind_param('i', $id);
$stmt->execute();
$rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) {
  $frm = enc_sanitize_form((string)$r['id_form']);
  if ($frm === '') continue;

  $types = [ (string)$r['type'] ];
  if ((string)$r['type_two'] !== '' && (string)$r['type_two'] !== 'not') $types[] = (string)$r['type_two'];

  $label = trim((string)$r['name']);
  if ($label === '') $label = $frm;

  $forms[] = [
    'form' => $frm,
    'start' => (int)$r['start'],
    'label' => $label,
    'types' => $types,
    'base_stats' => [
      'hp'=>(int)$r['hp'],
      'atk'=>(int)$r['atk'],
      'def'=>(int)$r['def'],
      'satk'=>(int)$r['satk'],
      'sdef'=>(int)$r['sdef'],
      'spd'=>(int)$r['spd'],
    ],
  ];
}
$stmt->close();

// Abilities (slots)
$abilities = [
  'slot1' => ['id'=>0,'name'=>'','name_rus'=>''],
  'slot2' => ['id'=>0,'name'=>'','name_rus'=>''],
  'hidden'=> ['id'=>0,'name'=>'','name_rus'=>''],
];

$q = $mysqli->query("SELECT slot1,slot2,hidden FROM base_ability_pokemon WHERE id=".(int)$id." LIMIT 1");
$slots = $q ? $q->fetch_assoc() : null;

$abilityIds = [];
if ($slots) {
  foreach (['slot1','slot2','hidden'] as $k) {
    $v = enc_int((isset($slots[$k]) ? $slots[$k] : 0), 0);
    if ($v > 0) $abilityIds[] = $v;
  }
}
$abilityIds = array_values(array_unique($abilityIds));

$abilityMap = [];
if ($abilityIds) {
  $q = $mysqli->query("SELECT id,name,name_rus FROM base_ability WHERE id IN (".implode(',', array_map('intval',$abilityIds)).")");
  if ($q) while ($r = $q->fetch_assoc()) $abilityMap[(int)$r['id']] = $r;
}

if ($slots) {
  foreach (['slot1','slot2','hidden'] as $k) {
    $aid = enc_int((isset($slots[$k]) ? $slots[$k] : 0), 0);
    if ($aid > 0 && isset($abilityMap[$aid])) {
      $abilities[$k] = [
        'id' => $aid,
        'name' => (string)$abilityMap[$aid]['name'],
        'name_rus' => (string)$abilityMap[$aid]['name_rus'],
      ];
    } elseif ($aid > 0) {
      $abilities[$k] = ['id'=>$aid,'name'=>'','name_rus'=>''];
    }
  }
}

// Moves (lvl/tm/hm) — from base_attacks_pokemons (attacks=list, lvl=list for type='lvl')
$movesLvl = [];
$movesTm = [];
$movesHm = [];

$formKey = ($formReq === '' ? '0' : $formReq);

$loadMoves = function($formVal) use ($mysqli, $id, &$movesLvl, &$movesTm, &$movesHm) {
  $stmt = $mysqli->prepare("SELECT type, attacks, lvl FROM base_attacks_pokemons WHERE pok=? AND type IN ('lvl','tm','hm') AND form=?");
  $stmt->bind_param("is", $id, $formVal);
  $stmt->execute();
  $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) {
    $type = (string)$r['type'];
    $ids = enc_csv_int_list((string)$r['attacks']);

    if ($type === 'lvl') {
      $lvls = enc_csv_int_list((string)$r['lvl']);
      $n = max(count($ids), count($lvls));
      for ($i=0; $i<count($ids); $i++) {
        $lvl = (isset($lvls[$i]) ? (int)$lvls[$i] : 1);
        if ($lvl <= 0) $lvl = 1;
        $movesLvl[] = ['id'=>(int)$ids[$i], 'lvl'=>$lvl];
      }
    } elseif ($type === 'tm') {
      foreach ($ids as $mid) $movesTm[] = (int)$mid;
    } elseif ($type === 'hm') {
      foreach ($ids as $mid) $movesHm[] = (int)$mid;
    }
  }
  $stmt->close();
};

$loadMoves($formKey);

if ($formKey !== '0' && empty($movesLvl) && empty($movesTm) && empty($movesHm)) {
  $loadMoves('0'); // fallback
}



// TM moves come from the game's TM tables (attac_poke_tm + base_items.info), not from base_attacks_pokemons.
$tmFromItems = enc_tm_move_ids_for_pokemon($mysqli, $id);
if (!empty($tmFromItems)) {
  foreach ($tmFromItems as $mid) $movesTm[] = (int)$mid;
}

// Deduplicate
$uniq = [];
$tmp = [];
foreach ($movesLvl as $m) {
  $key = (int)$m['id'] . ':' . (int)$m['lvl'];
  if (isset($uniq[$key])) continue;
  $uniq[$key] = 1;
  $tmp[] = ['id'=>(int)$m['id'], 'lvl'=>(int)$m['lvl']];
}
$movesLvl = $tmp;

$movesTm = array_values(array_unique(array_filter(array_map('intval', $movesTm))));
$movesHm = array_values(array_unique(array_filter(array_map('intval', $movesHm))));

// move map
$allMoveIds = [];
foreach ($movesLvl as $m) $allMoveIds[] = (int)$m['id'];
foreach ($movesTm as $m) $allMoveIds[] = (int)$m;
foreach ($movesHm as $m) $allMoveIds[] = (int)$m;
$allMoveIds = array_values(array_unique(array_filter($allMoveIds)));

$moveMap = [];
if ($allMoveIds) {
  // Pull extra fields if present in schema (PHP 5.6 safe)
  $sql = "SELECT * FROM base_atk WHERE id IN (".implode(',', array_map('intval', $allMoveIds)).")";
  $q = $mysqli->query($sql);
  if ($q) while ($r = $q->fetch_assoc()) {
    $mid = (int)$r['id'];
    $row = [
      'id'=>$mid,
      'name'=>(string)$r['name'],
      'name_rus'=>(string)$r['name_rus'],
      'type'=>(string)$r['type'],
      'category'=>(string)$r['category'],
      'power'=>(int)$r['power'],
      'accuracy'=>(int)$r['accuracy'],
      'pp'=>(int)$r['pp'],
    ];
    $extra = ['priority','target','contact','sound','punch','bite','bullet','pulse'];
    foreach ($extra as $k) {
      if (!isset($r[$k])) continue;
      $row[$k] = is_numeric($r[$k]) ? (int)$r[$k] : (string)$r[$k];
    }
    $moveMap[(string)$mid] = $row;
  }
}

// Sort lvl list for stable UX (lvl asc, id asc)
usort($movesLvl, function($a,$b){
  $la = (int)$a['lvl']; $lb = (int)$b['lvl'];
  if ($la === $lb) {
    $ai = (int)$a['id']; $bi = (int)$b['id'];
    if ($ai === $bi) return 0;
    return ($ai < $bi) ? -1 : 1;
  }
  return ($la < $lb) ? -1 : 1;
});

echo json_encode([
  'ok' => true,
  'pokemon' => [
    'id' => (int)$base['id'],
    'name' => (string)$base['name'],
    'name_rus' => (string)$base['name_rus'],
    'types' => $baseTypes,
    'base_stats' => [
      'hp'=>(int)$base['hp'],
      'atk'=>(int)$base['atk'],
      'def'=>(int)$base['def'],
      'satk'=>(int)$base['satk'],
      'sdef'=>(int)$base['sdef'],
      'spd'=>(int)$base['spd'],
    ],
  ],
  'forms' => $forms,
  'abilities' => $abilities,
  'moves' => [
    'lvl' => $movesLvl,
    'tm' => $movesTm,
    'hm' => $movesHm,
    'map' => $moveMap,
  ],
], JSON_UNESCAPED_UNICODE);