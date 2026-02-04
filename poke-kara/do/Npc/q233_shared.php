<?php
/* ============================================================
   Квест Q233 «Сердце механизма» + мини-игра «Быки и коровы»
   Плоская схема: подключается из /do/Npc/{id}.php
   ============================================================ */

if (!defined('Q233_INCLUDED')) define('Q233_INCLUDED', 1);

/* ---------- Общие константы квеста ---------- */
const Q233_QUEST_ID   = 233;
const Q233_QUEST_NAME = 'Сердце механизма';

const Q233_DROP_NEED  = 6;          // сколько «штифтов» нужно выбить
const Q233_FARM_BASE  = 0.30;       // базовый шанс дропа за попытку
const Q233_FARM_BOOST = 0.20;       // +20% если сделан магнитный фильтр у Лисандра
const Q233_FARM_SUPER = 0.15;       // +15% если донесли Громовой камень (id80) — опциональный апгрейд

/* — Награды: покемоны по финалу — */
const Q233_POKE_KLINK     = 599;    // инженерный путь
const Q233_POKE_KANGASKHAN= 115;    // «серый» путь
const Q233_POKE_KLEFKI    = 707;    // коллекционный путь

/* — Денежка и предметы общие — */
const Q233_MONEY_ID   = 1;
const Q233_MONEY_GOOD = 90000;      // за хороший путь
const Q233_MONEY_NEUT = 75000;      // нейтральный
const Q233_MONEY_GRAY = 60000;      // серый

const Q233_REWARD_ITEMS = [
  3   => 15,  // Грейтбол
  26  => 1,   // Жёлтая конфета
  193 => 1,   // Коробка витаминов
];

/* ---------- Мини-игра «Быки и коровы» ---------- */
const Q233_BC_LEN      = 4;   // длина кода
const Q233_BC_LIVES    = 6;   // попыток
const Q233_BC_DEADLINE = 15;  // сек. на ход

/* ---------- Кнопки для мини-игры ---------- */
const Q233_BTN_BASE = 920;          // служебная база
const Q233_BTN_SUBMIT = 920;
const Q233_BTN_CLEAR  = 921;
const Q233_BTN_BACK   = 922;
const Q233_BTN_HINT   = 923;        // мягкая подсказка от Лисандра (если есть буст)
const Q233_BTN_FARM   = 930;        // фарм «штифта»
const Q233_BTN_TURNIN = 931;        // сдать штифты / перейти к финалу
const Q233_BTN_ABOUT  = 932;        // куда идти ещё
/* 0..9 цифры: 900..909 */
function Q233_DIG_BTN($d){ return 900+(int)$d; }

/* ============================================================
   ВСПОМОГАТЕЛЬНОЕ
   ============================================================ */

function Q233_item_icon(int $id): string { return '/img/world/items/little/'.intval($id).'.png'; }
function Q233_pill(string $txt): string {
  return '<span style="display:inline-block;padding:2px 6px;border:1px solid rgba(255,255,255,.2);border-radius:8px;background:rgba(255,255,255,.06);margin-left:6px">'.$txt.'</span>';
}

/* — обёртки для журналов квеста — */
function Q233_log(int $step, string $txt): void {
  if (function_exists('quest_update')) quest_update(Q233_QUEST_ID, $step);
  if (function_exists('quest_zap'))    quest_zap(Q233_QUEST_ID, $step, $txt);
  if (function_exists('update_zap'))   update_zap(Q233_QUEST_ID, $step, $txt);
}

/* — выдача покемона с мягкой сигнатурой — */
function Q233_give_pokemon(int $uid, int $dex, int $lvl=5): void {
  if (function_exists('newPokemon')) {
    // newPokemon($id,$user,$lvl,$exp,$gen,$shiny,$count,$pregnant,$egg,$attacks,$pp,$isGift)
    @newPokemon($dex, $uid, $lvl, 25, 1, 'false', 1, false, false, 4, 15, true);
  } elseif (function_exists('addPokemon')) {
    @addPokemon($dex, $uid, $lvl);
  }
}

/* ============================================================
   ХРАНИЛКА КВЕСТА
   ============================================================ */
function Q233_q_get(mysqli $db, int $uid): ?array {
  $r = $db->query("SELECT * FROM `user_quests` WHERE `user_id`={$uid} AND `quest_id`=".Q233_QUEST_ID." LIMIT 1")->fetch_assoc();
  if (!$r) return null;
  $r['step'] = (int)$r['step'];
  $r['end']  = (int)($r['end'] ?? 0);
  $r['data'] = json_decode($r['data'] ?? '[]', true) ?: [];
  return $r;
}
function Q233_q_create(mysqli $db, int $uid): array {
  $now  = time();
  $data = [
    'start_ts' => $now,
    'pins'     => 0,         // выбитые «штифты»
    'boost'    => 0,         // 0/1/2 — от Лисандра
    'shu'      => 0,         // был «серый» обмен у Шу
  ];
  $json = $db->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
  $db->query("INSERT INTO `user_quests` (`user_id`,`quest_id`,`step`,`end`,`data`) VALUES ({$uid},".Q233_QUEST_ID.",1,0,'{$json}')");
  Q233_log(1, 'Старт квеста. Собрать 6 стальных штифтов.');
  return Q233_q_get($db,$uid);
}
function Q233_q_save(mysqli $db, int $uid, int $step, array $data, int $end=0): void {
  $json = $db->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
  $db->query("UPDATE `user_quests` SET `step`={$step}, `end`={$end}, `data`='{$json}' WHERE `user_id`={$uid} AND `quest_id`=".Q233_QUEST_ID." LIMIT 1");
}

/* ============================================================
   ФАРМ «ШТИФТОВ»
   ============================================================ */
function Q233_farm_try(array &$data): array {
  $chance = Q233_FARM_BASE + ($data['boost']>=1 ? Q233_FARM_BOOST : 0) + ($data['boost']>=2 ? Q233_FARM_SUPER : 0);
  if ($chance > 0.95) $chance = 0.95;
  $roll = mt_rand() / mt_getrandmax();
  $ok = ($roll <= $chance);
  if ($ok) $data['pins'] = (int)$data['pins'] + 1;
  return ['ok'=>$ok, 'chance'=>round($chance*100)];
}

/* ============================================================
   МИНИ-ИГРА «БЫКИ И КОРОВЫ»
   ============================================================ */
function Q233_bc_make_code(): string {
  // 4 неповторяющихся цифры, первая не 0
  $digits = range(0,9);
  shuffle($digits);
  if ($digits[0]===0) { // первая не 0
    for ($i=1;$i<10;$i++){ if ($digits[$i]!==0){ $t=$digits[0]; $digits[0]=$digits[$i]; $digits[$i]=$t; break; } }
  }
  $code = '';
  $used = [];
  for ($i=0;$i<Q233_BC_LEN;$i++){
    for ($j=0;$j<10;$j++){
      $d=$digits[$j];
      if (!isset($used[$d])){ $code.=$d; $used[$d]=1; break; }
    }
  }
  return $code;
}
function Q233_bc_start(array &$data): void {
  $data['bc'] = [
    'code'     => Q233_bc_make_code(),
    'input'    => '',
    'lives'    => Q233_BC_LIVES,
    'history'  => [],     // [ ['g'=>'1234','b'=>1,'c'=>2], ... ]
    'deadline' => time()+Q233_BC_DEADLINE
  ];
}
function Q233_bc_left(array $data): int {
  $dl = (int)($data['bc']['deadline'] ?? 0);
  $left = max(0, $dl - time());
  return $left;
}
function Q233_bc_set_deadline(array &$data): void {
  $data['bc']['deadline'] = time()+Q233_BC_DEADLINE;
}
function Q233_bc_bulls_cows(string $code, string $guess): array {
  $bulls=0; $cows=0;
  for ($i=0;$i<strlen($guess);$i++){
    if (!isset($code[$i])) continue;
    if ($guess[$i]===$code[$i]) $bulls++;
    elseif (strpos($code,$guess[$i])!==false) $cows++;
  }
  return [$bulls,$cows];
}
function Q233_bc_timeout(array &$data): ?array {
  $left = Q233_bc_left($data);
  if ($left>0) return null;
  // таймаут — теряем жизнь и очищаем ввод
  $data['bc']['lives'] = max(0, (int)$data['bc']['lives']-1);
  $data['bc']['input'] = '';
  Q233_bc_set_deadline($data);
  if ($data['bc']['lives']<=0) {
    // рестарт кода при полном сливе
    $old = $data['bc']['code'] ?? '----';
    Q233_bc_start($data);
    return ['reset'=>1,'code_was'=>$old];
  }
  return ['timeout'=>1];
}
function Q233_bc_push_digit(array &$data, int $digit): array {
  if (empty($data['bc']['code'])) Q233_bc_start($data);
  if ($t = Q233_bc_timeout($data)) return $t+['status'=>'timeout'];
  $cur = (string)$data['bc']['input'];
  if (strlen($cur)>=Q233_BC_LEN) return ['status'=>'full','input'=>$cur,'left'=>Q233_bc_left($data)];
  // запрет повторяющихся цифр
  if (strpos($cur,(string)$digit)!==false) return ['status'=>'dup','input'=>$cur,'left'=>Q233_bc_left($data)];
  if ($digit===0 && $cur==='' ) return ['status'=>'zlead','input'=>$cur,'left'=>Q233_bc_left($data)];
  $cur.=(string)$digit;
  $data['bc']['input']=$cur;
  Q233_bc_set_deadline($data);
  return ['status'=>'ok','input'=>$cur,'left'=>Q233_bc_left($data)];
}
function Q233_bc_clear(array &$data, bool $back=false): array {
  if (empty($data['bc']['code'])) Q233_bc_start($data);
  if ($t = Q233_bc_timeout($data)) return $t+['status'=>'timeout'];
  if ($back) $data['bc']['input'] = (string)mb_substr((string)$data['bc']['input'],0,-1);
  else $data['bc']['input'] = '';
  Q233_bc_set_deadline($data);
  return ['status'=>'ok','input'=>$data['bc']['input'],'left'=>Q233_bc_left($data)];
}
function Q233_bc_submit(array &$data): array {
  if (empty($data['bc']['code'])) Q233_bc_start($data);
  if ($t = Q233_bc_timeout($data)) return $t+['status'=>'timeout'];

  $g = (string)$data['bc']['input'];
  if (strlen($g) !== Q233_BC_LEN) return ['status'=>'short','input'=>$g,'left'=>Q233_bc_left($data)];

  [$b,$c] = Q233_bc_bulls_cows((string)$data['bc']['code'], $g);
  $data['bc']['history'][] = ['g'=>$g,'b'=>$b,'c'=>$c];
  $data['bc']['input'] = '';

  if ($b===Q233_BC_LEN) {
    return ['status'=>'win','guess'=>$g,'left'=>Q233_bc_left($data),'tries'=>count($data['bc']['history'])];
  }

  $data['bc']['lives'] = max(0, (int)$data['bc']['lives']-1);
  if ($data['bc']['lives']<=0) {
    $old = $data['bc']['code'] ?? '----';
    Q233_bc_start($data);
    return ['status'=>'reset','code_was'=>$old];
  }

  Q233_bc_set_deadline($data);
  return ['status'=>'next','b'=>$b,'c'=>$c,'lives'=>$data['bc']['lives'],'left'=>Q233_bc_left($data)];
}

/* ============================================================
   ФИНАЛИЗАЦИЯ НАГРАДЫ
   ============================================================ */
function Q233_finish(mysqli $db, int $uid, array &$data, string $ending, string $playerName): array {
  // Определяем покемона и деньги по ветке
  $poke = Q233_POKE_KLEFKI; $money = Q233_MONEY_NEUT; $title='Нейтральный исход';
  if ($ending==='engineer'){ $poke=Q233_POKE_KLINK; $money=Q233_MONEY_GOOD; $title='Инженерное решение'; }
  if ($ending==='gray'){ $poke=Q233_POKE_KANGASKHAN; $money=Q233_MONEY_GRAY; $title='Серый исход'; }

  if (function_exists('itemAdd')) {
    @itemAdd(Q233_MONEY_ID, $money, $uid);
    foreach (Q233_REWARD_ITEMS as $iid=>$q) @itemAdd($iid,$q,$uid);
  }
  Q233_give_pokemon($uid, $poke, 5);

  Q233_q_save($db,$uid,7,$data,1);

  $itemsTxt = '';
  foreach (Q233_REWARD_ITEMS as $iid=>$q){
    $itemsTxt .= '<img src="'.Q233_item_icon($iid).'" class="item"> #'.$iid.' × <b>'.$q.'</b><br>';
  }

  Q233_log(7, "Финал: {$title}. Выдан покемон #{$poke}, деньги {$money}.");

  return [
    'actionQuestPlus' =>
      '<img src="'.Q233_item_icon(Q233_MONEY_ID).'" class="item"> Генкары <b>'.number_format($money,0,'',' ').'</b><br>'.$itemsTxt.
      '<span>Покемон #'.$poke.' получен.</span>',
    'question' => "Аделина кладёт ладонь на крышку кейса: «Сердце на месте. Хорошая работа, {$playerName}».",
    'answer'   => [ 0 => 'Убрать кейс и уйти' ],
    'closeDialog' => true
  ];
}

/* ============================================================
   ОБЩИЕ УТИЛИТЫ ДЛЯ NPC
   ============================================================ */
function Q233_prepare_npc(mysqli $db, int $npcId, array &$response, string $fallbackName): void {
  $row = $db->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`=".(int)$npcId)->fetch_assoc();
  $response['name']  = $row['name']  ?? $fallbackName;
  $response['image'] = isset($row['image']) ? htmlspecialchars($row['image']) : '/img/default-npc.png';
}

/* ============================================================
   NPC 1: АДЕЛИНА (старт и финал)
   ============================================================ */
function Q233_handle_adeline(mysqli $db, int $uid, array &$response, int $req): void {
  Q233_prepare_npc($db, 8127, $response, 'Аделина');

  $player = htmlspecialchars($_SESSION['login'] ?? 'тренер');

  $st = Q233_q_get($db,$uid);

  if ($st && (int)$st['end']===1){
    $response['question'] = 'Аделина кивает: «Кейс уже у тебя, '.$player.'. Если захочешь ещё работы — ищи меня в Лумиосе».';
    $response['answer'] = [ 0 => 'Кивнуть' ];
    return;
  }

  if (!$st){
    if ($req===101){
      $st = Q233_q_create($db,$uid);
      $response['actionQuest'] = 'Начато задание <b>'.Q233_QUEST_NAME.'</b>.';
      $response['question'] =
        'Аделина раскладывает на столе связку ключей: «Говорят, под Терминусом спит устройство. Нужны стальные штифты, чтобы добраться до ядра».'
        .Q233_pill('Шаг 1/4')
        .'<br><br><b>Задача:</b> выбей <b>'.Q233_DROP_NEED.'</b> штифтов. Можешь обратиться к Шу (8129) или к Лисандру (8128) — они помогут по-своему.';
      $response['answer'] = [
        Q233_BTN_FARM   => 'Идти выбивать штифты',
        Q233_BTN_ABOUT  => 'Куда ещё заглянуть?',
      ];
      return;
    }
    $response['question'] = 'Аделина поправляет очки: «Ищешь редкие замки и ключи?».';
    $response['answer'] = [ 101 => 'Начать «Сердце механизма»' ];
    return;
  }

  /* — дальше квест уже начат — */
  $data = $st['data'];
  $step = (int)$st['step'];

  // ====== Ветки управления ======
  switch (true){

    // Фарм попытка
    case ($req === Q233_BTN_FARM):
      if ($step<1) $step=1;
      $farm = Q233_farm_try($data);
      Q233_q_save($db,$uid,$step,$data,0);
      $extra = $farm['ok'] ? '🔥 Удалось найти штифт!' : 'Пусто… попробуй ещё.';
      $boostTxt = $data['boost']>=2 ? 'Фильтр+: +'.(int)(Q233_FARM_BOOST*100).'%, +'.(int)(Q233_FARM_SUPER*100).'%' :
                  ($data['boost']>=1 ? 'Фильтр: +'.(int)(Q233_FARM_BOOST*100).'%' : 'Без усилений');
      $response['question'] =
        'Ты прочёсываешь туннели Терминуса… '.$extra
        .'<br><span style="color:#b8bce3">Штифтов: <b>'.$data['pins'].' / '.Q233_DROP_NEED.'</b> • Шанс: '.$farm['chance'].'% • '.$boostTxt.'</span>';
      $answers = [
        Q233_BTN_FARM   => 'Ещё раз',
        Q233_BTN_ABOUT  => 'Куда ещё заглянуть?',
      ];
      if ($data['pins']>=Q233_DROP_NEED) $answers[Q233_BTN_TURNIN] = 'Вернуться к Аделине с штифтами';
      $response['answer'] = $answers;
      return;

    // Подсказка по NPC
    case ($req === Q233_BTN_ABOUT):
      $response['question'] =
        'Аделина шепчет: «Шу (8129) торгует редкими деталями — но за всё придётся платить. Лисандр (8128) — инженер, может усилить магнитный сборник.»';
      $response['answer'] = [
        0 => 'Понятно',
        Q233_BTN_FARM => 'Идти добывать',
      ];
      return;

    // Сдача штифтов / переход к мини-игре
    case ($req === Q233_BTN_TURNIN):
      if ($data['pins'] < Q233_DROP_NEED){
        $response['question'] = 'Аделина считает детали: «Не хватает. Нужно ещё <b>'.(Q233_DROP_NEED - (int)$data['pins']).'</b> шт.»';
        $response['answer'] = [ Q233_BTN_FARM => 'Пойду ещё добуду' ];
        return;
      }
      // старт мини-игры (шаг 3)
      Q233_bc_start($data);
      Q233_q_save($db,$uid,3,$data,0);
      Q233_log(3,'Подготовка к вскрытию кейса: мини-игра «Быки и коровы».');

      // падение в default ниже (шаг 3)
      $st = Q233_q_get($db,$uid);
      $data = $st['data'];
      $step = 3;
      // no break (переход в вывод игры)
  }

  // ====== Отрисовка и обработка мини-игры ======
  if ($step===3){
    $left = Q233_bc_left($data);
    $hist = (array)($data['bc']['history'] ?? []);
    $historyHtml='';
    if ($hist){
      $historyHtml .= '<div style="margin-top:6px;border-top:1px dashed rgba(255,255,255,.12);padding-top:6px">';
      foreach ($hist as $h){
        $historyHtml .= '<div style="opacity:.9">⟦'.$h['g'].'⟧ → Быки: <b>'.$h['b'].'</b>, Коровы: <b>'.$h['c'].'</b></div>';
      }
      $historyHtml .= '</div>';
    }
    $input = htmlspecialchars((string)($data['bc']['input']??''));
    $lives = (int)($data['bc']['lives']??Q233_BC_LIVES);

    // ловим действия по кнопкам
    if ($req>=900 && $req<=909){
      $digit = $req-900;
      $r = Q233_bc_push_digit($data, $digit);
      Q233_q_save($db,$uid,3,$data,0);
      $left = Q233_bc_left($data);
      $input = htmlspecialchars((string)($data['bc']['input']??''));
      $lives = (int)($data['bc']['lives']??Q233_BC_LIVES);
      $msg = 'Ввод: <b>'.$input.'</b>';
      if ($r['status']==='dup') $msg = 'Эта цифра уже есть. '.$msg;
      if ($r['status']==='zlead') $msg = 'Код не начинается с нуля. '.$msg;
      if (!empty($r['reset']))   $msg = 'Попытки кончились. Кейс перезагрузил код.';
      if (!empty($r['timeout'])) $msg = 'Время вышло — минус попытка.';
      $response['question'] =
        'Вводи 4 цифры без повторов. '.Q233_pill('Шаг 3/4')
        .'<br>'.$msg
        .'<br><span style="color:#b8bce3">Осталось: <b>'.$lives.'</b> попыток • На ход: <b>'.$left.' сек.</b></span>'
        .$historyHtml;
      $response['answer'] = Q233_bc_keyboard($data);
      return;
    }
    if ($req===Q233_BTN_CLEAR || $req===Q233_BTN_BACK){
      $r = Q233_bc_clear($data, $req===Q233_BTN_BACK);
      Q233_q_save($db,$uid,3,$data,0);
      $left = Q233_bc_left($data);
      $input = htmlspecialchars((string)($data['bc']['input']??''));
      $lives = (int)($data['bc']['lives']??Q233_BC_LIVES);
      $msg = ($req===Q233_BTN_BACK?'Удалил последнюю цифру. ':'Сброс. ').'Ввод: <b>'.$input.'</b>';
      if (!empty($r['reset']))   $msg = 'Попытки кончились. Кейс перезагрузил код.';
      if (!empty($r['timeout'])) $msg = 'Время вышло — минус попытка.';
      $response['question'] =
        'Вводи 4 цифры без повторов. '.Q233_pill('Шаг 3/4')
        .'<br>'.$msg
        .'<br><span style="color:#b8bce3">Осталось: <b>'.$lives.'</b> попыток • На ход: <b>'.$left.' сек.</b></span>'
        .$historyHtml;
      $response['answer'] = Q233_bc_keyboard($data);
      return;
    }
    if ($req===Q233_BTN_SUBMIT){
      $r = Q233_bc_submit($data);
      if ($r['status']==='win'){
        // финал по ветке
        $ending = 'neutral';
        if (!empty($data['shu']))       $ending = 'gray';
        elseif ((int)($data['boost']??0)>=1) $ending = 'engineer';

        $fin = Q233_finish($db,$uid,$data,$ending,$player);
        $response = array_merge($response, $fin);
        return;
      }
      Q233_q_save($db,$uid,3,$data,0);
      $left  = Q233_bc_left($data);
      $input = htmlspecialchars((string)($data['bc']['input']??''));
      $lives = (int)($data['bc']['lives']??Q233_BC_LIVES);

      $msg = '';
      if ($r['status']==='short') $msg='Нужно 4 цифры. Сейчас: <b>'.$input.'</b>.';
      if ($r['status']==='next')  $msg='⟦Последняя попытка⟧ → Быки: <b>'.$r['b'].'</b>, Коровы: <b>'.$r['c'].'</b>.';
      if ($r['status']==='reset') $msg='Попытки кончились. Кейс перезагрузил код.';
      if (!empty($r['timeout']))  $msg='Время вышло — минус попытка.';

      $response['question'] =
        'Вводи 4 цифры без повторов. '.Q233_pill('Шаг 3/4')
        .'<br>'.$msg
        .'<br><span style="color:#b8bce3">Осталось: <b>'.$lives.'</b> попыток • На ход: <b>'.$left.' сек.</b></span>'
        .$historyHtml;
      $response['answer'] = Q233_bc_keyboard($data);
      return;
    }

    // первичный показ игры
    $response['question'] =
      'Аделина ставит кейс на стол: «Код — четыре цифры, без повторов. Быки — цифры на своих местах; Коровы — цифры есть, но стоят не там».'
      .Q233_pill('Шаг 3/4')
      .'<br><span style="color:#b8bce3">Осталось: <b>'.$lives.'</b> попыток • На ход: <b>'.$left.' сек.</b></span>'
      .$historyHtml;
    $response['answer'] = Q233_bc_keyboard($data);
    return;
  }

  // ====== Шаг 1: общий статус, если ещё фармим
  if ($step===1){
    $boostTxt = (int)($data['boost']??0)===0 ? 'Усилений нет' : ( (int)$data['boost']===1 ? 'Фильтр у Лисандра' : 'Фильтр+ у Лисандра' );
    $response['question'] =
      '«Пока ты добываешь штифты, я ищу схему замка», — говорит Аделина.'
      .Q233_pill('Шаг 1/4')
      .'<br><span style="color:#b8bce3">Штифтов: <b>'.$data['pins'].' / '.Q233_DROP_NEED.'</b> • '.$boostTxt.'</span>';
    $ans = [ Q233_BTN_FARM=>'Идти выбивать', Q233_BTN_ABOUT=>'Кто ещё поможет?' ];
    if ($data['pins']>=Q233_DROP_NEED) $ans[Q233_BTN_TURNIN]='Сдать штифты';
    $response['answer'] = $ans;
    return;
  }

  // страховка
  $response['question'] = 'Аделина осматривает тебя: «Готов продолжить?»';
  $response['answer'] = [ Q233_BTN_FARM=>'Идти добывать', Q233_BTN_ABOUT=>'Кто ещё поможет?' ];
}

/* — клавиатура для мини-игры — */
function Q233_bc_keyboard(array $data): array {
  $ans = [];
  for ($d=1;$d<=9;$d++) $ans[ Q233_DIG_BTN($d) ] = (string)$d;
  $ans[ Q233_DIG_BTN(0) ] = '0';
  $ans[ Q233_BTN_BACK ]   = '⌫ Удалить';
  $ans[ Q233_BTN_CLEAR ]  = 'Сброс';
  $ans[ Q233_BTN_SUBMIT ] = 'Ввести';
  if ((int)($data['boost']??0)>=1) {
    // мягкая «подсказка» отображаем как справку (без расходников) — просто правило
    $ans[ Q233_BTN_HINT ]  = 'Правила ещё раз';
  }
  return $ans;
}

/* ============================================================
   NPC 2: ЛИСАНДР (инженер — бусты фарма)
   ============================================================ */
function Q233_handle_lysandre(mysqli $db, int $uid, array &$response, int $req): void {
  Q233_prepare_npc($db, 8128, $response, 'Лисандр');

  $st = Q233_q_get($db,$uid);
  if (!$st){
    $response['question'] = 'Рыжеволосый инженер лениво чертит схему: «Если Аделина шлёт тебя — принесу пользу. Иначе подходи позже».';
    $response['answer']   = [ 0 => 'Вернуться' ];
    return;
  }

  $data = $st['data']; $boost = (int)($data['boost'] ?? 0);

  // Кнопки Лисандра
  $BTN_MAKE_FILTER   = 840;   // сделать фильтр за 10 Грейтболов
  $BTN_UPGRADE_PLUS  = 841;   // усилить фильтр Громовым камнем (id80)
  $BTN_RULES         = 842;   // объяснить «быки и коровы»

  if ($req===$BTN_MAKE_FILTER){
    $lack = [];
    if (!function_exists('item_isset') || !item_isset(3,10)) $lack[3]=10;
    if ($lack){
      $response['question'] = 'Лисандр смотрит укоризненно: «Нужны расходники:».'
        .'<ul style="margin:6px 0 0 18px"><li><img src="'.Q233_item_icon(3).'" class="item" style="width:18px;vertical-align:-3px;margin-right:6px">#3 × <b>10</b></li></ul>';
      $response['answer'] = [ 0 => 'Ладно' ];
      return;
    }
    if (function_exists('minus_item')) minus_item(3,10);
    $data['boost'] = max($boost,1);
    Q233_q_save($db,$uid,$st['step'],$data,0);
    $response['question'] = '«Готово. Магнитный фильтр поднимет шанс дропа примерно на 20%.»';
    $response['answer'] = [ 0=>'Спасибо' ];
    return;
  }

  if ($req===$BTN_UPGRADE_PLUS){
    if (!function_exists('item_isset') || !item_isset(80,1)){
      $response['question'] = '«Апгрейд потребует Громовой камень (#80). Принесёшь — улучшу фильтр».'; 
      $response['answer']   = [ 0 => 'Понял' ];
      return;
    }
    if (function_exists('minus_item')) minus_item(80,1);
    $data['boost'] = 2;
    Q233_q_save($db,$uid,$st['step'],$data,0);
    $response['question'] = '«Теперь фильтр+ добавит ещё мощи к магнитной ловушке. Должно быть заметно».'; 
    $response['answer']   = [ 0 => 'Вернуться к делу' ];
    return;
  }

  if ($req===$BTN_RULES){
    $response['question'] =
      'Лисандр быстро чертит сетку: «Код — четыре неповторяющихся цифры. Вводишь — получаешь оценку: Быки (совпало число и позиция), Коровы (число есть, но позиция другая)».';
    $response['answer'] = [ 0 => 'Понял' ];
    return;
  }

  // Статус Лисандра
  $txt = $boost===0 ? '«Без фильтра копаться можно долго. Надо бы улучшить сборник».' :
        ($boost===1 ? '«Фильтр у тебя есть. Могу слегка улучшить, если принесёшь Громовой камень (#80)».' :
                      '«Фильтр+ уже работает. Дальше — дело терпения».' );
  $response['question'] = $txt;
  $ans = [ $BTN_RULES=>'Объясни про замок', $BTN_MAKE_FILTER=>'Собрать фильтр (10 × Грейтбол)' ];
  if ($boost>=1) $ans[$BTN_UPGRADE_PLUS] = 'Усилить фильтр (Громовой камень ×1)';
  $response['answer'] = $ans;
}

/* ============================================================
   NPC 3: ШУ (добытчик — «серый» путь)
   ============================================================ */
function Q233_handle_shu(mysqli $db, int $uid, array &$response, int $req): void {
  Q233_prepare_npc($db, 8129, $response, 'Шу');

  $st = Q233_q_get($db,$uid);
  if (!$st){
    $response['question'] = 'Парень с фонарём прищуривается: «Туристы здесь не шастают. С Аделиной работаешь — тогда другое дело».';
    $response['answer']   = [ 0 => 'Уйти' ];
    return;
  }
  $data = $st['data'];

  $BTN_BUY_2   = 860; // купить 2 штифта
  $BTN_TRADE_3 = 861; // «серый» обмен на 3 штифта — минус репутация (флаг)
  $BTN_STATUS  = 862;

  if ($req===$BTN_BUY_2){
    // цена: 10 покеболов + 15000 денег
    $lack=[];
    if (!function_exists('item_isset') || !item_isset(2,10)) $lack[2]=10;
    if (!function_exists('item_isset') || !item_isset(1,15000)) $lack[1]=15000;
    if ($lack){
      $list=''; foreach($lack as $iid=>$q) $list.='<li><img src="'.Q233_item_icon($iid).'" class="item" style="width:18px;vertical-align:-3px;margin-right:6px">#'.$iid.' × <b>'.$q.'</b></li>';
      $response['question'] = 'Шу пожимает плечами: «Без оплаты никак»<ul style="margin:6px 0 0 18px">'.$list.'</ul>';
      $response['answer']=[0=>'Ладно'];
      return;
    }
    if (function_exists('minus_item')){ minus_item(2,10); minus_item(1,15000); }
    $data['pins'] = (int)$data['pins'] + 2;
    Q233_q_save($db,$uid,$st['step'],$data,0);
    $response['question'] = 'Шу кидает в ладонь два штифта: «Держи. И будь осторожнее — местные не любят лишних глаз».';
    $response['answer']   = [ 0=>'Спасибо' ];
    return;
  }

  if ($req===$BTN_TRADE_3){
    // серый обмен: мгновенно +3, но фиксируем «грязный» флаг
    $data['pins'] = (int)$data['pins'] + 3;
    $data['shu']  = 1;
    Q233_q_save($db,$uid,$st['step'],$data,0);
    $response['question'] = 'Шу шепчет: «Эти три — с чёрного входа. Если Аделина спросит — скажешь, что нашёл у старых вагонов».';
    $response['answer']   = [ 0 => 'Кивнуть' ];
    return;
  }

  if ($req===$BTN_STATUS){
    $response['question'] =
      'Шу постукивает по каске: «Мне не важно, кому ты помогаешь. Я — за результат».'
      .'<br><span style="color:#b8bce3">У тебя уже штифтов: <b>'.(int)$data['pins'].' / '.Q233_DROP_NEED.'</b></span>';
    $response['answer'] = [
      $BTN_BUY_2   => 'Купить 2 штифта (10 × Покебол + 15000₲)',
      $BTN_TRADE_3 => 'Достать 3 штифта «по-тихому»',
      0            => 'Уйти'
    ];
    return;
  }

  // первичный экран
  $response['question'] = '«Шу. Работаю быстро. Нужны детали — заплачу сам себе временем.»';
  $response['answer'] = [
    $BTN_STATUS => 'Что у тебя есть?',
    $BTN_BUY_2  => 'Купить 2 штифта (10 × Покебол + 15000₲)',
    $BTN_TRADE_3=> 'Достать 3 штифта «по-тихому»'
  ];
}
