<?php
// npc/quest121/yuna.php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

if (!isset($_SESSION['id'])) { $response['question'] = 'Нужно войти в игру.'; return; }
global $mysqli;

$uid    = (int)$_SESSION['id'];
$player = htmlspecialchars($_SESSION['login'] ?? 'тренер');

// (опционально приходит из роутера)
$npcRow = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`=".(int)($npcId ?? 0))->fetch_assoc();
$response['name']  = $npcRow['name']  ?? 'Юна Кавада';
$response['image'] = isset($npcRow['image']) ? htmlspecialchars($npcRow['image']) : '/img/default-npc.png';

/* ===================== КОНСТАНТЫ КВЕСТА ===================== */
const QUEST_ID   = 121;
const QUEST_NAME = 'Ключи Шторма';

const GROUP_WIND  = [16,17,21,22,163,278];          // Ветер
const GROUP_SPARK = [25,81,82,100];                 // Ток
const GROUP_VINE  = [43,45,69,70,71,114,187];        // Лоза
const GROUP_TIDE  = [60,98,118,119,54,72];          // Прилив

// Нужны УНИКАЛЬНЫЕ виды
const NEED_WIND  = 4;
const NEED_SPARK = 3;
const NEED_VINE  = 3;
const NEED_TIDE  = 4;

const TURNIN = [
  2  => 50,   // Покебол
  3  => 15,   // Грейтбол
  17 => 5,    // Энергетик
  22 => 5,    // Противоядие
  19 => 5,    // Антипарализ
];

const BONUS_ITEM_ID = 80;          // Громовой камень
const BONUS_FLAG    = 'bonus';

const REWARD_MONEY_ID  = 1;
const REWARD_MONEY_AMT = 120000;
const REWARD_ITEMS = [
  193 => 1,  // Коробка витаминов
  26  => 2,  // Жёлтая конфета
  3   => 20, // Грейтбол
];
const REWARD_BONUS = [ 196 => 1 ]; // Именной бланк

/* ===================== МИНИ-ИГРА «Саймон: Штормовые ключи» ===================== */
const SIMON_LEN       = 7;   // длина последовательности
const SIMON_LIVES     = 3;   // количество жизней
const SIMON_DEADLINE  = 15;  // СЕКУНД НА ХОД

// 1..4 → сигнал
const SIGNALS = [
  1 => ['key'=>'wind',  'name'=>'Ветер',  'emoji'=>'🌬️'],
  2 => ['key'=>'spark', 'name'=>'Ток',    'emoji'=>'⚡️'],
  3 => ['key'=>'vine',  'name'=>'Лоза',   'emoji'=>'🌿'],
  4 => ['key'=>'tide',  'name'=>'Прилив', 'emoji'=>'🌊'],
];

// Кнопки ввода игрока
const BTN_WIND  = 510;
const BTN_SPARK = 511;
const BTN_VINE  = 512;
const BTN_TIDE  = 513;

// Служебные кнопки мини-игры
const BTN_SIMON_SHOW   = 501; // «Показать последовательность» (всегда НОВАЯ)
const BTN_SIMON_RETRY  = 502; // «Начать заново» (новая связка + жизни)

/* ===================== УТИЛИТЫ UI ===================== */
function item_icon(int $id): string { return '/img/world/items/little/'.intval($id).'.png'; }
function pill($txt){return '<span style="display:inline-block;padding:2px 6px;border:1px solid rgba(255,255,255,.2);border-radius:8px;background:rgba(255,255,255,.06);margin-left:6px">'.$txt.'</span>';}

/* Живой таймер “сколько осталось на ход” (тик на клиенте, без привязки к системному времени пользователя) */
function simon_timer_html(array $data): string {
  $deadline = (int)($data['mini']['deadline'] ?? 0);
  if ($deadline <= 0) return '';
  $left = max(0, $deadline - time());
  if ($left > SIMON_DEADLINE) $left = SIMON_DEADLINE;
  $left = (int)$left;
  // Небольшой JS, который убывает локальный счётчик раз в секунду
  return '
<div id="turn-timer" style="margin:6px 0;color:#9fd7ff">
  ⏳ Осталось: <b><span id="turn-timer-val">'.$left.'</span> с</b>
</div>
<script>
(function(){
  var sec = '.$left.';
  var el  = document.getElementById("turn-timer-val");
  if(!el) return;
  function tick(){
    if(!el) return;
    if (sec > 0) {
      sec--;
      el.textContent = sec;
      setTimeout(tick, 1000);
    }
  }
  setTimeout(tick, 1000);
})();
</script>';
}

/* ===================== ХРАНИЛКА КВЕСТА ===================== */
function q_get(mysqli $db, int $uid): ?array {
  $r = $db->query("SELECT * FROM `user_quests` WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1")->fetch_assoc();
  if (!$r) return null;
  $r['step'] = (int)$r['step'];
  $r['end']  = (int)($r['end'] ?? 0);
  $r['data'] = json_decode($r['data'] ?? '[]', true) ?: [];
  return $r;
}
function q_create(mysqli $db, int $uid): array {
  $now  = time();
  $data = ['start_ts' => $now, 'ts_wind' => $now, 'bonus' => 0];
  $json = $db->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
  $db->query("INSERT INTO `user_quests` (`user_id`,`quest_id`,`step`,`end`,`data`) VALUES ({$uid},".QUEST_ID.",1,0,'{$json}')");
  log_quest_step(QUEST_ID, 1, 'Старт: Ключ Ветра — поймать '.NEED_WIND.' разных видов (Pidgey, Pidgeotto, Spearow, Fearow, Hoothoot, Wingull).');
  return q_get($db,$uid);
}
function q_save(mysqli $db, int $uid, int $step, array $data, int $end=0): void {
  $json = $db->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
  $db->query("UPDATE `user_quests` SET `step`={$step}, `end`={$end}, `data`='{$json}' WHERE `user_id`={$uid} AND `quest_id`=".QUEST_ID." LIMIT 1");
}

/* ===================== UI/ЛОГ ===================== */
function need_list_html(array $map): string {
  $buf=''; foreach($map as $id=>$q){
    $buf.='<li><img src="'.item_icon($id).'" class="item" style="width:18px;height:18px;vertical-align:-3px;margin-right:6px">#'.$id.' × <b>'.$q.'</b></li>';
  } return '<ul style="margin:6px 0 0 18px">'.$buf.'</ul>';
}
function log_quest_step(int $questId, int $step, string $text): void {
  if (function_exists('quest_update')) quest_update($questId, $step);
  if (function_exists('update_zap'))   update_zap($questId, $step, $text);
  if (function_exists('quest_zap'))    quest_zap($questId, $step, $text);
}

/* ===================== ОПРЕДЕЛЕНИЕ ИСТОЧНИКА ПОИМОК ===================== */
function detect_catch_source(mysqli $db): ?array {
  $candidates = [
    ['user_pokemons',  ['user_id','user','owner'], ['basenum','pokemon_id','species'], ['time','date','date_get','created_at','caught_at'], ['birthday','info','meta']],
    ['pokemons_users', ['user_id','user','owner'], ['basenum','pokemon_id','species'], ['time','date','date_get','created_at','caught_at'], ['birthday','info','meta']],
    ['users_pokemons', ['user_id','user','owner'], ['basenum','pokemon_id','species'], ['time','date','date_get','created_at','caught_at'], ['birthday','info','meta']],
    ['pokemons',       ['user_id','user','owner'], ['basenum','pokemon_id','species'], ['time','date','date_get','created_at','caught_at'], ['birthday','info','meta']],
  ];
  foreach ($candidates as [$t,$ucols,$scols,$tcols,$bcols]) {
    $chk  = $db->query("SHOW TABLES LIKE '".$db->real_escape_string($t)."'");
    if (!$chk || !$chk->num_rows) continue;

    $cols = []; $types = [];
    if ($rs = $db->query("SHOW COLUMNS FROM `{$t}`")) {
      while ($row = $rs->fetch_assoc()) { $cols[$row['Field']] = true; $types[$row['Field']] = strtolower($row['Type'] ?? ''); }
    }
    $u=$s=$tm=$bd=null;
    foreach ($ucols as $c) if (isset($cols[$c])) { $u=$c; break; }
    foreach ($scols as $c) if (isset($cols[$c])) { $s=$c; break; }
    foreach ($tcols as $c) if (isset($cols[$c])) { $tm=$c; break; }
    foreach ($bcols as $c) if (isset($cols[$c])) { $bd=$c; break; }
    if ($u && $tm) return ['table'=>$t,'user'=>$u,'species'=>$s,'time'=>$tm,'birthday'=>$bd];
  }
  return null;
}

/* ===================== СЕТ ВИДОВ С МОМЕНТА TС (ориг.ловец) ===================== */
function species_set_since(mysqli $db, int $uid, int $startTs, array $allowedSpecies): array {
  $src = detect_catch_source($db);
  if (!$src) return [];

  $t   = $src['table']; $uc=$src['user']; $sc=$src['species']; $tc=$src['time']; $bd=$src['birthday'];
  $startTs = max(0,(int)$startTs);

  $sel = "`{$tc}` AS tcol"; if ($bd) $sel.=",`{$bd}` AS bcol"; if ($sc) $sel.=",`{$sc}` AS scol";
  $conds = ["`{$uc}`={$uid}"]; if ($sc && $allowedSpecies) { $in=implode(',',array_map('intval',$allowedSpecies)); $conds[]="`{$sc}` IN ({$in})"; }
  $sql = "SELECT {$sel} FROM `{$t}` WHERE ".implode(' AND ',$conds);
  $res = $db->query($sql); if (!$res) return [];

  $ok=[];
  while ($row=$res->fetch_assoc()){
    // time
    $ts=0; $raw=$row['tcol'];
    if ($raw!==null && $raw!==''){ $ts = ctype_digit((string)$raw) ? (int)$raw : (strtotime($raw) ?: 0); }
    // fallback из birthday
    if ($ts===0 && isset($row['bcol'])){
      $b=(string)$row['bcol'];
      if (($j=json_decode($b,true)) && isset($j['date']) && ctype_digit((string)$j['date'])) $ts=(int)$j['date'];
      elseif (preg_match('/(?<!\d)(1[5-9]\d{8,10}|2\d{9,10})(?!\d)/',$b,$m)) $ts=(int)$m[0];
    }
    // оригинальный ловец
    $ownerOK=true;
    if (isset($row['bcol'])){
      $b=(string)$row['bcol']; $uidS=(string)$uid;
      $ownerOK = (strpos($b,'"user_id":"'.$uidS.'"')!==false) || (strpos($b,'"user_id":'.$uidS)!==false);
      if (($j=json_decode($b,true)) && isset($j['user_id'])) $ownerOK = (string)$j['user_id']===$uidS;
    }
    if ($ownerOK && $ts>=$startTs){
      $sp = isset($row['scol']) ? (int)$row['scol'] : 0;
      if ($sp && in_array($sp,$allowedSpecies,true)) $ok[$sp]=true; // уникальность видом
    }
  }
  return $ok;
}
function unique_count_since(mysqli $db, int $uid, int $startTs, array $allowedSpecies): int {
  return count(species_set_since($db,$uid,$startTs,$allowedSpecies));
}

/* ===================== ПРОГРЕСС (поэтапные метки) ===================== */
function greet_progress(mysqli $db, int $uid, array $data): string {
  $tsW=(int)($data['ts_wind']  ?? 0);
  $tsS=(int)($data['ts_spark'] ?? 0);
  $tsV=(int)($data['ts_vine']  ?? 0);
  $tsT=(int)($data['ts_tide']  ?? 0);

  $cw=$tsW?unique_count_since($db,$uid,$tsW,GROUP_WIND):0;
  $cs=$tsS?unique_count_since($db,$uid,$tsS,GROUP_SPARK):0;
  $cv=$tsV?unique_count_since($db,$uid,$tsV,GROUP_VINE):0;
  $ct=$tsT?unique_count_since($db,$uid,$tsT,GROUP_TIDE):0;

  return '<div style="margin-top:6px;color:#b8bce3">
    Ветер: <b>'.$cw.' / '.NEED_WIND.'</b> ·
    Ток: <b>'.$cs.' / '.NEED_SPARK.'</b> ·
    Лоза: <b>'.$cv.' / '.NEED_VINE.'</b> ·
    Прилив: <b>'.$ct.' / '.NEED_TIDE.'</b>
  </div>';
}

/* ===================== МИНИ-ИГРА: ЛОГИКА ===================== */
function simon_seq_to_string(array $seq): string {
  $s=''; foreach ($seq as $n) { $s .= (SIGNALS[$n]['emoji'] ?? '?'); }
  return $s;
}
function simon_generate_seq(int $len=SIMON_LEN): array {
  $seq=[]; for($i=0;$i<$len;$i++) $seq[] = random_int(1,4);
  return $seq;
}
function simon_set_deadline(array &$data): void {
  $data['mini']['deadline'] = time() + SIMON_DEADLINE;
}
function simon_start_new_full(array &$data): void {
  $data['mini'] = [
    'seq'   => simon_generate_seq(),
    'idx'   => 0,
    'lives' => SIMON_LIVES,
    'tries' => (int)($data['mini']['tries'] ?? 0) + 1
  ];
  simon_set_deadline($data);
}
function simon_reroll_keep_lives(array &$data): void {
  $lives = (int)($data['mini']['lives'] ?? SIMON_LIVES);
  $tries = (int)($data['mini']['tries'] ?? 0) + 1;
  $data['mini'] = [
    'seq'   => simon_generate_seq(),
    'idx'   => 0,
    'lives' => max(0,$lives),
    'tries' => $tries
  ];
  simon_set_deadline($data);
}
// Проверка таймаута на любой кнопке; возвращает массив результата или null если всё ок
function simon_check_timeout(array &$data): ?array {
  $now = time();
  $deadline = (int)($data['mini']['deadline'] ?? 0);
  if ($deadline>0 && $now > $deadline) {
    // таймаут — минус жизнь, сброс ввода
    $data['mini']['lives'] = max(0, ((int)$data['mini']['lives']) - 1);
    $data['mini']['idx']   = 0;
    if ($data['mini']['lives'] <= 0) {
      // Полный провал — новая связка и новые жизни
      simon_start_new_full($data);
      return ['timeout'=>'hard', 'lives'=>$data['mini']['lives'], 'seq'=> $data['mini']['seq'] ];
    }
    simon_set_deadline($data);
    return ['timeout'=>'soft', 'lives'=>$data['mini']['lives']];
  }
  return null;
}
function simon_handle_input(array &$data, int $pressed): array {
  if (empty($data['mini']) || empty($data['mini']['seq'])) {
    simon_start_new_full($data);
  }
  // сперва таймаут
  if ($t = simon_check_timeout($data)) return ['status'=>'timeout'] + $t;

  $idx = (int)$data['mini']['idx'];
  $len = count($data['mini']['seq']);
  $exp = (int)$data['mini']['seq'][$idx];

  if ($pressed === $exp) {
    $idx++;
    $data['mini']['idx'] = $idx;
    if ($idx >= $len) {
      // победа
      return ['status'=>'win','idx'=>$idx,'len'=>$len,'lives'=>$data['mini']['lives']];
    }
    // следующий ход — новый дедлайн
    simon_set_deadline($data);
    return ['status'=>'ok','idx'=>$idx,'len'=>$len,'lives'=>$data['mini']['lives']];
  } else {
    // ошибка
    $data['mini']['lives'] = max(0, ((int)$data['mini']['lives']) - 1);
    $data['mini']['idx']   = 0; // начинаем ввод заново
    if ($data['mini']['lives'] <= 0) {
      simon_start_new_full($data);
      return ['status'=>'fail-hard','lives'=>$data['mini']['lives'],'seq'=>$data['mini']['seq']];
    }
    simon_set_deadline($data);
    return ['status'=>'fail','idx'=>0,'len'=>$len,'lives'=>$data['mini']['lives']];
  }
}

/* ===================== НАГРАДА/ФИНАЛ ===================== */
function yuna_finalize_victory(mysqli $db, int $uid, array &$data, string $player): array {
  if (function_exists('itemAdd')) {
    itemAdd(REWARD_MONEY_ID, REWARD_MONEY_AMT, $uid);
    foreach (REWARD_ITEMS as $iid=>$q) itemAdd($iid,$q,$uid);
    if (!empty($data[BONUS_FLAG])) foreach (REWARD_BONUS as $iid=>$q) itemAdd($iid,$q,$uid);
  }
  unset($data['mini']);
  q_save($db,$uid,7,$data,1);

  $bonusTxt = !empty($data[BONUS_FLAG]) ? '<br>Бонус: <img src="'.item_icon(196).'" class="item" style="width:18px;height:18px;vertical-align:-3px"> Именной бланк ×1.' : '';

  log_quest_step(QUEST_ID,7,'Победа в мини-игре. Тайник вскрыт, награда выдана.'.(!empty($data[BONUS_FLAG])?' Бонус: Именной бланк ×1.':''));

  return [
    'actionQuestPlus' =>
      '<img src="'.item_icon(REWARD_MONEY_ID).'" class="item"> Генкары <b>'.number_format(REWARD_MONEY_AMT,0,'',' ').'</b><br>'.
      '<img src="'.item_icon(193).'" class="item"> Коробка витаминов <b>×1</b><br>'.
      '<img src="'.item_icon(26).'" class="item"> Жёлтая конфета <b>×2</b><br>'.
      '<img src="'.item_icon(3).'" class="item"> Грейтбол <b>×20</b>'.$bonusTxt,
    'question' => 'Юна улыбается: «Слышишь? Замок перестал рычать — он <i>поёт</i>. Мы сняли штормовой код чисто и без потерь. Тайник открыт, '.$player.', и это во многом твоя заслуга».',
    'answer'   => [0=>'Кивнуть'],
  ];
}

/* ===================== ДИАЛОГ ===================== */
$st  = q_get($mysqli,$uid);
$req = isset($npcStep) ? (int)$npcStep : 0;

if ($st && (int)$st['end'] === 1) {
  $response['question'] = 'Юна бережно складывает карту: «Сегодня штормовые ключи уже собраны, '.$player.'. Но если услышишь где-то странный “ритм” в ветре — приходи. У этой ветки ещё есть тайники».';
  $response['answer']   = [ 0 => 'Кивнуть и уйти' ];
  return;
}

if (!$st) {
  if ($req === 101) {
    $st = q_create($mysqli,$uid);
    $response['actionQuest'] = 'Обновлена информация в задании <b>'.QUEST_NAME.'</b>. Загляните в Дневник.';
    $response['question'] =
      'Юна ставит рядом с картой небольшой резонатор: «Замок слушает мир. Ему нужны четыре “ключа” — Ветер, Ток, Лоза и Прилив. Если собрать их правильно — тайник откроется без шума и паники». '.pill('Шаг 1/7').
      '<br><br><b>Ключ Ветра</b>: поймай <b>'.NEED_WIND.'</b> <u>разных видов</u> из: Pidgey(16), Pidgeotto(17), Spearow(21), Fearow(22), Hoothoot(163), Wingull(278).'.
      '<br><span style="color:#b8bce3">Считается только пойманное после разговора. Нужны именно <b>разные</b> виды — замок отличает “голоса”.</span>';
    $response['answer'] = [201=>'Проверить ловлю'];
  } else {
    $response['question'] = 'Юна Кавада, дежурная на старой прибрежной ветке, склонилась над схемой путей. «После последней бури один служебный тайник закрылся “штормовым замком”. Он открывается не ключом, а <i>ритмом стихий</i>. Поможешь?» '.pill('Долгий квест');
    $response['answer'] = [101=>'Начать «Ключи Шторма»'];
  }
  return;
}

/* ——— дальше квест уже начат ——— */
$data = $st['data']; $step = (int)$st['step'];

switch (true) {

  /* ---------- Проверка ловли ---------- */
  case ($req === 201):
    if ($step === 1) {
      $ts = (int)($data['ts_wind'] ?? $data['start_ts'] ?? time());
      $got = unique_count_since($mysqli,$uid,$ts,GROUP_WIND);
      if ($got >= NEED_WIND) {
        $data['ts_spark'] = time();
        q_save($mysqli,$uid,2,$data,0);
        log_quest_step(QUEST_ID,2,'Ключ Ветра готов. Далее: Ключ Тока — '.NEED_SPARK.' разных видов (Pikachu, Magnemite, Magneton, Voltorb).');
        $response['question'] =
          'Юна прислушивается, будто к рельсам: «Есть. Ветер “встал” в замок. Теперь нужна короткая, уверенная искра — <b>Ток</b>». '.pill('Шаг 2/7').
          '<br><b>Ключ Тока</b>: поймай <b>'.NEED_SPARK.'</b> <u>разных видов</u> из: Pikachu(25), Magnemite(81), Magneton(82), Voltorb(100).';
        $response['answer'] = [201=>'Проверить ловлю'];
      } else {
        $response['question'] = 'Юна качает головой: «Ветер ещё рваный — замок не “слышит” рисунок. Нужны разные летуны». Прогресс: <b>'.$got.' / '.NEED_WIND.'</b>.';
        $response['answer']   = [201=>'Проверить снова'];
      }
      break;
    }

    if ($step === 2) {
      $ts = (int)($data['ts_spark'] ?? time());
      $got = unique_count_since($mysqli,$uid,$ts,GROUP_SPARK);
      if ($got >= NEED_SPARK) {
        $data['ts_vine'] = time();
        q_save($mysqli,$uid,3,$data,0);
        log_quest_step(QUEST_ID,3,'Ключ Тока готов. Далее: Ключ Лозы — '.NEED_VINE.' разных видов (Oddish, Vileplume, Bellsprout, Weepinbell, Victreebel, Tangela, Hoppip).');
        $response['question'] =
          '«Вот теперь щёлкнуло. Ток закрепили». Юна улыбается краешком губ: «Дальше — <b>Лоза</b>. Живая нить, которая связывает механизмы и природу». '.pill('Шаг 3/7').
          '<br><b>Ключ Лозы</b>: поймай <b>'.NEED_VINE.'</b> <u>разных видов</u> из: Oddish(43), Vileplume(45), Bellsprout(69), Weepinbell(70), Victreebel(71), Tangela(114), Hoppip(187).';
        $response['answer'] = [201=>'Проверить ловлю'];
      } else {
        $response['question'] = 'Юна смотрит на резонатор: «Искра пляшет — добавь ещё один <b>другой</b> вид. Замку важны различия». Разных видов: <b>'.$got.' / '.NEED_SPARK.'</b>.';
        $response['answer']   = [201=>'Проверить снова'];
      }
      break;
    }

    if ($step === 3) {
      $ts = (int)($data['ts_vine'] ?? time());
      $got = unique_count_since($mysqli,$uid,$ts,GROUP_VINE);
      if ($got >= NEED_VINE) {
        $data['ts_tide'] = time();
        q_save($mysqli,$uid,4,$data,0);
        log_quest_step(QUEST_ID,4,'Ключ Лозы готов. Далее: Ключ Прилива — '.NEED_TIDE.' разных видов (Poliwag, Psyduck, Tentacool, Krabby, Goldeen, Seaking).');
        $response['question'] =
          'Юна проводит пальцем по схеме: «Лоза держит. Остался <b>Прилив</b> — вода “дожимает” штормовой замок, как груз на рычаге». '.pill('Шаг 4/7').
          '<br><b>Ключ Прилива</b>: поймай <b>'.NEED_TIDE.'</b> <u>разных видов</u> из: Poliwag(60), Psyduck(54), Tentacool(72), Krabby(98), Goldeen(118), Seaking(119).'.
          '<br><span style="color:#b8bce3">Подсказка: у воды виды меняются по времени — если не идёт один, попробуй другой “голос”.</span>';
        $response['answer'] = [201=>'Проверить ловлю'];
      } else {
        $response['question'] = 'Юна постукивает по корпусу резонатора: «Лозе не хватает “пульса”. Нужен ещё один <b>другой</b> вид». Разных видов: <b>'.$got.' / '.NEED_VINE.'</b>.';
        $response['answer']   = [201=>'Проверить снова'];
      }
      break;
    }

    if ($step === 4) {
      $ts = (int)($data['ts_tide'] ?? time());
      $got = unique_count_since($mysqli,$uid,$ts,GROUP_TIDE);
      if ($got >= NEED_TIDE) {
        q_save($mysqli,$uid,5,$data,0);
        log_quest_step(QUEST_ID,5,'Собраны четыре ключа. Сдать снабжение: Покебол×50, Грейтбол×15, Энергетик×5, Противоядие×5, Антипарализ×5. Бонус: Громовой камень×1 (необязательно).');
        $response['question'] =
          '«Есть. Четыре ключа на месте». Юна закрывает карту ремнём: «Но тайник — служебный. Откроем — и надо быть готовыми к любому сюрпризу. Нужны расходники и аптечка на случай, если штормовой замок сорвётся». '.pill('Шаг 5/7').
          '<br><b>Подготовь снабжение</b> и принеси:'.need_list_html(TURNIN).
          '<div style="margin-top:6px;color:#b8bce3">* Если принесёшь Громовой камень ×1 (id80) — я отмечу это как усиление стабилизатора (приятный бонус).</div>';
        $response['answer'] = [301=>'Передать снабжение'];
      } else {
        $response['question'] = 'Юна смотрит на воду: «Прилив ещё слабый — замок не поддаётся. Нужны разные водные “голоса”». Разных видов: <b>'.$got.' / '.NEED_TIDE.'</b>.';
        $response['answer']   = [201=>'Проверить снова'];
      }
      break;
    }

    $response['question'] = 'Юна кивает: «Этот шаг уже не про ловлю. Проверь дневник — там указано, что делать дальше».';
    break;

  /* ---------- Сдача снабжения → старт мини-игры ---------- */
  case ($req === 301):
    if ($step < 5) { $response['question'] = 'Юна поднимает ладонь: «Рано. Пока не соберём четыре ключа — снабжение не трогаем».';
      break;
    }

    $lack = [];
    foreach (TURNIN as $iid=>$q) if (!function_exists('item_isset') || !item_isset($iid,$q)) $lack[$iid]=$q;
    if ($lack) {
      $response['question'] = 'Юна быстро перебирает список и хмурится: «Почти. Но без этого я тайник не открою — слишком рискованно:» '.need_list_html($lack);
      $response['answer']   = [0=>'Понял'];
      break;
    }
    if (function_exists('minus_item')) foreach (TURNIN as $iid=>$q) minus_item($iid,$q);
    if (function_exists('item_isset') && item_isset(BONUS_ITEM_ID,1)) {
      if (function_exists('minus_item')) minus_item(BONUS_ITEM_ID,1);
      $data[BONUS_FLAG]=1;
    }

    // Переходим к мини-игре
    simon_start_new_full($data);
    q_save($mysqli,$uid,6,$data,0);
    log_quest_step(QUEST_ID,6,'Снабжение сдано. Финал — мини-игра «Саймон: Штормовые ключи».');

    $seqStr = simon_seq_to_string($data['mini']['seq']);
    $response['question'] =
      '«Финал. Штормовой замок покажет код вспышками». Юна кивает на панель: «Четыре руны — Ветер, Ток, Лоза, Прилив. Повтори их в том же порядке». '.pill('Шаг 6/7').'<br>'.
      'Код замка: <b style="font-size:18px">'.$seqStr.'</b><br>'.
      '<span style="color:#b8bce3">На ввод: '.SIMON_DEADLINE.' сек • Жизней: '.SIMON_LIVES.' • Длина кода: '.SIMON_LEN.'</span>'.
      simon_timer_html($data);
    $response['answer'] = [
      BTN_WIND  => '🌬️ Ветер',
      BTN_SPARK => '⚡️ Ток',
      BTN_VINE  => '🌿 Лоза',
      BTN_TIDE  => '🌊 Прилив',
      BTN_SIMON_SHOW => 'Показать последовательность ещё раз (новая)',
      BTN_SIMON_RETRY=> 'Начать заново (новая + жизни)',
    ];
    break;

  /* ---------- Мини-игра: показать (НОВАЯ) последовательность ---------- */
  case ($req === BTN_SIMON_SHOW):
    if ($step !== 6) { $response['question'] = 'Юна улыбается: «Рано нажимать руны — сначала дойдём до финальной проверки».';
      break;
    }
    if (empty($data['mini']) || empty($data['mini']['seq'])) simon_start_new_full($data);

    // таймаут перед действием
    $t = simon_check_timeout($data);

    // Рероллим связку, жизни сохраняем
    simon_reroll_keep_lives($data);
    q_save($mysqli,$uid,6,$data,0);

    $seqStr = simon_seq_to_string($data['mini']['seq']);
    $lives  = (int)$data['mini']['lives'];
    $pref   = $t ? 'Время вышло — штормовой замок сбросил вспышку. ' : '';
    $response['question'] =
      $pref.'Новый код замка: <b style="font-size:18px">'.$seqStr.'</b><br>'.
      '<span style="color:#b8bce3">Жизни: '.$lives.' • На ввод: '.SIMON_DEADLINE.' сек.</span>'.
      simon_timer_html($data);
    $response['answer'] = [
      BTN_WIND  => '🌬️ Ветер',
      BTN_SPARK => '⚡️ Ток',
      BTN_VINE  => '🌿 Лоза',
      BTN_TIDE  => '🌊 Прилив',
      BTN_SIMON_RETRY=> 'Начать заново (новая + жизни)',
    ];
    break;

  /* ---------- Мини-игра: начать заново (полный сброс) ---------- */
  case ($req === BTN_SIMON_RETRY):
    if ($step !== 6) { $response['question'] = 'Юна улыбается: «До финала ещё далеко. Вернёмся к рунам, когда замок будет готов».';
      break;
    }
    // таймаут перед действием
    simon_check_timeout($data);

    simon_start_new_full($data);
    q_save($mysqli,$uid,6,$data,0);

    $seqStr = simon_seq_to_string($data['mini']['seq']);
    $response['question'] =
      'Перезапускаю считывание. Замок мигнул по-новому: <b style="font-size:18px">'.$seqStr.'</b><br>'.
      '<span style="color:#b8bce3">Жизней: '.SIMON_LIVES.' • На ввод: '.SIMON_DEADLINE.' сек.</span>'.
      simon_timer_html($data);
    $response['answer'] = [
      BTN_WIND  => '🌬️ Ветер',
      BTN_SPARK => '⚡️ Ток',
      BTN_VINE  => '🌿 Лоза',
      BTN_TIDE  => '🌊 Прилив',
      BTN_SIMON_SHOW => 'Показать последовательность ещё раз (новая)',
    ];
    break;

  /* ---------- Мини-игра: ввод игрока (4 кнопки) ---------- */
  case in_array($req, [BTN_WIND, BTN_SPARK, BTN_VINE, BTN_TIDE], true):
    if ($step !== 6) { $response['question'] = 'Юна качает головой: «Сейчас не время для кода. Дойди до финального шага — и тогда».';
      break;
    }
    $pressed = [
      BTN_WIND  => 1,
      BTN_SPARK => 2,
      BTN_VINE  => 3,
      BTN_TIDE  => 4,
    ][$req] ?? 0;

    if (empty($data['mini']) || empty($data['mini']['seq'])) simon_start_new_full($data);
    $result = simon_handle_input($data, $pressed);

    if ($result['status'] === 'win') {
      // Победа → награда и финал
      $final = yuna_finalize_victory($mysqli,$uid,$data,$player);
      $response = array_merge($response,$final);
      break;
    }

    // Иначе продолжаем игру
    q_save($mysqli,$uid,6,$data,0);

    // Сообщения без подсказки «следующий шаг»
    if ($result['status'] === 'ok') {
      $idx   = (int)$result['idx'];
      $lives = (int)$result['lives'];
      $response['question'] =
        'Верно. Замок отзывается — не сбивай ритм!'.
        '<br><span style="color:#b8bce3">Прогресс: '.$idx.' / '.SIMON_LEN.' • Жизни: '.$lives.' • На ход: '.SIMON_DEADLINE.' сек.</span>'.
        simon_timer_html($data);
    } elseif ($result['status'] === 'timeout') {
      if ($result['timeout'] === 'hard') {
        $seqStr = simon_seq_to_string($data['mini']['seq']);
        $response['question'] =
          'Время вышло — замок “перегудел” и сбросил попытку. Лимит жизней исчерпан, выдаю новый код: <b style="font-size:18px">'.$seqStr.'</b><br>'.
          '<span style="color:#b8bce3">Жизней: '.SIMON_LIVES.' • На ход: '.SIMON_DEADLINE.' сек.</span>'.
          simon_timer_html($data);
      } else {
        $response['question'] =
          'Время вышло! Замок сбросил ввод. Жизней осталось: <b>'.$result['lives'].'</b>. Начинай код сначала.'.
          '<br><span style="color:#b8bce3">На ход: '.SIMON_DEADLINE.' сек.</span>'.
          simon_timer_html($data);
      }
    } elseif ($result['status'] === 'fail-hard') {
      $seqStr = simon_seq_to_string($data['mini']['seq']);
      $response['question'] =
        'Сбился и исчерпал жизни — замок сменил рисунок. Новый код: <b style="font-size:18px">'.$seqStr.'</b><br>'.
        '<span style="color:#b8bce3">Жизней: '.SIMON_LIVES.' • На ход: '.SIMON_DEADLINE.' сек.</span>'.
        simon_timer_html($data);
    } else { // fail
      $lives = (int)$result['lives'];
      $response['question'] =
        'Не тот символ. Жизней осталось: <b>'.$lives.'</b>. Начинай код сначала.'.
        '<br><span style="color:#b8bce3">На ход: '.SIMON_DEADLINE.' сек.</span>'.
        simon_timer_html($data);
    }

    $response['answer'] = [
      BTN_WIND  => '🌬️ Ветер',
      BTN_SPARK => '⚡️ Ток',
      BTN_VINE  => '🌿 Лоза',
      BTN_TIDE  => '🌊 Прилив',
      BTN_SIMON_SHOW => 'Показать последовательность ещё раз (новая)',
      BTN_SIMON_RETRY=> 'Начать заново (новая + жизни)',
    ];
    break;

  /* ---------- Статус ---------- */
  default:
    if     ($step === 1){ $response['question'] = 'Юна тихо говорит: «Сначала Ветер. Собери разные “голоса” — и замок начнёт слушать». '.pill('Шаг 1/7').greet_progress($mysqli,$uid,$st['data']); $response['answer']=[201=>'Проверить ловлю']; }
    elseif ($step === 2){ $response['question'] = '«Ключ Ветра уже на месте. Теперь нужен Ток — короткая искра от разных видов».'.greet_progress($mysqli,$uid,$st['data']); $response['answer']=[201=>'Проверить ловлю']; }
    elseif ($step === 3){ $response['question'] = '«Искру закрепили. Дай Лозе сцепиться — поймай несколько разных травяных видов».'.greet_progress($mysqli,$uid,$st['data']); $response['answer']=[201=>'Проверить ловлю']; }
    elseif ($step === 4){ $response['question'] = '«Лоза держит. Остался Прилив — замку нужны разные водные “голоса”».'.greet_progress($mysqli,$uid,$st['data']); $response['answer']=[201=>'Проверить ловлю']; }
    elseif ($step === 5){ $response['question'] = '«Ключи собраны. Теперь — страховка: принеси снабжение, и мы вскроем тайник без суеты»'.need_list_html(TURNIN).'<div style="margin-top:6px;color:#b8bce3">* Громовой камень (id80) — необязательный бонус для стабилизатора.</div>'; $response['answer']=[301=>'Передать снабжение']; }
    elseif ($step === 6){
      if (empty($data['mini']) || empty($data['mini']['seq'])) { simon_start_new_full($data); q_save($mysqli,$uid,6,$data,0); }
      $idx    = (int)$data['mini']['idx'];
      $lives  = (int)$data['mini']['lives'];
      $response['question'] =
        'Юна внимательно следит за панелью: «Слушай “ритм” вспышек и повторяй. Ошибёшься — начнёшь сначала. Хочешь подсказку — замок даст новый код».'.
        '<br><span style="color:#b8bce3">Прогресс: '.$idx.' / '.SIMON_LEN.' • Жизни: '.$lives.' • На ввод: '.SIMON_DEADLINE.' сек.</span>'.
        '<br><i>Кнопка «Показать» всегда выдаёт новую последовательность.</i>'.
        simon_timer_html($data);
      $response['answer']   = [
        BTN_WIND  => '🌬️ Ветер',
        BTN_SPARK => '⚡️ Ток',
        BTN_VINE  => '🌿 Лоза',
        BTN_TIDE  => '🌊 Прилив',
        BTN_SIMON_SHOW => 'Показать последовательность ещё раз (новая)',
        BTN_SIMON_RETRY=> 'Начать заново (новая + жизни)',
      ];
    }
    else {
      $response['question'] = 'Юна прячет резонатор в сумку: «Тайник открыт. Если услышишь новый “штормовой ритм” на линии — знай, где меня искать».';
      $response['answer']   = [0=>'До встречи'];
    }
    break;
}
