<?php
declare(strict_types=1);

use function App\Support\e;

/** @var string $title */
/** @var string $_csrf */
/** @var array|null $_flash */
/** @var int|null $_uid */
?>
<?php
$__root = dirname(__DIR__, 2);
$__cssVer = @filemtime($__root . '/public/assets/app.css') ?: 1;
$__jsVer  = @filemtime($__root . '/public/assets/app.js') ?: 1;
?>
<!doctype html>
<html lang="ru" data-loading="0">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f2f4f7">
  <title><?= e($title ?? 'RPG v2') ?></title>
  <link rel="stylesheet" href="/assets/app.css?v=<?= e((string)$__cssVer) ?>">
</head>
<body>
  <div id="topbar" aria-hidden="true"></div>

  <div class="container">
    <div class="nav">
      <div class="brand"><a href="<?= e($_uid ? '/me' : '/') ?>" class="brand-link">RPG v2</a></div>
      <div class="muted">|</div>
      <a href="/" class="badge nav-link">Витрина</a>
      <?php if ($_uid): ?>
        <a href="/me" class="badge nav-link">Профиль</a>
        <a href="/creatures" class="badge nav-link">Существа</a>
        <a href="/battle" class="badge nav-link">Бой</a>
        <a href="/inventory" class="badge nav-link">Инвентарь</a>
        <a href="/locations" class="badge nav-link">Локации</a>
        <form method="post" action="/logout" style="margin:0">
          <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
          <button class="btn danger" type="submit">Выйти</button>
        </form>
      <?php else: ?>
        <a href="/login" class="badge nav-link">Вход</a>
        <a href="/register" class="badge nav-link">Регистрация</a>
      <?php endif; ?>
    </div>

    <div id="flash-area">
      <?php if (is_array($_flash)): ?>
        <div class="flash <?= ($_flash['type'] === 'ok' ? 'ok' : 'err') ?>">
          <?= e((string)$_flash['message']) ?>
        </div>
      <?php endif; ?>
    </div>

    <main id="app" data-path="<?= e((string)($_SERVER['REQUEST_URI'] ?? '/')) ?>">
      <?php require $viewFile; ?>
    </main>

    <div class="footer">
      <div>Build: Milestone 2 (карточка существа + Battle MVP 1v1). Debug: <?= e((string)(App\Support\Env::get('APP_DEBUG','0'))) ?></div>
    </div>
  </div>

  <script src="/assets/app.js?v=<?= e((string)$__jsVer) ?>" defer></script>
</body>
</html>
