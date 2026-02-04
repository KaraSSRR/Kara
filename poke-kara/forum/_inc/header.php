<?php
// forum/_inc/header.php
require_once __DIR__ . '/bootstrap.php';

$title = $title ?? 'Форум';
?><!doctype html>
<html lang="<?=h($forumUser['lang'] ?? 'ru')?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?=h($title)?> | PokeKara</title>
  <link rel="stylesheet" href="/css/main_new.css?<?=microtime(true)?>">
  <link rel="stylesheet" href="<?=forum_url('assets/forum.css')?>?<?=microtime(true)?>">
</head>
<body class="pk">

<div class="topbarWrap">
  <div class="container">
    <div class="topbar">
      <a class="brand" href="/">
        <span class="logo" aria-hidden="true"></span>
        <div class="brandText">
          <b>PokeKara</b>
          <span>Форум сообщества</span>
        </div>
      </a>

      <nav class="nav" aria-label="Навигация">
        <a href="/">Главная</a>
        <a href="/world">В игру</a>
        <a href="<?=forum_url('/')?>" class="active">Форум</a>
        <a href="/pokedex">Энциклопедия</a>
      </nav>

      <div class="actions">
        <?php if ($forumUserId <= 0): ?>
          <a class="btn" href="<?=forum_url('login.php')?>">Войти</a>
        <?php else: ?>
          <a class="btn" href="<?=forum_url('profile.php')?>"><?=h($forumUser['login'] ?? 'Профиль')?></a>
          <a class="btn" href="<?=forum_url('logout.php')?>">Выйти</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<main class="forumWrap">
  <div class="container">

<?php if (!$forumInstalled): ?>
  <div class="forumNotice forumNoticeWarn">
    <b>Форум не установлен.</b>
    <div>Нужно выполнить SQL из <code><?=h($_SERVER['DOCUMENT_ROOT'] . '/forum/sql/install.sql')?></code>.</div>
  </div>
<?php endif; ?>
