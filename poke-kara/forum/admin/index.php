<?php
require_once __DIR__ . '/../_inc/bootstrap.php';

forum_require_login($forumUserId);
if (!forum_is_admin($forumUser, $forumPerms)) {
    http_response_code(403);
    die('Forbidden');
}

$forumTitle = 'Админ · Форум';
require_once __DIR__ . '/../_inc/layout_top.php';
?>

<div class="forum-card">
  <div class="forum-head">
    <div>
      <a class="back" href="<?=h(forum_url('/'))?>">← Форум</a>
      <h1>Администрирование</h1>
      <div class="muted">Управление правами доступа и настройками форума.</div>
    </div>
  </div>

  <div class="forum-list">
    <div class="row">
      <div>
        <div class="title">Права пользователей</div>
        <div class="muted">Раздача прав: создавать темы, отвечать, реакции, загрузка изображений, модерация.</div>
      </div>
      <div class="row-actions">
        <a class="btn primary" href="<?=h(forum_url('/admin/permissions.php'))?>">Открыть</a>
      </div>
    </div>

    <div class="row">
      <div>
        <div class="title">Категории</div>
        <div class="muted">Добавление и редактирование категорий, изображений категорий.</div>
      </div>
      <div class="row-actions">
        <a class="btn" href="<?=h(forum_url('/admin/categories.php'))?>">Открыть</a>
      </div>
    </div>

    <div class="row">
      <div>
        <div class="title">Форумные ранги</div>
        <div class="muted">Названия рангов по количеству сообщений. Управляется таблицей forum_ranks.</div>
      </div>
      <div class="row-actions">
        <a class="btn" href="<?=h(forum_url('/admin/ranks.php'))?>">Открыть</a>
      </div>
    </div>
  </div>

  <hr class="sep">
  <div class="muted small">
    Подсказка: если вы использовали старый install.sql, выполните forum/sql/upgrade.sql, чтобы добавить необходимые таблицы и колонки.
  </div>
</div>

<?php require_once __DIR__ . '/../_inc/layout_bottom.php'; ?>
