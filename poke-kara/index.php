<?php
session_start();

/* ~ Global Include ~ */
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $_SERVER['DOCUMENT_ROOT'].'/inc/conf/global.php';

if (!file_exists($patch_global)) {
    die('The problem with the connection files.');
}
require_once($patch_global);

/* ROUTE: logout */
$route = $_GET['route'] ?? '';
$openForgot = ($route === 'forgot');
if ($route === 'exit' || $route === 'exitdouble') {
    unset($_SESSION['id'], $_SESSION['login']);
    header('Location: /');
    exit;
}

/* AUTH */
$autorize = false;
$lang = 'ru';
$user = null;

if (isset($_SESSION['id'])) {
    $autorize = true;
    $uid = (int)$_SESSION['id'];
    $user = $mysqli->query("SELECT `login`,`user_group`,`rang`,`lang` FROM `users` WHERE `id` = ".$uid)->fetch_assoc();
    if (!empty($user['lang'])) $lang = $user['lang'];
}

/* ===========================
   AJAX API (выгрузка данных)
   =========================== */
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');

    $ajax = (string)$_GET['ajax'];

    $json = function($arr){
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        exit;
    };

    $ruDate = function($ts){
        $months = [
            'January'=>'января','February'=>'февраля','March'=>'марта','April'=>'апреля',
            'May'=>'мая','June'=>'июня','July'=>'июля','August'=>'августа',
            'September'=>'сентября','October'=>'октября','November'=>'ноября','December'=>'декабря'
        ];
        $s = date('d F', $ts);
        return strtr($s, $months);
    };

    // --- NEWS helpers: extract title/body from legacy HTML wrappers (__title / __text)
    $newsExtract = function($html){
        $title = '';
        $bodyHtml = $html;

        if (preg_match('~<div class="__title"[^>]*>(.*?)</div>~si', $html, $m)) {
            $title = trim(strip_tags($m[1]));
        }
        if (preg_match('~<div class="__text"[^>]*>(.*?)</div>~si', $html, $m)) {
            $bodyHtml = $m[1]; // only inner content of __text
        }

        // remove auto-inserted preview image (we show it via `img` column in the card preview)
        $bodyHtml = preg_replace('~<img[^>]*class="news-image"[^>]*>\s*~i', '', $bodyHtml);

        return [$title, $bodyHtml];
    };



    /* KPI */
    if ($ajax === 'kpi') {
        $timeOnline = time() - 300;
        $rowOnline = $mysqli->query("SELECT COUNT(*) as cnt FROM `users` WHERE `online` >= ".$timeOnline)->fetch_assoc();
        $online = (int)($rowOnline['cnt'] ?? 0);

        $startOfDay = strtotime('today');
        $rowDay = $mysqli->query("SELECT COUNT(*) as cnt FROM `users` WHERE `online` >= ".$startOfDay)->fetch_assoc();
        $day = (int)($rowDay['cnt'] ?? 0);

        $json(['online'=>$online, 'day'=>$day]);
    }

    /* Resolver картинок стартовой модели по вашему пути */
    if ($ajax === 'model') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) $json(['url'=>'']);

        // ваш точный шаблон:
        $url = '/img/avatars/model/ava/'.$id.'/'.$id.'/'.$id.'a.png';
        $abs = $_SERVER['DOCUMENT_ROOT'].$url;

        if (file_exists($abs)) $json(['url'=>$url]);

        // fallback (если вдруг появятся webp или другой суффикс):
        $alt = [
            '/img/avatars/model/ava/'.$id.'/'.$id.'/'.$id.'a.webp',
            '/img/avatars/model/ava/'.$id.'/'.$id.'/'.$id.'.png',
            '/img/avatars/model/ava/'.$id.'/'.$id.'/'.$id.'.webp',
        ];
        foreach ($alt as $u) {
            $a = $_SERVER['DOCUMENT_ROOT'].$u;
            if (file_exists($a)) $json(['url'=>$u]);
        }

        $json(['url'=>'']);
    }

    /* EVENTS */
    if ($ajax === 'events') {
        $items = [];

        // совместимость: если поля новые уже есть — используем их, иначе старый формат
        $hasNew = false;
        $chk = $mysqli->query("SHOW COLUMNS FROM `events` LIKE 'is_active'");
        if ($chk && $chk->num_rows > 0) $hasNew = true;

        if ($hasNew) {
            $q = $mysqli->query("
                SELECT `id`,`title`,`text`,`href`,`badge`,`img`,`starts_at`,`ends_at`
                FROM `events`
                WHERE `is_active`=1
                  AND (`starts_at` IS NULL OR `starts_at` <= NOW())
                  AND (`ends_at` IS NULL OR `ends_at` >= NOW())
                ORDER BY `sort` DESC, `id` DESC
                LIMIT 10
            ");
            if ($q) {
                while($r = $q->fetch_assoc()){
                    $items[] = [
                        'id'       => (int)$r['id'],
                        'title'    => (string)($r['title'] ?? ''),
                        'text'     => (string)($r['text'] ?? ''),
                        'href'     => (string)($r['href'] ?? ''),
                        'badge'    => (string)($r['badge'] ?? ''),
                        'img'      => (string)($r['img'] ?? ''),
                        'startsAt' => (string)($r['starts_at'] ?? ''),
                        'endsAt'   => (string)($r['ends_at'] ?? '')
                    ];
                }
            }
        } else {
            // старый формат (id, href, text)
            $q = $mysqli->query("SELECT `id`,`href`,`text` FROM `events` ORDER BY `id` DESC LIMIT 10");
            if ($q) {
                while($r = $q->fetch_assoc()){
                    $items[] = [
                        'id'    => (int)$r['id'],
                        'title' => (string)$r['text'],
                        'text'  => (string)$r['text'],
                        'href'  => (string)$r['href'],
                        'badge' => '',
                        'img'   => ''
                    ];
                }
            }
        }

        $json(['items'=>$items]);
    }

    /* NEWS */
    if ($ajax === 'news') {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['perPage']) ? max(1, min(12, (int)$_GET['perPage'])) : 3;
        $offset = ($page-1) * $perPage;

        $rowTotal = $mysqli->query("SELECT COUNT(*) as cnt FROM `news`")->fetch_assoc();
        $total = (int)($rowTotal['cnt'] ?? 0);

        $items = [];
        $q = $mysqli->query("SELECT * FROM `news` ORDER BY `id` DESC LIMIT ".$offset.",".$perPage);
        if ($q) {
            while($r = $q->fetch_assoc()){
                $id = (int)$r['id'];

                $authorRaw = trim((string)($r['author'] ?? ''));
                $authorId = (int)$authorRaw;
                $authorLogin = '';
                $authorGroup = 0;
                $authorAvatar = '/img/avatars/mini/6.png';
                if ($authorId > 0) {
                    $a = $mysqli->query("SELECT `id`,`login`,`user_group` FROM `users` WHERE `id`=".$authorId)->fetch_assoc();
                    if ($a) {
                        $authorLogin = (string)$a['login'];
                        $authorGroup = (int)$a['user_group'];
                        $try = $patch_project.'/img/avatars/mini/'.$authorId.'.png';
                        if (file_exists($try)) $authorAvatar = '/img/avatars/mini/'.$authorId.'.png';
                    }
                } elseif ($authorRaw !== '') {
                    // fallback: if stored as string (e.g. "Администрация"), show it as-is
                    $authorLogin = $authorRaw;
                }

                $ts = 0;
                if (!empty($r['date'])) $ts = is_numeric($r['date']) ? (int)$r['date'] : strtotime($r['date']);
                if ($ts <= 0) $ts = time();

                $fullHtml = (string)($r['text'] ?? '');
                list($titleFromHtml, $bodyHtml) = $newsExtract($fullHtml);

                $plainBody = trim(strip_tags($bodyHtml));
                $plainBody = preg_replace('/\s+/', ' ', $plainBody);

                $title = '';
                if (isset($r['title']) && trim((string)$r['title']) !== '') {
                    $title = (string)$r['title'];
                } elseif ($titleFromHtml !== '') {
                    $title = $titleFromHtml;
                } else {
                    $title = mb_substr($plainBody, 0, 60, 'UTF-8');
                    if (mb_strlen($plainBody, 'UTF-8') > 60) $title .= '…';
                    if ($title === '') $title = 'Обновление';
                }

                $excerpt = mb_substr($plainBody, 0, 120, 'UTF-8');
                if (mb_strlen($plainBody, 'UTF-8') > 120) $excerpt .= '…';
                if ($excerpt === '') $excerpt = 'Подробности внутри.';

                $tag = '';
                if (isset($r['tag']) && trim((string)$r['tag']) !== '') $tag = (string)$r['tag'];
                elseif (isset($r['type']) && trim((string)$r['type']) !== '') $tag = (string)$r['type'];
                else $tag = 'Обновление';

                $img = (string)($r['img'] ?? '');

                $like = (int)($r['like'] ?? 0);
                $love = (int)($r['love'] ?? 0);
                $haha = (int)($r['haha'] ?? 0);
                $sad  = (int)($r['sad']  ?? 0);

                $userReaction = '';
                $uid = (int)($_SESSION['id'] ?? 0);
                if ($uid > 0) {
                    $rq = $mysqli->query("SELECT reaction FROM news_reactions WHERE user_id=".$uid." AND news_id=".$id);
                    if ($rq && ($rr = $rq->fetch_assoc())) $userReaction = (string)$rr['reaction'];
                }

                $items[] = [
                    'id' => $id,
                    'date' => $ruDate($ts),
                    'tag' => $tag,
                    'title' => $title,
                    'excerpt' => $excerpt,                    'img' => $img,
                    'author' => [
                        'id' => $authorId,
                        'login' => $authorLogin,
                        'group' => $authorGroup,
                        'avatar' => $authorAvatar
                    ],
                    'reactions' => [
                        'like'=>$like, 'love'=>$love, 'haha'=>$haha, 'sad'=>$sad,
                        'userReaction'=>$userReaction
                    ]
                ];
            }
        }

        $hasMore = ($offset + $perPage) < $total;

        $json([
            'page'=>$page,
            'perPage'=>$perPage,
            'hasMore'=>$hasMore,
            'items'=>$items
        ]);
    }


    /* NEWS_TEXT (lazy-load full body) */
    if ($ajax === 'news_text') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) $json(['ok'=>false, 'error'=>'bad_id']);

        $q = $mysqli->query("SELECT * FROM `news` WHERE `id`=".$id." LIMIT 1");
        if (!$q || $q->num_rows === 0) $json(['ok'=>false, 'error'=>'not_found']);

        $r = $q->fetch_assoc();
        $fullHtml = (string)($r['text'] ?? '');
        list($titleFromHtml, $bodyHtml) = $newsExtract($fullHtml);

        $json([
            'ok' => true,
            'id' => $id,
            'html' => $bodyHtml,
            'img' => (string)($r['img'] ?? ''),
            'title' => $titleFromHtml
        ]);
    }

    /* RATING */
    if ($ajax === 'rating') {
        $type = (string)($_GET['type'] ?? 'pvp');
        $qStr = trim((string)($_GET['q'] ?? ''));

        $map = [
            'pvp'   => ['field'=>'pvp',         'label'=>'Очки'],
            'pve'   => ['field'=>'CountKillPok','label'=>'Очки'],
            'dex'   => ['field'=>'countNormal', 'label'=>'Поймано'],
            'shiny' => ['field'=>'countShine',  'label'=>'Поймано'],
        ];
        if (!isset($map[$type])) $type = 'pvp';

        $field = $map[$type]['field'];

        $where = " WHERE `user_group` != 1 AND `user_group` != 7 ";
        if ($qStr !== '') {
            $safe = $mysqli->real_escape_string($qStr);
            $where .= " AND `login` LIKE '%".$safe."%' ";
        }

        $sql = "SELECT `id`,`login`,`user_group`,`".$field."` as score FROM `users` ".$where." ORDER BY `".$field."` DESC LIMIT 50";
        $res = $mysqli->query($sql);

        $items = [];
        $place = 1;

        if ($res) {
            while($r = $res->fetch_assoc()){
                $id = (int)$r['id'];

                $avatar = '/img/avatars/mini/6.png';
                $try = $patch_project.'/img/avatars/mini/'.$id.'.png';
                if (file_exists($try)) $avatar = '/img/avatars/mini/'.$id.'.png';

                $items[] = [
                    'place' => $place,
                    'id' => $id,
                    'login' => (string)$r['login'],
                    'group' => (int)$r['user_group'],
                    'score' => (int)$r['score'],
                    'avatar' => $avatar
                ];
                $place++;
            }
        }

        $json(['items'=>$items]);
    }

    $json(['error'=>1, 'message'=>'Unknown ajax']);
}

?>
<!doctype html>
<html lang="<?=$lang?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PokeKara</title>
  <link rel="stylesheet" href="/css/main_new.css?<?=microtime(true)?>">
</head>

<body class="pk">

<div class="topbarWrap">
  <div class="container">
    <div class="topbar">
      <a class="brand" href="/" data-nav="home">
        <span class="logo" aria-hidden="true"></span>
        <div class="brandText">
          <b>PokeKara</b>
          <span>Браузерная онлайн-игра</span>
        </div>
      </a>

      <nav class="nav" aria-label="Навигация">
        <a href="#" data-nav="home" class="active">Главная</a>
        <a href="#" data-nav="rating">Рейтинг</a>
        <a href="#" data-nav="registration">Регистрация</a>
        <a href="/forum">Форум</a>
        <a href="/encyclopedia">Энциклопедия</a>
      </nav>

      <div class="actions">
        <?php if(!$autorize){ ?>
          <a class="btn" href="#" id="btnLogin">Войти</a>
          <a class="btn btnPrimary" href="#" data-nav="registration">Присоединиться</a>
        <?php }else{ ?>
          <a class="btn" href="/world">В игру</a>
          <a class="btn" href="/?route=exit">Выйти</a>
        <?php } ?>

        <button class="btn btnIcon burger" id="burger" aria-label="Меню">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- HOME -->
<main class="view active" id="view-home">
  <section class="hero">
    <div class="container">
      <div class="heroCard">
        <div class="heroInner">
          <div>
            <div class="kicker"><span class="dot"></span> Браузерная онлайн-игра по мотивам Pokémon</div>
            <h1>Добро пожаловать в <span class="grad">PokeKara</span></h1>
            <p class="lead">Собирайте команду, развивайте покемонов, исследуйте локации, участвуйте в ивентах и сражайтесь с другими игроками — всё в браузере.</p>

            <div class="cta">
              <?php if(!$autorize){ ?>
                <a class="btn btnPrimary" href="#" data-nav="registration">Присоединиться к игре</a>
              <?php }else{ ?>
                <a class="btn btnPrimary" href="/world">Перейти в игру</a>
              <?php } ?>

              <button class="btn" id="scrollNews">Смотреть новости</button>

              <div class="chips">
                <span class="chip">Сейчас в игре: <b id="kpiOnline">0</b></span>
                <span class="chip">Игроков за день: <b id="kpiDay">0</b></span>
              </div>
            </div>
          </div>

          <div class="heroMedia" aria-label="Иллюстрация">
  <img class="heroMediaImg" alt="" loading="lazy">
</div>


<script>
  (function () {
    const min = 1, max = 40;
    const n = Math.floor(Math.random() * (max - min + 1)) + min;

    const img = document.querySelector('.heroMedia .heroMediaImg');
    if (!img) return;

    img.src = `/img/_6maJ11yUfU.jpg`; // /img/main/big_logo_1.jpg ... big_logo_40.jpg
  })();
</script>


        </div>
      </div>
    </div>
  </section>

  <section class="section watermark" id="news" data-mark="POKEKARA">
    <div class="container">
      <div class="head">
        <div>
          <h2>Что нового в PokeKara</h2>
          <p>Свежие обновления, ивенты и изменения в игре.</p>
        </div>
        <button class="btn" id="moreNews">Ещё новости</button>
      </div>

      <div class="newsGrid" id="newsGrid"></div>
    </div>
  </section>

  <section class="section watermark" data-mark="">
    <div class="container">
      <div class="head">
        <div>
          <h2>Быстрый доступ</h2>
          <p>Короткие ссылки на ключевые разделы.</p>
        </div>
      </div>

      <div class="newsGrid pkQuick" style="grid-template-columns: repeat(3, minmax(0,1fr));">
        <a class="card pkQuickCard" href="#" data-action="scroll-news">
          <div class="title" style="margin-top:0">События</div>
          <p class="excerpt">Анонсы, обновления и важные изменения.</p>
        </a>
        <a class="card pkQuickCard" href="#" data-nav="registration">
          <div class="title" style="margin-top:0">Новичкам</div>
          <p class="excerpt">Регистрация и выбор стартового образа.</p>
        </a>
        <a class="card pkQuickCard" href="#" data-nav="rating">
          <div class="title" style="margin-top:0">Топ-рейтинг</div>
          <p class="excerpt">Переход к таблице лидеров.</p>
        </a>
      </div>
    </div>
  </section>
</main>

<!-- RATING -->
<main class="view" id="view-rating">
  <section class="section">
    <div class="container">
      <div class="head">
        <div>
          <h2>Рейтинг</h2>
          <p>Выберите категорию и найдите себя в таблице.</p>
        </div>
      </div>

      <div class="ratingWrap">
        <div class="ratingShell">
          <div class="ratingHeader">
            <div class="ratingHeaderLeft">
              <div class="ratingBadge" aria-hidden="true">★</div>
              <div>
                <b>Таблица лидеров</b>
                <span>Фильтр и поиск по нику</span>
              </div>
            </div>

            <div class="segTabs" id="tabs">
              <div class="segTab active" data-tab="pvp">PVP</div>
              <div class="segTab" data-tab="pve">PVE</div>
              <div class="segTab" data-tab="dex">Покедекс</div>
              <div class="segTab" data-tab="shiny">Шайни</div>
            </div>

            <div class="ratingTools">
              <input class="input" id="search" placeholder="Поиск по нику" />
              <button class="btn" id="myRank">Мой ранг</button>
            </div>
          </div>

          <div class="ratingLayout">
            <div class="rankList" id="rankList"></div>
          </div>

        </div>
      </div>

    </div>
  </section>
</main>

<!-- REGISTRATION -->
<main class="view" id="view-registration">
  <section class="section watermark" data-mark="POKEKARA">
    <div class="container">
      <div class="head">
        <div>
          <h2>Регистрация</h2>
          <p>Создайте аккаунт и выберите стартовый образ.</p>
        </div>
      </div>

      <div class="authGrid">
        <!-- ВАЖНО: блок модели помечен pkModelCard и на мобиле будет сверху -->
        <div class="formCard pkModelCard">
          <h2>Стартовый образ</h2>

          <div class="modelBar">
            <b id="modelLabel">Выберите модель</b>
            <div class="modelNav">
              <button class="mini" id="prev" aria-label="Предыдущая модель">←</button>
              <button class="mini" id="next" aria-label="Следующая модель">→</button>
            </div>
          </div>

          <div class="preview" id="modelPreview" aria-label="Превью модели">
            <img id="modelImg" alt="" />
          </div>

          <p class="pkHint">Переключайте стрелками и нажмите “Выбрать этот образ”.</p>
          <button class="btn btnPrimary" id="confirmModel" type="button">Выбрать этот образ</button>
          <div class="fieldErr" id="err-model" aria-live="polite"></div>

          <input type="hidden" id="baseModel" value="0" />
        </div>

        <div class="formCard">
          <h2>Создать аккаунт</h2>

          <div class="field">
            <div class="label">Логин</div>
            <input class="text" id="regLogin" placeholder="Введите логин" maxlength="16" />
            <div class="fieldErr" id="err-regLogin" aria-live="polite"></div>
          </div>
          <div class="field">
            <div class="label">Пароль</div>
            <input class="text" id="regPass" type="password" placeholder="Введите пароль" maxlength="20" />
            <div class="fieldErr" id="err-regPass" aria-live="polite"></div>
          </div>
          <div class="field">
            <div class="label">Повтор пароля</div>
            <input class="text" id="regDblPass" type="password" placeholder="Повторите пароль" maxlength="20" />
            <div class="fieldErr" id="err-regDblPass" aria-live="polite"></div>
          </div>
          <div class="field">
            <div class="label">Email</div>
            <input class="text" id="regMail" type="email" placeholder="name@example.com" />
            <div class="fieldErr" id="err-regMail" aria-live="polite"></div>
          </div>

          <div class="field">
            <div class="label">Реферальный код (опционально)</div>
            <input class="text" id="refCode" placeholder="RFL-XXXXXXX" />
            <div class="fieldErr" id="err-refCode" aria-live="polite"></div>
          </div>

          <div class="field">
            <div class="label">Пол</div>
            <div class="genderRow" id="genderRow">
              <div class="seg active" data-g="m">Мужской</div>
              <div class="seg" data-g="f">Женский</div>
            </div>
          </div>

          <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn btnPrimary" id="doRegister" style="flex:1">Создать аккаунт</button>
            <button class="btn" style="flex:1" data-nav="home">Вернуться</button>
          </div>
        </div>
      </div>

    </div>
  </section>
</main>

<!-- Login modal -->
<div class="pkOverlay" id="pkLoginOverlay"></div>
<div class="pkModal" id="pkLoginModal" role="dialog" aria-modal="true" aria-label="Вход">
  <div class="pkModalHeader">
    <b>Вход в PokeKara</b>
    <button class="btn btnIcon" id="pkCloseLogin" aria-label="Закрыть">✕</button>
  </div>
  <p class="pkModalSub">Введите логин и пароль, чтобы перейти в игру.</p>

  <div class="field">
    <div class="label">Логин</div>
    <input class="text" id="loginName" placeholder="Введите логин" autocomplete="username" />
  </div>
  <div class="field">
    <div class="label">Пароль</div>
    <input class="text" id="loginPass" type="password" placeholder="Введите пароль" autocomplete="current-password" />
  </div>

  <div class="formError" id="loginErr" aria-live="polite"></div>

  <div class="pkModalRow">
    <a class="smallLink" id="pkForgotLink" href="/?route=forgot">Забыли пароль?</a>
    <button class="btn btnPrimary" id="doLogin">Войти</button>
  </div>
</div>

<!-- Forgot password modal -->
<div class="pkOverlay" id="pkForgotOverlay"></div>
<div class="pkModal" id="pkForgotModal" role="dialog" aria-modal="true" aria-label="Восстановление пароля">
  <div class="pkModalHeader">
    <b>Восстановление пароля</b>
    <button class="btn btnIcon" id="pkCloseForgot" aria-label="Закрыть">✕</button>
  </div>
  <p class="pkModalSub">Укажите email, привязанный к аккаунту. Мы отправим инструкцию или новый пароль.</p>

  <div class="field">
    <div class="label">Email</div>
    <input class="text" id="forgotEmail" type="email" placeholder="name@example.com" autocomplete="email" />
    <div class="fieldErr" id="err-forgotEmail" aria-live="polite"></div>
  </div>

  <div class="formError" id="forgotErr" aria-live="polite"></div>

  <div class="pkModalRow">
    <button class="btn" id="backToLogin" type="button">Назад</button>
    <button class="btn btnPrimary" id="doForgot" type="button">Отправить</button>
  </div>
</div>

<!-- Info modal -->
<div class="pkOverlay" id="pkInfoOverlay"></div>
<div class="pkModal" id="pkInfoModal" role="dialog" aria-modal="true" aria-label="Сообщение">
  <div class="pkModalHeader">
    <b id="pkInfoTitle">Сообщение</b>
    <button class="btn btnIcon" id="pkInfoClose" aria-label="Закрыть">✕</button>
  </div>
  <div class="pkModalBody" id="pkInfoBody"></div>
  <div class="pkModalRow">
    <button class="btn btnPrimary" id="pkInfoOk" type="button">Ок</button>
  </div>
</div>

<!-- Toast root -->
<div id="pkToastRoot" aria-live="polite" aria-atomic="true"></div>


<!-- Mobile sheet menu -->
<div class="sheetOverlay" id="sheetOverlay"></div>
<div class="sheet" id="sheet" aria-label="Меню">
  <div class="sheetHeader">
    <b>Меню</b>
    <button class="btn btnIcon" id="closeSheet" aria-label="Закрыть">✕</button>
  </div>
  <a href="#" data-nav="home">Главная</a>
  <a href="#" data-nav="rating">Рейтинг</a>
  <a href="#" data-nav="registration">Регистрация</a>
  <a href="/forum">Форум</a>
  <a href="/encyclopedia">Энциклопедия</a>
</div>

<script>
  window.PK = {
    openForgotOnLoad: <?=($openForgot ? 'true' : 'false')?>,
    apiBase: '/',
    endpoints: {
      login: '/do/sign',
      reactNews: '/do/reactNews.php',
      registration: '/do/registration',
      forgot: '/do/forgot'
    },
    modelPath: function(id){
      return '/img/avatars/model/ava/' + id + '/' + id + '/' + id + 'a.png';
    }
  };
</script>
<script src="/js/index.js?<?=microtime(true)?>"></script>

</body>
</html>
