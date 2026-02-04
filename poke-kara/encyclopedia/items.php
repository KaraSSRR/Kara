<?php
require_once __DIR__ . '/_inc/bootstrap.php';
$pageTitle = 'Предметы';

$q = trim((string)((isset($_GET['q']) ? $_GET['q'] : '')));
$type = trim((string)((isset($_GET['type']) ? $_GET['type'] : '')));
$page = max(1, enc_int((isset($_GET['page']) ? $_GET['page'] : 1), 1));
$per = ENC_LIST_PER_PAGE;

$where = [];
$params = [];
$types = '';

if ($q !== '') {
  $where[] = "(name LIKE ? OR nameEng LIKE ? OR about LIKE ?)";
  $like = '%'.$q.'%';
  $params[]=$like; $params[]=$like; $params[]=$like;
  $types .= 'sss';
}
if ($type !== '' && preg_match('~^[a-z_]{2,20}$~', $type)) {
  $where[] = "type=?";
  $params[]=$type;
  $types .= 's';
} else $type='';

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$total=0; $rows=[];

$countSql = "SELECT COUNT(*) c FROM base_items $whereSql";
$listSql  = "SELECT id,name,type,about FROM base_items $whereSql ORDER BY id ASC LIMIT ? OFFSET ?";

if ($stmt=$mysqli->prepare($countSql)) {
  if ($types!=='') enc_stmt_bind_param($stmt, $types, $params);
  $stmt->execute();
  $row = enc_stmt_fetch_assoc($stmt);
  $total = ($row && isset($row['c'])) ? (int)$row['c'] : 0;
  $stmt->close();
}
$offset=($page-1)*$per;
if ($stmt=$mysqli->prepare($listSql)) {
  $t2=$types.'ii';
  $p2=$params; $p2[]=$per; $p2[]=$offset;
  enc_stmt_bind_param($stmt, $t2, $p2);
  $stmt->execute();
  $rows = enc_stmt_fetch_all_assoc($stmt);

  $stmt->close();
}

// distinct types for filter (cached)
$itemTypes=[];
$q2 = $mysqli->query("SELECT DISTINCT type FROM base_items ORDER BY type ASC");
if ($q2) while($r=$q2->fetch_assoc()) {
  $t=(string)$r['type'];
  if ($t!=='') $itemTypes[]=$t;
}

require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Предметы</h1>
  <div class="muted">Справочник предметов. Поиск по названию, описанию и типу.</div>

  <form method="get" class="form-row" style="margin-top:10px;">
    <div class="field">
      <label>Поиск</label>
      <input name="q" value="<?=enc_h($q)?>" placeholder="Название или описание...">
    </div>
    <div class="field">
      <label>Тип</label>
      <select name="type">
        <option value="">— любой —</option>
        <?php foreach($itemTypes as $t): ?>
          <option value="<?=enc_h($t)?>" <?=($type===$t?'selected':'')?>><?=enc_h(enc_item_type_ru($t))?></option>
        <?php endforeach; ?>
      </select>
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
    <thead><tr><th style="width:70px;">#</th><th style="width:44px;"></th><th>Предмет</th><th style="width:120px;">Тип</th><th>Описание</th></tr></thead>
    <tbody>
    <?php foreach($rows as $it): ?>
      <tr>
        <td><b>#<?=enc_h((int)$it['id'])?></b></td>
        <td class="td-icon"><?=enc_item_little_img((int)$it['id'], $it['name'])?></td>
        <td><a class="link" href="<?=enc_h(enc_url('/item.php?id='.(int)$it['id']))?>"><?=enc_h($it['name'])?></a></td>
        <td class="muted"><?=enc_h(enc_item_type_ru((string)$it['type']))?></td>
        <td class="muted"><?=enc_h(enc_excerpt((string)((isset($it['about']) ? $it['about'] : '')), 180))?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

  <div class="enc-show-mobile">
    <div class="enc-cardlist">
      <?php foreach($rows as $it): ?>
        <a class="enc-carditem" href="<?=enc_h(enc_url('/item.php?id='.(int)$it['id']))?>">
          <div class="enc-carditem__head">
            <div style="display:flex; gap:10px; align-items:center;">
              <div class="enc-carditem__icon"><?=enc_item_little_img((int)$it['id'], $it['name'], 'item-mini')?></div>
              <div>
                <div class="enc-carditem__title"><?=enc_h($it['name'])?></div>
                <div class="enc-carditem__sub"><?=enc_h(enc_item_type_ru((string)((isset($it['type']) ? $it['type'] : ''))))?></div>
              </div>
            </div>
          </div>
          <div class="enc-muted" style="margin-top:6px;"><?=enc_h(enc_excerpt((string)((isset($it['about']) ? $it['about'] : '')), 140))?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?=enc_pagination($page, $per, $total, enc_url('/items.php'), ['q'=>$q,'type'=>$type])?>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
