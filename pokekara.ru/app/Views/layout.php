<?php
declare(strict_types=1);

use function App\Support\e;

/** @var string $title */
/** @var string $_csrf */
/** @var array|null $_flash */
/** @var int|null $_uid */

$__root = dirname(__DIR__, 2);
$__cssVer = @filemtime($__root . '/public/assets/app.css') ?: 1;
$__jsVer  = @filemtime($__root . '/public/assets/app.js') ?: 1;

$__uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$__path = (string)(parse_url($__uri, PHP_URL_PATH) ?: '/');
$__path = rtrim($__path, '/') ?: '/';

$__isGame = (bool)($_uid && $__path === '/locations');
?>
<!doctype html>
<html lang="ru" data-loading="0">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f2f4f7">
  <meta name="csrf-token" content="<?= e($_csrf) ?>">
  <title><?= e($title ?? 'RPG v2') ?></title>
  <link rel="stylesheet" href="/assets/app.css?v=<?= e((string)$__cssVer) ?>">
</head>
<body data-shell="<?= $__isGame ? 'game' : 'vitrine' ?>">
  <div id="topbar" aria-hidden="true"></div>

  <?php if ($__isGame): ?>
    <div class="game-shell">
      <aside class="dock" aria-label="Навигация">
        <a href="/" class="dock-brand" title="Витрина">RPG</a>
        <div class="dock-group">
          <a class="dock-btn" href="/me" data-modal="1" title="Профиль">
            <span class="dock-ic">👤</span>
          </a>
          <a class="dock-btn" href="/creatures" data-modal="1" title="Команда">
            <span class="dock-ic">🧬</span>
          </a>
          <a class="dock-btn" href="/inventory" data-modal="1" title="Инвентарь">
            <span class="dock-ic">🎒</span>
          </a>
          <a class="dock-btn" href="/battle" data-modal="1" data-modal-kind="battle" title="Бой">
            <span class="dock-ic">⚔️</span>
          </a>
          <a class="dock-btn" href="/locations/panel" data-modal="1" title="Путешествие">
            <span class="dock-ic">🗺️</span>
          </a>
        </div>

        <div class="dock-spacer"></div>

        <form method="post" action="/logout" class="dock-logout" data-no-spa="1">
          <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
          <button class="dock-btn danger" type="submit" title="Выйти">
            <span class="dock-ic">⎋</span>
          </button>
        </form>
      </aside>

      <main class="stage" aria-label="Мир">
        <div id="flash-area" class="flash-area">
          <?php if (is_array($_flash)): ?>
            <div class="flash <?= ($_flash['type'] === 'ok' ? 'ok' : 'err') ?>">
              <?= e((string)$_flash['message']) ?>
            </div>
          <?php endif; ?>
        </div>

        <div id="app" data-path="<?= e($__uri) ?>">
          <?php require $viewFile; ?>
        </div>
      </main>

      <aside class="chat" aria-label="Чат">
        <div id="chat" class="chat-card" data-csrf="<?= e($_csrf) ?>">
          <div class="chat-head">
            <div class="chat-title">
              <span class="dot"></span>
              <span>Чат</span>
            </div>
            <div class="chat-tabs" role="tablist">
              <button type="button" class="chat-tab active" data-channel="global">Мир</button>
              <button type="button" class="chat-tab" data-channel="trade">Торговля</button>
              <button type="button" class="chat-tab" data-channel="clan" disabled>Клан</button>
              <button type="button" class="chat-tab" data-channel="dm">ЛС</button>
            </div>
          </div>

          <div class="chat-meta" id="chat-meta" hidden>
            <div class="chat-row">
              <label class="chat-label">Кому</label>
              <input class="input" id="chat-to" name="to_username" placeholder="ник" autocomplete="off">
            </div>
            <div class="chat-row">
              <label class="chat-label">Диалоги</label>
              <div class="chat-peers" id="chat-peers"></div>
              <div class="chat-hint muted">Нажми на диалог, или введи ник и отправь первое сообщение.</div>
            </div>
          </div>

          <div class="chat-body" id="chat-messages" aria-live="polite"></div>

          <form class="chat-form" id="chat-form" data-no-spa="1">
            <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
            <input type="hidden" name="channel" value="global" id="chat-channel">
            <input type="hidden" name="peer_id" value="" id="chat-peer-id">

            <input class="input" id="chat-input" name="message" placeholder="Сообщение..." autocomplete="off" maxlength="500">
            <button class="btn primary" type="submit">Отправить</button>
          </form>

          <div class="chat-footnote muted" id="chat-footnote">
            Торговля: лимиты и антифлуд. ЛС: введите ник получателя.
          </div>
        </div>
      </aside>
    </div>

    <div id="modal-root" class="modal-root" aria-hidden="true">
      <div class="modal-backdrop" data-modal-close="1"></div>
      <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
          <div class="modal-title" id="modal-title">...</div>
          <button class="modal-close" type="button" data-modal-close="1" aria-label="Закрыть">✕</button>
        </div>
        <div class="modal-content" id="modal-content"></div>
      </div>
    </div>
  <?php else: ?>
    <div class="container vitrine">
      <div class="nav">
        <div class="brand"><a href="/" class="brand-link">RPG v2</a></div>
        <div class="muted">|</div>
        <a href="/" class="badge nav-link">Витрина</a>
        <?php if ($_uid): ?>
          <a href="/locations" class="badge nav-link">Играть</a>
          <form method="post" action="/logout" style="margin:0" data-no-spa="1">
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

      <main id="app" data-path="<?= e($__uri) ?>">
        <?php require $viewFile; ?>
      </main>

      <div class="footer">
        <div>Build: Milestone 2 (SPA + Showdown-like creature model + Battle MVP 1v1 + Chat). Debug: <?= e((string)(App\Support\Env::get('APP_DEBUG','0'))) ?></div>
      </div>
    </div>
  <?php endif; ?>

  <script src="/assets/app.js?v=<?= e((string)$__jsVer) ?>" defer></script>
</body>
</html>
