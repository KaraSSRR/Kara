<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/inc/function/Functions.php';

$response['name'] = 'Саймон';

// Функция для генерации задания из базы данных daily_richi
function generateDailyPokemonTaskFromDB() {
    global $mysqli;

    // Получаем случайное задание из базы daily_richi
    $taskQuery = $mysqli->query("
        SELECT * 
        FROM `daily_richi` 
        ORDER BY RAND() 
        LIMIT 1
    ");
    $task = $taskQuery->fetch_assoc();

    if (!$task) {
        return null; // Если задания нет
    }

    return [
        "pokemonId" => $task['pokemon_id'],
        "pokemonName" => $task['name_rus'],
        "pokemonImage" => $task['image_url'],
        "minLevel" => $task['min_level'],
        "maxLevel" => $task['max_level'],
        "character" => $task['character'],
    ];
}

switch ($npcStep) {
    case 1: // Начальный диалог
        if (!quest_isset(50)) {
            $response['question'] = 'Привет! Я изучаю редких покемонов. У меня есть задание для тебя. Хочешь попробовать?';
            $response['answer'] = [
                2 => "Да, дайте задание!" // Переход к выдаче задания
            ];
        } else {
            $response['question'] = 'Как продвигается выполнение задания?';
            $response['answer'] = [
                3 => "Я готов сдать задание!", // Переход к сдаче задания
                5 => "Я ещё в процессе, подожди немного" // Ответ о продолжении выполнения задания
            ];
        }
        break;

    case 2: // Выдача задания
        if (!quest_isset(50)) {
            // Генерация задания из базы данных daily_richi
            $task = generateDailyPokemonTaskFromDB();

            if (!$task) {
                $response['question'] = 'Задание временно недоступно. Попробуй позже!';
                break;
            }

            // Подготовка и вывод задания
            $response['question'] = '<div style="text-align:center">
                <img src="'.$task['pokemonImage'].'" width="160">
                <br><b><span class="intextpoke sp'.$task['pokemonId'].'" onclick="openDex('.$task['pokemonId'].')">'.$task['pokemonName'].'</span>!</b>
                </div>
                Принеси мне этого покемона с уровнем от '.$task['minLevel'].' до '.$task['maxLevel'].' и характером '.$task['character'].'.';
            $response['answer'] = [
                3 => "Хорошо, я начну!" // Подтверждение начала задания
            ];

            // Обновление квеста
            quest_update(50, 1);
            update_zap(50, 1, 'Получено задание: найти и принести покемона <b>'.$task['pokemonName'].'</b> (#'.$task['pokemonId'].') с уровнем от '.$task['minLevel'].' до '.$task['maxLevel'].' и характером '.$task['character'].'.');
            $response['actionQuest'] = 'Обновлено задание в квесте <b>Начало путешествия</b>. Загляните в Дневник.';

            // Запись задания в таблицу daily_richi_user_tasks
            $mysqli->query("
                INSERT INTO `daily_richi_user_tasks` (`user_id`, `mission_id`, `pokemon_id`, `character`, `min_level`, `max_level`, `time`)
                VALUES (
                    ".$_SESSION['id'].",
                    50,
                    ".$task['pokemonId'].",
                    '".$task['character']."',
                    ".$task['minLevel'].",
                    ".$task['maxLevel'].",
                    UNIX_TIMESTAMP() + 86400
                )
            ");
        }
        break;

    case 3: // Переход к сдаче задания
        if (quest_step(50, 1)) {
            // Получаем данные задания из таблицы daily_richi_user_tasks
            $taskData = $mysqli->query("
                SELECT * FROM `daily_richi_user_tasks` 
                WHERE `user_id` = ".$_SESSION['id']." 
                  AND `mission_id` = 50
            ")->fetch_assoc();

            if (!$taskData) {
                $response['question'] = 'Ошибка в данных задания. Попробуй позже.';
                break;
            }

            // Вывод данных задания
            $response['question'] = 'Ты готов отдать мне покемона с нужными характеристиками? Это: 
                <ol>
                    <li>Покемон: <span class="intextpoke sp'.$taskData['pokemon_id'].'" onclick="openDex('.$taskData['pokemon_id'].')">#'.$taskData['pokemon_id'].' '.$taskData['pokemonName'].'</span></li>
                    <li>Уровень: от '.$taskData['min_level'].' до '.$taskData['max_level'].'</li>
                    <li>Характер: '.$taskData['character'].'</li>
                </ol>';
            $response['answer'] = [
                4 => "Да, можешь забрать!" // Подтверждение сдачи задания
            ];
        } else {
            $response['question'] = 'Ошибка! Ты не выполняешь задание.';
            $response['answer'] = [
                2 => "Начать задание снова" // Возможность начать задание заново
            ];
        }
        break;

    case 4: // Завершение сдачи задания
        if (quest_step(50, 1)) {
            // Получаем данные задания
            $taskData = $mysqli->query("
                SELECT * FROM `daily_richi_user_tasks` 
                WHERE `user_id` = ".$_SESSION['id']." 
                  AND `mission_id` = 50
            ")->fetch_assoc();

            if (!$taskData) {
                $response['question'] = 'Ошибка в данных задания. Попробуй позже.';
                break;
            }

            // Проверяем наличие покемона
            $pokemonQuery = $mysqli->query("
                SELECT * FROM `user_pokemons` 
                WHERE `user_id` = ".$_SESSION['id']." 
                  AND `active` = 1 
                  AND `basenum` = ".$taskData['pokemon_id']." 
                  AND `lvl` BETWEEN ".$taskData['min_level']." AND ".$taskData['max_level']." 
                  AND `character` = '".$taskData['character']."'
            ");

            $pokemon = $pokemonQuery->fetch_assoc();

            if ($pokemon) {
                // Удаляем покемона
                $mysqli->query("DELETE FROM `user_pokemons` WHERE `id` = ".$pokemon['id']." LIMIT 1");

                // Обновляем задание
                quest_update(50, 2);
                update_zap(50, 2, 'Покемон <b>'.$taskData['pokemonName'].'</b> с характером '.$taskData['character'].' доставлен. Задание выполнено.');

                // Награда
                $response['question'] = 'Ты справился! Держи награду!';
                $response['actionQuestPlus'] = '<img src="/img/world/items/little/1.png" class="item"> Монета <b>x50.000</b>';
                $response['actionQuest'] = 'Обновлено задание в квесте <b>Начало путешествия</b>. Загляните в Дневник.';
                itemAdd(1, 50000);
            } else {
                $response['question'] = 'У тебя нет покемона, который подходит под условия задания.';
            }
        } else {
            $response['question'] = 'Ошибка!';
        }
        break;

    case 5: // Ответ "Я ещё в процессе"
        $response['question'] = 'Хорошо, я подожду. Удачи в выполнении задания!';
        break;

    default: // Возможность сдать задание после вопроса о прогрессе
        if (quest_step(50, 0)) {
            $response['question'] = 'Привет! Ты еще не принял квест, хочешь начать?';
            $response['answer'] = [
                2 => "Да, начну задание" // Переход к началу квеста
            ];
        } else {
            $response['question'] = 'Привет! Как продвигается выполнение задания?';
            $response['answer'] = [
                3 => "Я готов сдать задание!", // Переход к сдаче задания
                5 => "Я ещё в процессе, подожди немного" // Ответ о продолжении выполнения задания
            ];
        }
        break;
}

?>
