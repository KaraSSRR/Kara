<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

@require_once __DIR__.'/lib/QuestKit.php';
@require_once __DIR__.'/lib/NpcBattle.php';

/**
 * NPC 80 — Профессор Оук
 *
 * Переработка стартового квеста (quest_id=1) под сюжет аниме:
 * - инцидент на Дороге 1
 * - сумка -> выбор стартера (Пикачу если late=1) -> бой
 * - развязка у профессора + запуск продолжения (1101..)
 *
 * Продолжение (Канто):
 * - 1101 "первый улов" (поймай любого дикого покемона)
 * - 1108 "Соперник: первая встреча" (скриптовый NPC-бой)
 */

$npcId = 80;

$npc = $mysqli->query("SELECT `name`, `image` FROM `base_npc` WHERE `id` = ".(int)$npcId)->fetch_assoc();
$response['name']  = $npc['name'] ?? 'Профессор Оук';
$response['image'] = isset($npc['image']) ? htmlspecialchars((string)$npc['image']) : '/img/default-npc.png';

$userId = (int)($_SESSION['id'] ?? 0);

$late = 1;
if (class_exists('QuestKit')) {
    $late = (int)QuestKit::dataGet(1, 'late', 1);
}

function _cntUserPokes($mysqli, $userId) {
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM `user_pokemons` WHERE `user_id`=".(int)$userId);
    $row = $res ? $res->fetch_assoc() : null;
    return (int)($row['c'] ?? 0);
}

function _battleEnded($mysqli, int $battleId): bool {
    if ($battleId <= 0) return false;
    $b = $mysqli->query("SELECT `w_end` FROM `battle` WHERE `id`=".(int)$battleId)->fetch_assoc();
    return ($b && !empty($b['w_end']));
}

function _battleEnv($mysqli, int $userId): array {
    $env = ['arena'=>1,'weather'=>1,'weather_round'=>10,'img'=>0];
    $u = $mysqli->query("SELECT `location`,`status`,`status_id` FROM `users` WHERE `id`=".(int)$userId)->fetch_assoc();
    if ($u) {
        if (!empty($u['location'])) $env['arena'] = (int)$u['location'];
        if (!empty($u['status']) && $u['status']==='battle' && (int)$u['status_id']>0) {
            $env['existing_battle_id'] = (int)$u['status_id'];
            return $env;
        }
    }
    $loc = $mysqli->query("SELECT `region`,`img_fight`,`weather` FROM `base_location` WHERE `id`=".(int)$env['arena'])->fetch_assoc();
    if ($loc) {
        if (!empty($loc['img_fight'])) $env['img'] = (int)$loc['img_fight'];
        if (!empty($loc['weather'])) {
            $env['weather'] = (int)$loc['weather'];
        } elseif (!empty($loc['region'])) {
            $reg = $mysqli->query("SELECT `weather` FROM `base_region` WHERE `id`=".(int)$loc['region'])->fetch_assoc();
            if ($reg && !empty($reg['weather'])) $env['weather'] = (int)$reg['weather'];
        }
    }
    return $env;
}

switch ($npcStep) {
    default:
        // -------------------------
        // 1108: Соперник — первая встреча
        // -------------------------
        if (class_exists('QuestKit') && QuestKit::exists(1108) && QuestKit::end(1108) == 0) {
            $response['name'] = 'Профессор Оук';

            if (QuestKit::isStep(1108, 1)) {
                $response['actionQuest'] = 'Обновлена информация в задании <b>Соперник: первая встреча</b>. Загляните в Дневник.';
                $response['question'] = 'Хм… Похоже, ты привлёк внимание одного самоуверенного тренера. Он ждёт тебя у выхода и требует бой. Готов?';
                $response['answer'] = [
                    11081 => 'Да, приму вызов.',
                    11082 => 'Позже.'
                ];
                break;
            }

            if (QuestKit::isStep(1108, 2)) {
                $battleId = (int)QuestKit::dataGet(1108, 'battle_id', 0);
                if ($battleId > 0 && _battleEnded($mysqli, $battleId)) {
                    QuestKit::set(1108, 3, 1);
                    if (function_exists('update_zap')) {
                        update_zap(1108, 3, 'Я встретил соперника и победил в первом серьёзном бою. Похоже, рядом снова крутятся Ракеты…');
                    }

                    itemAdd(1, 12000);
                    itemAdd(3, 2);

                    if (!QuestKit::exists(1109)) {
                        QuestKit::set(1109, 1, 0);
                        if (function_exists('update_zap')) {
                            update_zap(1109, 1, 'Сообщи Дженни о подозрительных людях в форме. Будь готов к бою.');
                        }
                    }

                    $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкары <b>x12000</b><br><img src="/img/world/items/little/3.png" class="item"> Грейтбол <b>x2</b><br>';
                    $response['actionQuest'] = 'Задание <b>Соперник: первая встреча</b> выполнено. Доступно новое задание: <b>Team Rocket: засада</b>.';
                    $response['question'] = 'Неплохо. Но запомни: победы не всегда приходят легко. Если увидишь подозрительных людей — сообщи Дженни.';
                    break;
                }

                $response['question'] = 'Сначала закончи бой с соперником. Возвращайся после схватки.';
                break;
            }

            $response['question'] = 'Похоже, сегодня ты уже достаточно повидал приключений.';
            break;
        }

        // -------------------------
        // 1101: первый улов
        // -------------------------
        if (class_exists('QuestKit') && QuestKit::exists(1101) && QuestKit::end(1101) == 0) {
            $baseline = (int)QuestKit::dataGet(1101, 'baseline_pokes', 0);
            if ($baseline <= 0) {
                $baseline = _cntUserPokes($mysqli, $userId);
                QuestKit::set(1101, 1, 0, null, ['baseline_pokes' => $baseline]);
            }
            $current = _cntUserPokes($mysqli, $userId);

            if ($current > $baseline) {
                // завершили 1101, запускаем 1102
                QuestKit::set(1101, 2, 1);
                update_zap(1101, 2, 'Я поймал своего первого дикого покемона. Пора привести команду в порядок и зайти в Покецентр.');

                if (!QuestKit::exists(1102)) {
                    QuestKit::set(1102, 1, 0);
                    update_zap(1102, 1, 'Зайди в Покецентр к сестре Джой и вылечи команду — это базовый навык любого тренера.');
                }

                $response['actionQuest'] = 'Задание <b>Дорога 1: первый улов</b> выполнено. Доступно новое задание: <b>Покецентр: первая помощь</b>.';
                $response['question'] = 'Отлично. Первый улов — важный шаг. Теперь зайди в Покецентр: здоровая команда важнее любой спешки.';
                break;
            }

            $response['question'] = 'Теперь главное — практика. Поймай любого дикого покемона. Не бойся: покеболы у тебя есть, а опыт приходит в бою.';
            break;
        }

        // -------------------------
        // Стартовый квест 1
        // -------------------------
        if (quest_step(1, 3)) {
            $response['question'] = 'Эй! Помоги! Дикие покемоны напали на меня на Дороге 1. Я выронил сумку и не могу выпустить своих покемонов!';
            $response['answer'] = [4 => 'Спокойно! Что мне делать?'];
        } elseif (quest_step(1, 4)) {
            $response['question'] = 'Сумка где-то рядом. Возьми покебол, выбери себе покемона и защищайся! Только быстро!';
            quest_update(1, 4);
            update_zap(1, 4, 'Профессор Оук в беде. Нужно найти его сумку, выбрать покемона и защитить профессора в бою.');
        } elseif (quest_step(1, 5) || quest_step(1, 6)) {
            quest_update(1, 6);

            $response['question'] = 'Ты справился… Фух. Я обязан тебе. Как ты здесь оказался?';
            if ($late) {
                $response['answer'] = [7 => 'Я опоздал в Академию и искал вас, чтобы получить своего первого покемона.'];
            } else {
                $response['answer'] = [7 => 'Я пришёл к вам за своим первым покемоном, но вы уже ушли, и я пошёл искать вас.'];
            }
        } else {
            $response['question'] = 'Погодные условия любопытны… О, здравствуй, тренер.';
        }
        break;

    case 4:
        quest_update(1, 4);
        $response['question'] = 'Скорее возьми в сумке покебол. Выбери покемона и вступай в бой!';
        $response['closeDialog'] = true;
        break;

    // 1108: старт боя с соперником
    case 11081:
        if (!(class_exists('QuestKit') && QuestKit::exists(1108) && QuestKit::end(1108) == 0 && QuestKit::step(1108) == 1)) {
            $response['question'] = 'Сейчас не время для этого.';
            break;
        }

        // Идемпотентность: если уже в бою — вернём текущий battle_id
        $env = _battleEnv($mysqli, $userId);
        if (!empty($env['existing_battle_id'])) {
            $response['closeDialog'] = true;
            $response['battle_id'] = (int)$env['existing_battle_id'];
            $response['question'] = 'Бой уже идёт.';
            break;
        }

        $starter = (int)QuestKit::dataGet(1, 'starter_id', 0);
        $rivalStarter = 133; // Eevee fallback
        if ($starter === 1) $rivalStarter = 4;      // Bulba -> Charmander
        elseif ($starter === 4) $rivalStarter = 7;  // Char -> Squirtle
        elseif ($starter === 7) $rivalStarter = 1;  // Squirt -> Bulba
        elseif ($starter === 25) $rivalStarter = 133;

        $enemy = [
            ['basenum' => 16, 'lvl' => 7, 'numb' => 0, 'boss' => 0, 'catch' => 0],
            ['basenum' => $rivalStarter, 'lvl' => 9, 'numb' => 0, 'boss' => 0, 'catch' => 0],
        ];

        $battleId = 0;
        if (class_exists('NpcBattle')) {
            $battleId = NpcBattle::start($enemy, [
                'battle_type' => 'npc',
                'weather' => (int)$env['weather'],
                'weather_round' => (int)$env['weather_round'],
                'img' => (int)$env['img'],
                'arena' => (int)$env['arena'],
                'logText' => 'Соперник бросает вызов!<br>'
            ]);
        }

        QuestKit::set(1108, 2, 0, null, ['battle_id' => $battleId]);

        $response['closeDialog'] = true;
        if ($battleId > 0) {
            $response['question'] = 'Соперник не ждёт. Бой начинается!';
            $response['battle_id'] = $battleId;
        } else {
            $response['question'] = 'Похоже, бой не удалось запустить. Попробуй ещё раз.';
        }
        break;

    case 11082:
        $response['question'] = 'Хорошо. Только не затягивай: соперник сам найдёт повод для драки.';
        break;

    case 7:
        // Завершение квеста 1
        quest_update(1, 7, 1);

        $response['question'] = 'Поздравляю. Ты сделал первый шаг как тренер. Держи припасы — и помни: сила не только в победах, но и в заботе о покемонах.';
        $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Генкары <b>x50000</b><br>
        <img src="/img/world/items/little/2.png" class="item"> Покебол <b>x20</b><br>
        <img src="/img/world/items/little/3.png" class="item"> Грейтбол <b>x5</b><br>
        <img src="/img/world/items/little/4.png" class="item"> Ультрабол <b>x3</b><br>';

        itemAdd(1, 50000);
        itemAdd(2, 20);
        itemAdd(3, 5);
        itemAdd(4, 3);

        if (class_exists('QuestKit') && !QuestKit::exists(1101)) {
            $baseline = _cntUserPokes($mysqli, $userId);
            QuestKit::set(1101, 1, 0, null, ['baseline_pokes' => $baseline]);
            update_zap(1101, 1, 'Пора сделать первый улов: поймай любого дикого покемона на маршрутах Канто.');
            $response['actionQuest'] = 'Доступно новое задание: <b>Дорога 1: первый улов</b>.';
        }

        $response['closeDialog'] = true;
        break;
}
?>
