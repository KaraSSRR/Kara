<?php
require_once __DIR__ . '/_inc/bootstrap.php';
$pageTitle = 'Путеводитель';

$slug = trim((string)((isset($_GET['p']) ? $_GET['p'] : '')));
if ($slug !== '' && !preg_match('~^[a-z0-9_-]{2,80}$~', $slug)) $slug = '';

$pages = [];
$current = null;

if ($encyInstalled) {
  $q = $mysqli->query("SELECT id, slug, title, grp, sort FROM enc_pages ORDER BY grp ASC, sort ASC, id ASC");
  if ($q) while($r=$q->fetch_assoc()) $pages[]=$r;

  if ($slug !== '') {
    $stmt = $mysqli->prepare("SELECT * FROM enc_pages WHERE slug=? LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $current = enc_stmt_fetch_assoc($stmt);
    $stmt->close();
  }
  if (!$current && $pages) {
    $slug0 = (string)$pages[0]['slug'];
    $stmt = $mysqli->prepare("SELECT * FROM enc_pages WHERE slug=? LIMIT 1");
    $stmt->bind_param("s", $slug0);
    $stmt->execute();
    $current = enc_stmt_fetch_assoc($stmt);
    $stmt->close();
  }
}

require_once __DIR__ . '/_inc/layout_top.php';

$groups = [];
foreach($pages as $p) {
  $g = (string)((isset($p['grp']) ? $p['grp'] : 'Другое'));
  if (!isset($groups[$g])) $groups[$g] = [];
  $groups[$g][] = $p;
}
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Путеводитель</h1>
  <div class="muted">Статьи и советы. <?php if($encyIsAdmin): ?>Вы можете редактировать в <a class="link" href="<?=enc_h(enc_url('/admin/guide.php'))?>">админке</a>.<?php endif; ?></div>
</div>

<div class="build-grid" style="margin-top:12px;">
  <div class="card">
    <h3 style="margin:0 0 8px;">Разделы</h3>
    <?php if (!$pages): ?>
      <div class="muted">Пока нет страниц.</div>
    <?php else: ?>
      <?php foreach($groups as $g=>$list): ?>
        <div style="margin-top:10px; font-weight:900;"><?=enc_h($g)?></div>
        <div style="display:flex; flex-direction:column; gap:6px; margin-top:6px;">
          <?php foreach($list as $p): $active = ($current && $current['slug']===$p['slug']); ?>
            <a class="tab <?=($active?'active':'')?>" href="<?=enc_h(enc_url('/guide.php?p='.$p['slug']))?>" style="display:inline-flex; justify-content:space-between;">
              <span><?=enc_h($p['title'])?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <?php if (!$current): ?>
      <h3 style="margin:0 0 8px;">Страница не найдена</h3>
      <div class="muted">Выберите раздел слева.</div>
    <?php else: ?>
      <h2 style="margin:0 0 10px;"><?=enc_h((string)$current['title'])?></h2>
      <div><?=enc_render_bbcode((string)((isset($current['content']) ? $current['content'] : '')))?></div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
