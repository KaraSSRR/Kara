<?php
require_once __DIR__ . '/../_inc/bootstrap.php';

forum_require_login($forumUserId);
if (!forum_is_admin($forumUser, $forumPerms)) {
    http_response_code(403);
    die('Forbidden');
}

$forumTitle = 'Админ · Права пользователей';

if (!$forumInstalled) {
    require_once __DIR__ . '/../_inc/layout_top.php';
    echo '<div class="forum-alert danger">Форум не установлен полностью. Выполните forum/sql/upgrade.sql (если у вас старая установка) или forum/sql/install.sql.</div>';
    require_once __DIR__ . '/../_inc/layout_bottom.php';
    exit;
}

$editUserId = (int)($_GET['user_id'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    csrf_check();

    $editUserId = (int)($_POST['user_id'] ?? 0);
    if ($editUserId <= 0) {
        redirect(forum_url('/admin/permissions.php'));
    }

    // Проверим что пользователь существует
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $editUserId);
    $stmt->execute();
    $rs = $stmt->get_result();
    $exists = $rs && $rs->fetch_assoc();
    $stmt->close();

    if (!$exists) {
        redirect(forum_url('/admin/permissions.php'));
    }

    $canPost = !empty($_POST['can_post_topics']) ? 1 : 0;
    $canReply = !empty($_POST['can_reply']) ? 1 : 0;
    $canReact = !empty($_POST['can_react']) ? 1 : 0;
    $canUpload = !empty($_POST['can_upload']) ? 1 : 0;
    $canModerate = !empty($_POST['can_moderate']) ? 1 : 0;
    $canAdmin = !empty($_POST['can_admin']) ? 1 : 0;

    // Защита от случайного снятия админки с себя через таблицу
    if ($editUserId === (int)$forumUserId) {
        $canAdmin = 1;
    }

    $now = time();
    $stmt = $mysqli->prepare(
        'INSERT INTO forum_permissions (user_id, can_post_topics, can_reply, can_react, can_upload, can_moderate, can_admin, updated_at)
         VALUES (?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
           can_post_topics=VALUES(can_post_topics),
           can_reply=VALUES(can_reply),
           can_react=VALUES(can_react),
           can_upload=VALUES(can_upload),
           can_moderate=VALUES(can_moderate),
           can_admin=VALUES(can_admin),
           updated_at=VALUES(updated_at)'
    );
    $stmt->bind_param('iiiiiiii', $editUserId, $canPost, $canReply, $canReact, $canUpload, $canModerate, $canAdmin, $now);
    $stmt->execute();
    $stmt->close();

    redirect(forum_url('/admin/permissions.php?user_id=' . $editUserId . '&saved=1'));
}

$saved = !empty($_GET['saved']);

// Выборка пользователей
$results = [];
if ($q !== '') {
    if (ctype_digit($q)) {
        $uid = (int)$q;
        $stmt = $mysqli->prepare(
            'SELECT u.id, u.login, u.user_group, u.rang,
                    p.can_post_topics, p.can_reply, p.can_react, p.can_upload, p.can_moderate, p.can_admin
             FROM users u
             LEFT JOIN forum_permissions p ON p.user_id=u.id
             WHERE u.id=?
             LIMIT 25'
        );
        $stmt->bind_param('i', $uid);
    } else {
        $like = '%' . $q . '%';
        $stmt = $mysqli->prepare(
            'SELECT u.id, u.login, u.user_group, u.rang,
                    p.can_post_topics, p.can_reply, p.can_react, p.can_upload, p.can_moderate, p.can_admin
             FROM users u
             LEFT JOIN forum_permissions p ON p.user_id=u.id
             WHERE u.login LIKE ?
             ORDER BY u.id DESC
             LIMIT 25'
        );
        $stmt->bind_param('s', $like);
    }
    $stmt->execute();
    $rs = $stmt->get_result();
    while ($rs && ($row = $rs->fetch_assoc())) {
        $results[] = $row;
    }
    $stmt->close();
} else {
    // Последние пользователи (для удобства)
    $rs = $mysqli->query(
        'SELECT u.id, u.login, u.user_group, u.rang,
                p.can_post_topics, p.can_reply, p.can_react, p.can_upload, p.can_moderate, p.can_admin
         FROM users u
         LEFT JOIN forum_permissions p ON p.user_id=u.id
         ORDER BY u.id DESC
         LIMIT 20'
    );
    while ($rs && ($row = $rs->fetch_assoc())) {
        $results[] = $row;
    }
}

$editUser = null;
$editPerms = null;
if ($editUserId > 0) {
    $stmt = $mysqli->prepare('SELECT id, login, user_group, rang FROM users WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $editUserId);
    $stmt->execute();
    $rs = $stmt->get_result();
    $editUser = $rs ? $rs->fetch_assoc() : null;
    $stmt->close();

    if ($editUser) {
        $editPerms = forum_get_permissions($mysqli, $editUserId);
    }
}

function perm_val(array $row, string $key, int $default): int {
    if (!array_key_exists($key, $row) || $row[$key] === null) return $default;
    return (int)$row[$key];
}

require_once __DIR__ . '/../_inc/layout_top.php';
?>

<div class="forum-card">
  <div class="forum-head">
    <div>
      <a class="back" href="<?=h(forum_url('/admin/'))?>">← Админ</a>
      <h1>Права пользователей</h1>
      <div class="muted">Чтение форума доступно всем. Создание тем, ответы, реакции и загрузка изображений — только по правам.</div>
    </div>
  </div>

  <?php if ($saved): ?>
    <div class="forum-alert success">Сохранено.</div>
  <?php endif; ?>

  <form method="get" class="forum-form">
    <label>
      <span>Поиск пользователя (ID или логин)</span>
      <input type="text" name="q" value="<?=h($q)?>" placeholder="Например: 123 или Player">
    </label>
    <button class="btn primary" type="submit">Найти</button>
  </form>

  <hr class="sep">

  <div class="forum-card inner">
    <h2>Список</h2>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Логин</th>
          <th>Группа</th>
          <th>Ранг</th>
          <th>Темы</th>
          <th>Ответы</th>
          <th>Реакции</th>
          <th>Загрузка</th>
          <th>Модер.</th>
          <th>Админ</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $r): ?>
          <?php
            $uid = (int)($r['id'] ?? 0);
            $pPost = perm_val($r, 'can_post_topics', 1);
            $pReply = perm_val($r, 'can_reply', 1);
            $pReact = perm_val($r, 'can_react', 1);
            $pUpload = perm_val($r, 'can_upload', 1);
            $pMod = perm_val($r, 'can_moderate', 0);
            $pAdmin = (int)($r['user_group'] ?? 0) === 1 ? 1 : perm_val($r, 'can_admin', 0);
          ?>
          <tr>
            <td><?= (int)$uid ?></td>
            <td><?=h((string)($r['login'] ?? ''))?></td>
            <td><?= (int)($r['user_group'] ?? 0) ?></td>
            <td><?=h((string)($r['rang'] ?? ''))?></td>
            <td><?= $pPost ? '✓' : '—' ?></td>
            <td><?= $pReply ? '✓' : '—' ?></td>
            <td><?= $pReact ? '✓' : '—' ?></td>
            <td><?= $pUpload ? '✓' : '—' ?></td>
            <td><?= $pMod ? '✓' : '—' ?></td>
            <td><?= $pAdmin ? '✓' : '—' ?></td>
            <td><a class="btn" href="<?=h(forum_url('/admin/permissions.php?user_id=' . $uid))?>">Изменить</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($editUser): ?>
    <hr class="sep">
    <div class="forum-card inner">
      <h2>Редактирование: <?=h((string)($editUser['login'] ?? ''))?> (ID <?= (int)$editUserId ?>)</h2>
      <div class="muted small">Если у игрока user_group=1 (админ игры), он всегда считается админом форума.</div>

      <form method="post" class="forum-form">
        <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
        <input type="hidden" name="user_id" value="<?= (int)$editUserId ?>">

        <div class="admin-grid">
          <label class="checkbox"><input type="checkbox" name="can_post_topics" value="1" <?= !empty($editPerms['can_post_topics']) ? 'checked' : '' ?>> <span>Создавать темы</span></label>
          <label class="checkbox"><input type="checkbox" name="can_reply" value="1" <?= !empty($editPerms['can_reply']) ? 'checked' : '' ?>> <span>Отвечать</span></label>
          <label class="checkbox"><input type="checkbox" name="can_react" value="1" <?= !empty($editPerms['can_react']) ? 'checked' : '' ?>> <span>Реакции</span></label>
          <label class="checkbox"><input type="checkbox" name="can_upload" value="1" <?= !empty($editPerms['can_upload']) ? 'checked' : '' ?>> <span>Загрузка изображений</span></label>
          <label class="checkbox"><input type="checkbox" name="can_moderate" value="1" <?= !empty($editPerms['can_moderate']) ? 'checked' : '' ?>> <span>Модерация (закрытие тем)</span></label>
          <label class="checkbox"><input type="checkbox" name="can_admin" value="1" <?= !empty($editPerms['can_admin']) ? 'checked' : '' ?>> <span>Админ форума</span></label>
        </div>

        <button class="btn primary" type="submit" name="save" value="1">Сохранить</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../_inc/layout_bottom.php'; ?>
