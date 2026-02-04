<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * NPC 85 — Старый рыбак (старт, Морской берег)
 * Квест: "Удочка мастера" (quest_id = 131)
 * PHP 5.6+
 *
 * Диалоги v6 (история + живость, подсказки сохранены):
 *  - Морской берег: старт (рыбак) и финал (мастер снастей)
 *  - Янтарное озеро: приманщик (крафт) и смотритель (активация приманки + запуск окна)
 */

$questId = 131;
$npcId   = 85;

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
$rodRentIcon = '<img src="/img/world/items/little/5045.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;">';

$routeHtml =
    '<div style="margin-top:6px;">'
  . '<b>Маршрут квеста</b><br>'
  . '• <b>Морской берег</b>: я (старт) и <b>Мастер снастей</b> (финал).<br>'
  . '• <b>Янтарное озеро</b>: <b>Приманщик</b> (крафт) и <b>Смотритель</b> (активация приманки + запуск окна на 30 минут).<br>'
  . '<span style="opacity:.85;">Почему так: мастер не может бросить мастерскую — берег живёт на его ремонтах. А Янтарное озеро — единственное место, где приманка “цепляется” за воду и работает честно под надзором смотрителя.</span>'
  . '</div>';

$aboutHtml =
    '<div style="margin-top:6px;">'
  . '<b>О чём испытание</b><br>'
  . 'Старую удочку мастер держит не как товар, а как память. Она прошла штормы и голодные сезоны — и он не отдаст её тому, кто дергает воду без уважения.<br>'
  . '<span style="opacity:.85;">На Янтарном озере ты покажешь не “силу”, а дисциплину: уложиться в окно и не сорваться.</span>'
  . '</div>';

switch ($npcStep) {

    default:

        if ($isEnd == 1) {
            $response['question'] = 'Ну вот. Морской берег любит, когда слово держат. Удочка у тебя — значит, озеро тебя не сломало, а научило. Не забудь: улов начинается с головы.';
            $response['answer']   = array(1 => 'Спасибо.');
            break;
        }

        if ($curStep >= 50) {
            $response['question'] = 'У тебя уже есть отметка от смотрителя. Дальше — к <b>Мастеру снастей</b> здесь, на <b>Морском берегу</b>, у мастерской возле лодок.';
            $response['answer']   = array(3 => 'Показать маршрут.', 1 => 'Понял.');
            break;
        }

        if ($curStep >= 10) {
            $response['question'] = 'Дальше — <b>Янтарное озеро</b>. Там приманщик сделает приманку, а смотритель активирует её и откроет окно на 30 минут. Вернёшься сюда — закроем дело.';
            $response['answer']   = array(3 => 'Показать маршрут.', 4 => 'О чём испытание?', 1 => 'Иду.');
            break;
        }

        $response['actionQuest'] = 'Доступно задание: <b>Удочка мастера</b>.';
        $response['question']    = 'Тренер… Видишь этот берег? Он помнит людей лучше, чем люди — себя. Мастер снастей хранит одну удочку “не для продажи”. Хочешь — заслужи. Испытание идёт на <b>Янтарном озере</b>: там вода спокойная, и оправданий не придумаешь.';
        $response['answer']      = array(2 => 'Берусь.', 4 => 'О чём испытание?', 3 => 'Показать маршрут.', 99 => 'Потом.');
        break;

    case 4:
        $response['question'] = $aboutHtml;
        $response['answer']   = array(1 => 'Ясно.');
        break;

    case 3:
        $response['question'] = $routeHtml;
        $response['answer']   = array(1 => 'Запомнил.');
        break;

    case 2:

        $hasAnyRod = (item_isset(5, 1) || item_isset(6, 1)) ? 1 : 0;

        if (!$hasAnyRod) {
            if (!item_isset(5045, 1)) itemAdd(5045, 1);
        }

        quest_update($questId, 10);
        if (function_exists('update_zap')) update_zap($questId, 10, 'Начато испытание. Следующая точка: Янтарное озеро (приманщик и смотритель).');

        $response['actionQuest'] = 'Вы приняли задание <b>Удочка мастера</b>.';
        if ($hasAnyRod) {
            $response['question'] = 'Хорошо. Снасть у тебя есть — значит не утонешь в мелочах. Держи маршрут: <b>Янтарное озеро</b> → приманщик (крафт) → смотритель (активация и старт окна). После успеха — обратно на <b>Морской берег</b> к мастеру снастей.';
        } else {
            $response['question'] = 'Снасти нет — держи прокат '.$rodRentIcon.'. Но это только чтобы пройти испытание: в конце вернёшь. Дальше по маршруту — <b>Янтарное озеро</b>.';
        }
        $response['answer']      = array(3 => 'Показать маршрут.', 1 => 'Понял, выдвигаюсь.');
        break;

    case 99:
        $response['question'] = 'Ладно. Если услышишь, как море “зовёт” — значит пора возвращаться. Я буду здесь.';
        $response['answer']   = array(1 => 'Хорошо.');
        break;
}
?>
