<?php
// /do/Npc/8128.php — Лисандр. Альтернативный «тёмный» финал.
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
global $mysqli, $response;
if (!isset($_SESSION['id'])) { $response['question'] = 'Нужно войти в игру.'; return; }

$uid    = (int)$_SESSION['id'];
$player = htmlspecialchars($_SESSION['login'] ?? 'тренер');
$THIS_NPC_ID = 8128;

/* Витрина */
$row = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`={$THIS_NPC_ID}")->fetch_assoc();
$response['name']  = $row['name']  ?? 'Лисандр';
$response['image'] = $row['image'] ?? '/img/default-npc.png';

/* Общие константы */
const QUEST_ID   = 221;
const QUEST_NAME = 'Стальные узоры';
const REWARD_MONEY_ID = 1;

// Награды этого финала (сделка)
const RWD_SHADY_MONEY = 90000;
const RWD_SHADY_ITEMS = [26=>3,3=>10];
const RWD_SHADY_PKMN  = 115; // Kangaskhan

function pill($t){return '<span style="display:inline-block;padding:2px 6px;border:1px solid rgba(255,255,255,.2);border-radius:8px;background:rgba(255,255,255,.06);margin-left:6px">'.$t.'</span>';}
function item_icon($id){ return '/img/world/items/little/'.intval($id).'.png'; }
function log_quest_step($step,$text){
  if(function_exists('quest_update')) quest_update(QUEST_ID,$step);
  if(function_exists('update_zap'))   update_zap(QUEST_ID,$step,$text);
  if(function_exists('quest_zap'))    quest_zap(QUEST_ID,$step,$text);
}

/* ХРАНИЛКА */
function q_get(mysqli $db,int $uid):?array{
  $r=$db->query("SELECT * FROM `user_quests` WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1")->fetch_assoc();
  if(!$r) return null;
  $r['step']=(int)$r['step']; $r['end']=(int)($r['end']??0);
  $r['data']=json_decode($r['data']??'[]',true)?:[];
  return $r;
}
function q_save(mysqli $db,int $uid,int $step,array $data,int $end=0):void{
  $json=$db->real_escape_string(json_encode($data,JSON_UNESCAPED_UNICODE));
  $db->query("UPDATE `user_quests` SET `step`={$step},`end`={$end},`data`='{$json}' WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1");
}

/* ДИАЛОГ */
$st=q_get($mysqli,$uid);
$req=isset($npcStep)?(int)$npcStep:0;

if(!$st){
  $response['question']='Лисандр лениво листает каталог: «Я говорю только с теми, кто держит ключи от узора».'; 
  $response['answer']=[0=>'Уйти'];
  return;
}
$data=$st['data']; $step=(int)$st['step'];

if ($st['end']===1){
  $response['question']='«Сделки закрыты. Ищи новые истории, '.$player.'».'; 
  $response['answer']=[0=>'Кивнуть'];
  return;
}

if($step<2){
  $response['question']='«Сначала наведайся к Аделине. У неё как раз нехватка деталей».'; 
  $response['answer']=[0=>'Понял'];
  return;
}

if($step===2 && empty($data['calibrated'])){
  $response['question']='«У Шу возьми чистый код. Тогда поговорим».'; 
  $response['answer']=[0=>'Ладно'];
  return;
}

/* Выбор: честность или сделка */
if($step>=3 && empty($st['end'])){
  if($req===701){
    // тёмный финал
    if(function_exists('itemAdd')){
      itemAdd(REWARD_MONEY_ID,RWD_SHADY_MONEY,$uid);
      foreach(RWD_SHADY_ITEMS as $iid=>$q) itemAdd($iid,$q,$uid);
    }
    if(function_exists('newPokemon')){
      newPokemon(RWD_SHADY_PKMN,$uid,15,25,1,'false',1,false,false,4,15,true);
    }
    $data['path']='shady'; q_save($mysqli,$uid,4,$data,1);
    log_quest_step(4,'Сделка с Лисандром. Награда: Kangaskhan.');
    $response['actionQuestPlus']='<img src="'.item_icon(1).'"> Генкары <b>'.number_format(RWD_SHADY_MONEY,0,'',' ').'</b><br><img src="'.item_icon(26).'"> Жёлтая конфета ×3<br><img src="'.item_icon(3).'"> Грейтбол ×10<br>Покемон: <b>#115 Kangaskhan</b>';
    $response['question']='Лисандр улыбается уголком губ: «Беру код, а ты берёшь свою долю. Нечего смотреть назад».'; 
    $response['answer']=[0=>'Уйти'];
    return;
  }

  // экран предложения
  $response['question']='Лисандр постукивает по столу: «У тебя есть калиброванный узор. Или отдаёшь его Аделине — и получаешь её благодарность… или отдаёшь мне — и получаешь кое-что покрупнее». '.pill('Шаг 3/4');
  $response['answer']=[
    701=>'Заключить сделку с Лисандром (тёмный финал)',
    0  =>'Пожалуй, останусь честным (уйти)'
  ];
  return;
}

/* fallback */
$response['question']='«Если передумаешь — знаешь, где меня найти».'; 
$response['answer']=[0=>'Кивнуть'];
