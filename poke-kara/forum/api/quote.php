<?php
require_once __DIR__ . '/../_inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!$forumInstalled) {
    echo json_encode(['ok'=>0,'error'=>'Forum is not installed'], JSON_UNESCAPED_UNICODE);
    exit;
}

forum_require_login($forumUserId);

// CSRF для действий в UI (даже для GET)
$token = (string)($_GET['csrf'] ?? '');
if ($token === '' || empty($_SESSION['forum_csrf']) || !hash_equals((string)$_SESSION['forum_csrf'], $token)) {
    echo json_encode(['ok'=>0,'error'=>'Bad request'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$canReply) {
    echo json_encode(['ok'=>0,'error'=>'Нет прав отвечать'], JSON_UNESCAPED_UNICODE);
    exit;
}

$postId = (int)($_GET['post_id'] ?? 0);
if ($postId <= 0) {
    echo json_encode(['ok'=>0,'error'=>'Неверный post_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

$expr = forum_post_content_expr($mysqli, 'p');

$sql = 'SELECT p.id, '.$expr.' AS content, p.created_at, u.login FROM forum_posts p JOIN users u ON u.id=p.user_id WHERE p.id=? LIMIT 1';
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $postId);
$stmt->execute();
$rs = $stmt->get_result();
$row = $rs ? $rs->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['ok'=>0,'error'=>'Пост не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

$login = (string)($row['login'] ?? '');
$when = date('d.m.Y H:i', (int)($row['created_at'] ?? 0));
$content = (string)($row['content'] ?? '');
$content = forum_clean_text($content, 4000);

$quote = "[quote={$login}|{$when}]\n{$content}\n[/quote]\n";

echo json_encode(['ok'=>1,'quote'=>$quote], JSON_UNESCAPED_UNICODE);
