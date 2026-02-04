<?php
require_once __DIR__ . '/_inc/bootstrap.php';

$forumTitle = 'Форум';

$categories = [];
if ($forumInstalled) {
    $q = $mysqli->query("SELECT c.*, 
        (SELECT COUNT(*) FROM forum_topics t WHERE t.category_id=c.id) AS topics_cnt,
        (SELECT SUM(t.posts_count) FROM forum_topics t WHERE t.category_id=c.id) AS posts_cnt,
        (SELECT t.id FROM forum_topics t WHERE t.category_id=c.id ORDER BY t.is_pinned DESC, t.last_post_at DESC LIMIT 1) AS last_topic_id,
        (SELECT t.title FROM forum_topics t WHERE t.category_id=c.id ORDER BY t.is_pinned DESC, t.last_post_at DESC LIMIT 1) AS last_topic_title,
        (SELECT t.last_post_at FROM forum_topics t WHERE t.category_id=c.id ORDER BY t.is_pinned DESC, t.last_post_at DESC LIMIT 1) AS last_post_at
     FROM forum_categories c
     ORDER BY c.sort DESC, c.id ASC");
    while ($q && ($row = $q->fetch_assoc())) {
        $categories[] = $row;
    }
}

require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <h1>Категории</h1>
  <div class="forum-list">
    <?php foreach ($categories as $c): ?>
      <div class="row">
        <div class="row-left">
          <div class="row-thumb"><img src="<?=h(forum_category_image_url($c['image'] ?? ''))?>" alt="cat"></div>
          <div class="row-main">
            <a class="title" href="<?=h(forum_url('/category.php?id='.(int)$c['id']))?>"><?=h($c['title'] ?? '')?></a>
          <div class="muted"><?=h($c['description'] ?? '')?></div>
          <?php if (!empty($c['is_locked'])): ?>
            <div class="badge">Только чтение</div>
          <?php endif; ?>
          </div>
        </div>
        <div class="meta">
          <div>Тем: <b><?= (int)($c['topics_cnt'] ?? 0) ?></b></div>
          <div>Сообщ.: <b><?= (int)($c['posts_cnt'] ?? 0) ?></b></div>
          <?php if (!empty($c['last_topic_id'])): ?>
            <div class="muted">Последнее: <a href="<?=h(forum_url('/topic.php?id='.(int)$c['last_topic_id']))?>"><?=h($c['last_topic_title'] ?? '')?></a></div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($categories)): ?>
      <div class="empty">Категории не найдены. Проверьте установку SQL.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php';
