<?php
require_once __DIR__ . '/../_inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!$forumInstalled) {
    echo json_encode(['ok'=>0,'error'=>'Forum is not installed'], JSON_UNESCAPED_UNICODE);
    exit;
}

forum_require_login($forumUserId);
csrf_check();

if (!$canReact) {
    echo json_encode(['ok'=>0,'error'=>'Нет прав на реакции'], JSON_UNESCAPED_UNICODE);
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
$reaction = trim((string)($_POST['reaction'] ?? ''));
if ($postId <= 0 || $reaction === '') {
    echo json_encode(['ok'=>0,'error'=>'Неверный запрос'], JSON_UNESCAPED_UNICODE);
    exit;
}

$cfg = forum_reactions_config();
if (empty($cfg[$reaction])) {
    echo json_encode(['ok'=>0,'error'=>'Недопустимая реакция'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем что пост существует
$stmt = $mysqli->prepare('SELECT id FROM forum_posts WHERE id=? LIMIT 1');
$stmt->bind_param('i', $postId);
$stmt->execute();
$rs = $stmt->get_result();
$ok = $rs && $rs->fetch_assoc();
$stmt->close();
if (!$ok) {
    echo json_encode(['ok'=>0,'error'=>'Пост не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Toggle (у пользователя может быть только одна реакция на один пост)
$stmt = $mysqli->prepare('SELECT id, reaction FROM forum_reactions WHERE post_id=? AND user_id=? ORDER BY id DESC LIMIT 1');
$stmt->bind_param('ii', $postId, $forumUserId);
$stmt->execute();
$rs = $stmt->get_result();
$existingId = 0;
$existingReaction = '';
if ($rs && ($row = $rs->fetch_assoc())) {
    $existingId = (int)($row['id'] ?? 0);
    $existingReaction = (string)($row['reaction'] ?? '');
}
$stmt->close();

if ($existingId > 0) {
    if ($existingReaction === $reaction) {
        // повторное нажатие снимает реакцию
        $stmt = $mysqli->prepare('DELETE FROM forum_reactions WHERE id=?');
        $stmt->bind_param('i', $existingId);
        $stmt->execute();
        $stmt->close();
    } else {
        // меняем тип реакции, не создавая вторую запись
        $stmt = $mysqli->prepare('UPDATE forum_reactions SET reaction=?, created_at=UNIX_TIMESTAMP() WHERE id=?');
        $stmt->bind_param('si', $reaction, $existingId);
        $stmt->execute();
        $stmt->close();
    }
} else {
    $stmt = $mysqli->prepare('INSERT INTO forum_reactions (post_id, user_id, reaction, created_at) VALUES (?,?,?,UNIX_TIMESTAMP())');
    $stmt->bind_param('iis', $postId, $forumUserId, $reaction);
    $stmt->execute();
    $stmt->close();
}

$html = forum_reactions_html($mysqli, $postId, $forumUserId, true);

echo json_encode(['ok'=>1,'html'=>$html], JSON_UNESCAPED_UNICODE);
