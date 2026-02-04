<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * NPC 78 — Исследователь Джек (Куратор Академии)
 *
 * Квест 1: "Начало путешествия"
 * - если late=1 (проспал у мамы) — Джек говорит, что игрок опоздал (как в аниме)
 * - если late=0 — Джек поддерживает: "успел, но профессор уже вышел в поле"
 */

$npcId = 78;

// Получаем данные NPC
$npc = null;
if ($stmt = $mysqli->prepare("SELECT `name`, `image` FROM `base_npc` WHERE `id` = ? LIMIT 1")) {
    $stmt->bind_param('i', $npcId);
    $stmt->execute();
    $npc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $query = $mysqli->query("SELECT `name`, `image` FROM `base_npc` WHERE `id` = ".(int)$npcId);
    $npc = $query ? $query->fetch_assoc() : null;
}

$response['name']  = $npc['name']  ?? 'Исследователь Джек';
$response['image'] = isset($npc['image']) ? htmlspecialchars((string)$npc['image']) : '/img/default-npc.png';

$late = 1;
if (class_exists('QuestKit')) {
    $late = (int)QuestKit::dataGet(1, 'late', 1);
}

// Сопровождение: Академия -> Алабастия -> Прохожий
$navToPasserby = [
    'route' => [
        ['type' => 'location', 'slug' => 'alabastia'],
        ['type' => 'npc',      'slug' => 'passerby']
    ]
];

switch ($npcStep) {
    default:
        if (quest_step(1, 1)) {
            $response['actionQuest'] = 'Обновлена информация в задании <b>Начало путешествия</b>. Загляните в Дневник.';

            if ($late) {
                $response['question'] = 'Здравствуй. Боюсь тебя расстроить, но ты опоздал: профессор Оук уже вышел в поле изучать диких покемонов.';
                $response['answer'] = [6 => "Как же так… Я должен получить своего первого покемона сегодня!"];
            } else {
                $response['question'] = 'Отлично, что ты успел. Но профессор Оук уже вышел в ближайшие районы — изучать повадки покемонов. Вероятно, он на Дороге 1.';
                $response['answer'] = [6 => "Понял. Где мне его искать?"];
            }

            $response['nav'] = $navToPasserby;
        } elseif (quest_step(1, 2)) {
            $response['question'] = 'Профессор не мог уйти далеко. Спроси у прохожих — возможно, они его видели.';
            $response['nav'] = $navToPasserby;
        } elseif (quest_step(1, 7, 1)) {
            $response['question'] = 'С возвращением. Как прошло первое испытание?';
            $response['answer'] = [9 => "Я справился."];
            $response['nav'] = [
                'target' => ['type' => 'npc', 'slug' => 'jack', 'name' => 'Исследователь Джек']
            ];
        } else {
            $response['question'] = 'Если ищешь профессора — начинай с прохожих в Алабастии.';
            $response['nav'] = $navToPasserby;
        }
        break;

    case 6:
        quest_update(1, 2);
        update_zap(1, 2, 'Куратор Джек сказал, что профессор Оук ушёл на Дорогу 1. Нужно расспросить прохожих в Алабастии.');
        $response['question'] = 'Удачи. Спроси у прохожих — они часто замечают, кто и куда идёт.';
        $response['nav'] = $navToPasserby;
        break;

    case 9:
        // Оставляем существующий unlock академического квеста, не конфликтует с новой сюжеткой
        if (quest_step(1, 7, 1) && !quest_isset(901)) {
            $response['actionQuest'] = 'Теперь доступен новый квест <b>Академия команды</b>.';
            quest_update(901, 0);
        }
        $response['question'] = 'Отлично. Возвращайся, если потребуется совет.';
        $response['nav'] = [
            'target' => ['type' => 'npc', 'slug' => 'jack', 'name' => 'Исследователь Джек']
        ];
        break;
}
?>
