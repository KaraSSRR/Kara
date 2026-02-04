<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';
header('Content-Type: application/json; charset=UTF-8');

$npcId   = 82; // Абомасноу
$questId = 51; // «К снежной горе»

/**
 * ID предмета «Угощение для Абомасноу», которое отдаёт Алиса после крафта.
 * ОБЯЗАТЕЛЬНО поставьте реальный ID из вашей базы!
 */
$TREAT_ITEM_ID = 509; // <<< ЗАМЕНИТЕ на ваш реальный ID угощения

// --- Карточка NPC
$npcRow = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`=".(int)$npcId)->fetch_assoc();
$name  = $npcRow['name']  ?? 'Абамасноу';
$image = $npcRow['image'] ?? '/img/npc/82.png';
$image = trim(preg_replace('~[\r\n\t]+~', '', $image));
if ($image === '') $image = '/img/npc/82.png';

$response = [
  'error' => 0,
  'name'  => $name,
  'image' => $image,
];

$step = (int)($npcStep ?? -1);

// Хелпер: человекочитаемое имя предмета
function _getItemTitle($id){
  global $mysqli;
  $r = $mysqli->query("SELECT `name` FROM `base_items` WHERE `id`=".(int)$id)->fetch_assoc();
  return ($r && !empty($r['name'])) ? $r['name'] : ('Предмет #'.$id);
}

/* ---------- Кнопка «закрыть» ---------- */
if ($step === 90) {
  $response['close']    = 1;
  $response['question'] = '';
  $response['answer']   = [];
  echo json_encode($response, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

/* ========================== ВЕТВЛЕНИЕ ПО ШАГАМ КВЕСТА ========================== */

// Квест ещё не начат
if (!quest_isset($questId)) {
  $response['question'] = 'Подъём на гору преграждает могучий Абамасноу! Его боевой настрой заставит запаниковать любого начинающего тренера.';
  $response['answer']   = [ 2 => 'Осмотреться в поиске, куда бежать.' ];
}
// Раннее начало (1–2)
elseif (quest_step($questId,1) || quest_step($questId,2)) {
  $response['question'] = 'Подъём на гору преграждает могучий Абамасноу! Его боевой настрой заставит запаниковать любого начинающего тренера.';
  $response['answer']   = [ 2 => 'Осмотреться в поиске, куда бежать.' ];
}
// Осмотр выполнен, но ещё не ходили к Алисе (3–7) либо она выдала рецепт (8)
elseif (quest_step($questId,3) || quest_step($questId,4) || quest_step($questId,5) ||
        quest_step($questId,6) || quest_step($questId,7) || quest_step($questId,8)) {
  $response['question'] = 'Р-р-р-р… Кажется, он кого-то защищает. Стоит спросить в покецентре Церулина.';
  $response['answer']   = [ 90 => 'Вернуться позже' ];
}
// Угощение у игрока готово (9)
elseif (quest_step($questId,9)) {
  $response['question'] = 'Р-р-р-р… (принюхивается)';
  $response['answer']   = [ 10 => 'Протянуть подготовленное угощение.' ];
}
// Переходные шаги
elseif (quest_step($questId,10)) {
  $response['question'] = 'Абамасноу настороженно смотрит на тренера…';
  $response['answer']   = [ 11 => 'Подойти медленно.' ];
}
elseif (quest_step($questId,11)) {
  $response['question'] = 'Абамасноу принял угощение и немного отступил, пропуская тренера. Сновер выглядывает из-за его спины.';
  $response['answer']   = [ 12 => 'Проследить за Сновером.' ];
}
// Финал (13)
elseif (quest_step($questId,13)) {
  $response['question'] = 'Сновер окреп и больше не в опасности. Путь на гору открыт.';
  $response['answer']   = [ 90 => 'Завершить' ];
}
// Фолбэк
else {
  $response['question'] = 'Р-р-р-р…';
  $response['answer']   = [ 90 => 'Вернуться позже' ];
}

/* ====================== ОБРАБОТКА НАЖАТИЙ (ПО step) ====================== */
switch ($step) {
  // Шаг 2 — первый осмотр
  case 2:
    if (!quest_step($questId,3)) {
      quest_update($questId,3);
      update_zap($questId,3,'За спиной Абамасноу заметен больной маленький Сновер. Абамасноу его защищает. Нужна помощь в покецентре Церулина.');
    }
    $response['question'] = 'И тут за спиной вы замечаете больного маленького Сновера. Так он не агрессивен? Абамасноу просто его защищает. Забрать его в покецентр я не смогу, Абомасноу точно нападёт. Что же делать?';
    $response['answer']   = [ 90 => 'Понял' ];
  break;

  // Шаг 10 — протянуть угощение (списываем предмет)
  case 10:
    if (!quest_step($questId,9)) {
      $response['question'] = 'Кажется, угощение ещё не готово. Сначала загляните к стажёру Алисе в покецентре.';
      $response['answer']   = [ 90 => 'Хорошо' ];
      break;
    }
    // Если уже были на 10-м — не списываем повторно
    if (!quest_step($questId,10)) {
      if (!item_isset($TREAT_ITEM_ID,1)) {
        $response['question'] = 'Похоже, угощения нет с собой. Вернитесь к Алисе — она подготовит всё необходимое.';
        $response['answer']   = [ 90 => 'Понял' ];
        break;
      }
      minus_item($TREAT_ITEM_ID,1);
      $response['actionQuestMinus'] =
        '<img src="/img/world/items/little/'.$TREAT_ITEM_ID.'.png" class="item"> '
        . htmlspecialchars(_getItemTitle($TREAT_ITEM_ID)) . ' <b>x1</b>';
      quest_update($questId,10);
    }
    $response['question'] = 'Не бойся, я пришёл помочь… (вы протягиваете угощение) — Абамасноу берёт его и успокаивается.';
    $response['answer']   = [ 11 => 'Подойти медленно.' ];
  break;

  // Шаг 11 — кормим Сновера лекарством
  case 11:
    if (!quest_step($questId,11)) quest_update($questId,11);
    $response['question'] = 'Принюхавшись, Абамасноу отступает в сторону, пропуская тренера. Аккуратно подойдя к Сноверу, вы проследили, чтобы он съел конфету с лекарством. Сновер на глазах окреп и встал на лапы, посеменив к старшему товарищу.';
    $response['answer']   = [ 12 => 'Мило проводить взглядом.' ];
  break;

  // Шаг 12 — финальная награда и закрытие
  case 12:
    if (!quest_step($questId,13)) {
      // Награда один раз
      itemAdd(400,1); 
      itemAdd(563,1); // Сухой лёд ×1 (оставляю как в ваших скриптах)
      itemAdd(1,100000);  // Эмеральды ×20000
      quest_update($questId,13,1);
      update_zap($questId,13,'Сновер выздоровел. Проход на гору открыт.');
      $response['actionQuestPlus'] =
        '<img src="/img/world/items/little/400.png" class="item"> Сухой лёд <b>x1</b><br>'.
        '<img src="/img/world/items/little/563.png" class="item"> Скобовое кольцо <b>x1</b><br>'.
        '<img src="/img/world/items/little/100.png" class="item"> Генкар <b>x100000</b>';
    }
    $response['question'] = 'Выполнено.';
    $response['answer']   = [ 90 => 'Завершить' ];
  break;
}

while (ob_get_level()) { ob_end_clean(); }
echo json_encode($response, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
exit;
