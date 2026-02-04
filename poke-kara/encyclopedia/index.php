<?php
require_once __DIR__ . '/_inc/bootstrap.php';
$pageTitle = 'Главная';
require_once __DIR__ . '/_inc/layout_top.php';

$popular = [];
if ($encyInstalled && $encyHasPopularity) {
  $countCol = enc_popularity_count_col($mysqli);
  $q = $mysqli->query("SELECT p.pokemon_id, p.{$countCol} AS cnt, bp.name_rus, bp.name
                       FROM enc_pokemon_popularity p
                       JOIN base_pokemons bp ON bp.id = p.pokemon_id
                       ORDER BY p.{$countCol} DESC
                       LIMIT 12");
  if ($q) while($r=$q->fetch_assoc()) $popular[]=$r;
}

$recentBuilds = [];
if ($encyInstalled) {
  $orderCol = enc_build_has_ts_cols($mysqli) ? 'b.updated_at_ts' : 'b.updated_at';
  $q = $mysqli->query("SELECT b.*, u.login
                       FROM enc_builds b
                       LEFT JOIN users u ON u.id=b.created_by
                       ORDER BY b.is_recommended DESC, b.sort_order DESC, {$orderCol} DESC, b.id DESC
                       LIMIT 60");
  if ($q) {
    while($r=$q->fetch_assoc()) {
      $b = enc_build_normalize_row($r);
      if (enc_can_view_build($encyUser, $b)) {
        $recentBuilds[] = $b;
        if (count($recentBuilds) >= 6) break;
      }
    }
  }
}
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Энциклопедия</h1>
  <div class="muted">Справочник по игре: покемоны, атаки, способности, предметы и сборки. Просмотр доступен всем.</div>
</div>

<div class="grid" style="margin-top:12px;">
  <a class="card" href="<?=enc_h(enc_url('/generations.php'))?>">
    <h3>Поколения</h3>
    <div class="muted">Быстрый выбор поколения (1–9).</div>
  </a>
  <a class="card" href="<?=enc_h(enc_url('/pokedex.php'))?>">
    <h3>Покедекс</h3>
    <div class="muted">Поиск, фильтры по типам и поколениям, формы.</div>
  </a>
  <a class="card" href="<?=enc_h(enc_url('/moves.php'))?>">
    <h3>Атаки</h3>
    <div class="muted">Список атак, параметры и кто изучает.</div>
  </a>
  <a class="card" href="<?=enc_h(enc_url('/abilities.php'))?>">
    <h3>Способности</h3>
    <div class="muted">Описание способностей и покемоны.</div>
  </a>
  <a class="card" href="<?=enc_h(enc_url('/items.php'))?>">
    <h3>Предметы</h3>
    <div class="muted">Справочник предметов и их описание.</div>
  </a>
  <a class="card" href="<?=enc_h(enc_url('/builds.php'))?>">
    <h3>Сборки</h3>
    <div class="muted">Рекомендованные и пользовательские сборки.</div>
  </a>
  <a class="card" href="<?=enc_h(enc_url('/guide.php'))?>">
    <h3>Путеводитель</h3>
    <div class="muted">Статьи и советы. Админ может редактировать.</div>
  </a>
  <a class="card" href="/forum">
    <h3>Форум</h3>
    <div class="muted">Обсуждения, темы и реакции.</div>
  </a>
</div>

<?php if (!empty($popular)): ?>
  <div class="forum-card" style="margin-top:14px;">
    <h2 style="margin:0 0 10px;">Популярные покемоны (по public-сборкам)</h2>
    <div class="grid">
      <?php foreach($popular as $p): $pid=(int)$p['pokemon_id']; ?>
        <a class="card" href="<?=enc_h(enc_url('/pokemon.php?id='.$pid))?>">
          <div class="poke-card">
            <img src="<?=enc_h(enc_sprite_anim($pid))?>" alt="">
            <div>
              <div class="num">#<?=enc_h(enc_pad3($pid))?> • <?=enc_h((int)$p['cnt'])?> сборок</div>
              <div style="font-weight:800;"><?=enc_h($p['name_rus'] ?: $p['name'])?></div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($recentBuilds)): ?>
  <div class="forum-card" style="margin-top:14px;">
    <h2 style="margin:0 0 10px;">Сборки</h2>
    <div class="build-grid">
      <?php foreach($recentBuilds as $b): ?>
        <?=enc_build_card_html($b)?>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
