<?php
class Index {

    private $mysqli;

    private $news = '';
    private $sidebar = '';
    private $auth = '';

    public $online = 0;
    public $events = '';

    public $userRatings = array(
        'pvp' => '',
        'pokedex' => '',
        'shinedex' => '',
        'clan' => ''
    );

    public function __construct(mysqli $mysqli) {
        $this->mysqli = $mysqli;

        // Новости и блоки драздо (оставлено как было)
        $page = (isset($_GET['page']) ? clearInt(($_GET['page']-1)) : 0);
        $start = abs(escapeMe($page)*10);
        $newsQuery = $mysqli->query("SELECT * FROM `news` ORDER BY `id` DESC LIMIT ".$start.",10");

        $drazdo = $mysqli->query('SELECT * FROM `drazdo` WHERE `date` = 2 ORDER BY `id` DESC LIMIT 10');
        while($drazdo1 = $drazdo->fetch_assoc()) {
            $user = $mysqli->query("SELECT * FROM `users` WHERE id = ".$drazdo1['user'])->fetch_assoc();
            $drazdo12 .= '<div class="trnr"><i class="fas fa-angle-double-right"></i> '.$user['login'].' <small>'.$drazdo1['prize'].'</small></div>';
        }
        $drazdo_2 = $mysqli->query('SELECT * FROM `drazdo` WHERE `date` = 1 ORDER BY `id` DESC LIMIT 10');
        while($drazdo1_2 = $drazdo_2->fetch_assoc()) {
            $user = $mysqli->query("SELECT * FROM `users` WHERE id = ".$drazdo1_2['user'])->fetch_assoc();
            $drazdo12_2 .= '<div class="trnr"><i class="fas fa-angle-double-right"></i> '.$user['login'].' <small>'.$drazdo1_2['prize'].'</small></div>';
        }

        // --- Новый адаптированный вывод новостей с изображением справа ---
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $newsPerPage = 5;
        $start = ($page - 1) * $newsPerPage;
        $totalNews = $mysqli->query("SELECT COUNT(*) as total FROM `news`")->fetch_assoc()['total'];
        $totalPages = ceil($totalNews / $newsPerPage);
        $newsQuery = $mysqli->query("SELECT * FROM `news` ORDER BY `id` DESC LIMIT $start, $newsPerPage");

        $this->news = '';

        while ($news = $newsQuery->fetch_assoc()) {
            $authorID = (int)$news['author'];
            $authorData = $mysqli->query("SELECT `id`, `login`, `user_group` FROM `users` WHERE `id` = $authorID")->fetch_assoc();
            $authorAvatar = file_exists("img/avatars/mini/".$authorData['id'].".png")
                ? "img/avatars/mini/".$authorData['id'].".png"
                : "img/avatars/mini/6.png";

            $dateFormatted = date('d F Y', strtotime($news['date']));
            $monthReplace = [
                "January" => "января", "February" => "февраля", "March" => "марта", "April" => "апреля",
                "May" => "мая", "June" => "июня", "July" => "июля", "August" => "августа",
                "September" => "сентября", "October" => "октября", "November" => "ноября", "December" => "декабря"
            ];
            $dateFormatted = strtr($dateFormatted, $monthReplace);

            // Реакция пользователя
            $userId = (int)($_SESSION['id'] ?? 0);
            $newsId = (int)$news['id'];
            $activeReaction = '';
            if ($userId) {
                $reactionQuery = $mysqli->query("SELECT reaction FROM news_reactions WHERE user_id = $userId AND news_id = $newsId");
                if ($reactionRow = $reactionQuery->fetch_assoc()) {
                    $activeReaction = $reactionRow['reaction'];
                }
            }

            $reactions = ['like' => '❤️', 'love' => '😍', 'haha' => '😄', 'sad' => '😢'];
            $reactionButtons = '';
            foreach ($reactions as $key => $emoji) {
                $count = (int)($news[$key] ?? 0);
                $activeClass = ($activeReaction === $key) ? ' active' : '';
                $reactionButtons .= "<button class='reaction-btn$activeClass' onclick=\"Index.news.reactNews($newsId, '$key')\">$emoji <span id='count-{$key}-$newsId'>$count</span></button> ";
            }

            $imgHtml = '';
            if (!empty($news['img'])) {
                $imgHtml = '<div class="timeline-item-img"><img src="'.htmlspecialchars($news['img']).'" alt="news-img"></div>';
            }

            $this->news .= '
            <section class="timeline">
              <div class="row">
                <div class="col-12 col-sm-12 timeline-wrapper">
                  <div class="timeline-item">
                    <div class="news-header">
                      <img src="'.$authorAvatar.'" alt="Аватар" class="author-avatar">
                      <div class="author-info">
                        <span class="author-name u-'.$authorData['user_group'].'">'.$authorData['login'].'</span>
                      </div>
                    </div>
                    <div class="timeline-item-flex">
                      <div class="timeline-item-content">
                        <div class="timeline-item-description">'.$news['text'].'</div>
                      </div>
                      '.$imgHtml.'
                    </div>
                    <div class="news-footer">
                      <span class="timeline-item-date">'.$dateFormatted.'</span>
                      <div class="news-reactions" id="reactions-'.$newsId.'">
                        '.$reactionButtons.'
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>';
        }

        // Навигация
        $this->news .= '<div class="news-pagination d-flex justify-content-between mt-4">';
        if ($page > 1) {
            $this->news .= '<a class="btn btn-outline-primary" href="/?page='.($page - 1).'">← Позже</a>';
        } else {
            $this->news .= '<span></span>';
        }
        if ($page < $totalPages) {
            $this->news .= '<a class="btn btn-outline-primary" href="/?page='.($page + 1).'">Раньше →</a>';
        } else {
            $this->news .= '<span></span>';
        }
        $this->news .= '</div>';

        // Авторизация
        if(!$autorize){
            $this->auth .= '<form onsubmit="sign();return false;" id="autorizeForm" class="form-inline my-2 my-lg-0">
                <input type="text" id="uLogin" placeholder="Логин" class="form-control btn-sm mr-sm-1">
                <input type="password" id="uPass" placeholder="Пароль" class="form-control btn-sm mr-sm-1">
                <button class="btn btn-sm btn-outline-success my-2 my-sm-0">Войти</button>
                <button class="btn btn-sm btn btn-outline-primary  my-2 my-sm-0" onclick="newPass()">Забыл пароль</button>
            </form><div class="AuthError" ></div>';
        }else{
            $this->auth .= '
<style>
  .auth-block {
    text-align: center;
    font-family: "Open Sans", sans-serif;
    margin-top: 1rem;
  }

  .auth-username {
    font-size: 1.4rem;
    font-weight: 700;
    color: #138d75;
    margin-bottom: 0.8rem;
  }

  .auth-buttons a {
    margin: 0.3rem;
    min-width: 110px;
  }
</style>

<div class="auth-block">
  <div class="auth-username">👤 '.$user['login'].'</div>
  <div class="auth-buttons">
    <a class="btn btn-success btn-sm" href="//'.DOMAIN.'/world">
      В игру <i class="fas fa-sign-in-alt"></i>
    </a>
    <a class="btn btn-danger btn-sm" href="//'.DOMAIN.'/?route=exit">
      Выйти <i class="fas fa-times-circle"></i>
    </a>
  </div>
</div>';
        }

        // Формируем рейтинги с аватарками
        $users_list_pvp = '';
        $pvp = $mysqli->query("SELECT `login`,`pvp`,`id`,`user_group` FROM `users` WHERE `user_group` != 1 AND `user_group` != 7 ORDER BY `pvp` DESC  LIMIT 10");
        $rank = 1;
        while ($users = $pvp->fetch_assoc()) {
            $avatar = file_exists("img/avatars/mini/".$users['id'].".png")
                ? "/img/avatars/mini/".$users['id'].".png"
                : "/img/avatars/mini/6.png";
            $users_list_pvp .= '
                <li class="rating-player">
                  <span class="rating-rank">'.$rank.'</span>
                  <img class="rating-avatar" src="'.$avatar.'" alt="'.$users['login'].'">
                  <span class="rating-player-info">
                    <span class="rating-nick u-'.$users['user_group'].'">'.$users['login'].'</span>
                    <span class="rating-value">Очки: '.$users['pvp'].'</span>
                  </span>
                </li>';
            $rank++;
        }

        $users_list_pve = '';
        $pve = $mysqli->query("SELECT `login`,`CountKillPok`,`id`,`user_group` FROM `users` WHERE `user_group` != 1 AND `user_group` != 7 ORDER BY `CountKillPok` DESC  LIMIT 10");
        $rank = 1;
        while ($users = $pve->fetch_assoc()) {
            $avatar = file_exists("img/avatars/mini/".$users['id'].".png")
                ? "/img/avatars/mini/".$users['id'].".png"
                : "/img/avatars/mini/6.png";
            $users_list_pve .= '
                <li class="rating-player">
                  <span class="rating-rank">'.$rank.'</span>
                  <img class="rating-avatar" src="'.$avatar.'" alt="'.$users['login'].'">
                  <span class="rating-player-info">
                    <span class="rating-nick u-'.$users['user_group'].'">'.$users['login'].'</span>
                    <span class="rating-value">Очки: '.$users['CountKillPok'].'</span>
                  </span>
                </li>';
            $rank++;
        }

        $users_list_pokNormal = '';
        $pokNormal = $mysqli->query("SELECT `login`,`countNormal`,`id`,`user_group` FROM `users` WHERE `user_group` != 1 AND `user_group` != 7 ORDER BY `countNormal` DESC  LIMIT 10");
        $rank = 1;
        while ($users = $pokNormal->fetch_assoc()) {
            $avatar = file_exists("img/avatars/mini/".$users['id'].".png")
                ? "/img/avatars/mini/".$users['id'].".png"
                : "/img/avatars/mini/6.png";
            $users_list_pokNormal .= '
                <li class="rating-player">
                  <span class="rating-rank">'.$rank.'</span>
                  <img class="rating-avatar" src="'.$avatar.'" alt="'.$users['login'].'">
                  <span class="rating-player-info">
                    <span class="rating-nick u-'.$users['user_group'].'">'.$users['login'].'</span>
                    <span class="rating-value">Поймано: '.$users['countNormal'].'</span>
                  </span>
                </li>';
            $rank++;
        }

        $users_list_pokShine = '';
        $pokShine = $mysqli->query("SELECT `login`,`countShine`,`id`,`user_group` FROM `users` WHERE `user_group` != 1 AND `user_group` != 7 ORDER BY `countShine` DESC  LIMIT 10");
        $rank = 1;
        while ($users = $pokShine->fetch_assoc()) {
            $avatar = file_exists("img/avatars/mini/".$users['id'].".png")
                ? "/img/avatars/mini/".$users['id'].".png"
                : "/img/avatars/mini/6.png";
            $users_list_pokShine .= '
                <li class="rating-player">
                  <span class="rating-rank">'.$rank.'</span>
                  <img class="rating-avatar" src="'.$avatar.'" alt="'.$users['login'].'">
                  <span class="rating-player-info">
                    <span class="rating-nick u-'.$users['user_group'].'">'.$users['login'].'</span>
                    <span class="rating-value">Поймано: '.$users['countShine'].'</span>
                  </span>
                </li>';
            $rank++;
        }

     // Онлайн и статистика

$timeOnline = time() - 300;

// Количество пользователей онлайн за последние 5 минут
$resultOnline = $mysqli->query('SELECT COUNT(*) as cnt FROM `users` WHERE `online` >= '.$timeOnline);
$rowOnline = $resultOnline->fetch_assoc();
$this->online = (int)$rowOnline['cnt'];

// Всего пользователей
$resultAllUsers = $mysqli->query('SELECT COUNT(*) as cnt FROM `users`');
$rowAllUsers = $resultAllUsers->fetch_assoc();
$this->alluser = (int)$rowAllUsers['cnt'];

// Всего покемонов
$resultAllPokemon = $mysqli->query('SELECT COUNT(*) as cnt FROM `user_pokemons`');
$rowAllPokemon = $resultAllPokemon->fetch_assoc();
$this->allpokemon = (int)$rowAllPokemon['cnt'];

// Уникальные тренеры за сутки (по уникальным id, кто заходил сегодня)
$startOfDay = strtotime("today"); // начало суток (00:00)
$resultUniqueDay = $mysqli->query("SELECT COUNT(*) as cnt FROM `users` WHERE `online` >= $startOfDay");
$rowUniqueDay = $resultUniqueDay->fetch_assoc();
$this->all = (int)$rowUniqueDay['cnt'];

// Вставка рейтинга с аватарками игроков
$this->news .= '
</div>
<div class="col-md-12"></div>
</div>

</h5>
<!-- Шапка с навигацией -->
<div class="card-header rating-header">
  <ul class="nav nav-tabs card-header-tabs">
    <li class="nav-item">
      <a class="nav-link active" data-toggle="tab" href="#item1" title="Player vs Player">
        <i class="fas fa-crosshairs"></i> PVP
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-toggle="tab" href="#item2" title="Player vs Environment">
        <i class="fas fa-shield-alt"></i> PVE
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-toggle="tab" href="#item3" title="Покедекс">
        <i class="fas fa-book"></i> Покедекс
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-toggle="tab" href="#item4" title="Шайни Покемони">
        <i class="fas fa-star"></i> Шайни
      </a>
    </li>
  </ul>
</div>
<!-- Текстовый контент -->
<div class="card-body tab-content">
  <div class="tab-pane fade show active " id="item1">
    <ul class="list-unstyled rating-list">
      '.$users_list_pvp.'
    </ul>
  </div>
  <div class="tab-pane fade show " id="item2">
    <ul class="list-unstyled rating-list">
      '.$users_list_pve.'
    </ul>
  </div>
  <div class="tab-pane fade" id="item3">
    <ul class="list-unstyled rating-list">
      '.$users_list_pokNormal.'
    </ul>
  </div>
  <div class="tab-pane fade" id="item4">
    <ul class="list-unstyled rating-list">
      '.$users_list_pokShine.'
    </ul>
  </div>
</div>
</div><!-- Конец карточки -->
<div class="other">
  <!-- Карточка с навигацией (в заголовке) -->
  <div class="card">
    </h5>
    <!-- Шапка с навигацией -->
    <!-- Текстовый контент -->
  </div><!-- Конец карточки -->
</div>
<div class="other">
<div class="other">
<div class="stats-card">
  <div class="stats-row">
    <div class="stats-icon stats-online"><i class="fas fa-signal"></i></div>
    <div class="stats-info">
      <div class="stats-title">Тренеров в сети</div>
      <div class="stats-value">'.$this->online.'</div>
    </div>
  </div>
  <div class="stats-row">
    <div class="stats-icon stats-today"><i class="fas fa-calendar-day"></i></div>
    <div class="stats-info">
      <div class="stats-title">Тренеров за сутки</div>
      <div class="stats-value">'.$this->all.'</div>
    </div>
  </div>
  <div class="stats-row">
    <div class="stats-icon stats-users"><i class="fas fa-users"></i></div>
    <div class="stats-info">
      <div class="stats-title">Всего тренеров</div>
      <div class="stats-value">'.$this->alluser.'</div>
    </div>
  </div>
  <div class="stats-row">
    <div class="stats-icon stats-pokemons"><i class="fas fa-dragon"></i></div>
    <div class="stats-info">
      <div class="stats-title">Всего покемонов у тренеров</div>
      <div class="stats-value">'.$this->allpokemon.'</div>
    </div>
  </div>
</div>
<!-- Гид-Бот (Плавающая иконка) -->
<div id="guide-bot" class="guide-bot" onclick="toggleGuideChat()">
  <img src="/img/bot-avatar.png" alt="Гид-Бот" title="Открыть Гид-Бота" />
</div>

<!-- Окно чата Гид-Бота с поддержкой Copilot, пользовательским вводом, анализом команды и мини-рекомендациями -->
<div id="guide-chat" class="guide-chat-window" style="display:none;">
  <div class="chat-header">
    <span>Гид-Бот</span>
    <button class="chat-close-btn" onclick="toggleGuideChat()">&times;</button>
  </div>
  <div class="chat-messages" id="guide-messages"></div>
  <div class="chat-actions" id="guide-actions"></div>
  
  <!-- Пользовательский ввод: вопрос или совет по команде -->
  <form id="guide-user-input-form" class="guide-user-input" onsubmit="return sendGuideUserInput(event);">
    <input
      type="text"
      id="guide-user-input"
      placeholder="Задать свой вопрос или запросить совет по команде..."
      autocomplete="off"
      maxlength="400"
    />
    <button type="submit" title="Отправить вопрос">➤</button>
  </form>
  
  <!-- Блок Team Builder для анализа состава через Copilot -->
  <div id="guide-team-builder" class="guide-team-builder" style="display:none; margin-top:12px;">
    <label for="team-purpose">Цель команды:</label>
    <input type="text" id="team-purpose" placeholder="PvP, турнир, рейд и т.д.">
    <label for="team-pokemon">Твои покемоны (через запятую):</label>
    <input type="text" id="team-pokemon" placeholder="Charizard, Jolteon, Gengar">
    <button type="button" onclick="getCopilotAdvice()">Получить совет</button>
    <div id="copilot-advice" class="copilot-advice"></div>
  </div>
</div>
</div>
</div>
';
        unset($newsQuery);

        // События (оставлено как было)
        $eventsQuery = $mysqli->query("SELECT * FROM `events` LIMIT 5");
        while($events = $eventsQuery->fetch_assoc()){
            $this->events = '<a href="'.$events['href'].'">'.$events['text'].'</a>';
        }
        unset($eventsQuery);
        $this->getUserRatings();
    }

    public function GetInfo() {
        if(isset($_GET['route']) && $_GET['route'] != 'exit'){
            require_once 'pages/'.$_GET['route'].'.php';
        }else{
            print $this->news;
        }
    }

    public function GetInfo1(){
        print $this->auth;
    }

    private function getUserRatings() {
        // Оставлено без изменений, можно адаптировать под новый вывод если нужно
        // ...
    }
}
?>