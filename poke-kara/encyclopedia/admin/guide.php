<?php
require_once __DIR__ . '/../_inc/bootstrap.php';
if (!$encyIsAdmin) { http_response_code(403); die('Admin only'); }
if (!$encyInstalled) { $pageTitle='Админ: путеводитель'; require_once __DIR__.'/../_inc/layout_top.php'; require_once __DIR__.'/../_inc/layout_bottom.php'; exit; }

$action = (string)((isset($_POST['action']) ? $_POST['action'] : ''));
$editId = enc_int((isset($_GET['id']) ? $_GET['id'] : 0), 0);

$errors = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  enc_csrf_check();

  if ($action === 'save') {
    $id = enc_int((isset($_POST['id']) ? $_POST['id'] : 0), 0);
    $slug = strtolower(trim((string)((isset($_POST['slug']) ? $_POST['slug'] : ''))));
    $title = trim((string)((isset($_POST['title']) ? $_POST['title'] : '')));
    $grp = trim((string)((isset($_POST['grp']) ? $_POST['grp'] : 'Другое')));
    $sort = enc_int((isset($_POST['sort']) ? $_POST['sort'] : 0), 0);
    $content = trim((string)((isset($_POST['content']) ? $_POST['content'] : '')));

    if (!preg_match('~^[a-z0-9_-]{2,80}$~', $slug)) $errors[] = 'Slug: только a-z, 0-9, _ и -, длина 2-80.';
    if ($title === '' || mb_strlen($title) > 120) $errors[] = 'Заголовок обязателен (до 120).';
    if (mb_strlen($content) > ENC_TEXT_MAX) $errors[] = 'Контент слишком длинный.';

    // unique slug
    if (!$errors) {
      $stmt = $mysqli->prepare("SELECT id FROM enc_pages WHERE slug=? AND id<>? LIMIT 1");
      $stmt->bind_param("si", $slug, $id);
      $stmt->execute();
      $dup = enc_stmt_fetch_assoc($stmt);
      $stmt->close();
      if ($dup) $errors[] = 'Slug уже используется.';
    }

    if (!$errors) {
      if ($id>0) {
        $stmt = $mysqli->prepare("UPDATE enc_pages SET slug=?, title=?, grp=?, sort=?, content=? WHERE id=? LIMIT 1");
        $stmt->bind_param("sssisi", $slug, $title, $grp, $sort, $content, $id);
        $stmt->execute();
        $stmt->close();
        $editId = $id;
      } else {
        $stmt = $mysqli->prepare("INSERT INTO enc_pages (slug,title,grp,sort,content) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssis", $slug, $title, $grp, $sort, $content);
        $stmt->execute();
        $editId = (int)$stmt->insert_id;
        $stmt->close();
      }
      $ok = true;
    }
  }
}

// fetch list
$pages = [];
$q = $mysqli->query("SELECT id, slug, title, grp, sort, updated_at FROM enc_pages ORDER BY grp ASC, sort ASC, id ASC");
if ($q) while($r=$q->fetch_assoc()) $pages[]=$r;

// fetch edit page
$edit = null;
if ($editId>0) {
  $stmt=$mysqli->prepare("SELECT * FROM enc_pages WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$editId);
  $stmt->execute();
  $edit = enc_stmt_fetch_assoc($stmt);
  $stmt->close();
}

$pageTitle = 'Админ: путеводитель';
require_once __DIR__ . '/../_inc/layout_top.php';
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Путеводитель — управление</h1>
  <div class="muted">Создание/редактирование. Удаление — вручную из БД (по требованию).</div>

  <?php if ($ok): ?>
    <div class="forum-alert success" style="margin-top:12px;">Сохранено.</div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="forum-alert danger" style="margin-top:12px;">
      <?php foreach($errors as $e): ?><div><?=enc_h($e)?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="build-grid" style="margin-top:12px;">
  <div class="card">
    <h3 style="margin:0 0 10px;">Страницы</h3>
    <div class="muted" style="margin-bottom:10px;">Всего: <?=enc_h(count($pages))?></div>
    <div style="display:flex; flex-direction:column; gap:8px;">
      <?php foreach($pages as $p): ?>
        <a class="tab <?=($edit && (int)$edit['id']===(int)$p['id']?'active':'')?>" href="<?=enc_h(enc_url('/admin/guide.php?id='.(int)$p['id']))?>">
          <?=enc_h((string)$p['grp'])?> / <?=enc_h((string)$p['title'])?>
          <span class="muted"> (<?=enc_h((string)$p['slug'])?>)</span>
        </a>
      <?php endforeach; ?>
      <a class="btn" href="<?=enc_h(enc_url('/admin/guide.php'))?>" style="margin-top:6px;">+ Новая страница</a>
    </div>
  </div>

  <div class="card">
    <h3 style="margin:0 0 10px;"><?= $edit ? 'Редактирование' : 'Новая страница' ?></h3>

    <form method="post">
      <input type="hidden" name="csrf" value="<?=enc_h(enc_csrf_token())?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?=enc_h((int)((isset($edit['id']) ? $edit['id'] : 0)))?>">

      <div class="form-row">
        <div class="field">
          <label>Slug</label>
          <input name="slug" value="<?=enc_h((string)((isset($edit['slug']) ? $edit['slug'] : '')))?>" placeholder="start-here">
        </div>
        <div class="field">
          <label>Группа</label>
          <input name="grp" value="<?=enc_h((string)((isset($edit['grp']) ? $edit['grp'] : 'Другое')))?>" placeholder="Основы">
        </div>
        <div class="field" style="flex:0 0 140px;">
          <label>Sort</label>
          <input name="sort" value="<?=enc_h((int)((isset($edit['sort']) ? $edit['sort'] : 0)))?>" type="number">
        </div>
      </div>

      <div class="field" style="margin-top:10px;">
        <label>Заголовок</label>
        <input name="title" value="<?=enc_h((string)((isset($edit['title']) ? $edit['title'] : '')))?>">
      </div>

      <div class="field" style="margin-top:10px;">
        <label>Контент (BBCode)</label>
        <textarea name="content"><?=enc_h((string)((isset($edit['content']) ? $edit['content'] : '')))?></textarea>
      </div>

      <div class="small-actions" style="margin-top:12px;">
        <button class="btn primary" type="submit">Сохранить</button>
        <a class="btn" href="<?=enc_h(enc_url('/guide.php?p='.(string)((isset($edit['slug']) ? $edit['slug'] : ''))))?>" target="_blank">Открыть</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../_inc/layout_bottom.php'; ?>
