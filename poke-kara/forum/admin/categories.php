<?php
require_once __DIR__ . '/../_inc/bootstrap.php';

forum_require_login($forumUserId);
if (!forum_is_admin($forumUser, $forumPerms)) {
    http_response_code(403);
    die('Forbidden');
}

$forumTitle = 'Админ · Категории';

if (!$forumInstalled) {
    require_once __DIR__ . '/../_inc/layout_top.php';
    echo '<div class="forum-alert danger">Форум не установлен по SQL. Выполните forum/sql/upgrade.sql или forum/sql/install.sql.</div>';
    require_once __DIR__ . '/../_inc/layout_bottom.php';
    exit;
}

$editId = (int)($_GET['id'] ?? 0);
$success = trim((string)($_GET['ok'] ?? ''));
$error = '';

$title = '';
$description = '';
$sort = 0;
$is_locked = 0;
$image = '';

if ($editId > 0) {
    $stmt = $mysqli->prepare('SELECT * FROM forum_categories WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $rs = $stmt->get_result();
    $row = $rs ? $rs->fetch_assoc() : null;
    $stmt->close();
    if ($row) {
        $title = (string)($row['title'] ?? '');
        $description = (string)($row['description'] ?? '');
        $sort = (int)($row['sort'] ?? 0);
        $is_locked = (int)($row['is_locked'] ?? 0);
        $image = (string)($row['image'] ?? '');
    } else {
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    csrf_check();

    $editId = (int)($_POST['id'] ?? 0);

    $title = forum_clean_title((string)($_POST['title'] ?? ''), 100);
    $description = trim((string)($_POST['description'] ?? ''));
    if (u_strlen($description) > 255) {
        $description = u_substr($description, 0, 255);
    }

    $sort = (int)($_POST['sort'] ?? 0);
    $is_locked = !empty($_POST['is_locked']) ? 1 : 0;

    $image = forum_sanitize_image_url((string)($_POST['image_url'] ?? ''));

    // Если в схеме нет колонки image — отключаем картинки (мягкая совместимость)
    if (defined('FORUM_HAS_CATEGORY_IMAGE') && !FORUM_HAS_CATEGORY_IMAGE) {
        $image = '';
    }

    // Если загружен файл — приоритетнее URL
    if ((defined('FORUM_HAS_CATEGORY_IMAGE') && FORUM_HAS_CATEGORY_IMAGE) && !empty($_FILES['image_file']['name'] ?? '')) {
        $up = forum_save_uploaded_image($mysqli, (int)$forumUserId, $_FILES['image_file'], 'cat');
        if (!empty($up['ok'])) {
            $image = (string)($up['url'] ?? '');
        } else {
            $error = (string)($up['error'] ?? 'Ошибка загрузки изображения');
        }
    }

    if ($error === '') {
        if ($title === '' || u_strlen($title) < 2) {
            $error = 'Введите название категории (минимум 2 символа).';
        } else {
            if ($editId > 0) {
                if (defined('FORUM_HAS_CATEGORY_IMAGE') && FORUM_HAS_CATEGORY_IMAGE) {
                    $stmt = $mysqli->prepare('UPDATE forum_categories SET title=?, description=?, sort=?, is_locked=?, image=? WHERE id=?');
                    $stmt->bind_param('ssiisi', $title, $description, $sort, $is_locked, $image, $editId);
                } else {
                    $stmt = $mysqli->prepare('UPDATE forum_categories SET title=?, description=?, sort=?, is_locked=? WHERE id=?');
                    $stmt->bind_param('ssiii', $title, $description, $sort, $is_locked, $editId);
                }
                $stmt->execute();
                $stmt->close();
                redirect(forum_url('/admin/categories.php?ok=Сохранено'));
            } else {
                if (defined('FORUM_HAS_CATEGORY_IMAGE') && FORUM_HAS_CATEGORY_IMAGE) {
                    $stmt = $mysqli->prepare('INSERT INTO forum_categories (title, description, sort, is_locked, image, created_at) VALUES (?,?,?,?,?,UNIX_TIMESTAMP())');
                    $stmt->bind_param('ssiis', $title, $description, $sort, $is_locked, $image);
                } else {
                    $stmt = $mysqli->prepare('INSERT INTO forum_categories (title, description, sort, is_locked, created_at) VALUES (?,?,?,?,UNIX_TIMESTAMP())');
                    $stmt->bind_param('ssii', $title, $description, $sort, $is_locked);
                }
                $stmt->execute();
                $stmt->close();
                redirect(forum_url('/admin/categories.php?ok=Добавлено'));
            }
        }
    }
}

$categories = [];
$q = $mysqli->query('SELECT * FROM forum_categories ORDER BY sort DESC, id ASC');
while ($q && ($r = $q->fetch_assoc())) {
    $categories[] = $r;
}

require_once __DIR__ . '/../_inc/layout_top.php';
?>

<div class="forum-card">
  <div class="forum-head">
    <div>
      <a class="back" href="<?=h(forum_url('/admin/index.php'))?>">← Админка</a>
      <h1>Категории</h1>
      <div class="muted">Добавление и редактирование категорий. Удаление — через БД.</div>
    </div>
  </div>

  <?php if ($success !== ''): ?>
    <div class="forum-alert"><?=h($success)?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="forum-alert danger"><?=h($error)?></div>
  <?php endif; ?>

  <div class="grid-2">
    <div class="forum-card inner">
      <h2><?= $editId>0 ? 'Редактирование' : 'Новая категория' ?></h2>

      <form method="post" class="forum-form" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="id" value="<?= (int)$editId ?>">
        <input type="hidden" name="save_category" value="1">

        <label>
          <span>Название</span>
          <input type="text" name="title" maxlength="100" value="<?=h($title)?>" required>
        </label>

        <label>
          <span>Описание</span>
          <input type="text" name="description" maxlength="255" value="<?=h($description)?>">
        </label>

        <div class="grid-2">
          <label>
            <span>Сортировка</span>
            <input type="number" name="sort" value="<?= (int)$sort ?>">
          </label>
          <label class="check">
            <input type="checkbox" name="is_locked" value="1" <?= $is_locked ? 'checked' : '' ?>>
            <span>Только чтение</span>
          </label>
        </div>

        <?php if (!defined('FORUM_HAS_CATEGORY_IMAGE') || FORUM_HAS_CATEGORY_IMAGE): ?>
        <div class="grid-2">
          <label>
            <span>Картинка (URL)</span>
            <input type="url" name="image_url" placeholder="https://..." value="<?=h($image)?>">
          </label>
          <label>
            <span>Картинка (файл)</span>
            <input type="file" name="image_file" accept="image/*">
          </label>
        </div>
        <?php else: ?>
        <div class="muted small">Картинки категорий недоступны: в вашей базе нет колонки forum_categories.image (запустите forum/sql/upgrade.sql).</div>
        <?php endif; ?>

        <div class="actions">
          <button class="btn primary" type="submit"><?= $editId>0 ? 'Сохранить' : 'Добавить' ?></button>
          <?php if ($editId>0): ?>
            <a class="btn" href="<?=h(forum_url('/admin/categories.php'))?>">Новая категория</a>
          <?php endif; ?>
        </div>

        <div class="muted small">
          Если картинка не задана — будет показана картинка по умолчанию.
        </div>
      </form>
    </div>

    <div class="forum-card inner">
      <h2>Список категорий</h2>
      <div class="forum-list">
        <?php foreach ($categories as $c): ?>
          <div class="row">
            <div class="row-left">
              <div class="row-thumb">
                <img src="<?=h(forum_category_image_url($c['image'] ?? ''))?>" alt="cat">
              </div>
              <div class="row-main">
                <div class="title"><?=h($c['title'] ?? '')?></div>
                <div class="muted"><?=h($c['description'] ?? '')?></div>
                <?php if (!empty($c['is_locked'])): ?><div class="badge">Только чтение</div><?php endif; ?>
              </div>
            </div>
            <div class="meta">
              <div class="muted small">sort: <?= (int)($c['sort'] ?? 0) ?></div>
              <div class="row-actions">
                <a class="btn small" href="<?=h(forum_url('/admin/categories.php?id='.(int)$c['id']))?>">Редактировать</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
          <div class="empty">Категорий пока нет.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../_inc/layout_bottom.php'; ?>
