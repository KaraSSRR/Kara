<?php
session_start();
$patch_global = $_SERVER['DOCUMENT_ROOT'].'/inc/conf/global.php';

if (!file_exists($patch_global)) {
    die('The problem with the connection files.');
} else {
    require_once($patch_global);
}

if (!isset($_SESSION['id'])) {
    $response = ['text' => 'Ошибка: пользователь не аутентифицирован.', 'error' => 1];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (!empty($_POST['subject']) && !empty($_POST['text'])) {
    // Сохранение заявки
    $text = $mysqli->real_escape_string($_POST['text']);
    $subject = $mysqli->real_escape_string($_POST['subject']);
    $date = date("Y-m-d H:i:s");

    $insert = json_encode(['text' => $text, 'subject' => $subject, 'date' => $date]);

    $stmt = $mysqli->prepare("INSERT INTO `police` (`user`, `info`, `check`, `created_at`) VALUES (?, ?, 0, ?)");
    $stmt->bind_param("sss", $_SESSION['id'], $insert, $date);

    if ($stmt->execute()) {
        $response = ['text' => 'Заявка успешно отправлена!', 'error' => 0];

        // Добавить сообщение в чат полиции
        $chat_message = "Пользователь ID: {$_SESSION['id']} отправил заявку. Тема: $subject";
        $stmt_chat = $mysqli->prepare("INSERT INTO `police_chat` (`user_id`, `message`, `created_at`) VALUES (?, ?, ?)");
        $stmt_chat->bind_param("iss", $_SESSION['id'], $chat_message, $date);
        $stmt_chat->execute();
        $stmt_chat->close();
    } else {
        $response = ['text' => 'Ошибка при отправке заявки.', 'error' => 1];
    }

    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Вывод NPC формы
$text = '<div class="market">';
$text .= '<div class="text">Заполните заявку. Выберите тему и оставьте комментарий.</div>';
$text .= '
    <form class="evolNpcForm" onsubmit="policeNPC();return false;">
        <select style="margin: 10px;width: 95%;" required id="subjectPolice">
            <option value="1">Нарушение правил боя</option>
            <option value="2">Кража</option>
            <option value="3">Подозрения в мультоводстве</option>
            <option value="4">Подозрения в финпрокачке</option>
            <option value="5">Прочие нарушения</option>
        </select>
        <textarea required id="textPolice"></textarea>
        <input style="margin-left: 10px;" class="mn-btn" type="submit" value="Отправить" />
    </form>';
$text .= '</div>';

$response = [
    'html' => $text,
    'type' => 'pokemarket',
    'title' => 'Офицер Патрик'
];

header('Content-Type: application/json');
echo json_encode($response);
