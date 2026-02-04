<?php
// encyclopedia/_inc/layout_top.php
if (!isset($pageTitle)) $pageTitle = 'Poke Kara';
$__enc_cur = basename((string)parse_url((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''), PHP_URL_PATH));
if ($__enc_cur === '' || $__enc_cur === 'encyclopedia') $__enc_cur = 'index.php';
$__enc_is = function(array $names) use ($__enc_cur) {
  return in_array($__enc_cur, $names, true) ? 'active' : '';
};
?><!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=enc_h($pageTitle)?> — Poke Kara</title>
  <link rel="stylesheet" href="<?=enc_h(enc_url('/assets/ency.css'))?>">
</head>
<body>
<div class="forum-root encyclopedia-root">
  <header class="forum-header">
    <div class="forum-header-inner">
      <a class="brand" href="<?=enc_h(enc_url('/'))?>">Poke Kara</a>

      <nav class="nav">
        <a class="<?=($__enc_is(['index.php']))?>" href="<?=enc_h(enc_url('/'))?>">Главная</a>
        <a class="<?=($__enc_is(['generations.php']))?>" href="<?=enc_h(enc_url('/generations.php'))?>">Поколения</a>
        <a class="<?=($__enc_is(['pokedex.php','pokemon.php']))?>" href="<?=enc_h(enc_url('/pokedex.php'))?>">Покедекс</a>
        <a class="<?=($__enc_is(['moves.php','move.php']))?>" href="<?=enc_h(enc_url('/moves.php'))?>">Атаки</a>
        <a class="<?=($__enc_is(['abilities.php','ability.php']))?>" href="<?=enc_h(enc_url('/abilities.php'))?>">Способности</a>
        <a class="<?=($__enc_is(['items.php','item.php']))?>" href="<?=enc_h(enc_url('/items.php'))?>">Предметы</a>
        <a class="<?=($__enc_is(['builds.php','build.php','builder.php']))?>" href="<?=enc_h(enc_url('/builds.php'))?>">Сборки</a>
        <a class="<?=($__enc_is(['guide.php']))?>" href="<?=enc_h(enc_url('/guide.php'))?>">Путеводитель</a>
        <a href="/world">В мир</a>
        <a href="/forum">Форум</a>
      </nav>

      <div class="header-actions">
        <form class="search" action="<?=enc_h(enc_url('/search.php'))?>" method="get">
          <input type="text" name="q" placeholder="Поиск..." value="<?=enc_h((string)((isset($_GET['q']) ? $_GET['q'] : '')))?>">
        </form>
        <?php if ($encyUserId > 0): ?>
          <a class="btn" href="<?=enc_h(enc_url('/builder.php'))?>">Создать сборку</a>
          <?php if ($encyIsAdmin): ?>
            <a class="btn" href="<?=enc_h(enc_url('/admin/'))?>">Админ</a>
          <?php endif; ?>
          <a class="btn" href="/?route=exit">Выйти</a>
        <?php else: ?>
          <a class="btn" href="<?=enc_h(enc_url('/login.php?next=' . urlencode((isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : enc_url('/')))))?>">Войти</a>
        <?php endif; ?>
      </div>

      <button class="burger" id="burgerBtn" aria-label="Меню">☰</button>
    </div>
    <div class="mobile-nav" id="mobileNav">
        <a class="<?=($__enc_is(['index.php']))?>" href="<?=enc_h(enc_url('/'))?>">Главная</a>
        <a class="<?=($__enc_is(['generations.php']))?>" href="<?=enc_h(enc_url('/generations.php'))?>">Поколения</a>
        <a class="<?=($__enc_is(['pokedex.php','pokemon.php']))?>" href="<?=enc_h(enc_url('/pokedex.php'))?>">Покедекс</a>
        <a class="<?=($__enc_is(['moves.php','move.php']))?>" href="<?=enc_h(enc_url('/moves.php'))?>">Атаки</a>
        <a class="<?=($__enc_is(['abilities.php','ability.php']))?>" href="<?=enc_h(enc_url('/abilities.php'))?>">Способности</a>
        <a class="<?=($__enc_is(['items.php','item.php']))?>" href="<?=enc_h(enc_url('/items.php'))?>">Предметы</a>
        <a class="<?=($__enc_is(['builds.php','build.php','builder.php']))?>" href="<?=enc_h(enc_url('/builds.php'))?>">Сборки</a>
        <a class="<?=($__enc_is(['guide.php']))?>" href="<?=enc_h(enc_url('/guide.php'))?>">Путеводитель</a>
        <a href="/world">В мир</a>
        <a href="/forum">Форум</a>
    </div>
  </header>

  <main class="forum-container">
<?php if (!$encyInstalled && $encyIsAdmin): ?>
  <div class="forum-alert danger">
    Энциклопедия не установлена. Установите модуль энциклопедии.
  </div>
<?php elseif ((!$encyHasBuildIndex || !$encyHasPopularity) && $encyIsAdmin): ?>
  <div class="forum-alert" style="margin-top:12px;">
    <b>Внимание:</b> некоторые функции энциклопедии отключены.
    <?php if (!$encyHasBuildIndex): ?> <div class="muted">• Отключено превью/поиск сборок по покемонам.</div><?php endif; ?>
    <?php if (!$encyHasPopularity): ?> <div class="muted">• Отключён рейтинг популярности покемонов.</div><?php endif; ?>
    <div class="muted" style="margin-top:6px;">Обновите модуль энциклопедии, чтобы включить весь функционал.</div>
  </div>
<?php endif; ?>
