<?php
// /do/Npc/8129.php — Шу. Мини-игра Mastermind и отметка калибровки.
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
global $mysqli, $response;
if (!isset($_SESSION['id'])) { $response['question'] = 'Нужно войти в игру.'; return; }

$uid    = (int)$_SESSION['id'];
$player = htmlspecialchars($_SESSION['login'] ?? 'тренер');
$THIS_NPC_ID = 8129;

/* Витрина */
$row = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`={$THIS_NPC_ID}")->fetch_assoc();
$response['name']  = $row['name']  ?? 'Шу';
$response['image'] = $row['image'] ?? '/img/default-npc.png';

/* Общие константы квеста (те же) */
const QUEST_ID   = 221;
const QUEST_NAME = 'Стальные узоры';

/* Параметры мини-игры */
const MM_LEN   = 4;   // длина кода
const MM_TRIES = 10;  // попыток

// 4 кнопки-сигнала
const MM_BTN_1 = 901;
const MM_BTN_2 = 902;
const MM_BTN_3 = 903;
const MM_BTN_4 = 904;
const MM_SUBMIT = 910;
const MM_CLEAR  = 911;
const MM_RESET  = 912;

function pill($t){return '<span style="display:inline-block;padding:2px 6px;border:1px solid rgba(255,255,255,.2);border-radius:8px;background:rgba(255,255,255,.06);margin-left:6px">'.$t.'</span>';}
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

/* Мини-игра — внутренние */
function mm_new_secret():string{
  $s=''; for($i=0;$i<MM_LEN;$i++){ $s .= (string)random_int(1,4); } return $s;
}
function mm_boot(array &$data):void{
  $data['mm']=[
    'secret'=>mm_new_secret(),
    'buffer'=>'',
    'log'=>[],     // каждая строка: guess + (A,B)
    'tries'=>0
  ];
}
function mm_view(array $mm):string{
  $rows='';
  foreach($mm['log'] as $i=>$row){
    $rows.='<div>Попытка '.($i+1).': <b>'.$row['g'].'</b> → на месте: <b>'.$row['a'].'</b>, в наборе: <b>'.$row['b'].'</b></div>';
  }
  if(!$rows) $rows='<div style="opacity:.7">История пуста.</div>';
  return $rows;
}
function mm_buttons():array{
  return [
    MM_BTN_1=>'①',
    MM_BTN_2=>'②',
    MM_BTN_3=>'③',
    MM_BTN_4=>'④',
    MM_SUBMIT=>'Проверить',
    MM_CLEAR=>'Стереть',
    MM_RESET=>'Начать заново',
  ];
}

/* ДИАЛОГ */
$st=q_get($mysqli,$uid);
$req=isset($npcStep)?(int)$npcStep:0;

if(!$st){
  $response['question']='Шу рассеянно щёлкает ножом: «Меня присылают только от Аделины».';
  $response['answer']=[0=>'Уйти'];
  return;
}
$data=$st['data']; $step=(int)$st['step'];

if($st['end']===1){
  $response['question']='«Работа уже сделана. Лишних кодов не держу».'; 
  $response['answer']=[0=>'Кивнуть'];
  return;
}

if($step<2){
  $response['question']='«Пока не время. Пусть Аделина сначала подготовит материалы».'; 
  $response['answer']=[0=>'Понял'];
  return;
}

/* ИГРА */
if(empty($data['mm']['secret'])) mm_boot($data);

switch(true){

  // Нажатия 4-х цифр
  case in_array($req,[MM_BTN_1,MM_BTN_2,MM_BTN_3,MM_BTN_4],true):
    $btnToDigit=[MM_BTN_1=>'1',MM_BTN_2=>'2',MM_BTN_3=>'3',MM_BTN_4=>'4'];
    $d=$btnToDigit[$req];
    $buf=(string)$data['mm']['buffer'];
    if(strlen($buf)<MM_LEN){ $buf.=$d; $data['mm']['buffer']=$buf; }
    q_save($mysqli,$uid,max($step,2),$data,0);
    break;

  // Стереть
  case ($req===MM_CLEAR):
    $data['mm']['buffer']='';
    q_save($mysqli,$uid,max($step,2),$data,0);
    break;

  // Сброс
  case ($req===MM_RESET):
    mm_boot($data);
    q_save($mysqli,$uid,max($step,2),$data,0);
    break;

  // Проверка
  case ($req===MM_SUBMIT):
    $buf=(string)($data['mm']['buffer']??'');
    if(strlen($buf)<MM_LEN){
      // ничего не делаем
    }else{
      $sec=(string)$data['mm']['secret'];
      // считаем «быков» (на месте)
      $A=0; for($i=0;$i<MM_LEN;$i++){ if($buf[$i]===$sec[$i]) $A++; }
      // считаем «коров» (в наборе, но не на месте)
      $counts=function($s){$m=[1,2,3,4];$c=['1'=>0,'2'=>0,'3'=>0,'4'=>0]; for($i=0;$i<strlen($s);$i++){ $c[$s[$i]]++; } return $c;};
      $ca=$counts($buf); $cb=$counts($sec); $sum=0; foreach(['1','2','3','4'] as $k){ $sum+=min($ca[$k],$cb[$k]); }
      $B=$sum-$A;

      $data['mm']['log'][]=['g'=>$buf,'a'=>$A,'b'=>$B];
      $data['mm']['tries']=(int)$data['mm']['tries']+1;
      $data['mm']['buffer']='';

      if($A===MM_LEN){
        // Победа → отметка «калибровано», шаг 3
        $data['calibrated']=1;
        q_save($mysqli,$uid,3,$data,0);
        log_quest_step(3,'Код откалиброван у Шу. Можно завершать у Аделины или пойти к Лисандру (8128).');
        $response['question']='Шу щёлкает: «Совпало. Передай Аделине, что код готов. Если хочешь — загляни к Лисандру, он интересовался узором». '.pill('Шаг 3/4');
        $response['answer']=[0=>'Понял'];
        return;
      } elseif($data['mm']['tries']>=MM_TRIES){
        // Проигрыш → новая партия
        $old=$data['mm']['secret'];
        mm_boot($data);
        q_save($mysqli,$uid,max($step,2),$data,0);
        $response['question']='«Не срослось. Мой код был: <b>'.$old.'</b>. Сброшу — попробуем ещё».'; 
      }
      else{
        q_save($mysqli,$uid,max($step,2),$data,0);
      }
    }
    break;
}

/* Отрисовка состояния */
$mm=$data['mm']; $buf=$mm['buffer']??'';
$log=mm_view($mm);
$triesLeft = MM_TRIES - (int)($mm['tries']??0);

$response['question']=
  'Шу прикладывает палец к губам: «Подбери последовательность из четырёх знаков. После каждого ввода скажу — сколько на месте и сколько просто встречается».'.
  ' '.pill('Mastermind').'<br>'.
  '<div style="margin:6px 0 8px;color:#b8bce3">Длина: <b>'.MM_LEN.'</b> · Попыток осталось: <b>'.$triesLeft.'</b></div>'.
  '<div><b>Текущий ввод:</b> <span style="font-size:16px">'.$buf.'</span></div>'.
  '<div style="margin-top:8px">'.$log.'</div>';

$response['answer']=mm_buttons();
