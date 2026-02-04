<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * NPC 87 — Смотритель водоёма (Янтарное озеро)
 * Quest 131: Удочка мастера
 *
 * v14 (фикс):
 *  - состояние квеста берём из user_quests.step (20/40/50), а user_quest_info используется ТОЛЬКО как счётчик
 *  - если user_quest_info удаляется системой — квест всё равно фиксируется (step=50) и Смотритель не предлагает запуск
 *  - если попытка уже идёт (step=40 и приманка активна) — Смотритель показывает прогресс, а не “Запускай”
 *  - без actionQuest (чтобы не триггерить notifications 500)
 *  - PHP 5.6 safe, без header/echo/json_encode
 */

$questId = 131;
$npcId   = 87;

if (!isset($npcStep)) $npcStep = 1;

$npc = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`=".(int)$npcId." LIMIT 1")->fetch_assoc();
$response = array();
$response['name']  = !empty($npc['name']) ? $npc['name'] : 'NPC';
$response['image'] = !empty($npc['image']) ? htmlspecialchars($npc['image']) : '/img/default-npc.png';

$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

function q131_fmt_left($sec) {
    $sec = (int)$sec;
    if ($sec < 0) $sec = 0;
    $m = floor($sec / 60);
    $s = $sec - ($m * 60);
    if ($s < 10) $s = '0'.$s;
    return $m.':'.$s;
}

function q131_bait_until($mysqli, $userId) {
    $q = $mysqli->query("SELECT `time` FROM `bafs` WHERE `user`=".(int)$userId." AND `type`=6 ORDER BY `time` DESC LIMIT 1");
    if(!$q) return 0;
    $r = $q->fetch_assoc();
    return !empty($r['time']) ? (int)$r['time'] : 0;
}
function q131_bait_left($mysqli, $userId) {
    $until = q131_bait_until($mysqli, $userId);
    if ($until <= 0) return 0;
    $left = $until - time();
    return ($left > 0) ? $left : 0;
}
function q131_set_bait_30m($mysqli, $userId) {
    $until = time() + 1800;
    $q = $mysqli->query("SELECT `id` FROM `bafs` WHERE `user`=".(int)$userId." AND `type`=6 LIMIT 1");
    $r = $q ? $q->fetch_assoc() : null;
    if (!empty($r['id'])) {
        $mysqli->query("UPDATE `bafs` SET `time`=".$until.", `baf`=5044 WHERE `user`=".(int)$userId." AND `type`=6 LIMIT 1");
    } else {
        $mysqli->query("INSERT INTO `bafs` (`user`,`baf`,`time`,`type`) VALUES (".(int)$userId.",5044,".$until.",6)");
    }
    return $until;
}

function q131_item_count($mysqli, $userId, $itemId) {
    $q = $mysqli->query("SELECT `count` FROM `items_users` WHERE `user`=".(int)$userId." AND `item_id`=".(int)$itemId." LIMIT 1");
    if(!$q) return 0;
    $r = $q->fetch_assoc();
    return !empty($r['count']) ? (int)$r['count'] : 0;
}
function q131_item_dec($mysqli, $userId, $itemId, $cnt) {
    $cnt = (int)$cnt;
    if ($cnt <= 0) return;
    $cur = q131_item_count($mysqli, $userId, $itemId);
    $new = $cur - $cnt;
    if ($new <= 0) {
        $mysqli->query("DELETE FROM `items_users` WHERE `user`=".(int)$userId." AND `item_id`=".(int)$itemId." LIMIT 1");
    } else {
        $mysqli->query("UPDATE `items_users` SET `count`=".$new." WHERE `user`=".(int)$userId." AND `item_id`=".(int)$itemId." LIMIT 1");
    }
}

function q131_uqi_get($mysqli, $userId, $questId) {
    $q = $mysqli->query("SELECT `id`,`questStep`,`countPok` FROM `user_quest_info` WHERE `userID`=".(int)$userId." AND `questID`=".(int)$questId." LIMIT 1");
    return $q ? $q->fetch_assoc() : null;
}
function q131_uqi_ensure($mysqli, $userId, $questId, $step) {
    $q = $mysqli->query("SELECT `id` FROM `user_quest_info` WHERE `userID`=".(int)$userId." AND `questID`=".(int)$questId." LIMIT 1");
    $r = $q ? $q->fetch_assoc() : null;
    if (empty($r['id'])) {
        $mysqli->query("INSERT INTO `user_quest_info` (`userID`,`questID`,`questStep`,`countPok`) VALUES (".(int)$userId.",".(int)$questId.",".(int)$step.",0)");
    } else {
        $mysqli->query("UPDATE `user_quest_info` SET `questStep`=".(int)$step." WHERE `id`=".(int)$r['id']." LIMIT 1");
    }
}
function q131_uqi_set($mysqli, $userId, $questId, $step, $count) {
    q131_uqi_ensure($mysqli, $userId, $questId, $step);
    $mysqli->query("UPDATE `user_quest_info` SET `questStep`=".(int)$step.", `countPok`=".(int)$count." WHERE `userID`=".(int)$userId." AND `questID`=".(int)$questId." LIMIT 1");
}

$baitIcon = '<img src="/img/world/items/little/5044.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;">';

$rulesHtml =
    '<div style="margin-top:6px;">'
  . '<b>Правила Янтарного озера</b><br>'
  . '• Я активирую приманку '.$baitIcon.' → окно <b>30 минут</b>.<br>'
  . '• За окно победи <b>10</b> противников (#130).<br>'
  . '• Время вышло — попытка <b>сгорает</b>.'
  . '</div>';

$routeHtml =
    '<div style="margin-top:6px;">'
  . '<b>Куда дальше</b><br>'
  . '• Приманщик и я — <b>Янтарное озеро</b>.<br>'
  . '• После 10 побед: <b>Морской берег</b> → мастер снастей (награда).'
  . '</div>';

$flavorHtml =
    '<div style="margin-top:6px;">'
  . '<b>Почему активирую я</b><br>'
  . 'Приманка капризная: чуть ошибёшься — и вместо “зова” получишь хаос. Я включаю окно при тебе — честно и ровно.'
  . '</div>';

// user_quests: мастер-статус
$uq = $mysqli->query("SELECT `step`,`end` FROM `user_quests` WHERE `user_id`=".(int)$userId." AND `quest_id`=".(int)$questId." LIMIT 1")->fetch_assoc();
$step = !empty($uq['step']) ? (int)$uq['step'] : 0;
$end  = !empty($uq['end']) ? (int)$uq['end'] : 0;

// если квест уже выполнен/сдан
if ($end == 1 || $step >= 50) {
    $response['question'] = 'Вода снова спокойна. Значит, ты всё сделал как надо. Возвращайся на <b>Морской берег</b> к мастеру снастей за наградой.';
    $response['answer']   = array(3=>'Куда дальше?', 1=>'Понял.');
    return;
}

// попытка активна (step=40) — показываем прогресс, а не “запуск”
$baitLeft = q131_bait_left($mysqli, $userId);

if ($step == 40) {

    if ($baitLeft <= 0) {
        // окно закрыто — сброс
        $mysqli->query("UPDATE `user_quests` SET `step`=20 WHERE `user_id`=".(int)$userId." AND `quest_id`=".(int)$questId." LIMIT 1");
        // если записи user_quest_info нет — это нормально, но если есть — сбросим
        $mysqli->query("UPDATE `user_quest_info` SET `questStep`=20, `countPok`=0 WHERE `userID`=".(int)$userId." AND `questID`=".(int)$questId." LIMIT 1");
        // удаляем трофеи водоёма (5046), чтобы не переносились между попытками
        $mysqli->query("DELETE FROM `items_users` WHERE `user`=".(int)$userId." AND `item_id`=5046");
        $mysqli->query("DELETE FROM `items_users` WHERE `user`=".(int)$userId." AND `item`=5046");


        $response['question'] = 'Окно закрыто. Попытка сгорела. Нужна новая приманка — и попробуем снова.';
        $response['answer']   = array(4=>'Правила', 3=>'Куда дальше?', 1=>'Понял.');
        return;
    }

    $uqi = q131_uqi_get($mysqli, $userId, $questId);
    $k   = !empty($uqi['countPok']) ? (int)$uqi['countPok'] : 0;

    if ($k >= 10) {
        // страховка: если счёт уже 10, фиксируем победу даже если запись user_quest_info потом удалится системой
        $mysqli->query("UPDATE `user_quests` SET `step`=50 WHERE `user_id`=".(int)$userId." AND `quest_id`=".(int)$questId." LIMIT 1");
        q131_uqi_set($mysqli, $userId, $questId, 50, 10);

        $response['question'] = 'Десять побед. Чисто. Теперь к мастеру снастей на <b>Морской берег</b> — он выдаст награду.';
        $response['answer']   = array(3=>'Куда дальше?', 1=>'Иду.');
        return;
    }

    $response['question'] = 'Попытка идёт. Победы: <b>'.$k.'/10</b><br>Осталось времени: <b>'.q131_fmt_left($baitLeft).'</b>';
    $response['answer']   = array(4=>'Правила', 3=>'Куда дальше?', 1=>'Понял.');
    return;
}

// ниже — попытка ещё не идёт (step < 40)
switch ($npcStep) {

    default:

        if ($baitLeft > 0) {
            // приманка уже активна, но step ещё не 40 — предложим старт
            $response['question'] = 'Приманка уже активна. До закрытия окна: <b>'.q131_fmt_left($baitLeft).'</b>.<br>Начать попытку сейчас?';
            $response['answer']   = array(6=>'Начать попытку.', 4=>'Правила', 3=>'Куда дальше?', 7=>'Почему активируешь?', 1=>'Позже.');
            break;
        }

        $baitCount = q131_item_count($mysqli, $userId, 5044);
        if ($baitCount < 1) {
            $response['question'] = 'У тебя нет приманки '.$baitIcon.'. Сначала к приманщику — он её подготовит.';
            $response['answer']   = array(3=>'Куда дальше?', 4=>'Правила', 1=>'Понял.');
            break;
        }

        $response['question'] = 'Вижу приманку '.$baitIcon.'. Активирую — и окно начнётся. Готов?';
        $response['answer']   = array(2=>'Активируй приманку и начни испытание.', 4=>'Правила', 7=>'Почему активируешь?', 3=>'Куда дальше?', 1=>'Позже.');
        break;

    case 7:
        $response['question'] = $flavorHtml;
        $response['answer']   = array(1=>'Понял.');
        break;

    case 3:
        $response['question'] = $routeHtml;
        $response['answer']   = array(1=>'Ясно.');
        break;

    case 4:
        $response['question'] = $rulesHtml;
        $response['answer']   = array(1=>'Ясно.');
        break;

    // Активировать приманку и старт
    case 2:

        $baitCount = q131_item_count($mysqli, $userId, 5044);
        if ($baitCount < 1) {
            $response['question'] = 'Приманки нет. Без неё окно не открыть.';
            $response['answer']   = array(3=>'Куда дальше?', 1=>'Понял.');
            break;
        }

        q131_item_dec($mysqli, $userId, 5044, 1);
        $until = q131_set_bait_30m($mysqli, $userId);

        // старт попытки: статус в user_quests
        $mysqli->query("UPDATE `user_quests` SET `step`=40 WHERE `user_id`=".(int)$userId." AND `quest_id`=".(int)$questId." LIMIT 1");

        // счётчик — в user_quest_info (если система удалит позже — это не сломает финал)
        q131_uqi_set($mysqli, $userId, $questId, 40, 0);

        $response['question'] = 'Окно открыто. Время: <b>30:00</b>. Цель: <b>10 побед</b>. Удачи.';
        $response['answer']   = array(4=>'Правила', 3=>'Куда дальше?', 1=>'Понял.');
        break;

    // Начать попытку, если приманка уже активна
    case 6:

        $baitLeft = q131_bait_left($mysqli, $userId);
        if ($baitLeft <= 0) {
            $response['question'] = 'Окно уже закрылось. Нужна новая приманка.';
            $response['answer']   = array(3=>'Куда дальше?', 1=>'Понял.');
            break;
        }

        $mysqli->query("UPDATE `user_quests` SET `step`=40 WHERE `user_id`=".(int)$userId." AND `quest_id`=".(int)$questId." LIMIT 1");
        q131_uqi_set($mysqli, $userId, $questId, 40, 0);

        $response['question'] = 'Пошли. До закрытия окна: <b>'.q131_fmt_left($baitLeft).'</b>. Цель: <b>10 побед</b>.';
        $response['answer']   = array(4=>'Правила', 3=>'Куда дальше?', 1=>'Понял.');
        break;
}
?>
