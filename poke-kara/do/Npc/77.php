<?php
$response['name'] = 'Исследователь Холт';

switch ($npcStep) {
    case 1: // Начало квеста
        $response['question'] = 'Путник, ты любишь загадки? Я изучаю древний Ледяной Лабиринт, но мне не хватает сил и знаний, чтобы разгадать его тайну. Говорят, что вход в лабиринт скрыт и запечатан древней магией. Ты поможешь мне?';
        $response['answer'] = array(
            2 => "Расскажи подробнее, что я должен сделать."
        );
        break;

    case 2: // Инструкция для игрока
        if (!quest_isset($iceLabyrinthQuestId)) {
            $response['question'] = 'Для открытия входа в Ледяной Лабиринт тебе нужно собрать 50 ледяных осколков (ID: 401) и 10 снежных кристаллов (ID: 402). Эти материалы охраняются дикими покемонами <b>#361 Снорант</b> и <b>#221 Пилосвайн</b>, которых можно найти в Ледяных Холмах. Как только соберешь всё необходимое, возвращайся ко мне!';
            $response['answer'] = array(
                3 => "Я понял, начну собирать материалы!"
            );
            quest_update($iceLabyrinthQuestId, 1); // Обновляем квест до этапа 1
            $response['actionQuest'] = "notify";
            $response['actionText'] = '<img src="/img/quests/4.png" class="quest"> Обновлена информация в задании «Тайна Ледяного Лабиринта». Проверьте Дневник.';
        }
        break;

    case 3: // Этап сбора материалов
        if (quest_step($iceLabyrinthQuestId, 1)) {
            if (item_isset(401, 50) && item_isset(402, 10)) {
                $response['question'] = 'Прекрасно, ты собрал все материалы! Теперь мы можем открыть портал в Ледяной Лабиринт. Готов отправиться туда?';
                $response['answer'] = array(
                    4 => "Да, открой портал!"
                );
                quest_update($iceLabyrinthQuestId, 2); // Обновляем квест до этапа 2
                $response['actionQuest'] = "notify";
                $response['actionText'] = '<img src="/img/quests/4.png" class="quest"> Обновлена информация в задании «Тайна Ледяного Лабиринта». Проверьте Дневник.';
                $response['actionQuestMinus'] = 'Списано: 50 ледяных осколков, 10 снежных кристаллов';
                minus_item(401, 50); // Списываем ледяные осколки
                minus_item(402, 10); // Списываем снежные кристаллы
            } else {
                $response['question'] = 'У тебя пока нет всех материалов. Собери 50 ледяных осколков и 10 снежных кристаллов.';
            }
        }
        break;

    case 4: // Вход в лабиринт через новую локацию
        if (quest_step($iceLabyrinthQuestId, 2)) {
            $response['question'] = 'Портал открыт! Теперь ты можешь отправиться в Ледяной Лабиринт. Там тебе нужно победить ледяных стражей: <b>#473 Мамосвайн</b>, <b>#362 Глейли</b>, и <b>#365 Валрейн</b>. Победи их всех и вернись ко мне с артефактом, который они охраняют.';
            $response['answer'] = array(
                5 => "Я отправляюсь в лабиринт!"
            );
            quest_update($iceLabyrinthQuestId, 3); // Обновляем квест до этапа 3
            $response['actionLocation'] = "add"; // Добавляем новую локацию
            $response['actionLocationName'] = "Ледяной Лабиринт"; // Название локации
            $response['actionLocationDescription'] = "Лабиринт, скрытый в вечных льдах. Здесь обитают сильные ледяные покемоны и охраняется древний артефакт."; // Описание локации
        }
        break;

    case 5: // Завершение битв с ледяными стражами
        if (quest_step($iceLabyrinthQuestId, 3)) {
            if (player_defeated_pokemon_count([473, 362, 365], 5)) { // Проверяем, победил ли игрок 5 стражей
                $response['question'] = 'Ты победил всех ледяных стражей и добыл артефакт? Это невероятно! Верни его мне, и я щедро награжу тебя.';
                $response['answer'] = array(
                    6 => "Да, вот артефакт."
                );
                quest_update($iceLabyrinthQuestId, 4); // Обновляем квест до этапа 4
                $response['actionQuest'] = "notify";
                $response['actionText'] = '<img src="/img/quests/4.png" class="quest"> Обновлена информация в задании «Тайна Ледяного Лабиринта». Проверьте Дневник.';
            } else {
                $response['question'] = 'Ты ещё не победил 5 ледяных стражей. Продолжай сражаться в Ледяном Лабиринте!';
            }
        }
        break;

    case 6: // Завершение квеста и выдача наград
        if (quest_step($iceLabyrinthQuestId, 4)) {
            $response['question'] = 'Ты вернул древний артефакт и помог разгадать тайну Ледяного Лабиринта. Спасибо за твою смелость и помощь! Прими эти награды как знак благодарности.';
            
            // Награды
            $response['actionQuest'] = "notify";
            $response['actionText'] = '<img src="/img/quests/4.png" class="quest"> Задание «Тайна Ледяного Лабиринта» завершено. Проверьте награды.';
            $response['actionQuestPlus'] = 
                '<img src="/img/world/items/little/197.png" class="item"> Большой тренировочный набор (15 шт.)<br>' .
                '<img src="/img/world/items/little/33.png" class="item"> Конфета загадка (25 шт.)<br>' .
                '<img src="/img/world/items/little/1.png" class="item"> Монеты (2,000,000)<br>' .
                '<img src="/img/world/items/little/11.png" class="item"> Драгоценный ящик (2 шт.)<br>' .
                '<img src="/img/pokemons/anim/normal/473.gif" class="item"> Покемон: #473 (Mamoswine, уровень 50)';

            // Выдача наград
            itemAdd(197, 15); // Большой тренировочный набор
            itemAdd(33, 25); // Конфета загадка
            itemAdd(1, 2000000); // Монеты
            itemAdd(11, 2); // Драгоценный ящик
            NewPokemon(473, $_SESSION['id'], 1, 50, 0, 'false', 0, false, 0, false, true, true); // Покемон Мамосвайн

            quest_update($iceLabyrinthQuestId, 5); // Завершаем квест
        }
        break;

    default: // Стартовый диалог
        if (!quest_isset($iceLabyrinthQuestId)) {
            $response['question'] = 'Я изучаю древний Ледяной Лабиринт. Хочешь помочь мне разгадать его тайну?';
            $response['answer'] = array(
                1 => "Что за тайна? Расскажи подробнее."
            );
        } else if (quest_step($iceLabyrinthQuestId, 3)) {
            $response['question'] = 'Ты уже победил 5 ледяных стражей?';
            if (player_defeated_pokemon_count([473, 362, 365], 5)) {
                $response['answer'] = array(
                    5 => "Да, я справился!"
                );
            }
        } else {
            $response['question'] = '<i>~Исследователь записывает что-то в своём дневнике~</i>';
        }
        break;
}
?>
