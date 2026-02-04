<?php
require_once __DIR__ . '/_inc/bootstrap.php';

unset($_SESSION['id'], $_SESSION['login']);
header('Location: ' . forum_url('/'));
exit;
