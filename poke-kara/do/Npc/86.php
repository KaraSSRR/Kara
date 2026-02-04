<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * NPC 86 — Приманщик (Янтарное озеро)
 * Квест: "Удочка мастера" (quest_id = 131)
 * PHP 5.6+
 *
 * Диалоги v6 (история + живость, подсказки сохранены):
 *  - Морской берег: старт (рыбак) и финал (мастер снастей)
 *  - Янтарное озеро: приманщик (крафт) и смотритель (активация приманки + запуск окна)
 */

$questId = 131;
$npcId   = 86;

$qNpc = $mysqli->query("SELECT `name`, `image` FROM `base_npc` WHERE `id` = ".$npcId." LIMIT 1");
$npc  = ($qNpc) ? $qNpc->fetch_assoc() : null;

$response['name']  = $npc ? $npc['name']  : 'NPC';
$response['image'] = $npc ? htmlspecialchars($npc['image']) : '/img/default-npc.png';

$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

$uq = null;
if ($userId > 0) {
    $qUq = $mysqli->query("SELECT `step`, `end`, `run_until` FROM `user_quests` WHERE `user_id` = ".$userId." AND `quest_id` = ".$questId." LIMIT 1");
    if (!$qUq) {
        $qUq = $mysqli->query("SELECT `step`, `end` FROM `user_quests` WHERE `user_id` = ".$userId." AND `quest_id` = ".$questId." LIMIT 1");
    }
    $uq  = ($qUq) ? $qUq->fetch_assoc() : null;
}

$curStep  = ($uq && isset($uq['step'])) ? (int)$uq['step'] : 0;
$isEnd    = ($uq && !empty($uq['end'])) ? 1 : 0;
$runUntil = ($uq && isset($uq['run_until'])) ? (int)$uq['run_until'] : 0;

if (!isset($npcStep)) $npcStep = 1;

function _fmt_left($sec) {
    $sec = (int)$sec;
    if ($sec < 0) $sec = 0;
    $m = floor($sec / 60);
    $s = $sec - ($m * 60);
    $ss = ($s < 10) ? ('0'.$s) : $s;
    return $m.':'.$ss;
}
?>
<?php
$baitIcon = '<img src="/img/world/items/little/5044.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;">';

$recipeHtml =
    '<div style="margin-top:6px;">'
  . '<div style="margin-bottom:6px;"><b>Временная приманка</b> '.$baitIcon.' <span style="opacity:.85;">(окно 30 минут после активации у смотрителя)</span></div>'
  . '<div style="opacity:.9;margin-bottom:6px;">Состав:</div>'
  . '<div><img src="/img/world/items/little/5040.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;"> Споры Параса <b>x4</b></div>'
  . '<div><img src="/img/world/items/little/5041.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;"> Пух Хоппипа <b>x4</b></div>'
  . '<div><img src="/img/world/items/little/5042.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;"> Фермент Випинбелла <b>x2</b></div>'
  . '<div><img src="/img/world/items/little/5043.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;"> Нектар Флоргес <b>x1</b></div>'
  . '<div style="margin-top:6px;opacity:.85;">Я крафчу. Активирует и запускает окно — <b>смотритель</b>.</div>'
  . '</div>';

$routeBack =
    '<div style="margin-top:6px;">'
  . '<b>Куда дальше</b><br>'
  . '• Приманка готова → к <b>Смотрителю водоёма</b> (активация + старт окна).<br>'
  . '• После успеха → <b>Морской берег</b> к мастеру снастей.'
  . '</div>';

$storyHtml =
    '<div style="margin-top:6px;">'
  . '<b>Почему приманка работает только здесь</b><br>'
  . 'Янтарная вода “тяжёлая”: в ней приманка не растворяется мгновенно и держит след. В море её рвёт солёной пеной и ветром — толку ноль.<br>'
  . '<span style="opacity:.85;">Поэтому я делаю смесь, а смотритель активирует её при тебе — чтобы окно было честным.</span>'
  . '</div>';

$canCraft = (item_isset(5040, 4) && item_isset(5041, 4) && item_isset(5042, 2) && item_isset(5043, 1)) ? 1 : 0;

switch ($npcStep) {

    default:

        if ($isEnd == 1) {
            $response['question'] = 'Слышал, ты выдержал окно. Значит, руки у тебя быстрые, а голова — спокойная. Редкое сочетание.';
            $response['answer']   = array(1 => 'Спасибо.');
            break;
        }

        if ($curStep < 10) {
            $response['question'] = 'Сначала возьми задание у старого рыбака на <b>Морском берегу</b>. Потом приходи — у меня всё по списку.';
            $response['answer']   = array(1 => 'Понял.');
            break;
        }

        if ($curStep >= 40) {
            $response['question'] = 'Тсс. Если попытка идёт — экономь секунды. Смотритель тебе всё скажет по таймеру.';
            $response['answer']   = array(3 => 'Куда дальше?', 1 => 'Понял.');
            break;
        }

        $response['question'] = 'Я — приманщик. Не шарлатан: я не обещаю “лёгкую рыбу”, я обещаю честный шанс. Но помни: я только собираю смесь, а окно и порядок — у <b>смотрителя</b>.';
        if ($canCraft) {
            $response['answer'] = array(2 => 'Смешай приманку.', 4 => 'Показать рецепт.', 5 => 'Почему только здесь?', 3 => 'Куда дальше?', 1 => 'Пока нет.');
        } else {
            $response['answer'] = array(4 => 'Показать рецепт.', 5 => 'Почему только здесь?', 3 => 'Куда дальше?', 1 => 'Пока нет.');
        }
        break;

    case 5:
        $response['question'] = $storyHtml;
        $response['answer']   = array(1 => 'Понял.');
        break;

    case 3:
        $response['question'] = $routeBack;
        $response['answer']   = array(1 => 'Ясно.');
        break;

    case 4:
        $response['question'] = $recipeHtml;
        $response['answer']   = array(1 => 'Запомнил.');
        break;

    case 2:

        if (!$canCraft) {
            $response['question'] = 'Сырьё не сходится. Без ингредиентов я не буду “рисовать” приманку из воздуха.';
            $response['answer']   = array(4 => 'Показать рецепт.', 1 => 'Ладно.');
            break;
        }

        minus_item(5040, 4);
        minus_item(5041, 4);
        minus_item(5042, 2);
        minus_item(5043, 1);

        itemAdd(5044, 1);

        quest_update($questId, 20);
        if (function_exists('update_zap')) update_zap($questId, 20, 'Приманка готова. Иди к смотрителю на Янтарном озере для активации.');

        $response['question'] = 'Готово. Забирай '.$baitIcon.'. Теперь к <b>смотрителю</b>: он активирует и запустит окно. Не тяни — в этом квесте решают секунды.';
        $response['answer']   = array(3 => 'Куда дальше?', 1 => 'Иду.');
        break;
}
?>
