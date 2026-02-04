<?php
require_once __DIR__ . '/_inc/bootstrap.php';
$pageTitle = 'Поколения';
require_once __DIR__ . '/_inc/layout_top.php';

$gens = enc_generations();
$counts = [];
foreach($gens as $g=>$r) {
  $from=(int)$r['from']; $to=(int)$r['to'];
  $q = $mysqli->query("SELECT COUNT(*) c FROM base_pokemons WHERE id BETWEEN $from AND $to");
  $counts[$g]= enc_query_count($q);
}
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Поколения</h1>
  <div class="muted">Выберите поколение для просмотра покедекса.</div>
</div>

<div class="grid" style="margin-top:12px;">
<?php foreach($gens as $g=>$r): ?>
  <a class="card" href="<?=enc_h(enc_url('/pokedex.php?gen='.(int)$g))?>">
    <h3>Поколение <?=enc_h((int)$g)?></h3>
    <div class="muted">#<?=enc_h((int)$r['from'])?>–#<?=enc_h((int)$r['to'])?> • <?=enc_h((isset($counts[$g]) ? $counts[$g] : 0))?> пок.</div>
  </a>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
