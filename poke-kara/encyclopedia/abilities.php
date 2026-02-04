<?php
require_once __DIR__ . '/_inc/bootstrap.php';
$pageTitle = 'Способности';

$q = trim((string)((isset($_GET['q']) ? $_GET['q'] : '')));
$page = max(1, enc_int((isset($_GET['page']) ? $_GET['page'] : 1), 1));
$per = ENC_LIST_PER_PAGE;

$where = '';
$params = [];
$types = '';
if ($q !== '') {
  $where = "WHERE (name_rus LIKE ? OR name LIKE ?)";
  $like = '%'.$q.'%';
  $params = [$like,$like];
  $types = 'ss';
}

$total = 0;
$rows = [];
$countSql = "SELECT COUNT(*) c FROM base_ability $where";
$listSql  = "SELECT id,name_rus,name,about FROM base_ability $where ORDER BY id ASC LIMIT ? OFFSET ?";

if ($stmt = $mysqli->prepare($countSql)) {
  if ($types !== '') enc_stmt_bind_param($stmt, $types, $params);
  $stmt->execute();
  $row = enc_stmt_fetch_assoc($stmt);
  $total = ($row && isset($row['c'])) ? (int)$row['c'] : 0;
  $stmt->close();
}
$offset = ($page-1)*$per;
if ($stmt = $mysqli->prepare($listSql)) {
  $t2 = $types . 'ii';
  $p2 = $params; $p2[]=$per; $p2[]=$offset;
  enc_stmt_bind_param($stmt, $t2, $p2);
  $stmt->execute();
  $rows = enc_stmt_fetch_all_assoc($stmt);

  $stmt->close();
}

require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Способности</h1>
  <div class="muted">Справочник способностей. Поиск по названию и описанию.</div>

  <form method="get" class="form-row" style="margin-top:10px;">
    <div class="field">
      <label>Поиск</label>
      <input name="q" value="<?=enc_h($q)?>" placeholder="Название...">
    </div>
    <div class="field" style="flex:0 0 160px;">
      <label>&nbsp;</label>
      <button class="btn primary" type="submit" style="width:100%;">Показать</button>
    </div>
  </form>
</div>

<div class="forum-card" style="margin-top:12px;">
  <div class="muted" style="margin-bottom:10px;">Найдено: <b><?=enc_h($total)?></b></div>

  <div class="enc-hide-mobile">
  <table class="table">
    <thead><tr><th style="width:70px;">#</th><th>Способность</th><th>Описание</th></tr></thead>
    <tbody>
    <?php foreach($rows as $a): ?>
      <tr>
        <td><b>#<?=enc_h((int)$a['id'])?></b></td>
        <td><a class="link" href="<?=enc_h(enc_url('/ability.php?id='.(int)$a['id']))?>"><?=enc_h($a['name_rus'] ?: $a['name'])?></a></td>
        <td class="muted"><?=enc_h(enc_excerpt((string)((isset($a['about']) ? $a['about'] : '')), 180))?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

  <div class="enc-show-mobile">
    <div class="enc-cardlist">
      <?php foreach($rows as $a): ?>
        <a class="enc-carditem" href="<?=enc_h(enc_url('/ability.php?id='.(int)$a['id']))?>">
          <div class="enc-carditem__head">
            <div class="enc-carditem__title"><?=enc_h($a['name_rus'] ?: $a['name'])?></div>
            <div class="enc-carditem__sub">Способность</div>
          </div>
          <div class="enc-muted" style="margin-top:6px;"><?=enc_h(enc_excerpt((string)((isset($a['about']) ? $a['about'] : '')), 160))?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?=enc_pagination($page, $per, $total, enc_url('/abilities.php'), ['q'=>$q])?>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
