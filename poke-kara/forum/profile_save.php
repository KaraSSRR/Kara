<?php
require_once __DIR__ . '/_inc/bootstrap.php';

forum_require_login($forumUserId);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(forum_url('/profile.php'));
}

csrf_check();

$sig = forum_clean_text((string)($_POST['signature'] ?? ''), FORUM_SIGNATURE_MAX);

$stmt = $mysqli->prepare('UPDATE forum_users SET signature=? WHERE user_id=?');
$stmt->bind_param('si', $sig, $forumUserId);
$stmt->execute();
$stmt->close();

redirect(forum_url('/profile.php?saved=1'));
