<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';

if (!file_exists($patch_global) || !is_readable($patch_global)) {
    die('Configuration file is missing or unreadable.');
}

require_once($patch_global);

// Получаем данные текущего пользователя
$stmt_user = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param('i', $_SESSION['id']);
$stmt_user->execute();
$UserQuery = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$UserQuery) {
    die(json_encode(['error' => 'User not found.']));
}

$html = '';
$c = 0;
$g = 0;

// Проверяем доступность просмотра боев на данной локации
if ($UserQuery['location'] == 76) {
    $html = "На данной локации просмотр боев недоступен!";
    $g = 1;
} else {
    // Получаем только бои, где хотя бы один участник на текущей локации
    $stmt_battle = $mysqli->prepare(
        "SELECT b.id, b.user_1, b.user_2, u1.login as login1, u1.sex as sex1, u1.user_group as group1, u2.login as login2, u2.sex as sex2, u2.user_group as group2
         FROM battle b
         JOIN users u1 ON u1.id = b.user_1
         JOIN users u2 ON u2.id = b.user_2
         WHERE b.type = 'pvp'
           AND (u1.location = ? OR u2.location = ?)"
    );
    $stmt_battle->bind_param('ii', $UserQuery['location'], $UserQuery['location']);
    $stmt_battle->execute();
    $result_battle = $stmt_battle->get_result();

    while ($battle = $result_battle->fetch_assoc()) {
        $html .= '<div class="Step"><div class="fS"><div class="user-link">
            <div onclick="showUserTooltip(\'' . $battle['user_1'] . '\')" class="Info-Link sex' . $battle['sex1'] . '">
                <i class="fa fa-info"></i>
            </div> 
            <div class="u-' . $battle['group1'] . ' label" onclick="user_to_chat_add(\'' . $battle['user_1'] . '\')">' . htmlspecialchars($battle['login1']) . '</div>
            </div> 
            <span onclick="createView(' . $battle['id'] . ');">vs</span> 
            <div class="user-link">
                <div onclick="showUserTooltip(\'' . $battle['user_2'] . '\')" class="Info-Link sex' . $battle['sex2'] . '">
                    <i class="fa fa-info"></i>
                </div> 
                <div class="u-' . $battle['group2'] . ' label" onclick="user_to_chat_add(\'' . $battle['user_2'] . '\')">' . htmlspecialchars($battle['login2']) . '</div>
            </div></div></div>';
        $c++;
    }
    $stmt_battle->close();
}

// Формируем ответ
$response = [
    'count' => $c,
    'gym' => $g,
    'html' => $html
];

echo json_encode($response);
?>