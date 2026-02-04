<?php
// /do/Npc/8127.php — Аделина (инженер). Старт квеста и честный финал.
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
global $mysqli, $response;
if (!isset($_SESSION['id'])) { $response['question'] = 'Нужно войти в игру.'; return; }

$uid    = (int)$_SESSION['id'];
$player = htmlspecialchars($_SESSION['login'] ?? 'тренер');
$THIS_NPC_ID = 8127;

/* ==== Витрина NPC ==== */
$row = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`={$THIS_NPC_ID}")->fetch_assoc();
$response['name']  = $row['name']  ?? 'Аделина';
$response['image'] = $row['image'] ?? '/img/default-npc.png';

/* ==== КОНСТАНТЫ КВЕСТА (общие для всех трёх файлов) ==== */
const QUEST_ID   = 221;
const QUEST_NAME = 'Стальные узоры';
const REWARD_MONEY_ID  = 1;

const DROP_ITEM_ID      = 80; // Громовой камень — как «импульсная шестерня»
const NEED_ELECTRO_STEEL = 3; // нужно выбить (поймать) 3 уникальных из списка ниже

// Разрешённые виды для «выбивания» (учтём сталь/электро; уникальность видом; с момента старта)
const ALLOWED_SPECIES = [25,81,82,100,135,599]; // Pikachu, Magnemite/Magneton, Voltorb, Jolteon, Klink

// Награды по финалам
const RWD_GOOD_MONEY  = 70000;
const RWD_GOOD_ITEMS  = [193=>1,3=>15]; // витамины, грейтболы
const RWD_GOOD_PKMN   = 707; // Klefki

const RWD_SHADY_MONEY = 90000;
const RWD_SHADY_ITEMS = [26=>3,3=>10]; // конфеты, грейты
const RWD_SHADY_PKMN  = 115; // Kangaskhan

const RWD_PROTO_MONEY = 50000;
const RWD_PROTO_ITEMS = [3=>10];
const RWD_PROTO_PKMN  = 599; // Klink

/* ==== ХЕЛПЕРЫ ==== */
function pill($txt){return '<span style="display:inline-block;padding:2px 6px;border:1px solid rgba(255,255,255,.2);border-radius:8px;background:rgba(255,255,255,.06);margin-left:6px">'.$txt.'</span>';}
function item_icon($id){ return '/img/world/items/little/'.intval($id).'.png'; }
function need_list_html(array $map): string {
  $buf=''; foreach($map as $id=>$q){
    $buf.='<li><img src="'.item_icon($id).'" style="width:18px;height:18px;vertical-align:-3px;margin-right:6px">#'.$id.' × <b>'.$q.'</b></li>';
  } return '<ul style="margin:6px 0 0 18px">'.$buf.'</ul>';
}
function log_quest_step($step,$text){
  if (function_exists('quest_update')) quest_update(QUEST_ID,$step);
  if (function_exists('update_zap'))   update_zap(QUEST_ID,$step,$text);
  if (function_exists('quest_zap'))    quest_zap(QUEST_ID,$step,$text);
}

/* ==== ХРАНИЛКА ==== */
function q_get(mysqli $db,int $uid):?array{
  $r=$db->query("SELECT * FROM `user_quests` WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1")->fetch_assoc();
  if(!$r) return null;
  $r['step']=(int)$r['step']; $r['end']=(int)($r['end']??0);
  $r['data']=json_decode($r['data']??'[]',true)?:[];
  return $r;
}
function q_create(mysqli $db,int $uid):array{
  $now=time();
  $data=['start_ts'=>$now,'ts_drop'=>$now,'path'=>null]; // path: good|shady|proto
  $json=$db->real_escape_string(json_encode($data,JSON_UNESCAPED_UNICODE));
  $db->query("INSERT INTO `user_quests` (`user_id`,`quest_id`,`step`,`end`,`data`) VALUES ({$uid},".QUEST_ID.",1,0,'{$json}')");
  log_quest_step(1,'Старт у Аделины. Задача: добыть импульсную шестерню (id80) или выбить 3 вида сталь/электро.');
  return q_get($db,$uid);
}
function q_save(mysqli $db,int $uid,int $step,array $data,int $end=0):void{
  $json=$db->real_escape_string(json_encode($data,JSON_UNESCAPED_UNICODE));
  $db->query("UPDATE `user_quests` SET `step`={$step},`end`={$end},`data`='{$json}' WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1");
}

/* ==== ВЫБИВАНИЕ (учёт уникальных видов после ts_drop) ==== */
function detect_catch_source(mysqli $db):?array{
  $cands=[
    ['user_pokemons',['user_id','user','owner'],['basenum','pokemon_id','species'],['time','date','date_get','created_at','caught_at'],['birthday','info','meta']],
    ['pokemons_users',['user_id','user','owner'],['basenum','pokemon_id','species'],['time','date','date_get','created_at','caught_at'],['birthday','info','meta']],
    ['users_pokemons',['user_id','user','owner'],['basenum','pokemon_id','species'],['time','date','date_get','created_at','caught_at'],['birthday','info','meta']],
    ['pokemons',['user_id','user','owner'],['basenum','pokemon_id','species'],['time','date','date_get','created_at','caught_at'],['birthday','info','meta']],
  ];
  foreach($cands as [$t,$uc,$sc,$tc,$bc]){
    $chk=$db->query("SHOW TABLES LIKE '".$db->real_escape_string($t)."'"); if(!$chk||!$chk->num_rows) continue;
    $cols=[];$types=[];
    if($rs=$db->query("SHOW COLUMNS FROM `{$t}`")) while($rw=$rs->fetch_assoc()){ $cols[$rw['Field']]=1; $types[$rw['Field']]=strtolower($rw['Type']??'');}
    $u=$s=$tm=$bd=null;
    foreach($uc as $c) if(isset($cols[$c])){ $u=$c; break; }
    foreach($sc as $c) if(isset($cols[$c])){ $s=$c; break; }
    foreach($tc as $c) if(isset($cols[$c])){ $tm=$c; break; }
    foreach($bc as $c) if(isset($cols[$c])){ $bd=$c; break; }
    if($u && $tm) return ['t'=>$t,'u'=>$u,'s'=>$s,'tm'=>$tm,'bd'=>$bd];
  }
  return null;
}
function unique_count_since(mysqli $db,int $uid,int $startTs,array $allow):int{
  $src=detect_catch_source($db); if(!$src) return 0;
  $t=$src['t']; $u=$src['u']; $s=$src['s']; $tm=$src['tm']; $bd=$src['bd'];
  $sel="`{$tm}` AS tcol"; if($bd) $sel.=",`{$bd}` AS bcol"; if($s) $sel.=",`{$s}` AS scol";
  $cond=["`{$u}`={$uid}"]; if($s && $allow){ $in=implode(',',array_map('intval',$allow)); $cond[]="`{$s}` IN ({$in})"; }
  $res=$db->query("SELECT {$sel} FROM `{$t}` WHERE ".implode(' AND ',$cond)); if(!$res) return 0;
  $ok=[];
  while($r=$res->fetch_assoc()){
    $ts=0; $raw=$r['tcol'];
    if($raw!==null && $raw!==''){ $ts=ctype_digit((string)$raw)?(int)$raw:(strtotime($raw)?:0); }
    if($ts===0 && isset($r['bcol'])){
      $b=(string)$r['bcol'];
      if(($j=json_decode($b,true)) && isset($j['date']) && ctype_digit((string)$j['date'])) $ts=(int)$j['date'];
      elseif(preg_match('/(?<!\d)(1[5-9]\d{8,10}|2\d{9,10})(?!\d)/',$b,$m)) $ts=(int)$m[0];
    }
    $ownerOK=true;
    if(isset($r['bcol'])){
      $b=(string)$r['bcol']; $uidS=(string)$uid;
      $ownerOK=(strpos($b,'"user_id":"'.$uidS.'"')!==false)||(strpos($b,'"user_id":'.$uidS)!==false);
      if(($j=json_decode($b,true)) && isset($j['user_id'])) $ownerOK=(string)$j['user_id']===$uidS;
    }
    if($ownerOK && $ts>=$startTs){
      $sp = isset($r['scol']) ? (int)$r['scol'] : 0;
      if($sp && in_array($sp,$allow,true)) $ok[$sp]=true;
    }
  }
  return count($ok);
}

/* ==== ДИАЛОГ ==== */
$st  = q_get($mysqli,$uid);
$req = isset($npcStep) ? (int)$npcStep : 0;

if ($st && (int)$st['end']===1){
  $response['question']='Аделина поправляет очки: «Мы уже достроили узор. Если услышишь о новых сбоях — заглядывай».'; 
  $response['answer']=[0=>'Кивнуть'];
  return;
}

/* Старт */
if(!$st){
  if($req===101){
    $st=q_create($mysqli,$uid);
    $response['actionQuest']='Начато задание <b>'.QUEST_NAME.'</b>.';
    $response['question']='«Нужна импульсная шестерня для полевого резонатора. Добыть можно с редким импульс-камнем (id80) или поймав разных стальных/электрических — я соберу из осколков». '.pill('Шаг 1/4').'<br><br><b>Варианты:</b><br>• Принеси <b>Громовой камень ×1</b>.<br>• Или поймай <b>'.NEED_ELECTRO_STEEL.'</b> разных: Pikachu(25), Magnemite(81), Magneton(82), Voltorb(100), Jolteon(135), Klink(599).<br><i>Считается только после этого разговора.</i>';
    $response['answer']=[201=>'Проверить прогресс', 301=>'Передать материалы'];
  } else {
    $response['question']='Аделина достаёт блокнот: «Ищешь работу по уму?»';
    $response['answer']=[101=>'Помочь Аделине'];
  }
  return;
}

/* ——— уже в квесте ——— */
$data=$st['data']; $step=(int)$st['step'];

switch(true){

  // ПРОГРЕСС
  case ($req===201):
    $caught = unique_count_since($mysqli,$uid,(int)($data['ts_drop']??$data['start_ts']??time()),ALLOWED_SPECIES);
    $haveStone = (function_exists('item_isset') && item_isset(DROP_ITEM_ID,1));
    $txt='<b>Поймано разных</b>: '.$caught.' / '.NEED_ELECTRO_STEEL.'<br><b>Камень</b>: '.($haveStone?'есть':'нет');
    if($step===1){
      $response['question']='«Делаешь успехи».<br>'.$txt;
      $response['answer']=[201=>'Проверить ещё раз',301=>'Передать материалы'];
    } elseif ($step>=2){
      $response['question']='«Материалы уже в работе. Иди к Шу — он калибрует код». '.pill('Шаг 2/4');
      $response['answer']=[0=>'Понял'];
    }
    break;

  // ПЕРЕДАЧА МАТЕРИАЛОВ → шаг 2
  case ($req===301):
    if($step>1){ $response['question']='«Материалы уже приняты. Шу ждёт тебя у мастерской».'; break; }

    $caught = unique_count_since($mysqli,$uid,(int)($data['ts_drop']??$data['start_ts']??time()),ALLOWED_SPECIES);
    $haveStone = (function_exists('item_isset') && item_isset(DROP_ITEM_ID,1));

    if(!$haveStone && $caught<NEED_ELECTRO_STEEL){
      $response['question']='«Пока мало. Нужен <b>Громовой камень</b> или <b>'.NEED_ELECTRO_STEEL.'</b> разных сталь/электро».'; 
      $response['answer']=[201=>'Проверить прогресс'];
      break;
    }

    // списываем камень, если есть
    if($haveStone && function_exists('minus_item')) minus_item(DROP_ITEM_ID,1);
    $data['gear_ready']=1;
    q_save($mysqli,$uid,2,$data,0);
    log_quest_step(2,'Материалы у Аделины. Направляет к Шу (8129) для калибровки.');
    $response['question']='Аделина кивает: «Отлично. Шу доведёт код до ума. Скажи, что от меня». '.pill('Шаг 2/4');
    $response['answer']=[0=>'К Шу (8129)'];

    // вариант прототипа, если камня не было
    if(!$haveStone){
      $response['question'].='<br><span style="color:#b8bce3">Кстати, если камень так и не найдёшь — вернёшься, соберём прототип.</span>';
      $data['proto_allowed']=1; q_save($mysqli,$uid,2,$data,0);
    }
    break;

  // ФИНАЛ (честный или прототип)
  case ($req===401): // честный
    if($step<3 || empty($data['calibrated'])){
      $response['question']='«Сначала забери у Шу откалиброванный код».'; break;
    }
    // честный финал
    if(function_exists('itemAdd')){
      itemAdd(REWARD_MONEY_ID,RWD_GOOD_MONEY,$uid);
      foreach(RWD_GOOD_ITEMS as $iid=>$q) itemAdd($iid,$q,$uid);
    }
    if(function_exists('newPokemon')){
      // lvl 15, как у твоего примера параметры
      newPokemon(RWD_GOOD_PKMN,$uid,15,25,1,'false',1,false,false,4,15,true);
    }
    $data['path']='good'; q_save($mysqli,$uid,4,$data,1);
    log_quest_step(4,'Честный финал у Аделины. Награда: Klefki.');
    $response['actionQuestPlus']='<img src="'.item_icon(1).'"> Генкары <b>'.number_format(RWD_GOOD_MONEY,0,'',' ').'</b><br><img src="'.item_icon(193).'"> Коробка витаминов ×1<br><img src="'.item_icon(3).'"> Грейтбол ×15<br>Покемон: <b>#707 Klefki</b>';
    $response['question']='«Идеально! Сетка поёт ровно. Забирай Клефки — ему по душе твоя аккуратность».'; 
    $response['answer']=[0=>'Спасибо'];
    break;

  case ($req===402): // прототип (если нет камня, но были поимки)
    if($step<2 || empty($data['proto_allowed'])){
      $response['question']='«Этот путь не нужен, у тебя есть материалы».'; break;
    }
    if(function_exists('itemAdd')){
      itemAdd(REWARD_MONEY_ID,RWD_PROTO_MONEY,$uid);
      foreach(RWD_PROTO_ITEMS as $iid=>$q) itemAdd($iid,$q,$uid);
    }
    if(function_exists('newPokemon')){
      newPokemon(RWD_PROTO_PKMN,$uid,15,25,1,'false',1,false,false,4,15,true);
    }
    $data['path']='proto'; q_save($mysqli,$uid,4,$data,1);
    log_quest_step(4,'Финал с прототипом у Аделины. Награда: Klink.');
    $response['actionQuestPlus']='<img src="'.item_icon(1).'"> Генкары <b>'.number_format(RWD_PROTO_MONEY,0,'',' ').'</b><br><img src="'.item_icon(3).'"> Грейтбол ×10<br>Покемон: <b>#599 Klink</b>';
    $response['question']='«Без камня грубее, но рабоче. Держи Клинка — честная плата за смекалку».'; 
    $response['answer']=[0=>'Спасибо'];
    break;

  default:
    if($step===1){
      $response['question']='«Импульсную шестерню либо добываем камнем (id80), либо собираем из пойманных стальных/электро». '.pill('Шаг 1/4');
      $response['answer']=[201=>'Проверить прогресс',301=>'Передать материалы'];
    } elseif($step===2){
      $response['question']='«Материалы приняты. Иди к Шу (8129) за калибровкой». '.pill('Шаг 2/4');
      $btns=[0=>'Понял'];
      if(!empty($data['proto_allowed'])) $btns[402]='Собрать прототип и закончить';
      $response['answer']=$btns;
    } elseif($step===3){
      $response['question']='«Шу должен был выдать тебе код. Вернёшься — закроем работу». '.pill('Шаг 3/4');
      $response['answer']=[401=>'Завершить честно у Аделины'];
    } else {
      $response['question']='«Узор завершён. Береги оборудование».'; 
      $response['answer']=[0=>'До встречи'];
    }
    break;
}
