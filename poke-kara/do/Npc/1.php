<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

/**
 * NPC 1 — Мама
 *
 * Цель: сделать старт квеста (quest_id=1) сюжетным в стиле аниме:
 * - игрок выбирает: "сразу бегу" или "ещё пять минут"
 * - выбор сохраняется в user_quests.data (late=0/1)
 * - если late=1, в сумке профессора останется только Пикачу
 *
 * Важно: не ломаем существующие шаги квеста 1 (step 1..7), только добавляем ветвление через data.
 */

$npcId = 1;

// Получаем NPC
$npc = $mysqli->query("SELECT `name`,`image` FROM `base_npc` WHERE `id` = ".(int)$npcId)->fetch_assoc();
$response['name']  = $npc['name']  ?? 'Мама';
$response['image'] = isset($npc['image']) ? htmlspecialchars((string)$npc['image']) : '/img/default-npc.png';

$questId = 1;

// Состояние квеста (JSON data)
$late = 1; // по умолчанию как в аниме — "опоздал"
$giftGiven = 0;
if (class_exists('QuestKit')) {
    $late = (int)QuestKit::dataGet($questId, 'late', 1);
    $giftGiven = (int)QuestKit::dataGet($questId, 'gift_given', 0);
}

// Навигационный маршрут: Дом -> Алабастия -> Академия -> Джек
$defaultNav = [
    'route' => [
        ['type' => 'location', 'slug' => 'alabastia'],
        ['type' => 'location', 'slug' => 'academy'],
        ['type' => 'npc',      'slug' => 'jack']
    ]
];

switch ($npcStep) {
    default:
        // Если квест ещё не стартовал — запускаем и даём выбор (ветка late/on-time)
        if (!quest_isset($questId)) {
            $response['actionQuest'] = 'Обновлена информация в задании <b>Начало путешествия</b>. Загляните в Дневник.';
            $response['question'] = 'Доброе утро, ' . $_SESSION['login'] . '! Сегодня ты становишься тренером. Профессор Оук ждёт тебя — не опоздай. 
Мы с папой приготовили подарок: надень его на первого покемона — он поможет быстрее развиваться.';

            // Два ответа — сюжетная развилка:
            // 1) успел -> late=0 -> в сумке 3 стартера
            // 2) проспал -> late=1 -> в сумке останется Пикачу
            $response['answer'] = [
                1 => "Я уже бегу! Спасибо!",
                2 => "Ещё пять минут… (поспать)"
            ];

            // Старт квеста (step=1) без выбора late — он фиксируется в case 1/2
            quest_update($questId, 1);
            update_zap($questId, 1, 'Мама разбудила меня и сказала бежать к профессору Оуку. Нужно попасть в Академию и узнать, где профессор.');
            $response['nav'] = $defaultNav;
        } else {
            // Квест уже активен — короткое напоминание и навигация
            $response['question'] = 'Не забудь про подарок и беги — твоё приключение начинается сегодня!';
            $response['answer'] = [
                10 => "Я помню, спасибо!"
            ];
            $response['nav'] = $defaultNav;
        }
        break;

    // Ветка "успел"
    case 1:
        if (!$giftGiven) {
            itemAdd(563, 1);
            $response['actionQuestPlus'] = '<img src="/img/world/items/little/563.png" class="item"> Скобовое кольцо <b>x1</b><br>';
        }

        if (class_exists('QuestKit')) {
            QuestKit::set($questId, 1, 0, null, ['late' => 0, 'gift_given' => 1]);
        }

        $response['question'] = 'Вот это настрой. Удачи! И будь внимателен на дорогах.';
        $response['closeDialog'] = true;
        $response['nav'] = $defaultNav;
        break;

    // Ветка "проспал"
    case 2:
        if (!$giftGiven) {
            itemAdd(563, 1);
            $response['actionQuestPlus'] = '<img src="/img/world/items/little/563.png" class="item"> Скобовое кольцо <b>x1</b><br>';
        }

        if (class_exists('QuestKit')) {
            QuestKit::set($questId, 1, 0, null, ['late' => 1, 'gift_given' => 1]);
        }

        $response['question'] = 'Эх… Ладно, только не затягивай. Профессор не будет ждать вечно.';
        $response['closeDialog'] = true;
        $response['nav'] = $defaultNav;
        break;

    case 10:
        $response['question'] = 'Тогда вперёд — весь Канто ждёт твоих побед.';
        $response['closeDialog'] = true;
        $response['nav'] = $defaultNav;
        break;
}
?>
