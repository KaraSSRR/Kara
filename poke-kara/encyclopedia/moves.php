<?php
require_once __DIR__ . '/_inc/bootstrap.php';
$pageTitle = 'Атаки';

$q = trim((string)((isset($_GET['q']) ? $_GET['q'] : '')));
$type = strtolower(trim((string)((isset($_GET['type']) ? $_GET['type'] : ''))));
$cat = strtolower(trim((string)((isset($_GET['cat']) ? $_GET['cat'] : ''))));
$page = max(1, enc_int((isset($_GET['page']) ? $_GET['page'] : 1), 1));
$per = ENC_LIST_PER_PAGE;

$where = [];
$params = [];
$types = '';

if ($q !== '') {
  $where[] = "(name_rus LIKE ? OR name LIKE ? OR title_all LIKE ?)";
  $like = '%' . $q . '%';
  $params[] = $like; $params[] = $like; $params[] = $like;
  $types .= 'sss';
}
$allowedType = ['normal','fire','water','grass','electric','ice','fighting','poison','ground','fly','psychic','bug','rock','ghost','dragon','dark','steel','fairy'];
if ($type !== '' && in_array($type, $allowedType, true)) {
  $where[] = "FIND_IN_SET(?, REPLACE(type,' ',''))";
  $params[] = $type;
  $types .= 's';
} else $type='';
$allowedCat = ['physical','special','status','specific'];
if ($cat !== '' && in_array($cat, $allowedCat, true)) {
  $where[] = "FIND_IN_SET(?, REPLACE(category,' ',''))";
  $params[] = $cat;
  $types .= 's';
} else $cat='';

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$total = 0;
$rows = [];

$countSql = "SELECT COUNT(*) c FROM base_atk $whereSql";
$cols = ['id','name_rus','name','type','category','power','accuracy','pp'];
$extra = ['priority','target','contact','sound','punch','bite','bullet','pulse'];
foreach ($extra as $c) { if (enc_table_has_column($mysqli, 'base_atk', $c)) $cols[] = $c; }
$listSql  = "SELECT ".implode(',', $cols)." FROM base_atk $whereSql ORDER BY id ASC LIMIT ? OFFSET ?";

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
  if ($t2 !== '') enc_stmt_bind_param($stmt, $t2, $p2);
  $stmt->execute();
  $rows = enc_stmt_fetch_all_assoc($stmt);

  $stmt->close();
}

require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Атаки</h1>
  <div class="muted">Справочник атак. Поиск по названию, типу и категории.</div>

  <form method="get" class="form-row" style="margin-top:10px;">
    <div class="field">
      <label>Поиск</label>
      <input name="q" value="<?=enc_h($q)?>" placeholder="Название...">
    </div>
    <div class="field">
      <label>Тип</label>
      <select name="type">
        <option value="">— любой —</option>
        <?php
        $opts = ['normal','fire','water','grass','electric','ice','fighting','poison','ground','fly','psychic','bug','rock','ghost','dragon','dark','steel','fairy'];
        foreach($opts as $t) {
          $sel = ($type===$t) ? 'selected' : '';
          echo '<option value="'.enc_h($t).'" '.$sel.'>'.enc_h(enc_type_ru($t==='fly'?'flying':$t)).'</option>';
        }
        ?>
      </select>
    </div>
    <div class="field">
      <label>Категория</label>
      <select name="cat">
        <option value="">— любая —</option>
        <?php
          $cats = ['physical'=>'Физическая','special'=>'Специальная','status'=>'Статус','specific'=>'Особая'];
          foreach($cats as $k=>$lbl) {
            $sel = ($cat===$k) ? 'selected' : '';
            echo '<option value="'.enc_h($k).'" '.$sel.'>'.enc_h($lbl).'</option>';
          }
        ?>
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
    <thead><tr><th style="width:70px;">#</th><th>Атака</th><th style="width:190px;">Параметры</th></tr></thead>
    <tbody>
    <?php foreach($rows as $m): ?>
      <tr>
        <td><b>#<?=enc_h((int)$m['id'])?></b></td>
        <td>
          <a class="link" href="<?=enc_h(enc_url('/move.php?id='.(int)$m['id']))?>"><?=enc_h($m['name_rus'] ?: $m['name'])?></a>
          <div class="type-row" style="margin-top:6px;"><?=enc_type_badge((string)$m['type'])?></div>
        </td>
        <td class="muted"><?php $meta = enc_move_meta_ru((int)$m['power'], (int)$m['accuracy'], (int)$m['pp']); ?><?=enc_h(enc_move_category_ru((string)$m['category']))?><?php if ($meta!==''): ?> • <?=enc_h($meta)?><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

  <div class="enc-show-mobile">
    <div class="enc-cardlist">
      <?php foreach($rows as $m): ?>
        <a class="enc-carditem" href="<?=enc_h(enc_url('/move.php?id='.(int)$m['id']))?>">
          <div class="enc-carditem__head">
            <div class="enc-carditem__title"><?=enc_h($m['name_rus'] ?: $m['name'])?></div>
            <div class="enc-carditem__sub"><?=enc_type_badge((string)$m['type'])?> <?=enc_move_category_badge((string)$m['category'])?></div>
          </div>
          <?=enc_move_param_chips($m)?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?=enc_pagination($page, $per, $total, enc_url('/moves.php'), ['q'=>$q,'type'=>$type,'cat'=>$cat])?>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
