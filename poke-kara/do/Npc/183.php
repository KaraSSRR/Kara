<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
header('Content-Type: application/json; charset=UTF-8');

/*
  КВЕСТ: «К снежной горе» (id = 51)
  РОЛЬ АЛИСЫ:
  1) Нормальное приветствие.
  2) По просьбе игрока — рассказать, что произошло на перевале (step 40).
  3) Выдать рецепт: принести «цветы лекаря» двух видов (update -> step 8).
  4) Принять по 5 шт. каждого вида, выдать промежуточную награду, поставить таймер 1 час.
  5) До истечения часа — «ещё не готово».
  6) После часа — попросить 2 типовые конфеты: ТРАВЯНУЮ и СНЕЖНУЮ (step 120).
  7) Принять конфеты, добавить лекарство в травяную, выдать «угощение», обновить квест до step 9.
*/

/* ---------- ПАРАМЕТРЫ (подставьте реальные ID) ---------- */

// Цветы лекаря (из дропа #412 Бурми и #043 Оддиш)
$FLOWER_BURMY_ITEM  = 510;   // <<< ID «Лепестки лекаря (Бурми)»
$FLOWER_ODDISH_ITEM = 511;   // <<< ID «Лепестки лекаря (Оддиш)»
$FLOWERS_NEED_EACH  = 5;

// Типовые конфеты (любой один из списка подойдёт)
$GRASS_CANDY_IDS = [ 47 ]; // <<< ваши ID травяных конфет
$ICE_CANDY_IDS   = [ 43 ]; // <<< ваши ID снежных конфет

// Готовое угощение, которое затем заберёт Абамасноу
$TREAT_ITEM_ID   = 509;  // <<< ID «Угощения для Абамасноу»

// Промежуточная награда после сдачи цветов
$STIMPACK_ITEM_ID = 13; // «Стимпак»
$STIMPACK_COUNT   = 5;
$EMERALDS_ID      = 1;  // Эмеральды
$EMERALDS_1       = 50000;

/* ------------------------------------------------------- */

$npcId   = 183; // Алиса
$questId = 51;

// Карточка NPC
$npcRow = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`=".(int)$npcId)->fetch_assoc();
$response = [
  'error' => 0,
  'name'  => $npcRow['name']  ?? 'Стажёр Алиса',
  'image' => isset($npcRow['image']) ? htmlspecialchars(trim($npcRow['image'])) : '/img/default-npc.png',
];

$step = (int)($npcStep ?? -1);

/* ----------------------------- ХЕЛПЕРЫ ----------------------------- */
function titleOf($id){
  global $mysqli;
  $r = $mysqli->query("SELECT `name` FROM `base_items` WHERE `id`=".(int)$id)->fetch_assoc();
  return ($r && $r['name']) ? $r['name'] : ('Предмет #'.$id);
}
function listHtml($pairs){
  // Для списков рецепта/недостатков — без жёсткого размера (их можно стилизовать CSS-ом)
  $rows = [];
  foreach($pairs as $id=>$need){
    $rows[] = '<div class="need-row"><img class="item" src="/img/world/items/little/'.$id.'.png"> '
            . htmlspecialchars(titleOf($id)) . ' <b>x'.$need.'</b></div>';
  }
  return implode('', $rows);
}
// Аккуратная иконка фиксированного размера (для уведомлений Plus/Minus)
function iconTag($id, $size = 36){
  $s = (int)$size;
  return '<img class="item" src="/img/world/items/little/'.$id.'.png" alt="" style="width:'.$s.'px;height:'.$s.'px;object-fit:contain;vertical-align:middle">';
}
// Ряд с фикс-иконкой (для уведомлений Plus/Minus)
function rowsHtmlFixed($pairs, $size = 36){
  $rows = [];
  foreach($pairs as $id=>$need){
    $rows[] = '<div class="need-row">'.iconTag($id,$size).' '
            . htmlspecialchars(titleOf($id)) . ' <b>x'.$need.'</b></div>';
  }
  return implode('', $rows);
}
function hasOneFromList($ids, $need = 1){
  foreach ($ids as $id){
    if (item_isset($id,$need)) return (int)$id;
  }
  return 0;
}
function minusOneFromList($ids, $need = 1){
  foreach ($ids as $id){
    if (item_isset($id,$need)) { minus_item($id,$need); return (int)$id; }
  }
  return 0;
}
// Таймер: пишем / читаем `base_npc_data.time`
function setNpcTimer($npcId, $secondsAhead){
  global $mysqli;
  $until = time() + (int)$secondsAhead;
  $uid   = (int)$_SESSION['id'];
  $mysqli->query("
    INSERT INTO `base_npc_data` (`userID`,`npcID`,`time`)
    VALUES ($uid,$npcId,$until)
    ON DUPLICATE KEY UPDATE `time` = VALUES(`time`)
  ");
}
function getNpcTimer($npcId){
  global $mysqli;
  $uid = (int)$_SESSION['id'];
  $r = $mysqli->query("SELECT `time` FROM `base_npc_data` WHERE `userID`=$uid AND `npcID`=".(int)$npcId)->fetch_assoc();
  return $r ? (int)$r['time'] : 0;
}

// Кнопка «закрыть»
if ($step === 90){
  $response['close'] = 1;
  $response['question'] = '';
  $response['answer'] = [];
  echo json_encode($response, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

/* ============================== ДИАЛОГИ ============================== */
switch ($step){

  /* -------------------- БАЗОВОЕ ПОВЕДЕНИЕ -------------------- */
  default:

    if (!quest_isset($questId)){
      $response['question'] = 'Здравствуйте! Я Алиса, стажёр покецентра Церулина. Если понадобится помощь — обращайтесь.';
      $response['answer']   = [ 90 => 'Спасибо' ];
      break;
    }

    // После перевала, до рецепта
    if ((quest_step($questId,1) || quest_step($questId,2) || quest_step($questId,3) ||
         quest_step($questId,4) || quest_step($questId,5) || quest_step($questId,6) ||
         quest_step($questId,7)) && !quest_step($questId,8)) {

      $response['question'] = 'Здравствуйте, тренер. Вы выглядите взволнованно… Чем я могу помочь?';
      $response['answer']   = [
        40 => 'Рассказать о встрече с Абамасноу и больным Сновером на Дороге 28',
        90 => 'Ничего, спасибо'
      ];
      break;
    }

    // Рецепт выдан (step 8), угощение ещё не готово
    if (quest_step($questId,8) && !quest_step($questId,9)) {

      // Проверим таймер (час ожидания после сдачи цветов)
      $till = getNpcTimer($npcId);
      if ($till > time()){
        $response['question'] = 'Ты уже пришёл? Пока нет, подожди ещё немного.';
        $response['answer']   = [ 90 => 'Хорошо, подожду' ];
        break;
      }

      // Если таймера нет — ещё не сдавал цветы → напоминаем рецепт
      if ($till === 0){
        $pairs = [
          $FLOWER_BURMY_ITEM  => $FLOWERS_NEED_EACH,
          $FLOWER_ODDISH_ITEM => $FLOWERS_NEED_EACH
        ];
        $response['question'] = 'Нужно лечебное зелье. К сожалению, запасы цветов лекаря закончились. Принеси, пожалуйста, по <b>'.$FLOWERS_NEED_EACH.'</b> шт. каждого:'
          .'<div class="craft-list">'.listHtml($pairs).'</div>';
        $response['answer']   = [
          100 => 'Передать лепестки лекаря',
          90  => 'Я вернусь позже'
        ];
        break;
      }

      // Таймер истёк — просим 2 типовые конфеты
      $response['question'] = 'Готово. Но как ты решишь проблему с агрессивно настроенным Абамасноу?'
        .'<br>— У меня есть идея. Этот вид покемонов живёт высоко в горах и редко видел людей. Может, если его чем-то угостить, он поймёт, что ты пришёл с миром?'
        .'<br>Принеси мне <b>2 типовые конфеты</b>: одну <b>травяного</b> и одну <b>снежного</b> типа — во вторую я добавлю лекарство для Сновера.';
      $response['answer']   = [
        120 => 'Передать 2 подходящие конфеты',
        90  => 'Скоро вернусь'
      ];
      break;
    }

    // Угощение готово (step 9)
    if (quest_step($questId,9)){
      $response['question'] = 'Угощение готово. Вернись на Дорогу 28 и спокойно протяни его Абамасноу — он поймёт.';
      $response['answer']   = [ 90 => 'Бегу!' ];
      break;
    }

    // После развязки
    if (quest_step($questId,10) || quest_step($questId,11) || quest_step($questId,13)){
      $response['question'] = 'Рада, что всё закончилось хорошо. Если что — заходи.';
      $response['answer']   = [ 90 => 'Спасибо' ];
      break;
    }

    // Фолбэк
    $response['question'] = 'Чем могу помочь?';
    $response['answer']   = [ 90 => 'Ничем, спасибо' ];
  break;

  /* ----------------------------- 40: рассказ игрока ----------------------------- */
  case 40:
    if (!quest_step($questId,8)){
      quest_update($questId,8);
      update_zap($questId,8,'Стажёр Алиса дала рецепт угощения. Нужно собрать лепестки лекаря двух видов.');
    }
    $pairs = [
      $FLOWER_BURMY_ITEM  => $FLOWERS_NEED_EACH,
      $FLOWER_ODDISH_ITEM => $FLOWERS_NEED_EACH
    ];
    $response['question'] =
      'Ох… Ему срочно нужно лечебное зелье. Но запасы лепестков лекаря закончились, поставки только на следующей неделе.'
      .'<br>Можешь добыть сам? Нужны два вида: по <b>'.$FLOWERS_NEED_EACH.'</b> шт. каждого.'
      .'<div class="craft-list">'.listHtml($pairs).'</div>';
    $response['answer'] = [
      100 => 'Передать лепестки лекаря',
      90  => 'Хорошо, соберу и вернусь'
    ];
  break;

  /* ----------------------- 100: сдача цветов лекаря (+таймер) ---------------------- */
  case 100:
    if (!quest_step($questId,8)){
      $response['question'] = 'Пока тебе это не нужно.';
      $response['answer']   = [ 90 => 'Понял' ];
      break;
    }

    $need = [
      $FLOWER_BURMY_ITEM  => $FLOWERS_NEED_EACH,
      $FLOWER_ODDISH_ITEM => $FLOWERS_NEED_EACH
    ];
    $miss = [];
    foreach ($need as $id=>$cnt){
      if (!item_isset($id,$cnt)) $miss[$id] = $cnt;
    }
    if (!empty($miss)){
      $response['question'] = 'Пока не хватает материалов:'
        .'<div class="miss-list">'.listHtml($miss).'</div>';
      $response['answer']   = [ 90 => 'Хорошо, принесу' ];
      break;
    }

    // Списываем цветы
    foreach ($need as $id=>$cnt) minus_item($id,$cnt);

    // Промежуточная награда
    $plusA = '';
    if ($STIMPACK_ITEM_ID > 0 && $STIMPACK_COUNT > 0){
      itemAdd($STIMPACK_ITEM_ID,$STIMPACK_COUNT);
      $plusA = iconTag($STIMPACK_ITEM_ID).' '.htmlspecialchars(titleOf($STIMPACK_ITEM_ID)).' <b>x'.$STIMPACK_COUNT.'</b><br>';
    }
    $plusB = '';
    if ($EMERALDS_ID > 0 && $EMERALDS_1 > 0){
      itemAdd($EMERALDS_ID,$EMERALDS_1);
      $plusB = iconTag($EMERALDS_ID).' Эмеральды <b>x'.$EMERALDS_1.'</b>';
    }

    // Уведомления с фикс-иконками
    $response['actionQuestMinus'] = rowsHtmlFixed($need, 36);
    $response['actionQuestPlus']  = $plusA.$plusB;

    // Ставим таймер на 1 час
    setNpcTimer($npcId, 3600);
    update_zap($questId,8,'Лепестки переданы. Вернитесь к стажёру Алисе через час.');

    $response['question'] = 'Спасибо! Я подготовлю основу зелья. Приходи через час — у меня посетители.';
    $response['answer']   = [ 90 => 'Вернусь позже' ];
  break;

  /* --------------------------- 120: сдача 2 конфет --------------------------- */
  case 120:
    if (!quest_step($questId,8)){
      $response['question'] = 'Похоже, мы ещё не договорились о рецепте.';
      $response['answer']   = [ 90 => 'Понял' ];
      break;
    }
    // Должен истечь час ожидания
    if (getNpcTimer($npcId) > time()){
      $response['question'] = 'Пока нет, подожди ещё немного.';
      $response['answer']   = [ 90 => 'Хорошо' ];
      break;
    }

    // Нужна одна травяная + одна снежная конфета
    $grassId = hasOneFromList($GRASS_CANDY_IDS,1);
    $iceId   = hasOneFromList($ICE_CANDY_IDS,1);

    if (!$grassId || !$iceId){
      $response['question'] = 'Я думаю, они не будут их есть. Это покемоны снежного и травяного типа. Принеси подходящие: одну травяного и одну снежного типа.';
      $response['answer']   = [ 90 => 'Понял' ];
      break;
    }

    // Списываем по 1 шт. каждой подходящей конфеты
    $grassId = minusOneFromList($GRASS_CANDY_IDS,1);
    $iceId   = minusOneFromList($ICE_CANDY_IDS,1);

    // Уведомление списания с фикс-иконками
    $response['actionQuestMinus'] =
      iconTag($grassId).' '.htmlspecialchars(titleOf($grassId)).' <b>x1</b><br>'.
      iconTag($iceId).' '.htmlspecialchars(titleOf($iceId)).' <b>x1</b>';

    // Выдаём угощение
    if ($TREAT_ITEM_ID > 0){
      itemAdd($TREAT_ITEM_ID,1);
      $response['actionQuestPlus'] = iconTag($TREAT_ITEM_ID).' '.htmlspecialchars(titleOf($TREAT_ITEM_ID)).' <b>x1</b>';
    }

    // Переводим квест на шаг 9 — можно идти к Абамасноу
    if (!quest_step($questId,9)){
      quest_update($questId,9);
      update_zap($questId,9,'Угощение готово. Вернитесь к Абамасноу на Дороге 28 и спокойно протяните его.');
    }

    $response['question'] = 'Хорошо. Я добавила лекарство в конфету травяного типа — не перепутай. Угощение готово, удачи тебе!';
    $response['answer']   = [ 90 => 'Бегу к перевалу' ];
  break;
}

while (ob_get_level()) { ob_end_clean(); }
echo json_encode($response, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
exit;
