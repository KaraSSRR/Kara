<?php
require_once __DIR__ . '/_inc/bootstrap.php';
$pageTitle = 'Поиск';

$q = trim((string)((isset($_GET['q']) ? $_GET['q'] : '')));
$term = $q;
if (mb_strlen($term) > 60) $term = mb_substr($term, 0, 60);

$pokemons=[]; $moves=[]; $abilities=[]; $items=[]; $builds=[];

if ($term !== '') {
  // Pokemon
  if (preg_match('~^\d{1,4}$~', $term)) {
    $pid=(int)$term;
    $stmt=$mysqli->prepare("SELECT id,name,name_rus,type,type_two FROM base_pokemons WHERE id=? LIMIT 10");
    $stmt->bind_param("i",$pid); $stmt->execute(); $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) $pokemons[]=$r;
    $stmt->close();
  } else {
    $like='%'.$term.'%';
    $stmt=$mysqli->prepare("SELECT id,name,name_rus,type,type_two FROM base_pokemons WHERE name_rus LIKE ? OR name LIKE ? ORDER BY id ASC LIMIT 20");
    $stmt->bind_param("ss",$like,$like); $stmt->execute(); $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) $pokemons[]=$r;
    $stmt->close();
  }

  // Moves
  $like='%'.$term.'%';
  $stmt=$mysqli->prepare("SELECT id,name,name_rus,type,category,power,accuracy,pp FROM base_atk WHERE name_rus LIKE ? OR name LIKE ? OR title_all LIKE ? ORDER BY id ASC LIMIT 20");
  $stmt->bind_param("sss",$like,$like,$like); $stmt->execute(); $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) $moves[]=$r;
  $stmt->close();

  // Abilities
  $stmt=$mysqli->prepare("SELECT id,name,name_rus,about FROM base_ability WHERE name_rus LIKE ? OR name LIKE ? ORDER BY id ASC LIMIT 20");
  $stmt->bind_param("ss",$like,$like); $stmt->execute(); $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) $abilities[]=$r;
  $stmt->close();

  // Items
  $stmt=$mysqli->prepare("SELECT id,name,type,about FROM base_items WHERE name LIKE ? OR about LIKE ? ORDER BY id ASC LIMIT 20");
  $stmt->bind_param("ss",$like,$like); $stmt->execute(); $rows = enc_stmt_fetch_all_assoc($stmt);
  foreach ($rows as $r) $items[]=$r;
  $stmt->close();

  // Builds (ACL filtered)
  if ($encyInstalled) {
    $like = '%'.$term.'%';
    $order = enc_build_has_ts_cols($mysqli) ? 'b.updated_at_ts' : 'b.updated_at';
    $sql = "SELECT b.*, u.login
            FROM enc_builds b
            LEFT JOIN users u ON u.id=b.created_by
            WHERE (b.title LIKE ? OR b.description LIKE ?)
            ORDER BY b.is_recommended DESC, b.sort_order DESC, {$order} DESC
            LIMIT 120";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
      $stmt->bind_param('ss', $like, $like);
      $stmt->execute();
      $rows = enc_stmt_fetch_all_assoc($stmt);
      foreach ($rows as $r) {
        $b = enc_build_normalize_row($r);
        if (enc_can_view_build($encyUser, $b)) {
          $builds[] = $b;
          if (count($builds) >= 20) break;
        }
      }
      $stmt->close();
    }
  }
}

require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Поиск</h1>
  <div class="muted">Введите часть названия или номер.</div>
</div>

<?php if ($term===''): ?>
  <div class="forum-card" style="margin-top:12px;">
    <div class="muted">Пустой запрос.</div>
  </div>
<?php else: ?>

  <div class="forum-card" style="margin-top:12px;">
    <h2 style="margin:0 0 10px;">Покемоны</h2>
    <?php if(!$pokemons): ?><div class="muted">Не найдено.</div>
    <?php else: ?>
      <div class="grid">
        <?php foreach($pokemons as $p): $pid=(int)$p['id']; ?>
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

  <div class="forum-card" style="margin-top:12px;">
    <h2 style="margin:0 0 10px;">Атаки</h2>
    <?php if(!$moves): ?><div class="muted">Не найдено.</div>
    <?php else: ?>
      <table class="table">
        <thead><tr><th style="width:70px;">#</th><th>Атака</th><th style="width:190px;">Параметры</th></tr></thead>
        <tbody>
        <?php foreach($moves as $m): ?>
          <tr>
            <td><b>#<?=enc_h((int)$m['id'])?></b></td>
            <td><a class="link" href="<?=enc_h(enc_url('/move.php?id='.(int)$m['id']))?>"><?=enc_h($m['name_rus'] ?: $m['name'])?></a></td>
            <td class="muted"><?=enc_h(enc_move_category_ru((string)$m['category']))?> • Сила <?=enc_h((int)$m['power'])?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="forum-card" style="margin-top:12px;">
    <h2 style="margin:0 0 10px;">Способности</h2>
    <?php if(!$abilities): ?><div class="muted">Не найдено.</div>
    <?php else: ?>
      <table class="table">
        <thead><tr><th style="width:70px;">#</th><th>Способность</th><th>Описание</th></tr></thead>
        <tbody>
        <?php foreach($abilities as $a): ?>
          <tr>
            <td><b>#<?=enc_h((int)$a['id'])?></b></td>
            <td><a class="link" href="<?=enc_h(enc_url('/ability.php?id='.(int)$a['id']))?>"><?=enc_h($a['name_rus'] ?: $a['name'])?></a></td>
            <td class="muted"><?=enc_h(mb_substr((string)((isset($a['about']) ? $a['about'] : '')),0,160))?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="forum-card" style="margin-top:12px;">
    <h2 style="margin:0 0 10px;">Предметы</h2>
    <?php if(!$items): ?><div class="muted">Не найдено.</div>
    <?php else: ?>
      <table class="table">
        <thead><tr><th style="width:70px;">#</th><th>Предмет</th><th style="width:120px;">Тип</th></tr></thead>
        <tbody>
        <?php foreach($items as $it): ?>
          <tr>
            <td><b>#<?=enc_h((int)$it['id'])?></b></td>
            <td><a class="link" href="<?=enc_h(enc_url('/item.php?id='.(int)$it['id']))?>"><?=enc_h($it['name'])?></a></td>
            <td class="muted"><?=enc_h((string)$it['type'])?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <?php if($builds): ?>
    <div class="forum-card" style="margin-top:12px;">
      <h2 style="margin:0 0 10px;">Сборки</h2>
      <div class="build-grid">
        <?php foreach($builds as $b): ?>
          <a class="card" href="<?=enc_h(enc_url('/build.php?id='.(int)$b['id']))?>">
            <div style="display:flex; justify-content:space-between; gap:10px;">
              <div style="font-weight:900;"><?=enc_h($b['title'])?></div>
              <?php if((int)$b['is_recommended']===1): ?><span class="badge rec">Рекомендовано</span><?php endif; ?>
            </div>
            <div class="muted" style="margin-top:6px;">Автор: <?=enc_h($b['login'] ?: '')?> • <?=enc_h($b['visibility'])?></div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
