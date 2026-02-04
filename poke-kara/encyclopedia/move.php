<?php
require_once __DIR__ . '/_inc/bootstrap.php';

$id = enc_int((isset($_GET['id']) ? $_GET['id'] : 0), 0);
if ($id<=0) { http_response_code(404); die('Атака не найдена'); }

$stmt = $mysqli->prepare("SELECT * FROM base_atk WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$move = enc_stmt_fetch_assoc($stmt);
$stmt->close();
if (!$move) { http_response_code(404); die('Атака не найдена'); }

$pageTitle = ($move['name_rus'] ?: $move['name']) . ' #' . $id;
require_once __DIR__ . '/_inc/layout_top.php';

// Find pokemons who learn (lvl)
$pokes = [];
$q = $mysqli->query("SELECT DISTINCT CAST(pok AS UNSIGNED) pid
                     FROM base_attacks_pokemons
                     WHERE type='lvl' AND FIND_IN_SET(".(int)$id.", REPLACE(attacks,' ',''))");
if ($q) while($r=$q->fetch_assoc()) {
  $pid=(int)$r['pid'];
  if ($pid>0) $pokes[]=$pid;
}
$pokes = array_slice($pokes, 0, 200);

$pokeRows = [];
if ($pokes) {
  $in = implode(',', array_map('intval', $pokes));
  $q = $mysqli->query("SELECT id,name_rus,name,type,type_two FROM base_pokemons WHERE id IN ($in) ORDER BY id ASC");
  if ($q) while($r=$q->fetch_assoc()) $pokeRows[]=$r;
}
?>

<div class="forum-card enc-hero">
  <div class="enc-hero__main">
    <div class="enc-hero__kicker">Атака</div>
    <h1 class="enc-hero__title" title="ID атаки: <?=enc_h($id)?>"><?=enc_h($move['name_rus'] ?: $move['name'])?></h1>
    <div class="enc-hero__meta">
      <div class="type-row">
        <?=enc_type_badge((string)$move['type'])?>
        <?=enc_move_category_badge((string)$move['category'])?>
      </div>
      <?=enc_move_param_chips($move)?>
    </div>
  </div>
</div>
</div>

<div class="forum-card" style="margin-top:12px;">
  <h2 style="margin:0 0 10px;">Описание / эффекты</h2>
  <div><?=enc_render_richtext((string)((isset($move['mechanics']) ? $move['mechanics'] : '')))?></div>
</div>

<div class="forum-card" style="margin-top:12px;">
  <h2 style="margin:0 0 10px;">Кто может изучить (уровень)</h2>
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
