<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/function/Functions.php';
$response = ['name' => 'Куратор рандом-арены'];

// Получение текущего шага диалога NPC
$npcStep = isset($_POST['npcStep']) ? intval($_POST['npcStep']) : 0;

// Проверка локации пользователя
$stmtUser = $mysqli->prepare("SELECT location FROM users WHERE id = ?");
$stmtUser->bind_param("i", $_SESSION['id']);
$stmtUser->execute();
$userInfo = $stmtUser->get_result()->fetch_assoc();

if ($userInfo['location'] != 8009) { // Локация арены
    $response['question'] = 'Вы не находитесь на арене!';
    echo json_encode($response);
    exit;
}

switch ($npcStep) {
    case 0: // Начальный шаг
        $response['question'] = 'Приветствую на арене! Хотите участвовать в рандомной битве?';
        $response['answer'] = [
            1 => 'Да, начнем!',
            2 => 'Расскажите больше о правилах.',
            3 => 'Я вернусь позже.',
        ];
        break;

    case 1: // Начало битвы
        include_once $_SERVER['DOCUMENT_ROOT'] . '/do/Npc/aviablePoks.php';

        // Назначение случайных покемонов для битвы
        \matsuka\giveRandomArenaPoks($_SESSION['id']);

        $response['question'] = 'Ваши покемоны подготовлены. Ищем противника!';
        $response['answer'] = [
            4 => 'Отменить поиск.',
        ];
        break;

    case 2: // Правила арены
        $response['question'] = 'На арене вам выдают случайных покемонов для битвы. Победитель получает награды. Покемоны исчезают после завершения битвы.';
        $response['answer'] = [
            0 => 'Вернуться назад.',
        ];
        break;

    case 3: // Возвращение позже
        $response['question'] = 'Возвращайтесь, когда будете готовы. Мы всегда рады видеть новых участников!';
        break;

    case 4: // Отмена поиска
        // Логика отмены битвы
        $response['question'] = 'Поиск противника отменён. Вы можете начать снова, когда будете готовы.';
        $response['answer'] = [
            0 => 'Вернуться назад.',
        ];
        break;

    default:
        $response['question'] = 'Что-то пошло не так. Попробуйте снова.';
        break;
}

// Возврат ответа в формате JSON
echo json_encode($response);
