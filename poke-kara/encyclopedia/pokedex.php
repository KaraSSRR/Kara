<?php
require_once __DIR__ . '/_inc/bootstrap.php';
$pageTitle = 'Покедекс';

$q = trim((string)((isset($_GET['q']) ? $_GET['q'] : '')));
$gen = enc_int((isset($_GET['gen']) ? $_GET['gen'] : 0), 0);
$type = strtolower(trim((string)((isset($_GET['type']) ? $_GET['type'] : ''))));
$page = max(1, enc_int((isset($_GET['page']) ? $_GET['page'] : 1), 1));
$per = ENC_POKEDEX_PER_PAGE;

$where = [];
$params = [];
$types = "s";
$gens = enc_generations();
$range = $gen > 0 ? ((isset($gens[$gen]) ? $gens[$gen] : null)) : null;

if ($range) {
  $where[] = "id BETWEEN ? AND ?";
  $params[] = (int)$range['from'];
  $params[] = (int)$range['to'];
  $types = "ii";
} else {
  $types = "";
}

if ($q !== '') {
  if (preg_match('~^\d{1,4}$~', $q)) {
    $where[] = "id = ?";
    $params[] = (int)$q;
    $types .= "i";
  } else {
    $where[] = "(name_rus LIKE ? OR name LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
  }
}

$allowedTypes = ['normal','fighting','fly','flying','poison','ground','rock','bug','ghost','steel','fire','water','grass','electric','psychic','ice','dragon','dark','fairy'];
if ($type !== '' && in_array($type, $allowedTypes, true)) {
  // In DB flying is 'fly'
  $tDb = ($type === 'flying') ? 'fly' : $type;
  $where[] = "(type=? OR type_two=?)";
  $params[] = $tDb;
  $params[] = $tDb;
  $types .= "ss";
} else {
  $type = '';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$countSql = "SELECT COUNT(*) c FROM base_pokemons $whereSql";
$listSql  = "SELECT id, name, name_rus, type, type_two FROM base_pokemons $whereSql ORDER BY id ASC LIMIT ? OFFSET ?";

$total = 0;
$rows = [];

if ($stmt = $mysqli->prepare($countSql)) {
  if ($types !== '') enc_stmt_bind_param($stmt, $types, $params);
  $stmt->execute();
  $row = enc_stmt_fetch_assoc($stmt);
  $total = ($row && isset($row['c'])) ? (int)$row['c'] : 0;
  $stmt->close();
}

$offset = ($page - 1) * $per;
if ($stmt = $mysqli->prepare($listSql)) {
  $t2 = $types . "ii";
  $params2 = $params;
  $params2[] = $per;
  $params2[] = $offset;
  if ($t2 !== '') enc_stmt_bind_param($stmt, $t2, $params2);
  $stmt->execute();
  $rows = enc_stmt_fetch_all_assoc($stmt);

  $stmt->close();
}

require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Покедекс</h1>
  <div class="muted">Поиск по имени/номеру, фильтр по поколениям и типам. Формы доступны на странице покемона.</div>

  <div class="tabs pokedex-tabs" style="margin-top:10px;">
    <a class="tab <?=($gen===0?'active':'')?>" href="<?=enc_h(enc_url('/pokedex.php'))?>">Все</a>
    <?php foreach($gens as $g=>$r): ?>
      <a class="tab <?=($gen===(int)$g?'active':'')?>" href="<?=enc_h(enc_url('/pokedex.php?'.http_build_query(['gen'=>$g, 'type'=>$type, 'q'=>$q])))?>">Gen <?=enc_h((int)$g)?></a>
    <?php endforeach; ?>
  </div>

  <form method="get" class="form-row">
    <div class="field">
      <label>Поиск</label>
      <input name="q" value="<?=enc_h($q)?>" placeholder="Имя или номер">
    </div>
    <div class="field">
      <label>Тип</label>
      <select name="type">
        <option value="">— любой —</option>
        <?php
          $typeOpts = ['normal','fire','water','grass','electric','ice','fighting','poison','ground','flying','psychic','bug','rock','ghost','dragon','dark','steel','fairy'];
          foreach($typeOpts as $t):
            $val = $t;
            $sel = ($type === $val) ? 'selected' : '';
            echo '<option value="'.enc_h($val).'" '.$sel.'>'.enc_h(enc_type_ru($val)).'</option>';
          endforeach;
        ?>
      </select>
    </div>
    <div class="field">
      <label>Поколение</label>
      <select name="gen">
        <option value="0">Все</option>
        <?php foreach($gens as $g=>$r): ?>
          <option value="<?=enc_h((int)$g)?>" <?=($gen===(int)$g?'selected':'')?>>Gen <?=enc_h((int)$g)?> (#<?=enc_h((int)$r['from'])?>-<?=enc_h((int)$r['to'])?>)</option>
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

  <div class="grid grid--pokedex">
    <?php foreach($rows as $p): $pid=(int)$p['id']; ?>
      <a class="card" href="<?=enc_h(enc_url('/pokemon.php?id='.$pid))?>">
        <div class="poke-card">
          <img src="<?=enc_h(enc_sprite_anim($pid))?>" alt="">
          <div>
            <div class="num">#<?=enc_h(enc_pad3($pid))?></div>
            <div style="font-weight:900;"><?=enc_h($p['name_rus'] ?: $p['name'])?></div>
            <div class="type-row">
              <?=enc_type_badge((string)$p['type'])?>
              <?php if (!empty($p['type_two']) && (string)$p['type_two'] !== 'not'): ?>
                <?=enc_type_badge((string)$p['type_two'])?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <?=enc_pagination($page, $per, $total, enc_url('/pokedex.php'), ['gen'=>$gen,'type'=>$type,'q'=>$q])?>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
