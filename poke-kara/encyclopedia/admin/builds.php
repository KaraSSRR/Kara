<?php
require_once __DIR__ . '/../_inc/bootstrap.php';
if (!$encyIsAdmin) { http_response_code(403); die('Admin only'); }
if (!$encyInstalled) { $pageTitle='Админ: сборки'; require_once __DIR__.'/../_inc/layout_top.php'; require_once __DIR__.'/../_inc/layout_bottom.php'; exit; }

$ok = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  enc_csrf_check();
  $ids = (isset($_POST['id']) ? $_POST['id'] : []);
  if (!is_array($ids)) $ids = [];
  foreach($ids as $bid) {
    $bid = (int)$bid;
    $rec = isset($_POST['rec'][$bid]) ? 1 : 0;
    $sort = enc_int((isset($_POST['sort'][$bid]) ? $_POST['sort'][$bid] : 0), 0);
    $stmt = $mysqli->prepare("UPDATE enc_builds SET is_recommended=?, sort_order=? WHERE id=? LIMIT 1");
    $stmt->bind_param("iii", $rec, $sort, $bid);
    $stmt->execute();
    $stmt->close();
  }
  $ok = true;
}

$q = trim((string)((isset($_GET['q']) ? $_GET['q'] : '')));
$where = '1=1';
if ($q !== '') {
  $like = '%'.$mysqli->real_escape_string($q).'%';
  $where = "(b.title LIKE '{$like}' OR u.login LIKE '{$like}')";
}

$rows=[];
$q2 = $mysqli->query("SELECT b.id,b.title,b.visibility,b.is_recommended,b.sort_order,b.updated_at,b.created_by,u.login
                      FROM enc_builds b
                      LEFT JOIN users u ON u.id=b.created_by
                      WHERE $where
                      ORDER BY b.is_recommended DESC, b.sort_order DESC, b.updated_at DESC
                      LIMIT 200");
if ($q2) while($r=$q2->fetch_assoc()) $rows[]=$r;

$pageTitle = 'Админ: сборки';
require_once __DIR__ . '/../_inc/layout_top.php';
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Сборки — управление</h1>
  <div class="muted">Рекомендованные сборки выводятся сверху (is_recommended=1), далее по sort_order.</div>

  <?php if ($ok): ?>
    <div class="forum-alert success" style="margin-top:12px;">Обновлено.</div>
  <?php endif; ?>

  <form method="get" class="form-row" style="margin-top:10px;">
    <div class="field">
      <label>Поиск</label>
      <input name="q" value="<?=enc_h($q)?>" placeholder="Название или автор...">
    </div>
    <div class="field" style="flex:0 0 160px;">
      <label>&nbsp;</label>
      <button class="btn primary" type="submit" style="width:100%;">Показать</button>
    </div>
  </form>
</div>

<div class="forum-card" style="margin-top:12px;">
  <form method="post">
    <input type="hidden" name="csrf" value="<?=enc_h(enc_csrf_token())?>">

    <table class="table">
      <thead>
        <tr>
          <th style="width:70px;">ID</th>
          <th>Сборка</th>
          <th style="width:140px;">Автор</th>
          <th style="width:110px;">Доступ</th>
          <th style="width:130px;">Рекоменд.</th>
          <th style="width:110px;">Sort</th>
          <th style="width:160px;">Обновлено</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($rows as $b): $bid=(int)$b['id']; ?>
        <tr>
          <td><b>#<?=enc_h($bid)?></b><input type="hidden" name="id[]" value="<?=enc_h($bid)?>"></td>
          <td>
            <a class="link" href="<?=enc_h(enc_url('/build.php?id='.$bid))?>" target="_blank"><?=enc_h((string)$b['title'])?></a>
          </td>
          <td class="muted"><?=enc_h((string)($b['login'] ?: ('#'.$b['created_by'])))?></td>
          <td class="muted"><?=enc_h((string)$b['visibility'])?></td>
          <td>
            <label style="display:inline-flex; align-items:center; gap:8px;">
              <input type="checkbox" name="rec[<?=$bid?>]" value="1" <?=((int)$b['is_recommended']===1?'checked':'')?>>
              <span class="muted">is_recommended</span>
            </label>
          </td>
          <td><input name="sort[<?=$bid?>]" type="number" value="<?=enc_h((int)$b['sort_order'])?>" style="width:90px; padding:8px 10px; border-radius:12px; border:1px solid var(--border);"></td>
          <td class="muted"><?=enc_h((string)$b['updated_at'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="small-actions" style="margin-top:12px;">
      <button class="btn primary" type="submit">Сохранить</button>
      <a class="btn" href="<?=enc_h(enc_url('/builds.php'))?>" target="_blank">Открыть список</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../_inc/layout_bottom.php'; ?>
