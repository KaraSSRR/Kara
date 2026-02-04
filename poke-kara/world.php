<?php
declare(strict_types=1);

// Настройки cookie для сессии
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax', // Можно 'Strict', если устраивает
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Глобальный конфиг
$patch_project = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/');
$patch_global  = $patch_project . '/inc/conf/global.php';

if (!is_file($patch_global)) {
    exit('The problem with the connection files.');
}
require_once $patch_global;

// Проверки сессии и cookie
if (!isset($_SESSION['id'], $_COOKIE['hash'])) {
    header('Location: ./');
    exit;
}

// Жёстко приводим id к int
$userId = (int)$_SESSION['id'];

// Безопасная выборка пользователя
$user = null;

// Локальный (сессионный) микрокеш профиля, чтобы не дергать БД при частых перезагрузках вкладки/страницы.
// TTL маленький: не ломает актуальность, но снижает нагрузку.
if (isset($_SESSION['_user_cache']) && is_array($_SESSION['_user_cache'])) {
    $c = $_SESSION['_user_cache'];
    if (
        isset($c['ts'], $c['id'], $c['hash'], $c['row'])
        && (int)$c['id'] === $userId
        && (string)$c['hash'] === (string)$_COOKIE['hash']
        && (time() - (int)$c['ts']) <= 3
        && is_array($c['row'])
    ) {
        $user = $c['row'];
    }
}
if ($stmt = $mysqli->prepare(
    'SELECT `id`,`login`,`user_group`,`rang`,`hash`,`location`,`sound`,`lang`,`opros`,`HotClick`
     FROM `users` WHERE `id` = ? LIMIT 1'
)) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    // Обновляем микрокеш профиля в сессии
    if ($user) {
        $_SESSION['_user_cache'] = [
            'ts'   => time(),
            'id'   => $userId,
            'hash' => (string)$_COOKIE['hash'],
            'row'  => $user,
        ];
    }
}

if (!$user) {
    header('Location: ./');
    exit;
}

// Сравнение хеша из cookie с БД — безопасно
$cookieHash = (string)($_COOKIE['hash'] ?? '');
$dbHash     = (string)$user['hash'];
if (!hash_equals($dbHash, $cookieHash)) {
    header('Location: ./');
    exit;
}

// Хелперы для безопасного вывода
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
// Для безопасного включения в JS.
function j($v): string {
    return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Безопасные производные значения
$userSound   = (int)$user['sound'];
$userHotClick= (int)$user['HotClick'];
$userGroup   = e((string)$user['user_group']);
$userLogin   = e((string)$user['login']);
$userLang    = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$user['lang']); // для путей
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Poke Kara - Мир</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no">
    <meta name="theme-color" content="#9d4edd">
    <meta name="color-scheme" content="light dark">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Производительность: hint'ы -->
    <link rel="preconnect" href="https://cdn.socket.io" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <!-- Стили (оставляем структуру, но лучше перевести на билд и версии вместо microtime) -->
    <link rel="stylesheet" href="/css/world.css?<?=e((string)$versionGame);?>">
    <link rel="stylesheet" href="/css/makasimka.css?<?=e((string)$versionGame);?>">
    <link rel="stylesheet" href="/css/emoji.css?<?=e((string)$versionGame);?>">
    <link href="fontawesome/css/all.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/tipped/tipped.css?<?=e((string)$versionGame);?>"/>
    <link rel="stylesheet" href="/css/adaptive.css?<?=e((string)$versionGame);?>">
    <link rel="stylesheet" href="/css/buffs-dropdown.css?<?=e((string)$versionGame);?>">
    <link rel="stylesheet" href="/css/battle.v4.css?<?=e((string)$versionGame);?>">

    <style>
    .BuffsDropdown-list {
    transition: all 0.2s ease-in-out;
    min-width: 180px;
    max-width: 300px;
    max-height: 280px;
    font-size: 0.95rem;
    padding: 8px 0;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 8px 24px rgba(109, 95, 160, 0.15);
    backdrop-filter: blur(10px);
    overflow-y: auto;
    scrollbar-width: thin;
}

.BuffsDropdown-list:not(.open) {
    opacity: 0;
    pointer-events: none;
    transform: translateY(-8px) scale(0.98);
}

.BuffsDropdown-list.open {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0) scale(1);
}

.BuffsDropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    font-size: 0.95rem;
    color: #5d4e96;
    cursor: pointer;
    border-bottom: 1px solid rgba(242, 234, 253, 0.6);
    min-height: 32px;
    line-height: 1.3;
    background: none;
    transition: background 0.15s, transform 0.1s;
    border-radius: 6px;
    margin: 2px 6px;
}

.BuffsDropdown-item:hover {
    background: rgba(163, 155, 199, 0.08);
    transform: translateX(2px);
}

.BuffsDropdown-item:active {
    background: rgba(163, 155, 199, 0.15);
}

.BuffsDropdown-item:last-child { 
    border-bottom: none; 
}

.BuffsDropdown-item-title {
    color: #8a7db8;
    font-size: 0.9rem;
    font-weight: 400;
    margin-left: 4px;
    font-style: italic;
    flex: 1 1 auto;
    white-space: normal;
}

.BuffsDropdown-item.baf-icon {
    background-position: 16px center;
    background-repeat: no-repeat;
    background-size: 24px 24px;
    padding-left: 48px;
    min-height: 32px;
}

.BuffsDropdown-item img {
    width: 26px; 
    height: 26px; 
    border-radius: 8px; 
    object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.BuffsDropdown-empty { 
    color: #a49ac2; 
    text-align: center; 
    padding: 16px 0; 
    font-size: 0.95rem;
    font-style: italic;
}

.BuffsDropdown-btn { 
    min-width: 36px; 
    padding: 6px 12px; 
    font-size: 1rem; 
    border-radius: 10px; 
    gap: 8px;
    background: rgba(163, 155, 199, 0.15);
    transition: background 0.2s, transform 0.1s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.BuffsDropdown-btn:hover {
    background: rgba(163, 155, 199, 0.25);
    transform: translateY(-1px);
}

.BuffsDropdown-btn i { 
    font-size: 1.2rem; 
}

.BuffsDropdown-count { 
    font-size: 0.85rem; 
    line-height: 18px; 
    height: 18px;
    padding: 0 8px; 
    min-width: 18px;
    text-align: center;
    border-radius: 9px;
    background: #7b68c5;
    color: white;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 700px) {
    .BuffsDropdown-list {
        left: 50%; 
        transform: translateX(-50%); 
        max-height: 50vh;
        width: 90%;
        max-width: 320px;
    }
    
    .BuffsDropdown-list:not(.open) {
        transform: translateX(-50%) translateY(-8px) scale(0.98);
    }
    
    .BuffsDropdown-list.open {
        transform: translateX(-50%) translateY(0) scale(1);
    }
}
    </style>
</head>
<body>
<?php if($user['opros'] == 1): ?>
    <div class="Opros">
        <div class="Block">
            <span>Администрация просит Вас принять участие в опросе. Напишите, что бы Вы хотели видеть в игре, что Вам нравится в проекте. Опишите как можно его улучшить, чтобы желание играть возросло. Опрос анонимный, администрация увидит только Ваши пожелания.</span>
            <textarea id="opros" placeholder="Писать сюда..."></textarea>
            <div class="Button add" onclick="opros()">Отправить</div>
            <div class="Button" onclick="oprosper()">Скрыть</div>
        </div>
    </div>
<?php endif; ?>

<div class="GlassModalBg">
    <div class="GlassModal">
        <img src="/img/poke-welcome.png" alt="Добро пожаловать" class="GlassModalImage">
        <div class="GlassModalTitle">Добро пожаловать!</div>
        <button class="GlassModalButton" onclick="startGame()">Войти в мир</button>
    </div>
</div>

<div id="locationPreloader" style="display:none;">
    <div class="preloaderGlass">
        <div class="preloaderGlassSpinner"></div>
        <div class="preloaderGlassText">Загрузка мира...</div>
    </div>
</div>
<audio id="battleAudio"><source src="battleStart.mp3" type="audio/mp3"></audio>

<div class="BlockOtherContent" style="display: none;">
    <div class="DivNotifyBlock"></div>
</div>
<div class="LittleModal" style="display: none;"></div>
<div class="tooltip" id="mainTooltip" style="display: none;"></div>
<div class="tooltip-block" style="display: none;"></div>
<div class="mudol" style="display: none;"></div>
<div class="BigModal" style="display: none;"></div>
<div class="inform" style="display: none;"></div>
<div class="CraftModal" style="top: 100px; right: 80px; display: none;"></div>
<div class="invaiteModal" style="display: none;"></div>
<div class="DivNotification"></div>

<div class="TopWorldMobile">
    <div class="Left" onclick="$('.TopMenu').toggle();"><i class="fa fa-bars"></i></div>
    <div class="Center">
        <span class="Time" id="map_time">03:56</span>
    </div>
    <div class="RightGroup">
        <div class="Right" onclick="swapTagMobile(1);">
            <i class="fa fa-comment-dots"></i>
        </div>
        <div class="el_wild Button NoActive" onclick="setHunt(this)">
            <i class="fas fa-paw"></i>
        </div>
    </div>
</div>

<div class="TopMenu">
    <div class="LeftMenu">
        <div class="BuffsDropdown">
            <button class="BuffsDropdown-btn" id="BuffsDropdownBtn" title="Ваши активные эффекты">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1.35em" height="1.35em" fill="currentColor" aria-hidden="true">
  <path d="M12 2.5l3.09 6.26L22 9.28l-5 4.87L18.18 21 12 17.27 5.82 21 7 14.15l-5-4.87 6.91-1.02z"/>
</svg>
  <span class="BuffsDropdown-count" id="BuffsCount">0</span>
</button>
            <div class="BuffsDropdown-list" id="BuffsDropdownList">
                <!-- Бафы будут динамически -->
            </div>
        </div>
    </div>
    <div class="MidMenu"></div>
    <div class="RightMenu">
        <div class="Buttons">
            <div class="Button NoActive gift-inbox" onclick="showGiftInboxPanel()" style="position:relative;cursor:pointer;">
                <i class="fa fa-gift"></i>
                <span id="giftInboxCount" style="
                    position:absolute;
                    top:-6px; 
                    right:-9px; 
                    background:#c00; 
                    color:#fff; 
                    border-radius:50%; 
                    font-size:12px; 
                    min-width:18px; 
                    padding:2px 5px; 
                    text-align:center;
                    display:none;"></span>
            </div>
            <div class="Button NoActive el_teleport" onclick="goLocationTelep(3)">
                <i class="fas fa-portal-enter"></i>
            </div>
            <div class="Button NoActive notif" onclick="showNotify(this);">
                <i class="fas fa-bell"></i>
                <span class="ntUs">
                    <div id="countNotif" style="display: none;">0</div>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="loadWorld"></div>
<div id="window_games" class="DivWorld">
    <div class="elf"></div>
    <div class="DivMap">
        <div class="Left">
            <div class="Name">Доступные локации</div>
            <div class="Steps"></div>
        </div>
        <div class="Center">
            <div class="ImageLoc">
                <div class="About">
                    <div class="Text"></div>
                </div>
                <div class="Name">
                    <div onclick="$('.ImageLoc .About').toggle();"></div>
                </div>
            </div>
        </div>
        <div class="Right">
            <div class="Name">Доступные персонажи</div>
            <div class="Steps"></div>
        </div>
    </div>
</div>

<div class="ChatBox mobile">
    <div class="DivChat">
        <div class="Chat">
            <div class="CategoryRow">
                <div class="Category"></div>
                <input class="__chat_scrolls simple-tooltip" title="Чат автоматически вниз" type="checkbox" checked/>
            </div>
            <div class="Talk">
                <div class="Message-Block Channel_8" data-channel="8"></div>
                <div class="Message-Block Channel_9" data-channel="9"></div>
                <div class="Message-Block Channel_21" data-channel="21"></div>
                <div class="Message-Block Channel_7" data-channel="7"></div>
                <div class="Message-Block Channel_10" data-channel="10"></div>
                <div class="Message-Block Channel_1" data-channel="1"></div>
                <div class="Message-Block Channel_0 active" data-channel="0"></div>
            </div>
        </div>
        <div class="Trainers" style="display:none;">
            <div id="window_games_mobile"></div>
            <div class="Close"><i class="fas fa-times" onclick="$('.ChatBox.mobile .Trainers').hide();"></i></div>
            <div class="Name"></div>
            <div class="TrainerList"></div>
        </div>
    </div>
    <div class="DivBottomGame mobile">
        <div class="Inputs">
            <form onsubmit="ClassChat._send();return false;">
                <div class="User">
                    <input id="chat_user_to" placeholder="Имя тренера..." type="text">
                    <div class="Button" onclick="$(this).prev().val('');"><i class="fas fa-times"></i></div>
                </div>
                <div class="Text" id="messageFieldBox">
                    <input id="chat_send" placeholder="Сообщение..." type="text">
                    <div onclick="ClassChat._send();return false;" class="Button"><i class="fas fa-chevron-right"></i></div>
                </div>
                <input type="submit" style="display: none">
            </form>
        </div>
        <div class="MiniButtons">
            <div class="UserList Button">
                <i class="fas fa-users" onclick="$('.ChatBox.mobile .Trainers').toggle();"></i>
            </div>
        </div>
    </div>
</div>

<div class="ChatBox">
    <div class="DivChat">
        <div class="Chat">
            <input class="__chat_scrolls simple-tooltip" title="Чат автоматически вниз" type="checkbox" checked/>
            <div class="Category"></div>
            <div class="Talk">
                <div class="Message-Block Channel_8" data-channel='8'></div>
                <div class="Message-Block Channel_9" data-channel='9'></div>
                <div class="Message-Block Channel_21" data-channel='21'></div>
                <div class="Message-Block Channel_7" data-channel='7'></div>
                <div class="Message-Block Channel_10" data-channel='10'></div>
                <div class="Message-Block Channel_1" data-channel='1'></div>
                <div class="Message-Block Channel_0 active" data-channel='0'></div>
            </div>
        </div>
        <div class="Trainers">
            <div id="window_games_mobile"></div>
            <div class="Close"><i class="fas fa-times" onclick="$('.DivChat .Trainers').toggle();"></i></div>
            <div class="Name"></div>
            <div class="TrainerList"></div>
        </div>
    </div>
</div>
<div class="DivBottomGame">
    <div class="Inputs">
        <form onsubmit="ClassChat._send_desktop();return false;">
            <div class="User">
                <input id="chat_user_to_desktop" placeholder="Имя тренера..." type="text">
                <div class="Button" onclick="$(this).prev().val('');"><i class="fas fa-times"></i></div>
            </div>
            <div class="Text" id="messageFieldBoxDesktop">
                <input id="chat_send_desktop" placeholder="Сообщение..." type="text">
                <div onclick="ClassChat._send_desktop();return false;" class="Button"><i class="fas fa-chevron-right"></i></div>
            </div>
            <input type="submit" style="display: none">
        </form>
    </div>
    <div class="MiniButtons">
        <div class="UserList Button"><i class="fas fa-users" onclick="$('.DivChat .Trainers').toggle();"></i></div>
    </div>
    <div class="DivRightButtons">
        <div class="Wrap"></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/js/device.js?<?=$versionGame;?>"></script>
<script src="/js/jquery/draggabilly.js?<?=$versionGame;?>" type="text/javascript"></script>
<script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
<script src="/js/socket.js?<?=$versionGame;?>" type="text/javascript"></script>
<script src="/js/emoji.js?<?=$versionGame;?>" type="text/javascript"></script>
<script src="/js/world.js?<?=microtime(true);?>" type="text/javascript"></script>
<script src="/js/makasimka.js?<?=microtime(true);?>" type="text/javascript"></script>
<script src="/js/battle-v5-patch.js?<?=microtime(true);?>" type="text/javascript"></script>
<script src="/js/antibot.js?<?=microtime(true);?>" type="text/javascript"></script>
<script src="/js/test.js?<?=microtime(true);?>" type="text/javascript"></script>

<script src="/js/pagination.js?<?=microtime(true);?>" type="text/javascript"></script>
<script src="/js/npc-guide-v2.js?<?=microtime(true);?>" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dragabilly@2/dist/dragabilly.pkgd.min.js"></script>
<script src="/js/lang/<?=$user['lang'];?>.js?<?=$versionGame;?>" type="text/javascript"></script>
<script src="/js/admin-panel.js"></script>
<script src="/js/tipped/tipped.js?<?=$versionGame;?>" type="text/javascript"></script>
<script src="/js/moment/moment.js?<?=$versionGame;?>" type="text/javascript"></script>
<script>
// Безопасная подстановка в JS (значения подготовлены в PHP функцией j())
  const VERSION   = <?= j($versionGame ?? '') ?>;
  const GROUP_USER= <?= j($userGroup ?? '') ?>;
  const ID_USER   = <?= j((string)$userId) ?>;
  const LOGIN     = <?= j($userLogin ?? '') ?>;
  const REITS     = <?= j($reitsGlobal ?? '') ?>;
  const REITS_TEXT= 'Рады что ты снова с нами!';

  let ClassInfo, ClassChat;
  const UserAudio = <?= json_encode($userSound, JSON_THROW_ON_ERROR) ?>;
  const HotClick  = <?= json_encode($userHotClick, JSON_THROW_ON_ERROR) ?>;

  $(function(){
      Game.init();

      ClassChat = new GameChat({
          id: ID_USER,
          login: LOGIN,
          group: GROUP_USER
      });
      ClassInfo = new GameInfo({
          id: ID_USER,
          login: LOGIN,
          group: GROUP_USER
      });
  });

  // Подсказки
  $(document).ready(function() {
      Tipped.create('.simple-tooltip');
  });


function bindBuffsDropdown() {
  const btn  = document.getElementById('BuffsDropdownBtn');
  const list = document.getElementById('BuffsDropdownList');
  if (!btn || !list) return;

  // ARIA и роли
  btn.setAttribute('aria-haspopup', 'menu');
  btn.setAttribute('aria-expanded', 'false');
  btn.setAttribute('aria-controls', 'BuffsDropdownList');

  list.setAttribute('role', 'menu');
  list.setAttribute('aria-hidden', 'true');

  // Фокусируемые элементы меню
  const focusables = () => Array.from(list.querySelectorAll('.BuffsDropdown-item')).map(el => {
    el.setAttribute('role', 'menuitem');
    el.setAttribute('tabindex', '-1');
    return el;
  });

  let isOpen = false;

  function open() {
    if (isOpen) return;
    isOpen = true;
    list.classList.add('open');
    list.setAttribute('aria-hidden', 'false');
    btn.setAttribute('aria-expanded', 'true');
    const items = focusables();
    if (items[0]) items[0].setAttribute('tabindex', '0'), items[0].focus();
    document.addEventListener('click', onDocClick, { once: true });
    document.addEventListener('keydown', onKeydown);
  }

  function close() {
    if (!isOpen) return;
    isOpen = false;
    list.classList.remove('open');
    list.setAttribute('aria-hidden', 'true');
    btn.setAttribute('aria-expanded', 'false');
    focusables().forEach(el => el.setAttribute('tabindex', '-1'));
    document.removeEventListener('keydown', onKeydown);
    btn.focus();
  }

  function toggle(e) {
    e.stopPropagation();
    isOpen ? close() : open();
  }

  function onDocClick() {
    close();
  }

  function onKeydown(e) {
    const items = focusables();
    const current = document.activeElement;
    const idx = items.indexOf(current);

    if (e.key === 'Escape') { e.preventDefault(); close(); return; }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      const next = items[Math.min(idx + 1, items.length - 1)] || items[0];
      if (next) next.setAttribute('tabindex', '0'), next.focus();
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      const prev = items[Math.max(idx - 1, 0)] || items[items.length - 1];
      if (prev) prev.setAttribute('tabindex', '0'), prev.focus();
    }
    if (e.key === 'Home') { e.preventDefault(); if (items[0]) items[0].focus(); }
    if (e.key === 'End')  { e.preventDefault(); if (items.at(-1)) items.at(-1).focus(); }
    if (e.key === 'Enter' || e.key === ' ') {
      // Тут можно повесить действие по клику на айтем
      if (current && current.classList.contains('BuffsDropdown-item')) {
        current.click();
        close();
      }
    }
  }

  // Слушатели
  btn.addEventListener('click', toggle);
  btn.addEventListener('keydown', (e) => {
    if ((e.key === 'ArrowDown' || e.key === ' ') && !isOpen) { e.preventDefault(); open(); }
  });
  list.addEventListener('click', (e) => e.stopPropagation()); // клики внутри не закрывают
}

// Инициализация
document.addEventListener('DOMContentLoaded', bindBuffsDropdown);


</script>
</body>
</html>
