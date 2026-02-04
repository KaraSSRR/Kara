<?php
require_once __DIR__ . '/_inc/bootstrap.php';

$id = enc_int((isset($_GET['id']) ? $_GET['id'] : 0), 0);
if ($id<=0) { http_response_code(404); die('Способность не найдена'); }

$stmt = $mysqli->prepare("SELECT * FROM base_ability WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$ab = enc_stmt_fetch_assoc($stmt);
$stmt->close();
if (!$ab) { http_response_code(404); die('Способность не найдена'); }

$pageTitle = ($ab['name_rus'] ?: $ab['name']) . ' #' . $id;
require_once __DIR__ . '/_inc/layout_top.php';

// find pokemons with this ability
$pids = [];
$q = $mysqli->query("SELECT id FROM base_ability_pokemon WHERE slot1=".(int)$id." OR slot2=".(int)$id." OR hidden=".(int)$id." LIMIT 500");
if ($q) while($r=$q->fetch_assoc()) $pids[]=(int)$r['id'];

$pokeRows = [];
if ($pids) {
  $in = implode(',', array_map('intval', $pids));
  $q = $mysqli->query("SELECT id,name_rus,name,type,type_two FROM base_pokemons WHERE id IN ($in) ORDER BY id ASC");
  if ($q) while($r=$q->fetch_assoc()) $pokeRows[]=$r;
}
?>

<div class="forum-card enc-hero">
  <h1 class="enc-hero__title" style="margin:0 0 6px;" title="ID способности: <?=enc_h($id)?>"><?=enc_h($ab['name_rus'] ?: $ab['name'])?></h1>
  <div class="enc-muted enc-small" title="ID способности: <?=enc_h($id)?>">Способность</div>
</div>

<div class="forum-card" style="margin-top:12px;">
  <h2 style="margin:0 0 10px;">Описание</h2>
  <div><?=enc_render_richtext((string)((isset($ab['about']) ? $ab['about'] : '')))?></div>
</div>

<div class="forum-card" style="margin-top:12px;">
  <h2 style="margin:0 0 10px;">Покемоны со способностью</h2>
  <?php if (!$pokeRows): ?>
    <div class="muted">Не найдено.</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach($pokeRows as $p): $pid=(int)$p['id']; ?>
        <a class="card" href="<?=enc_h(enc_url('/pokemon.php?id='.$pid))?>">
          <div class="poke-card">
            <img src="<?=enc_h(enc_sprite_anim($pid))?>" alt="">
            <div>
              <div class="num">#<?=enc_h(enc_pad3($pid))?></div>
              <div style="font-weight:900;"><?=enc_h($p['name_rus'] ?: $p['name'])?></div>
              <div class="type-row">
                <?=enc_type_badge((string)$p['type'])?>
                <?php if((string)$p['type_two']!=='not'): ?><?=enc_type_badge((string)$p['type_two'])?><?php endif; ?>
              </div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
