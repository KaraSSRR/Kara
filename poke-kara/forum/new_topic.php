<?php
require_once __DIR__ . '/_inc/bootstrap.php';

$catId = (int)($_GET['cat'] ?? 0);
if ($catId <= 0) {
    redirect(forum_url('/'));
}

$cat = null;
if ($forumInstalled) {
    $stmt = $mysqli->prepare('SELECT * FROM forum_categories WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $catId);
    $stmt->execute();
    $rs = $stmt->get_result();
    $cat = $rs ? $rs->fetch_assoc() : null;
    $stmt->close();
}

if (!$cat) {
    http_response_code(404);
    $forumTitle = 'Категория не найдена';
    require_once __DIR__ . '/_inc/layout_top.php';
    echo '<div class="forum-alert danger">Категория не найдена.</div>';
    require_once __DIR__ . '/_inc/layout_bottom.php';
    exit;
}

forum_require_login($forumUserId);

$catLocked = !empty($cat['is_locked']);
if ($catLocked && !forum_is_moderator($forumUser, $forumPerms)) {
    http_response_code(403);
    $forumTitle = 'Только чтение';
    require_once __DIR__ . '/_inc/layout_top.php';
    echo '<div class="forum-alert danger">В эту категорию нельзя писать.</div>';
    require_once __DIR__ . '/_inc/layout_bottom.php';
    exit;
}

if (!$canPostTopics && !forum_is_moderator($forumUser, $forumPerms)) {
    http_response_code(403);
    $forumTitle = 'Нет прав';
    require_once __DIR__ . '/_inc/layout_top.php';
    echo '<div class="forum-alert danger">У вас нет прав создавать темы.</div>';
    require_once __DIR__ . '/_inc/layout_bottom.php';
    exit;
}

$error = '';
$title = '';
$content = '';
$topicImageUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title = forum_clean_title((string)($_POST['title'] ?? ''), FORUM_TITLE_MAX);
    $content = forum_clean_text((string)($_POST['content'] ?? ''), FORUM_POST_MAX);
    $topicImageUrl = forum_sanitize_image_url((string)($_POST['topic_image_url'] ?? ''));
    // Если загрузили файл картинки — он приоритетнее URL
    if (!empty($_FILES['topic_image_file']['name'] ?? '')) {
        $up = forum_save_uploaded_image($mysqli, (int)$forumUserId, $_FILES['topic_image_file'], 'topic');
        if (!empty($up['ok'])) {
            $topicImageUrl = (string)($up['url'] ?? '');
        } else {
            $error = (string)($up['error'] ?? 'Ошибка загрузки изображения');
        }
    }

    if ($title === '' || u_strlen($title) < 3) {
        $error = 'Введите заголовок (минимум 3 символа).';
    } elseif ($content === '' || u_strlen($content) < 3) {
        $error = 'Введите текст сообщения (минимум 3 символа).';
    } else {
        $now = time();
        $ip = client_ip();

        $mysqli->begin_transaction();
        try {
            // Создаём тему
            $topicSql = '';
            if (defined('FORUM_HAS_TOPIC_IMAGE') && FORUM_HAS_TOPIC_IMAGE) {
                $topicSql = 'INSERT INTO forum_topics (category_id, user_id, title, image, is_pinned, is_locked, views, posts_count, created_at, updated_at, last_post_id, last_post_at, last_post_user_id)
                             VALUES (?,?,?, ?,0,0,0,1,?,?,NULL,?,?)';
            } else {
                $topicSql = 'INSERT INTO forum_topics (category_id, user_id, title, is_pinned, is_locked, views, posts_count, created_at, updated_at, last_post_id, last_post_at, last_post_user_id)
                             VALUES (?,?,?,0,0,0,1,?,?,NULL,?,?)';
            }

            $stmt = $mysqli->prepare($topicSql);
            if (defined('FORUM_HAS_TOPIC_IMAGE') && FORUM_HAS_TOPIC_IMAGE) {
                $stmt->bind_param('iissiiii', $catId, $forumUserId, $title, $topicImageUrl, $now, $now, $now, $forumUserId);
            } else {
                $stmt->bind_param('iisiiii', $catId, $forumUserId, $title, $now, $now, $now, $forumUserId);
            }
$stmt->execute();
            $topicId = (int)$stmt->insert_id;
            $stmt->close();

            // Первый пост (совместимость схем: content/text/message)
            $contentCol = defined('FORUM_POST_CONTENT_COL') ? (string)FORUM_POST_CONTENT_COL : 'content';
            $contentCol = preg_replace('~[^a-zA-Z0-9_]+~', '', $contentCol);
            if ($contentCol === '') $contentCol = 'content';
            $stmt = $mysqli->prepare('INSERT INTO forum_posts (topic_id, user_id, `'.$contentCol.'`, created_at, updated_at, ip, reply_to_post_id) VALUES (?,?,?, ?,0, ?, NULL)');
            $stmt->bind_param('iisis', $topicId, $forumUserId, $content, $now, $ip);
            $stmt->execute();
            $postId = (int)$stmt->insert_id;
            $stmt->close();

            // Обновляем тему
            $stmt = $mysqli->prepare('UPDATE forum_topics SET last_post_id=?, last_post_at=?, last_post_user_id=?, updated_at=? WHERE id=?');
            $stmt->bind_param('iiiii', $postId, $now, $forumUserId, $now, $topicId);
            $stmt->execute();
            $stmt->close();

            // Счётчики пользователя
            $stmt = $mysqli->prepare('UPDATE forum_users SET topics_count = topics_count+1, posts_count = posts_count+1 WHERE user_id=?');
            $stmt->bind_param('i', $forumUserId);
            $stmt->execute();
            $stmt->close();

            $mysqli->commit();

            redirect(forum_url('/topic.php?id='.$topicId.'#p'.$postId));
        } catch (Throwable $e) {
            $mysqli->rollback();
            $error = 'Ошибка создания темы: ' . $e->getMessage();
        }
    }
}

$forumTitle = 'Новая тема · ' . ($cat['title'] ?? '');
require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <div class="forum-head">
    <div>
      <a class="back" href="<?=h(forum_url('/category.php?id='.$catId))?>">← Назад</a>
      <h1>Новая тема</h1>
      <div class="muted"><?=h($cat['title'] ?? '')?></div>
    </div>
  </div>

  <?php if ($error !== ''): ?>
    <div class="forum-alert danger"><?=h($error)?></div>
  <?php endif; ?>

  <form method="post" class="forum-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <label>
      <span>Заголовок</span>
      <input type="text" name="title" maxlength="<?= (int)FORUM_TITLE_MAX ?>" value="<?=h($title)?>" required>
    </label>


<div class="grid-2">
  <label>
    <span>Картинка темы (URL)</span>
    <input type="url" name="topic_image_url" placeholder="https://..." value="<?=h($topicImageUrl)?>">
  </label>
  <label>
    <span>Картинка темы (файл)</span>
    <input type="file" name="topic_image_file" accept="image/*">
  </label>
</div>

    <div class="bb-toolbar" data-target="postContent">
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
      <textarea id="postContent" name="content" rows="10" maxlength="<?= (int)FORUM_POST_MAX ?>" required><?=h($content)?></textarea>
      <div class="hint">Поддерживается BBCode: [b][i][u][s][url][img][quote][code]</div>
    </label>

    <button class="btn primary" type="submit">Создать тему</button>
  </form>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
