<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/function/Functions.php';
$response['name'] = 'Рику';

switch ($npcStep) {
    case 1: // Введение
        $response['question'] = 'Битвы — пожалуй, самая важная и зрелищная составляющая мира Poke-Route. 
        Чтобы побеждать, тебе нужна сильная и сбалансированная команда. 
        Хочешь узнать больше о боевой механике и покемонах?';
        $response['answer'] = [
            2 => "Да, расскажите подробнее"
        ];
        break;

    case 2: // Основы боёв
        $response['question'] = 'В каждой битве важны три вещи: 
        типы покемонов, их характеристики и атаки. 
        Например, у покемонов есть 6 основных характеристик: HP, атака, защита, скорость, спец. атака и спец. защита. 
        Кроме того, тип покемона влияет на урон. Хотите узнать о типах или атаках подробнее?';
        $response['answer'] = [
            3 => "О типах",
            4 => "Об атаках"
        ];
        break;

    case 3: // О типах покемонов
        $response['question'] = 'Существует 18 типов покемонов. Например, огненный тип слаб против водного, 
        но силён против травяного. У покемонов с двумя типами слабости и сопротивления комбинируются. 
        Умение использовать разные типы покемонов и атак — ключ к победе.';
        $response['answer'] = [
            5 => "Расскажите об атаках"
        ];
        break;

    case 4: // Об атаках
    case 5:
        $response['question'] = 'Каждая атака имеет мощность, точность, тип и дополнительные эффекты. 
        Например, атаки того же типа, что и покемон, получают бонус урона (STAB). 
        Важно выбирать атаки, которые подходят для типа и характеристик покемона.';
        $response['answer'] = [
            6 => "Что дальше?"
        ];
        break;

    case 6: // Предложение боя
        if (lvluser() < 10) {
            quest_update(3, 1);
            $response['question'] = 'Теперь проверим ваши знания на практике! 
            Я буду использовать покемона травяного типа 40 уровня. Вы готовы к битве?';
            $response['answer'] = [
                7 => "Да, я готов"
            ];
            $response['actionQuest'] = 'Квест обновлён: <b>Мастер боёв</b>. Загляните в Дневник.';
            update_zap(3, 1, 'Рику рассказал мне об основах боёв и предложил сразиться с его покемоном травяного типа.');
        } else {
            $response['question'] = 'Прекрасно! Я рад, что ты всё понял. Удачи тебе!';
            quest_update(3, 5, 1);
            update_zap(3, 5, 'Рику рассказал мне об основах боёв, но я был слишком опытным для выполнения его задания.');
        }
        break;

    case 7: // Генерация боя
        $user = $mysqli->query("SELECT * FROM users WHERE id = " . $_SESSION['id'])->fetch_assoc();
        if (quest_step(3, 1) && $user['status'] == 'free') {
            $mysqli->query("UPDATE `users` SET `status`='battle' WHERE `id`='" . $_SESSION['id'] . "'");
            $response['question'] = 'Начинаем битву!';
            $location_id = $mysqli->query("SELECT `location` FROM `users` WHERE `id`='" . $_SESSION['id'] . "'")->fetch_assoc();
            $battleResult = Info::_generatePve($_SESSION['id'], $location_id['location'], null, 1);

            if (!$battleResult) {
                $response['question'] = 'Ошибка при создании боя. Попробуйте позже.';
            }
        } else {
            $response['question'] = 'Вы заняты. Сначала завершите текущие дела.';
        }
        break;

    case 8: // Победа в битве
        if (quest_step(3, 1)) {
            quest_update(3, 2);
            $response['question'] = 'Отличная работа! Вот ваша награда.';
            $rewardId = rand(125, 141);
            $rewardItem = $mysqli->query("SELECT * FROM base_items WHERE id = $rewardId")->fetch_assoc();
            $response['actionQuestPlus'] = '<img src="/img/world/items/little/' . $rewardId . '.png" class="item"> ' . $rewardItem['name'] . ' <b>x1</b>';
            itemAdd($rewardId, 1);
            $response['answer'] = [
                9 => "Хотите сразиться с моим огненным покемоном?"
            ];
        } else {
            $response['question'] = 'Ошибка!';
        }
        break;

    case 9: // Вторая битва
        $user = $mysqli->query("SELECT * FROM users WHERE id = " . $_SESSION['id'])->fetch_assoc();
        if (quest_step(3, 2) && $user['status'] == 'free') {
            $mysqli->query("UPDATE `users` SET `status`='battle' WHERE `id`='" . $_SESSION['id'] . "'");
            $response['question'] = 'Начинаем битву!';
            $location_id = $mysqli->query("SELECT `location` FROM `users` WHERE `id`='" . $_SESSION['id'] . "'")->fetch_assoc();
            $battleResult = Info::_generatePve($_SESSION['id'], $location_id['location'], null, 2);

            if (!$battleResult) {
                $response['question'] = 'Ошибка при создании боя. Попробуйте позже.';
            }
        } else {
            $response['question'] = 'Вы заняты. Сначала завершите текущие дела.';
        }
        break;

    case 10: // Завершение квеста
        if (quest_step(3, 3)) {
            quest_update(3, 5, 1);
            $rewardId = rand(125, 141);
            $rewardItem = $mysqli->query("SELECT * FROM base_items WHERE id = $rewardId")->fetch_assoc();
            $response['actionQuestPlus'] = '<img src="/img/world/items/little/' . $rewardId . '.png" class="item"> ' . $rewardItem['name'] . ' <b>x1</b>';
            itemAdd($rewardId, 1);
            lvlupuser(500);
            $response['question'] = 'Вы отличный тренер! Удачи в вашем приключении.';
            update_zap(3, 3, 'Рику похвалил меня за победы и дал вторую награду.');
        } else {
            $response['question'] = 'Ошибка!';
        }
        break;

    default: // Начальный текст
        $response['question'] = 'Привет, ' . $_SESSION['login'] . '! Я Рику, эксперт по боям покемонов. Хочешь узнать об этом больше?';
        $response['answer'] = [
            1 => "Да, хочу!"
        ];
        break;
}
?>
