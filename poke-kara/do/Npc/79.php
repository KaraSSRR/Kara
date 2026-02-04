<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * NPC 79 — Прохожий
 * Сюжетный мостик к профессору на Дороге 1.
 */

$npcId = 79;

$npc = $mysqli->query("SELECT `name`, `image` FROM `base_npc` WHERE `id` = ".(int)$npcId)->fetch_assoc();
$response['name']  = $npc['name'] ?? 'Прохожий';
$response['image'] = isset($npc['image']) ? htmlspecialchars((string)$npc['image']) : '/img/default-npc.png';

switch ($npcStep) {
    default:
        if (quest_step(1, 2)) {
            $response['question'] = 'Доброе утро! Ты выглядишь взволнованным.';
            $response['answer'] = [2 => "Я ищу профессора Оука. Вы его не видели?"];
        } elseif (quest_step(1, 3)) {
            $response['question'] = 'Кажется, профессор ушёл в сторону Дороги 1. Поторопись — там бывает неспокойно.';
        } elseif (quest_step(1, 7, 1)) {
            $response['question'] = 'Доброе утро! Вижу, ты уже с покемоном. Удачи в пути!';
        }
        break;

    case 2:
        $response['question'] = 'Профессора Оука? Видел. Он направился в сторону Дороги 1 — говорил, что хочет понаблюдать за дикими покемонами.';
        $response['answer'] = [3 => "Спасибо! Я побегу туда."];
        break;

    case 3:
        quest_update(1, 3);
        update_zap(1, 3, 'Прохожий видел профессора Оука — он ушёл на Дорогу 1. Нужно найти его и узнать, что случилось.');
        $response['question'] = 'Не теряй времени. Дорога 1 начинается сразу за городом.';
        $response['closeDialog'] = true;
        break;
}
?>
