<?php
require_once __DIR__ . '/_inc/bootstrap.php';

$catId = (int)($_GET['id'] ?? 0);
if ($catId <= 0) redirect(forum_url('/'));

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

// Доступ на создание темы: только авторизованный + права + категория не locked (или модератор/админ)
$catLocked = !empty($cat['is_locked']);
$canCreateTopicHere = $canPostTopics && (!$catLocked || forum_is_moderator($forumUser, $forumPerms));

// Пагинация
$page = (int)($_GET['page'] ?? 1);
$perPage = FORUM_TOPICS_PER_PAGE;
$total = 0;
$stmt = $mysqli->prepare('SELECT COUNT(*) c FROM forum_topics WHERE category_id=?');
$stmt->bind_param('i', $catId);
$stmt->execute();
$stmt->bind_result($total);
$stmt->fetch();
$stmt->close();

[$page, $perPage, $pages, $offset] = paginate($page, $perPage, (int)$total);

$topics = [];
$stmt = $mysqli->prepare('SELECT t.*, u.login last_login
  FROM forum_topics t
  LEFT JOIN users u ON u.id=t.last_post_user_id
  WHERE t.category_id=?
  ORDER BY t.is_pinned DESC, t.last_post_at DESC, t.id DESC
  LIMIT ? OFFSET ?');
$stmt->bind_param('iii', $catId, $perPage, $offset);
$stmt->execute();
$rs = $stmt->get_result();
while ($rs && ($row = $rs->fetch_assoc())) {
    $topics[] = $row;
}
$stmt->close();

$forumTitle = 'Категория · ' . ($cat['title'] ?? '');
require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <div class="forum-head">
    <div class="head-left">
      <img class="cover cover-cat" src="<?=h(forum_category_image_url($cat['image'] ?? ''))?>" alt="cat">
      <div class="head-text">
        <a class="back" href="<?=h(forum_url('/'))?>">← Категории</a>
      <h1><?=h($cat['title'] ?? '')?></h1>
      <div class="muted"><?=h($cat['description'] ?? '')?></div>
      <?php if ($catLocked): ?><div class="badge">Только чтение</div><?php endif; ?>
      </div>
    </div>
    <div class="head-actions">
      <?php if ($canCreateTopicHere): ?>
        <a class="btn primary" href="<?=h(forum_url('/new_topic.php?cat='.$catId))?>">Новая тема</a>
      <?php else: ?>
        <button class="btn" disabled>Новая тема</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="forum-list">
    <?php foreach ($topics as $t): ?>
      <div class="row">
        <div class="row-left">
          <div class="row-thumb"><img src="<?=h(forum_topic_image_url($t['image'] ?? ''))?>" alt="topic"></div>
          <div class="row-main">
            <a class="title" href="<?=h(forum_url('/topic.php?id='.(int)$t['id']))?>"><?php if (!empty($t['is_pinned'])) echo '📌 '; ?><?=h($t['title'] ?? '')?></a>
          <div class="muted">
            <?= !empty($t['is_locked']) ? '<span class="badge">Закрыта</span>' : '' ?>
          </div>
          </div>
        </div>
        <div class="meta">
          <div>Ответов: <b><?=max(0, (int)($t['posts_count'] ?? 1) - 1)?></b></div>
          <div>Просмотров: <b><?=(int)($t['views'] ?? 0)?></b></div>
          <?php if (!empty($t['last_post_at'])): ?>
            <div class="muted">Последнее: <?=date('d.m.Y H:i', (int)$t['last_post_at'])?><?= !empty($t['last_login']) ? ' · '.h($t['last_login']) : '' ?></div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($topics)): ?>
      <div class="empty">Пока нет тем.</div>
    <?php endif; ?>
  </div>

  <?= pagination_html(forum_url('/category.php?id='.$catId), $page, $pages) ?>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php';
