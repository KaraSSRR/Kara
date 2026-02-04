<?php
require_once __DIR__ . '/_inc/bootstrap.php';

if (!$encyInstalled) {
  $pageTitle = 'Сборки';
  require_once __DIR__ . '/_inc/layout_top.php';
  ?>
  <div class="forum-card">
    <h1 style="margin:0 0 6px;">Сборки</h1>
    <div class="muted">Энциклопедия сейчас недоступна. Обратитесь к администратору.</div>
  </div>
  <?php
  require_once __DIR__ . '/_inc/layout_bottom.php';
  exit;
}

$viewer = $encyUser;

$page = enc_int((isset($_GET['page']) ? $_GET['page'] : 1), 1);
if ($page < 1) $page = 1;
$per = 12;

$q = trim((string)(isset($_GET['q']) ? $_GET['q'] : ''));

$sort = strtolower(trim((string)(isset($_GET['sort']) ? $_GET['sort'] : 'updated')));
if (!in_array($sort, array('updated','popular'), true)) $sort = 'updated';

$filter = strtolower(trim((string)(isset($_GET['filter']) ? $_GET['filter'] : 'available')));
if (!in_array($filter, array('available','mine','public'), true)) $filter = 'available';

$where = [];
$params = [];
$types = '';

$useIndex = $encyHasBuildIndex;
$joinIndex = '';

// Filter by pokemon id: ?pokemon=1 or query like "#1"
$pokemonFilter = enc_int((isset($_GET['pokemon']) ? $_GET['pokemon'] : 0), 0);
if ($pokemonFilter <= 0 && preg_match('~^#?\s*(\d+)\s*$~u', $q, $m)) {
  $pokemonFilter = (int)$m[1];
}

if ($pokemonFilter > 0 && $useIndex) {
  $joinIndex = "JOIN enc_build_pokemon bp ON bp.build_id=b.id AND bp.pokemon_id=".(int)$pokemonFilter;
}

if ($q !== '' && $pokemonFilter <= 0) {
  $where[] = "(b.title LIKE CONCAT('%',?,'%') OR b.description LIKE CONCAT('%',?,'%'))";
  $params[] = $q;
  $params[] = $q;
  $types .= 'ss';
}

if ($filter === 'mine' && $encyUserId > 0) {
  $where[] = "b.created_by=".(int)$encyUserId;
} elseif ($filter === 'public') {
  $where[] = "b.visibility='public'";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$selectPop = '';
$joinPop = '';
if ($sort === 'popular' && enc_has_popularity($mysqli) && $useIndex) {
  $col = enc_popularity_count_col($mysqli);
  $selectPop = ", COALESCE(pop.pop_score,0) AS pop_score";
  $joinPop = "LEFT JOIN (
      SELECT bp2.build_id, SUM(pp2.{$col}) AS pop_score
      FROM enc_build_pokemon bp2
      JOIN enc_pokemon_popularity pp2 ON pp2.pokemon_id=bp2.pokemon_id
      GROUP BY bp2.build_id
    ) pop ON pop.build_id=b.id";
}

$order = "ORDER BY b.is_recommended DESC, b.sort_order DESC";
if ($sort === 'popular' && $selectPop !== '') {
  $order = "ORDER BY COALESCE(pop.pop_score,0) DESC, b.is_recommended DESC, b.sort_order DESC";
}

if (enc_build_has_ts_cols($mysqli)) {
  $order .= ", b.updated_at_ts DESC, b.id DESC";
} else {
  $order .= ", b.updated_at DESC, b.id DESC";
}

$sql = "SELECT b.*, u.login{$selectPop}
        FROM enc_builds b
        LEFT JOIN users u ON u.id=b.created_by
        {$joinIndex}
        {$joinPop}
        {$whereSql}
        {$order}";

$rows = [];
if ($types !== '') {
  $stmt = $mysqli->prepare($sql);
  if ($stmt) {
    enc_stmt_bind_param($stmt, $types, $params);
    $stmt->execute();
    $rows = enc_stmt_fetch_all_assoc($stmt);

    $stmt->close();
  }
} else {
  $res = $mysqli->query($sql);
  if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
}

// ACL filter — no leaks
$filtered = [];
foreach ($rows as $r) {
  $b = enc_build_normalize_row($r);
  if (enc_can_view_build($viewer, $b)) {
    $filtered[] = $b;
  }
}

$total = count($filtered);
$offset = ($page - 1) * $per;
if ($offset < 0) $offset = 0;
$pageItems = array_slice($filtered, $offset, $per);

// Prefetch pokemon names for key labels (current page only)
$pokemonIds = [];
foreach ($pageItems as $b) {
  $slots = enc_team_slots_from_json((string)(isset($b['team_json']) ? $b['team_json'] : ''));
  foreach ($slots as $s) {
    $pid = (int)$s['pokemon_id'];
    if ($pid > 0) $pokemonIds[$pid] = 1;
  }
}
$pokemonNameMap = [];
if ($pokemonIds) {
  $ids = array_map('intval', array_keys($pokemonIds));
  $q2 = $mysqli->query("SELECT id,name,name_rus FROM base_pokemons WHERE id IN (".implode(',', $ids).")");
  if ($q2) while ($r = $q2->fetch_assoc()) {
    $pid = (int)$r['id'];
    $nm = trim((string)$r['name_rus']);
    if ($nm === '') $nm = trim((string)$r['name']);
    $pokemonNameMap[$pid] = $nm;
  }
}

$pageTitle = 'Сборки';
require_once __DIR__ . '/_inc/layout_top.php';

// Preserve query params for pagination and controls
$baseParams = [
  'q' => $q,
  'sort' => $sort,
  'filter' => $filter,
];
if ($pokemonFilter > 0) $baseParams['pokemon'] = $pokemonFilter;
?>

<div class="forum-card">
  <div class="enc-hero">
    <div class="enc-hero__title">
      <h1 style="margin:0;">Сборки</h1>
      <div class="enc-muted" style="margin-top:6px;">Найдено: <?=enc_h((int)$total)?></div>
    </div>
    <?php if ($encyUserId>0): ?>
      <a class="enc-btn enc-btn--primary" href="<?=enc_h(enc_url('/builder.php'))?>">Создать сборку</a>
    <?php endif; ?>
  </div>

  <form method="get" class="enc-toolbar">
    <input type="hidden" name="page" value="1">
    <div class="enc-toolbar__row">
      <div class="enc-toolbar__cell enc-toolbar__cell--grow">
        <input class="enc-input" type="text" name="q" value="<?=enc_h($q)?>" placeholder="Поиск по названию/описанию или ID покемона">
      </div>

      <div class="enc-toolbar__cell">
        <select class="enc-input" name="sort">
          <option value="updated" <?=($sort==='updated'?'selected':'')?>>Обновлено</option>
          <?php if (enc_has_popularity($mysqli) && $useIndex): ?>
            <option value="popular" <?=($sort==='popular'?'selected':'')?>>Популярные</option>
          <?php endif; ?>
        </select>
      </div>

      <div class="enc-toolbar__cell">
        <select class="enc-input" name="filter">
          <option value="available" <?=($filter==='available'?'selected':'')?>>Доступные мне</option>
          <option value="public" <?=($filter==='public'?'selected':'')?>>Публичные</option>
          <?php if ($encyUserId>0): ?>
            <option value="mine" <?=($filter==='mine'?'selected':'')?>>Мои</option>
          <?php endif; ?>
        </select>
      </div>

      <?php if ($pokemonFilter > 0): ?>
        <input type="hidden" name="pokemon" value="<?=enc_h((int)$pokemonFilter)?>">
      <?php endif; ?>

      <div class="enc-toolbar__cell">
        <button class="enc-btn" type="submit">Показать</button>
      </div>
    </div>

    <?php if ($pokemonFilter > 0): ?>
      <div class="enc-help" style="margin-top:8px;">
        Фильтр по покемону: <span class="enc-badge"><?=enc_h((int)$pokemonFilter)?></span>
        <a class="enc-link" href="<?=enc_h(enc_url('/builds.php'))?>">Сбросить</a>
      </div>
    <?php endif; ?>
  </form>

  <?php if (!$pageItems): ?>
    <div class="enc-muted" style="margin-top:12px;">Сборки не найдены.</div>
  <?php else: ?>
    <div class="enc-grid enc-grid--builds" style="margin-top:12px;">
      <?php foreach ($pageItems as $b): ?>
        <?=enc_build_card_html($b, $pokemonNameMap)?>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:12px;">
      <?=enc_pagination($page, $per, $total, enc_url('/builds.php'), $baseParams)?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
