<?php
// forum/_inc/layout_top.php
// Требует, чтобы bootstrap.php уже был подключён.

$forumTitle = $forumTitle ?? 'Форум';
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=h($forumTitle)?></title>
    <link rel="stylesheet" href="<?=h(forum_url('/assets/forum.css'))?>">
</head>
<body>
<div class="forum-wrap">
    <header class="forum-header">
        <div class="forum-brand">
            <a href="<?=h(forum_url('/'))?>" class="forum-logo">Форум</a>
            <div class="forum-links">
                <a href="/" class="forum-link">На сайт</a>
                <a href="/world" class="forum-link">В мир</a>
            </div>
        </div>
        <div class="forum-userbox">
            <?php if ($forumUserId > 0 && $forumUser): ?>
                <a class="user" href="<?=h(forum_url('/profile.php'))?>">
                    <img class="ava" src="<?=h(forum_avatar_url((int)$forumUserId))?>" alt="ava">
                    <div class="meta">
                        <div class="login"><?=h($forumUser['login'] ?? '')?></div>
                        <div class="sub">
                            <span class="tag">Ранг: <?=h(((int)($forumUser['user_group'] ?? 0) === 1) ? 'Администратор' : (string)($forumUser['rang'] ?? ''))?></span>
                            <?php if ($forumProfile): ?>
                                <span class="tag">Форум: <?=h(forum_get_rank_title($mysqli, (int)($forumProfile['posts_count'] ?? 0)))?></span>
                            <?php endif; ?>
                            <?php if (!empty($forumPerms['can_admin']) || ((int)($forumUser['user_group'] ?? 0) === 1)): ?>
                                <span class="tag badge-admin">Админ форума</span>
                            <?php elseif (!empty($forumPerms['can_moderate'])): ?>
                                <span class="tag badge-mod">Модератор</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <div class="actions">
                    <?php if (forum_is_admin($forumUser, $forumPerms)): ?>
                        <a class="btn" href="<?=h(forum_url('/admin/'))?>">Админ</a>
                    <?php endif; ?>
                    <a class="btn" href="/?route=exit">Выйти</a>
                </div>
            <?php else: ?>
                <div class="guest">
                    <span class="muted">Гость</span>
                    <a class="btn" href="<?=h(forum_url('/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? forum_url('/'))))?>">Войти</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!$forumInstalled): ?>
        <div class="forum-alert danger">
            Таблицы форума не установлены. Выполните <b>forum/sql/upgrade.sql</b> (у вас старая установка) или <b>install.sql</b>.
        </div>
    <?php endif; ?>

    <?php if (!empty($forumIsBanned)): ?>
        <div class="forum-alert danger">Ваш аккаунт заблокирован. Форум доступен только для чтения.</div>
    <?php endif; ?>

    <main class="forum-main">
        <div class="forum-root" id="forum-root">
