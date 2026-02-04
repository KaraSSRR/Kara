<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * NPC 88 — Мастер снастей (Морской берег)
 * Quest 131: Удочка мастера
 *
 * v2: награда только при наличии доказательства — "Трофей водоёма" (item 5046) x10
 *  - если трофеев < 10: отправляет обратно на Янтарное озеро
 *  - при сдаче: удаляет 5046 x10 и выдаёт Старую удочку (item 5)
 *  - без actionQuest (не триггерит notifications)
 */

$questId = 131;
$npcId   = 88;

if (!isset($npcStep)) $npcStep = 1;

$npcQ = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id`=".(int)$npcId." LIMIT 1");
$npc  = $npcQ ? $npcQ->fetch_assoc() : null;

$response = array();
$response['name']  = !empty($npc['name']) ? $npc['name'] : 'NPC';
$response['image'] = !empty($npc['image']) ? htmlspecialchars($npc['image']) : '/img/default-npc.png';

$userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

function q131_item_count_any($mysqli, $userId, $itemId) {
    $q = $mysqli->query("SELECT `count` FROM `items_users` WHERE `user`=".(int)$userId." AND `item_id`=".(int)$itemId." LIMIT 1");
    if ($q) {
        $r = $q->fetch_assoc();
        if (!empty($r['count'])) return (int)$r['count'];
    }
    $q = $mysqli->query("SELECT `count` FROM `items_users` WHERE `user`=".(int)$userId." AND `item`=".(int)$itemId." LIMIT 1");
    if ($q) {
        $r = $q->fetch_assoc();
        if (!empty($r['count'])) return (int)$r['count'];
    }
    return 0;
}
function q131_item_dec_any($mysqli, $userId, $itemId, $cnt) {
    $cnt = (int)$cnt;
    if ($cnt <= 0) return;

    $q = $mysqli->query("SELECT `count` FROM `items_users` WHERE `user`=".(int)$userId." AND `item_id`=".(int)$itemId." LIMIT 1");
    if ($q) {
        $r = $q->fetch_assoc();
        $cur = !empty($r['count']) ? (int)$r['count'] : 0;
        if ($cur > 0) {
            $new = $cur - $cnt;
            if ($new <= 0) $mysqli->query("DELETE FROM `items_users` WHERE `user`=".(int)$userId." AND `item_id`=".(int)$itemId." LIMIT 1");
            else $mysqli->query("UPDATE `items_users` SET `count`=".$new." WHERE `user`=".(int)$userId." AND `item_id`=".(int)$itemId." LIMIT 1");
            return;
        }
    }

    $q = $mysqli->query("SELECT `count` FROM `items_users` WHERE `user`=".(int)$userId." AND `item`=".(int)$itemId." LIMIT 1");
    if ($q) {
        $r = $q->fetch_assoc();
        $cur = !empty($r['count']) ? (int)$r['count'] : 0;
        if ($cur > 0) {
            $new = $cur - $cnt;
            if ($new <= 0) $mysqli->query("DELETE FROM `items_users` WHERE `user`=".(int)$userId." AND `item`=".(int)$itemId." LIMIT 1");
            else $mysqli->query("UPDATE `items_users` SET `count`=".$new." WHERE `user`=".(int)$userId." AND `item`=".(int)$itemId." LIMIT 1");
        }
    }
}

$uqQ = $mysqli->query("SELECT `step`,`end` FROM `user_quests` WHERE `user_id`=".(int)$userId." AND `quest_id`=".(int)$questId." LIMIT 1");
$uq  = $uqQ ? $uqQ->fetch_assoc() : null;

$step = !empty($uq['step']) ? (int)$uq['step'] : 0;
$end  = !empty($uq['end']) ? (int)$uq['end'] : 0;

$trophyIcon = '<img src="/img/world/items/little/5046.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;">';
$rodIcon    = '<img src="/img/world/items/little/5.png" class="item" style="width:34px;height:34px;object-fit:cover;vertical-align:middle;border-radius:8px;">';

switch ($npcStep) {

    default:

        if ($end == 1) {
            $response['question'] = 'Старая удочка у тебя. Береги её — и не суши леску на солнце.';
            $response['answer']   = array(1 => 'Понял.');
            break;
        }

        if ($step < 50) {
            $response['question'] = 'Без Янтарного озера я даже не разговариваю о наградах. Сначала докажи, что умеешь держать ритм.';
            $response['answer']   = array(1 => 'Понял.');
            break;
        }

        $t = q131_item_count_any($mysqli, $userId, 5046);
        if ($t < 10) {
            $response['question'] = 'Говоришь, справился? Тогда покажи доказательство: '.$trophyIcon.' <b>Трофей водоёма x10</b>.<br>'
                                  . '<span style="opacity:.85;">Без трофеев я не отдаю снасть. Возвращайся на Янтарное озеро.</span>';
            $response['answer'] = array(1 => 'Понял.');
            break;
        }

        $response['question'] = 'Вот это разговор. '.$trophyIcon.' <b>x10</b> — значит, ты не болтал, а работал.<br>'
                              . 'Сдаёшь трофеи — получаешь '.$rodIcon.' <b>Старую удочку</b>.';
        $response['answer']   = array(2 => 'Сдать трофеи и получить удочку.', 1 => 'Позже.');
        break;

    case 2:

        if ($step < 50 || $end == 1) {
            $response['question'] = 'Рано. Сначала пройди испытание.';
            $response['answer']   = array(1 => 'Понял.');
            break;
        }

        $t = q131_item_count_any($mysqli, $userId, 5046);
        if ($t < 10) {
            $response['question'] = 'Не хватает трофеев: нужно '.$trophyIcon.' <b>x10</b>.';
            $response['answer']   = array(1 => 'Понял.');
            break;
        }

        // забираем трофеи
        q131_item_dec_any($mysqli, $userId, 5046, 10);

        // выдаём награду (Старая удочка, item 5)
        itemAdd(5, 1, $userId);

        // закрываем квест
        $mysqli->query("UPDATE `user_quests` SET `end`=1 WHERE `user_id`=".(int)$userId." AND `quest_id`=".(int)$questId." LIMIT 1");

        $response['question'] = 'Держи. '.$rodIcon.' <b>Старая удочка</b> — не новая, но честная.<br>'
                              . '<span style="opacity:.85;">Если вода позовёт снова — приходи, но без сказок. Только с делом.</span>';
        $response['answer']   = array(1 => 'Спасибо.');
        break;
}
?>