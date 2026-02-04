<?php
require_once __DIR__ . '/_inc/bootstrap.php';

$topicId = (int)($_GET['id'] ?? 0);
if ($topicId <= 0) redirect(forum_url('/'));

// Тема + категория
$topic = null;
if ($forumInstalled) {
    $stmt = $mysqli->prepare('SELECT t.*, c.title AS cat_title, c.id AS cat_id, c.is_locked AS cat_locked, u.login AS author_login
      FROM forum_topics t
      JOIN forum_categories c ON c.id=t.category_id
      JOIN users u ON u.id=t.user_id
      WHERE t.id=? LIMIT 1');
    $stmt->bind_param('i', $topicId);
    $stmt->execute();
    $rs = $stmt->get_result();
    $topic = $rs ? $rs->fetch_assoc() : null;
    $stmt->close();
}

if (!$topic) {
    http_response_code(404);
    $forumTitle = 'Тема не найдена';
    require_once __DIR__ . '/_inc/layout_top.php';
    echo '<div class="forum-alert danger">Тема не найдена.</div>';
    require_once __DIR__ . '/_inc/layout_bottom.php';
    exit;
}

$topicLocked = !empty($topic['is_locked']);
$catLocked = !empty($topic['cat_locked']);
$canModerate = forum_is_moderator($forumUser, $forumPerms);
$staffOverride = $canModerate && (defined('FORUM_STAFF_CAN_REPLY_LOCKED') ? (bool)FORUM_STAFF_CAN_REPLY_LOCKED : true);
$canReplyHere = $canReply && ((!$topicLocked && !$catLocked) || $staffOverride);
// --- MOD ACTIONS (lock/unlock) ---
$modError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mod_action'])) {
    forum_require_login($forumUserId);
    if (!$canModerate) {
        http_response_code(403);
        die('Forbidden');
    }
    csrf_check();
    $act = (string)($_POST['mod_action'] ?? '');
    if ($act === 'lock') {
        $stmt = $mysqli->prepare('UPDATE forum_topics SET is_locked=1 WHERE id=?');
        $stmt->bind_param('i', $topicId);
        $stmt->execute();
        $stmt->close();
        redirect(forum_url('/topic.php?id='.$topicId));
    } elseif ($act === 'unlock') {
        $stmt = $mysqli->prepare('UPDATE forum_topics SET is_locked=0 WHERE id=?');
        $stmt->bind_param('i', $topicId);
        $stmt->execute();
        $stmt->close();
        redirect(forum_url('/topic.php?id='.$topicId));
    }
}


// --- DELETE POST (moderation) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_post') {
    forum_require_login($forumUserId);
    if (!$canModerate) {
        http_response_code(403);
        die('Forbidden');
    }
    csrf_check();

    $postId = (int)($_POST['post_id'] ?? 0);
    if ($postId <= 0) {
        $modError = 'Некорректный ID сообщения.';
    } else {
        // Убедимся, что сообщение принадлежит этой теме
        $stmt = $mysqli->prepare('SELECT id FROM forum_posts WHERE id=? AND topic_id=? LIMIT 1');
        $stmt->bind_param('ii', $postId, $topicId);
        $stmt->execute();
        $rs = $stmt->get_result();
        $ok = $rs && $rs->fetch_assoc();
        $stmt->close();

        if (!$ok) {
            $modError = 'Сообщение не найдено.';
        } else {
            // Мягкое удаление: заменяем контент на служебную пометку
            $deletedText = '[i]Сообщение удалено модератором.[/i]';
            $now = time();

            $cols = forum_detect_post_content_columns($mysqli);
            $primary = preg_replace('~[^a-zA-Z0-9_]+~', '', (string)($cols['primary'] ?? 'content'));
            $alt = preg_replace('~[^a-zA-Z0-9_]+~', '', (string)($cols['alt'] ?? ''));

            if ($alt !== '') {
                $sqlu = 'UPDATE forum_posts SET `'.$primary.'`=?, `'.$alt.'`=?, updated_at=? WHERE id=? AND topic_id=?';
                $stmt = $mysqli->prepare($sqlu);
                $stmt->bind_param('ssiii', $deletedText, $deletedText, $now, $postId, $topicId);
            } else {
                $sqlu = 'UPDATE forum_posts SET `'.$primary.'`=?, updated_at=? WHERE id=? AND topic_id=?';
                $stmt = $mysqli->prepare($sqlu);
                $stmt->bind_param('siii', $deletedText, $now, $postId, $topicId);
            }
            $stmt->execute();
            $stmt->close();

            // Чистим реакции к удалённому сообщению, чтобы не было «залипаний»
            $stmt = $mysqli->prepare('DELETE FROM forum_reactions WHERE post_id=?');
            $stmt->bind_param('i', $postId);
            $stmt->execute();
            $stmt->close();

            redirect(forum_url('/topic.php?id='.$topicId.'#p'.$postId));
        }
    }
}

// --- REPLY ---
$replyError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    forum_require_login($forumUserId);
    csrf_check();

    if (!$canReplyHere) {
        $replyError = 'Вы не можете отвечать в этой теме.';
    } else {
        $content = forum_clean_text((string)($_POST['content'] ?? ''), FORUM_POST_MAX);
        $replyTo = (int)($_POST['reply_to'] ?? 0);
        if ($replyTo > 0) {
            $stmt = $mysqli->prepare("SELECT id FROM forum_posts WHERE id=? AND topic_id=? LIMIT 1");
            $stmt->bind_param("ii", $replyTo, $topicId);
            $stmt->execute();
            $rs = $stmt->get_result();
            $ok = $rs && $rs->fetch_assoc();
            $stmt->close();
            if (!$ok) $replyTo = 0;
        }

        if ($content === '' || u_strlen($content) < 1) {
            $replyError = 'Сообщение пустое.';
        } else {
            $now = time();
            $ip = client_ip();
            $mysqli->begin_transaction();
            $contentCol = defined('FORUM_POST_CONTENT_COL') ? (string)FORUM_POST_CONTENT_COL : 'content';
            $contentCol = preg_replace('~[^a-zA-Z0-9_]+~', '', $contentCol);
            if ($contentCol === '') $contentCol = 'content';
            $stmt = $mysqli->prepare('INSERT INTO forum_posts (topic_id, user_id, `'.$contentCol.'`, created_at, updated_at, ip, reply_to_post_id) VALUES (?,?,?, ?,0, ?, ?)');
            $stmt->bind_param('iisisi', $topicId, $forumUserId, $content, $now, $ip, $replyTo);
            $stmt->execute();
            $postId = (int)$stmt->insert_id;
            $stmt->close();

            // Обновляем тему
            $stmt = $mysqli->prepare('UPDATE forum_topics SET posts_count = posts_count+1, last_post_id=?, last_post_at=?, last_post_user_id=?, updated_at=? WHERE id=?');
            $stmt->bind_param('iiiii', $postId, $now, $forumUserId, $now, $topicId);
            $stmt->execute();
            $stmt->close();

            // Счётчики пользователя
            $stmt = $mysqli->prepare('UPDATE forum_users SET posts_count = posts_count+1 WHERE user_id=?');
            $stmt->bind_param('i', $forumUserId);
            $stmt->execute();
            $stmt->close();

            $mysqli->commit();

            // Страница, на которой оказался новый пост
            $total = 0;
            $stmt = $mysqli->prepare('SELECT COUNT(*) c FROM forum_posts WHERE topic_id=?');
            $stmt->bind_param('i', $topicId);
            $stmt->execute();
            $stmt->bind_result($total);
            $stmt->fetch();
            $stmt->close();

            $pageNew = (int)ceil(max(1, (int)$total) / FORUM_POSTS_PER_PAGE);
            redirect(forum_url('/topic.php?id='.$topicId.'&page='.$pageNew.'#p'.$postId));
        }
    }
}

// Увеличение просмотров (1 раз за 10 минут на сессию)
$viewKey = 'forum_view_topic_'.$topicId;
$lastView = (int)($_SESSION[$viewKey] ?? 0);
if (time() - $lastView > 600) {
    $_SESSION[$viewKey] = time();
    $stmt = $mysqli->prepare('UPDATE forum_topics SET views = views + 1 WHERE id=?');
    $stmt->bind_param('i', $topicId);
    $stmt->execute();
    $stmt->close();
    $topic['views'] = (int)($topic['views'] ?? 0) + 1;
}

// Пагинация постов
$page = (int)($_GET['page'] ?? 1);
$perPage = FORUM_POSTS_PER_PAGE;
$totalPosts = 0;
$stmt = $mysqli->prepare('SELECT COUNT(*) c FROM forum_posts WHERE topic_id=?');
$stmt->bind_param('i', $topicId);
$stmt->execute();
$stmt->bind_result($totalPosts);
$stmt->fetch();
$stmt->close();

[$page, $perPage, $pages, $offset] = paginate($page, $perPage, (int)$totalPosts);

$posts = [];

$expr = forum_post_content_expr($mysqli, 'p');

$perPageI = (int)$perPage;
$offsetI  = (int)$offset;

$sql = 'SELECT p.id, p.topic_id, p.user_id, p.reply_to_post_id, p.created_at, p.updated_at, p.ip, ' . $expr . ' AS content,
  u.login, u.rang, u.user_group, fu.signature, fu.posts_count AS fu_posts,
  fp.can_moderate AS author_can_moderate, fp.can_admin AS author_can_admin
  FROM forum_posts p
  JOIN users u ON u.id=p.user_id
  LEFT JOIN forum_users fu ON fu.user_id=u.id
  LEFT JOIN forum_permissions fp ON fp.user_id=u.id
  WHERE p.topic_id=?
  ORDER BY p.id ASC
  LIMIT '.$perPageI.' OFFSET '.$offsetI;

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $topicId);
$stmt->execute();
$rs = $stmt->get_result();
while ($rs && ($row = $rs->fetch_assoc())) {
    $row['content'] = (string)($row['content'] ?? '');
    $posts[] = $row;
}
$stmt->close();

// Fallback: если контент по каким-то причинам пришёл пустым, дочитываем отдельно
if (!empty($posts)) {
    foreach ($posts as &$pp) {
        if (trim((string)($pp['content'] ?? '')) === '') {
            $pp['content'] = forum_get_post_content($mysqli, (int)($pp['id'] ?? 0));
        }
    }
    unset($pp);
}

$forumTitle = ($topic['title'] ?? 'Тема') . ' · Форум';
require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <div class="forum-head">
    <div class="head-left">
      <img class="cover cover-topic" src="<?=h(forum_topic_image_url($topic['image'] ?? ''))?>" alt="topic">
      <div class="head-text">
        <a class="back" href="<?=h(forum_url('/category.php?id='.(int)$topic['cat_id']))?>">← <?=h($topic['cat_title'] ?? 'Категория')?></a>
      <h1><?=h($topic['title'] ?? '')?></h1>
      <div class="muted">Автор: <?=h($topic['author_login'] ?? '')?> · Создано: <?=date('d.m.Y H:i', (int)($topic['created_at'] ?? 0))?> · Просмотров: <?= (int)($topic['views'] ?? 0) ?></div>
      <?php if ($topicLocked): ?><div class="badge">Тема закрыта</div><?php endif; ?>
      </div>
    </div>
    <div class="head-actions">
      <?php if ($canModerate): ?>
        <form method="post" class="inline">
          <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
          <?php if ($topicLocked): ?>
            <button class="btn" name="mod_action" value="unlock" type="submit">Открыть</button>
          <?php else: ?>
            <button class="btn" name="mod_action" value="lock" type="submit">Закрыть</button>
          <?php endif; ?>
        </form>
      <?php endif; ?>
      <?php if ($forumUserId > 0): ?>
        <a class="btn" href="#reply">Ответить</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($replyError !== ''): ?>
    <div class="forum-alert danger"><?=h($replyError)?></div>
  <?php endif; ?>

  <div class="posts">
    <?php foreach ($posts as $p): ?>
      <?php
        $authorId = (int)$p['user_id'];
        $authorLogin = (string)($p['login'] ?? '');
        $authorRang = (string)($p['rang'] ?? '');
        // Игровая группа 1 => показываем ранг «Администратор»
        if ((int)($p['user_group'] ?? 0) === 1) {
          $authorRang = 'Администратор';
        }
        $authorForumPosts = (int)($p['fu_posts'] ?? 0);
        $authorForumRank = forum_get_rank_title($mysqli, $authorForumPosts);
        $authorForumAdmin = ((int)($p['user_group'] ?? 0) === 1) || !empty($p['author_can_admin']);
        $authorForumMod = (!$authorForumAdmin) && !empty($p['author_can_moderate']);
        $sig = (string)($p['signature'] ?? '');
        $created = (int)($p['created_at'] ?? 0);
      ?>
      <article class="post" id="p<?= (int)$p['id'] ?>">
        <div class="post-left">
          <img class="ava" src="<?=h(forum_avatar_url($authorId))?>" alt="ava">
          <div class="u">
            <div class="login"><?=h($authorLogin)?></div>
            <div class="muted small">Ранг: <?=h($authorRang)?></div>
            <div class="muted small">Форум: <?=h($authorForumRank)?></div>
          </div>
        </div>
        <div class="post-right">
          <div class="post-head">
            <div class="muted">#<?= (int)$p['id'] ?> · <?=date('d.m.Y H:i', $created) ?></div>
            <div class="post-actions">
              <?php if ($forumUserId > 0 && $canReplyHere): ?>
                <button type="button" class="btn small" data-quote-post="<?= (int)$p['id'] ?>">Цитировать</button>
                <button type="button" class="btn small" data-reply-post="<?= (int)$p['id'] ?>">Ответить</button>
              <?php endif; ?>
              <?php if ($forumUserId > 0 && $canModerate): ?>
                <form method="post" class="inline" onsubmit="return confirm('Удалить сообщение #<?= (int)$p['id'] ?>?');">
                  <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                  <input type="hidden" name="action" value="delete_post">
                  <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="btn small danger">Удалить</button>
                </form>
              <?php endif; ?>
            </div>
          </div>

          <div class="post-body"><?= forum_render_bbcode((string)$p['content']) ?></div>

          <?= forum_reactions_html($mysqli, (int)$p['id'], $forumUserId, (bool)$canReact) ?>

          <?php if ($sig !== ''): ?>
            <div class="post-signature"><?= forum_render_bbcode($sig) ?></div>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?= pagination_html(forum_url('/topic.php?id='.$topicId), $page, $pages) ?>
</div>

<div class="forum-card" id="reply">
  <h2>Ответ</h2>
  <?php if ($forumUserId <= 0): ?>
    <div class="forum-alert">Чтобы отвечать, нужно <a href="<?=h(forum_url('/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? forum_url('/'))))?>">войти</a>.</div>
  <?php elseif (!$canReplyHere): ?>
    <div class="forum-alert">Ответы в этой теме недоступны.</div>
  <?php else: ?>
    <form method="post" class="forum-form" id="reply_form">
      <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
      <input type="hidden" name="action" value="reply">
      <input type="hidden" name="reply_to" id="reply_to" value="0">

      <div class="bb-toolbar" data-target="reply_text">
        <button type="button" data-action="b"><b>B</b></button>
        <button type="button" data-action="i"><i>I</i></button>
        <button type="button" data-action="u"><u>U</u></button>
        <button type="button" data-action="s"><s>S</s></button>
        <button type="button" data-action="url">Ссылка</button>
        <button type="button" data-action="img">Внешн. картинка</button>
        <button type="button" data-action="upload" <?= $canUpload ? '' : 'disabled' ?>>Загрузить</button>
        <button type="button" data-action="quote">Цитата</button>
        <button type="button" data-action="code">Код</button>
      </div>

      <label>
        <span>Сообщение</span>
        <textarea id="reply_text" name="content" rows="8" maxlength="<?= (int)FORUM_POST_MAX ?>" required></textarea>
        <div class="hint">BBCode: [b][i][u][s][url][img][quote][code] · Ответ на пост: &gt;&gt;#ID</div>
      </label>

      <button class="btn primary" type="submit">Отправить</button>
    </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
