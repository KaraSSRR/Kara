<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

// Story libs (defensive)
@require_once __DIR__.'/lib/QuestKit.php';
@require_once __DIR__.'/lib/NpcBattle.php';

/**
 * NPC 71 — Брок
 *
 * Storyline:
 *  - 1104: Первый стадион (battle)
 *  - 1105: Развилка (choice)
 *  - 1106: Послание Дженни (talk/bring)
 *  - 1110: Второй выбор маршрута (choice)
 *  - 1111: Подготовка к пути (train/check)
 *
 * Legacy quest 101 is preserved when storyline quests are not active.
 */

// Имя по умолчанию (legacy)
$response['name'] = 'Богач Стив./Миллиардер Бронк';

$userId = (int)($_SESSION['id'] ?? 0);

function _cntUserPokesBrock($mysqli, int $userId): int {
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM `user_pokemons` WHERE `user_id`=".(int)$userId);
    $row = $res ? $res->fetch_assoc() : null;
    return (int)($row['c'] ?? 0);
}

function _maxUserLvlBrock($mysqli, int $userId): int {
    $res = $mysqli->query("SELECT MAX(`lvl`) AS m FROM `user_pokemons` WHERE `user_id`=".(int)$userId);
    $row = $res ? $res->fetch_assoc() : null;
    return (int)($row['m'] ?? 0);
}

function _battleEndedBrock($mysqli, int $battleId): bool {
    if ($battleId <= 0) return false;
    $b = $mysqli->query("SELECT `w_end` FROM `battle` WHERE `id`=".(int)$battleId)->fetch_assoc();
    return ($b && !empty($b['w_end']));
}

// --------------------
// 1104: Первый стадион
// --------------------
if (class_exists('QuestKit') && QuestKit::exists(1104) && QuestKit::end(1104) == 0) {
    $response['name'] = 'Брок';

    $arena = 1;
    $uLoc = $mysqli->query("SELECT `location` FROM `users` WHERE `id`=".(int)$userId)->fetch_assoc();
    if ($uLoc && isset($uLoc['location'])) $arena = (int)$uLoc['location'];

    switch ($npcStep) {
        default:
            if (QuestKit::isStep(1104, 1)) {
                $response['actionQuest'] = 'Обновлена информация в задании <b>Первый стадион: испытание лидера</b>. Загляните в Дневник.';
                $response['question'] = 'Я Брок, лидер стадиона. Здесь проверяют характер тренера. Готов к испытанию?';
                $response['answer'] = [
                    11041 => 'Да, начинаем бой.',
                    11042 => 'Позже.'
                ];
            } elseif (QuestKit::isStep(1104, 2)) {
                $battleId = (int)QuestKit::dataGet(1104, 'battle_id', 0);
                if (_battleEndedBrock($mysqli, $battleId)) {
                    QuestKit::set(1104, 3, 1);
                    if (function_exists('update_zap')) {
                        update_zap(1104, 3, 'Я прошёл испытание стадиона. Дальше — выбор пути.');
                    }

                    // Награды
                    itemAdd(1, 20000);
                    itemAdd(3, 3);

                    // Запуск развилки 1105
                    if (!QuestKit::exists(1105)) {
                        QuestKit::set(1105, 1, 0);
                        if (function_exists('update_zap')) {
                            update_zap(1105, 1, 'После стадиона передо мной развилка. Нужно выбрать, куда двигаться дальше.');
                        }
                    }

                    $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкары <b>x20000</b><br><img src="/img/world/items/little/3.png" class="item"> Грейтбол <b>x3</b><br>';
                    $response['actionQuest'] = 'Задание <b>Первый стадион: испытание лидера</b> выполнено. Доступно новое задание: <b>Развилка: выбор пути</b>.';
                    $response['question'] = 'Неплохо. Вперёд — и помни: путь тренера состоит из решений.';
                } else {
                    $response['question'] = 'Сначала закончи бой. Возвращайся после схватки.';
                }
            } else {
                $response['question'] = 'Стадион открыт для тех, кто готов учиться.';
            }
            break;

        case 11041:
            $enemy = [
                ['basenum' => 74, 'lvl' => 10, 'numb' => 0, 'boss' => 0, 'catch' => 0], // Geodude
                ['basenum' => 95, 'lvl' => 12, 'numb' => 0, 'boss' => 0, 'catch' => 0], // Onix
            ];

            $battleId = 0;
            if (class_exists('NpcBattle')) {
                $battleId = NpcBattle::start($enemy, [
                    'battle_type' => 'npc',
                    'weather' => 1,
                    'weather_round' => 10,
                    'img' => 0,
                    'arena' => $arena,
                    'logText' => 'Брок принимает вызов!<br>'
                ]);
            }

            QuestKit::set(1104, 2, 0, null, ['battle_id' => $battleId]);

            $response['closeDialog'] = true;
            if ($battleId > 0) {
                $response['question'] = 'Бой начинается!';
                $response['battle_id'] = $battleId;
            } else {
                $response['question'] = 'Начинаем испытание.';
            }
            break;

        case 11042:
            $response['question'] = 'Хорошо. Возвращайся, когда будешь готов.';
            break;
    }

    return;
}

// --------------------
// 1105: Развилка
// --------------------
if (class_exists('QuestKit') && QuestKit::exists(1105) && QuestKit::end(1105) == 0) {
    $response['name'] = 'Брок';

    switch ($npcStep) {
        default:
            if (QuestKit::isStep(1105, 1)) {
                $response['actionQuest'] = 'Обновлена информация в задании <b>Развилка: выбор пути</b>. Загляните в Дневник.';
                $response['question'] = 'Два маршрута: сложный (больше риска и опыта) или безопасный (меньше опасности). Что выбираешь?';
                $response['answer'] = [
                    11051 => 'Сложный путь (лес).',
                    11052 => 'Безопасный путь (через город).'
                ];
            } else {
                $response['question'] = 'Выбор пути — часть взросления тренера.';
            }
            break;

        case 11051:
            QuestKit::set(1105, 2, 1, null, ['path' => 'hard_forest']);
            if (function_exists('update_zap')) {
                update_zap(1105, 2, 'Я выбрал сложный путь через лес. Это рискованно, но даст больше опыта.');
            }
            itemAdd(2, 5);
            itemAdd(3, 2);

            // Продолжение сюжетки
            if (!QuestKit::exists(1106)) {
                QuestKit::set(1106, 1, 0);
                if (function_exists('update_zap')) {
                    update_zap(1106, 1, 'Брок хочет, чтобы я передал сообщение офицеру Дженни.');
                }
                $response['actionQuest'] = 'Задание <b>Развилка: выбор пути</b> выполнено. Доступно новое задание: <b>Послание Дженни</b>.';
            } else {
                $response['actionQuest'] = 'Задание <b>Развилка: выбор пути</b> выполнено.';
            }

            $response['actionQuestPlus'] = '<img src="/img/world/items/little/2.png" class="item"> Покебол <b>x5</b><br><img src="/img/world/items/little/3.png" class="item"> Грейтбол <b>x2</b><br>';
            $response['question'] = 'Смелый выбор. В лесу тебе пригодятся покеболы и хладнокровие. И ещё: загляни к Дженни — у меня есть для неё важное сообщение.';
            break;

        case 11052:
            QuestKit::set(1105, 2, 1, null, ['path' => 'safe_city']);
            if (function_exists('update_zap')) {
                update_zap(1105, 2, 'Я выбрал безопасный путь через город. Это надёжно и даст время подготовиться.');
            }
            itemAdd(2, 10);

            // Продолжение сюжетки
            if (!QuestKit::exists(1106)) {
                QuestKit::set(1106, 1, 0);
                if (function_exists('update_zap')) {
                    update_zap(1106, 1, 'Брок хочет, чтобы я передал сообщение офицеру Дженни.');
                }
                $response['actionQuest'] = 'Задание <b>Развилка: выбор пути</b> выполнено. Доступно новое задание: <b>Послание Дженни</b>.';
            } else {
                $response['actionQuest'] = 'Задание <b>Развилка: выбор пути</b> выполнено.';
            }

            $response['actionQuestPlus'] = '<img src="/img/world/items/little/2.png" class="item"> Покебол <b>x10</b><br>';
            $response['question'] = 'Разумно. Подготовка — тоже сила тренера. И ещё: загляни к Дженни — у меня есть для неё важное сообщение.';
            break;
    }

    return;
}

// --------------------
// 1106: Послание Дженни
// --------------------
if (class_exists('QuestKit') && QuestKit::exists(1106) && QuestKit::end(1106) == 0) {
    $response['name'] = 'Брок';

    switch ($npcStep) {
        default:
            if (QuestKit::isStep(1106, 1)) {
                $response['actionQuest'] = 'Обновлена информация в задании <b>Послание Дженни</b>.';
                $response['question'] = 'После боя я заметил странных людей. Похоже, Team Rocket снова рядом. Передай Дженни мою записку — это важно.';
                $response['answer'] = [
                    11061 => 'Хорошо, передам.',
                    11062 => 'Сейчас не могу.'
                ];
            } else {
                $response['question'] = 'Сначала передай записку Дженни. Она в Покецентре и на посту в городе.';
            }
            break;

        case 11061:
            QuestKit::set(1106, 2, 0, null, ['note' => 1]);
            if (function_exists('update_zap')) {
                update_zap(1106, 2, 'Нужно найти офицера Дженни и передать ей записку Брока.');
            }
            $response['question'] = 'Отлично. Не задерживайся: если это Team Rocket — они действуют быстро.';
            break;

        case 11062:
            $response['question'] = 'Понимаю. Но помни: иногда промедление стоит слишком дорого.';
            break;
    }

    return;
}

// --------------------
// 1110: Второй выбор маршрута
// --------------------
if (class_exists('QuestKit') && QuestKit::exists(1110) && QuestKit::end(1110) == 0) {
    $response['name'] = 'Брок';

    switch ($npcStep) {
        default:
            if (QuestKit::isStep(1110, 1)) {
                $response['actionQuest'] = 'Обновлена информация в задании <b>Следующий путь</b>.';
                $response['question'] = 'Team Rocket отступили, но дальше будет сложнее. Есть два варианта: коротко через Лунную гору (опасно) или длиннее в обход (безопаснее). Что выбираешь?';
                $response['answer'] = [
                    11101 => 'Через Лунную гору (сложно).',
                    11102 => 'Обходным путём (проще).'
                ];
            } else {
                $response['question'] = 'Выбор пути определяет стиль тренера.';
            }
            break;

        case 11101:
            QuestKit::set(1110, 2, 1, null, ['route' => 'mt_moon']);
            if (function_exists('update_zap')) {
                update_zap(1110, 2, 'Я выбрал путь через Лунную гору. Потребуется подготовка и запас припасов.');
            }
            // Награда: даём немного лечения вместо лишних шаров
            itemAdd(5, 3);
            $response['actionQuestPlus'] = '<img src="/img/world/items/little/5.png" class="item"> Зелье <b>x3</b><br>';
            $response['actionQuest'] = 'Задание <b>Следующий путь</b> выполнено.';

            if (!QuestKit::exists(1111)) {
                QuestKit::set(1111, 1, 0, null, ['route' => 'mt_moon']);
                if (function_exists('update_zap')) {
                    update_zap(1111, 1, 'Нужно подготовить команду к сложному пути: собрать минимум 3 покемона и прокачать хотя бы одного до 10 уровня.');
                }
                $response['actionQuest'] .= ' Доступно новое задание: <b>Подготовка к пути</b>.';
            }

            $response['question'] = 'Хорошо. В горе темно и тесно — там легко попасть в неприятности. Подготовь команду и возвращайся, если понадобится совет.';
            break;

        case 11102:
            QuestKit::set(1110, 2, 1, null, ['route' => 'detour']);
            if (function_exists('update_zap')) {
                update_zap(1110, 2, 'Я выбрал обходной путь. Он спокойнее, но потребует больше времени.');
            }
            itemAdd(2, 5);
            $response['actionQuestPlus'] = '<img src="/img/world/items/little/2.png" class="item"> Покебол <b>x5</b><br>';
            $response['actionQuest'] = 'Задание <b>Следующий путь</b> выполнено.';

            if (!QuestKit::exists(1111)) {
                QuestKit::set(1111, 1, 0, null, ['route' => 'detour']);
                if (function_exists('update_zap')) {
                    update_zap(1111, 1, 'Нужно подготовить команду к дороге: собрать минимум 2 покемона и прокачать хотя бы одного до 9 уровня.');
                }
                $response['actionQuest'] .= ' Доступно новое задание: <b>Подготовка к пути</b>.';
            }

            $response['question'] = 'Отлично. Время иногда важнее риска. Но даже на спокойной дороге тебя ждут тренеры — держи команду в форме.';
            break;
    }

    return;
}

// --------------------
// 1111: Подготовка к пути
// --------------------
if (class_exists('QuestKit') && QuestKit::exists(1111) && QuestKit::end(1111) == 0) {
    $response['name'] = 'Брок';

    $route = (string)QuestKit::dataGet(1111, 'route', (string)QuestKit::dataGet(1110, 'route', 'detour'));
    $needPokes = ($route === 'mt_moon') ? 3 : 2;
    $needLvl   = ($route === 'mt_moon') ? 10 : 9;

    $cnt = _cntUserPokesBrock($mysqli, $userId);
    $mx  = _maxUserLvlBrock($mysqli, $userId);

    if ($cnt >= $needPokes && $mx >= $needLvl) {
        QuestKit::set(1111, 2, 1);
        if (function_exists('update_zap')) {
            update_zap(1111, 2, 'Я подготовил команду и готов двигаться дальше по сюжету Канто.');
        }
        itemAdd(1, 30000);
        itemAdd(3, 2);

        $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкары <b>x30000</b><br><img src="/img/world/items/little/3.png" class="item"> Грейтбол <b>x2</b><br>';
        $response['actionQuest'] = 'Задание <b>Подготовка к пути</b> выполнено.';
        $response['question'] = 'Отлично. Ты готов к следующему отрезку пути. Дальше — новые города, новые тренеры и новые тайны Team Rocket.';
        return;
    }

    $response['actionQuest'] = 'Задание <b>Подготовка к пути</b> активно. Выполни условия и вернись.';
    $response['question'] = 'Перед дорогой проверь команду. Текущий прогресс: покемонов <b>'.$cnt.'</b> / <b>'.$needPokes.'</b>, максимальный уровень <b>'.$mx.'</b> / <b>'.$needLvl.'</b>.';
    return;
}

// --------------------
// Legacy quest 101 (как было)
// --------------------
switch($npcStep) {
    case 1:
        $response['question'] = 'Ты тренер? Славно, рад тебе. Могу ли дать тебе работу? Но знай, загадки так себе. Найдешь на голову заботу.';
        $response['answer'] = array(
            2 => 'Да',
            3 => 'Нет'
        );
    break;

    case 2:
        if (!quest_isset(101)) {
            $response['question'] = 'Загадка 1: Готов принять ты мой заказ? Его рога всегда растут. Не так уж сложно в этот раз? Так возраст понимают тут.';
            quest_update(101, 1);
            $response['actionQuest'] = 'Загадка обновлена в квесте. Проверь свой Дневник!';
        }
    break;

    case 3:
        if (quest_step(101, 1)) {
            $response['question'] = 'Ты принес мне правильного покемона?';
            $response['answer'] = array(
                5 => 'Да, вот он',
                6 => 'Нет'
            );
        } else {
            $response['question'] = 'Привет, ты что-то искал?';
        }
    break;

    case 5:
        // Проверяем наличие покемона #306 Агрон у игрока
        if(quest_step(101, 1)){
            if(search_pok_active_stat(306, 1, 1, 1)){
                // Если покемон есть
                quest_update(101, 2, 1);
                lvlupuser(1000);
                itemAdd(1, 100000); // 100000 монет
                $response['actionQuest'] = 'Ты угадал загадку, я забрал покемона, и ты получаешь награду: 100000 монет.';
                update_zap(101, 2, 'Ты правильно разгадал загадку и отдал покемона. Эдвард поблагодарил тебя и дал награду.');
            } else {
                // Если покемона нет
                $response['question'] = 'Ты не принес правильного покемона! Ищи ответы в дексе.';
                $response['actionQuest'] = 'Загадку ты не разгадал, но не сдавайся. Жду твоего ответа снова.';
                itemAdd(1, 10000); // 10000 монет за неправильный ответ
            }
        }
    break;

    default:
        if (!quest_isset(101)) {
            $response['question'] = '<i>( Ты встретил Эдварда, который объясняет, что ему нужно найти покемонов с определенными характеристиками. )</i>';
            $response['answer'] = array(
                1 => 'Привет, чем могу помочь?'
            );
        } else if (quest_step(101, 1)) {
            $response['question'] = 'Ты уже принес покемонов?';
            $response['answer'] = array(
                5 => 'Да, вот они'
            );
        } else {
            $response['question'] = 'Какая прекрасная погода, но квест так и не завершился...';
        }
    break;
}
?>
