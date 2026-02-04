<?php
require_once __DIR__ . '/../_inc/bootstrap.php';

forum_require_login($forumUserId);
if (!forum_is_admin($forumUser, $forumPerms)) {
    http_response_code(403);
    die('Forbidden');
}

$forumTitle = 'Админ · Ранги форума';

if (!$forumInstalled) {
    require_once __DIR__ . '/../_inc/layout_top.php';
    echo '<div class="forum-alert danger">Форум не установлен полностью. Выполните forum/sql/upgrade.sql или forum/sql/install.sql.</div>';
    require_once __DIR__ . '/../_inc/layout_bottom.php';
    exit;
}

$err = '';

// Удаление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_check();
    $id = (int)($_POST['delete_id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare('DELETE FROM forum_ranks WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    redirect(forum_url('/admin/ranks.php?saved=1'));
}

// Сохранение (новый или существующий)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rank'])) {
    csrf_check();

    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $minPosts = (int)($_POST['min_posts'] ?? 0);
    $sort = (int)($_POST['sort'] ?? 0);

    if ($title === '' || u_strlen($title) < 2) {
        $err = 'Название ранга должно быть не короче 2 символов.';
    } elseif ($minPosts < 0) {
        $err = 'min_posts не может быть отрицательным.';
    } else {
        if ($id > 0) {
            $stmt = $mysqli->prepare('UPDATE forum_ranks SET title=?, min_posts=?, sort=? WHERE id=?');
            $stmt->bind_param('siii', $title, $minPosts, $sort, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $mysqli->prepare('INSERT INTO forum_ranks (title, min_posts, sort) VALUES (?,?,?)');
            $stmt->bind_param('sii', $title, $minPosts, $sort);
            $stmt->execute();
            $stmt->close();
        }
        redirect(forum_url('/admin/ranks.php?saved=1'));
    }
}

$saved = !empty($_GET['saved']);

$ranks = [];
$rs = $mysqli->query('SELECT id, title, min_posts, sort FROM forum_ranks ORDER BY min_posts ASC, sort ASC, id ASC');
while ($rs && ($row = $rs->fetch_assoc())) {
    $ranks[] = $row;
}

require_once __DIR__ . '/../_inc/layout_top.php';
?>

<div class="forum-card">
  <div class="forum-head">
    <div>
      <a class="back" href="<?=h(forum_url('/admin/'))?>">← Админ</a>
      <h1>Ранги форума</h1>
      <div class="muted">Ранг определяется по количеству сообщений (forum_users.posts_count). Берётся максимальный min_posts ≤ posts_count.</div>
    </div>
  </div>

  <?php if ($saved): ?>
    <div class="forum-alert success">Сохранено.</div>
  <?php endif; ?>

  <?php if ($err !== ''): ?>
    <div class="forum-alert danger"><?=h($err)?></div>
  <?php endif; ?>

  <div class="forum-card inner">
    <h2>Добавить ранг</h2>
    <form method="post" class="forum-form">
      <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
      <input type="hidden" name="save_rank" value="1">
      <div class="form-row">
        <label>
          <span>Название</span>
          <input type="text" name="title" maxlength="100" required>
        </label>
        <label>
          <span>min_posts</span>
          <input type="text" name="min_posts" value="0" required>
        </label>
        <label>
          <span>sort</span>
          <input type="text" name="sort" value="0" required>
        </label>
      </div>
      <button class="btn primary" type="submit">Добавить</button>
    </form>
  </div>

  <div class="forum-card inner">
    <h2>Список рангов</h2>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Название</th>
          <th>min_posts</th>
          <th>sort</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ranks as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td colspan="3">
              <form method="post" class="forum-form" style="gap:8px; margin:0;">
                <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                <input type="hidden" name="save_rank" value="1">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                <div class="form-row">
                  <input type="text" name="title" value="<?=h((string)$r['title'])?>" maxlength="100" required>
                  <input type="text" name="min_posts" value="<?= (int)$r['min_posts'] ?>" required>
                  <input type="text" name="sort" value="<?= (int)$r['sort'] ?>" required>
                </div>

                <div class="row-actions">
                  <button class="btn primary" type="submit">Сохранить</button>
                </div>
              </form>
            </td>
            <td>
              <form method="post" onsubmit="return confirm('Удалить ранг?');" style="margin:0;">
                <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                <button class="btn danger" type="submit">Удалить</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($ranks)): ?>
          <tr><td colspan="5" class="muted">Ранги не заданы.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php require_once __DIR__ . '/../_inc/layout_bottom.php'; ?>
