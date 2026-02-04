<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';

if (!file_exists($patch_global)) {
    die(json_encode(['error' => 'The problem with the connection files.']));
}
require_once($patch_global);

session_start();
$response = [];
if (!isset($mysqli) || !$mysqli) {
    die(json_encode(['error' => 'Ошибка подключения к базе данных.']));
}

// Очищаем и проверяем входные данные
$user = isset($_POST['user']) ? $mysqli->real_escape_string(clearStr($_POST['user'])) : null;
$self_id = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if (!$user || !$self_id) {
    die(json_encode(['error' => 'Некорректные данные пользователя.']));
}

// Получаем данные пользователя (id/login/group)
$stmt = $mysqli->prepare("SELECT `id`, `login`, `user_group` AS `group` FROM `users` WHERE `id` = ?");
$stmt->bind_param('i', $user);
$stmt->execute();
$userTo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userTo) {
    die(json_encode(['error' => 'Пользователь не найден.']));
}

// Получаем инфу о кланах
function getClanUser($mysqli, $id) {
    $stmt = $mysqli->prepare("SELECT * FROM `base_clans_users` WHERE `user_id` = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}
$userTo1 = getClanUser($mysqli, $userTo['id']);
$userTo2 = getClanUser($mysqli, $self_id);

// Клановые действия
if ($userTo2 && $userTo2['group'] == 1 && $userTo['id'] != $self_id) {
    if ($userTo2['clan_id'] == $userTo1['clan_id']) {
        $userTo['delClan'] = true;
    } else {
        $userTo['addClan'] = true;
    }
}

// Мои действия и дружба
if ($userTo['id'] == $self_id) {
    $userTo['my'] = true;
} else {
    $stmt = $mysqli->prepare("SELECT `id` FROM `users_friend` WHERE (`user_id` = ? AND `friend_id` = ?) OR (`user_id` = ? AND `friend_id` = ?)");
    $stmt->bind_param('iiii', $self_id, $userTo['id'], $userTo['id'], $self_id);
    $stmt->execute();
    $friends = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($friends['id'])) {
        $userTo['friend'] = true;
    }
}

Work::_setInfo('userTooltip', $userTo);
$response['html'] = 1;

// Шаблон вывода
$html = [];
$html[] = '<div id="DivAbout">Информация <b>' . htmlspecialchars($userTo['login']) . '</b></div>';
$html[] = '<div class="wrap"><div class="PokList">';
$html[] = '<div class="PokBtn" onclick="openTrenCard(\'' . $userTo['id'] . '\');">Тренеркарта</div>';

if ($userTo['id'] != $self_id) {
    // Дружба, бой, обмен, ЧС и т.д.
    $stmt = $mysqli->prepare("SELECT `id` FROM `users_friend` WHERE (`user_id` = ? AND `friend_id` = ?) OR (`user_id` = ? AND `friend_id` = ?)");
    $stmt->bind_param('iiii', $self_id, $userTo['id'], $userTo['id'], $self_id);
    $stmt->execute();
    $friends = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $html[] = '<div class="PokBtn" onclick="setTrade(\'send\',' . $userTo['id'] . ');">Обмен</div>';
    $html[] = '<div class="PokBtn">Бой</div>';
    if (empty($friends['id'])) {
        $html[] = '<div class="PokBtn" onclick="userAction(\'' . $userTo['id'] . '\',\'friend\');">Дружить</div>';
    } else {
        $html[] = '<div class="PokBtn" onclick="userAction(\'' . $userTo['id'] . '\',\'DeleteFriend\');">Удалить из друзей</div>';
    }
    $html[] = '<div class="PokBtn">Добавить в ЧС</div>';
}
$html[] = '</div></div>';

$response['tpl'] = implode('', $html);

Work::_viewOut();
echo json_encode($response);
