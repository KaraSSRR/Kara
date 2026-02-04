<?php
require_once __DIR__ . '/_inc/bootstrap.php';

if (!$encyInstalled) {
  $pageTitle = 'Сборка';
  require_once __DIR__ . '/_inc/layout_top.php';
  ?>
  <div class="forum-card">
    <h1 style="margin:0 0 6px;">Сборка</h1>
    <div class="muted">Энциклопедия сейчас недоступна. Обратитесь к администратору.</div>
  </div>
  <?php
  require_once __DIR__ . '/_inc/layout_bottom.php';
  exit;
}

$viewer = $encyUser;

$id = enc_int((isset($_GET['id']) ? $_GET['id'] : 0), 0);
if ($id <= 0) {
  header('Location: ' . enc_url('/builds.php'));
  exit;
}

// Load build (do not depend on optional users columns)
$sql = "SELECT * FROM enc_builds WHERE id=".(int)$id." LIMIT 1";
$res = $mysqli->query($sql);
$build = $res ? $res->fetch_assoc() : null;

if ($build) {
  $authorLogin = '';
  $authorId = (int)(isset($build['created_by']) ? $build['created_by'] : 0);
  if ($authorId > 0) {
    $u = enc_fetch_user($mysqli, $authorId);
    if ($u && isset($u['login'])) $authorLogin = (string)$u['login'];
  }
  $build['login'] = $authorLogin;
}

if (!$build) {
  $pageTitle = 'Сборка не найдена';
  require_once __DIR__ . '/_inc/layout_top.php';
  ?>
  <div class="forum-card">
    <h1 style="margin:0 0 6px;">Сборка не найдена</h1>
    <div class="enc-muted">Проверьте ссылку и попробуйте снова.</div>
    <div style="margin-top:12px;"><a class="enc-btn" href="<?=enc_h(enc_url('/builds.php'))?>">К списку сборок</a></div>
  </div>
  <?php
  require_once __DIR__ . '/_inc/layout_bottom.php';
  exit;
}

$build = enc_build_normalize_row($build);

if (!enc_can_view_build($viewer, $build)) {
  $pageTitle = 'Нет доступа';
  require_once __DIR__ . '/_inc/layout_top.php';
  ?>
  <div class="forum-card enc-empty">
    <h1 style="margin:0 0 6px;">Нет доступа</h1>
    <div class="enc-muted">У вас нет прав на просмотр этой сборки.</div>
    <div style="margin-top:12px;"><a class="enc-btn" href="<?=enc_h(enc_url('/builds.php'))?>">К списку сборок</a></div>
  </div>
  <?php
  require_once __DIR__ . '/_inc/layout_bottom.php';
  exit;
}

$canEdit = enc_can_edit_build($viewer, $build);

$title = trim((string)(isset($build['title']) ? $build['title'] : ''));
if ($title === '') $title = 'Сборка ' . (int)$build['id'];

$author = (string)(isset($build['login']) ? $build['login'] : '');
$visibility = (string)(isset($build['visibility']) ? $build['visibility'] : 'private');
$updated = enc_human_updated_at((int)(isset($build['updated_at_ts']) ? $build['updated_at_ts'] : 0));

// Slots
$slots = enc_team_slots_from_json((string)(isset($build['team_json']) ? $build['team_json'] : ''));

// Prefetch pokemon, abilities, moves
$pokemonIds = [];
$formPairs = [];
$abilityIds = [];
$moveIds = [];

foreach ($slots as $s) {
  $pid = (int)$s['pokemon_id'];
  if ($pid <= 0) continue;

  $pokemonIds[$pid] = 1;
  $form = enc_sanitize_form((string)$s['form']);
  if ($form !== '') $formPairs[$pid . '|' . $form] = ['pid'=>$pid,'form'=>$form];

  $aid = (int)$s['ability_id'];
  if ($aid > 0) $abilityIds[$aid] = 1;

  foreach ((array)$s['moves'] as $mid) {
    $mid = (int)$mid;
    if ($mid > 0) $moveIds[$mid] = 1;
  }
}

$pokemonMap = [];
if ($pokemonIds) {
  $ids = array_map('intval', array_keys($pokemonIds));
  $q = $mysqli->query("SELECT id,name,name_rus,type,type_two,hp,atk,def,satk,sdef,spd FROM base_pokemons WHERE id IN (".implode(',', $ids).")");
  if ($q) while ($r = $q->fetch_assoc()) {
    $pid = (int)$r['id'];
    $pokemonMap[$pid] = $r;
  }
}

$formMap = [];
if ($formPairs) {
  $stmt = $mysqli->prepare("SELECT pokemons,id_form,name,type,type_two,hp,atk,def,satk,sdef,spd,start FROM base_pokemon_forms WHERE pokemons=? AND id_form=? LIMIT 1");
  foreach ($formPairs as $p) {
    $pid = (int)$p['pid'];
    $form = (string)$p['form'];
    $stmt->bind_param('is', $pid, $form);
    $stmt->execute();
    $row = enc_stmt_fetch_assoc($stmt);
    if ($row) $formMap[$pid . '|' . $form] = $row;
  }
  $stmt->close();
}

$abilityMap = [];
if ($abilityIds) {
  $ids = array_map('intval', array_keys($abilityIds));
  $q = $mysqli->query("SELECT id,name,name_rus FROM base_ability WHERE id IN (".implode(',', $ids).")");
  if ($q) while ($r = $q->fetch_assoc()) $abilityMap[(int)$r['id']] = $r;
}

$moveMap = [];
if ($moveIds) {
  $ids = array_map('intval', array_keys($moveIds));
  $q = $mysqli->query("SELECT id,name,name_rus,type,category,power,accuracy,pp FROM base_atk WHERE id IN (".implode(',', $ids).")");
  if ($q) while ($r = $q->fetch_assoc()) $moveMap[(int)$r['id']] = $r;
}

function enc_stat_label($k) {
  $map = ['hp'=>'HP','atk'=>'Atk','def'=>'Def','satk'=>'SpA','sdef'=>'SpD','spd'=>'Spe'];
  return (isset($map[$k]) ? $map[$k] : $k);
}

function enc_move_cat_badge($cat) {
  $c = strtolower(trim((string)$cat));
  if ($c === 'physical' || $c === 'phys') return '<span class="enc-badge enc-badge--cat enc-badge--physical">Физ</span>';
  if ($c === 'special' || $c === 'spec') return '<span class="enc-badge enc-badge--cat enc-badge--special">Спец</span>';
  return '<span class="enc-badge enc-badge--cat enc-badge--status">Статус</span>';
}

$pageTitle = $title;
require_once __DIR__ . '/_inc/layout_top.php';

$link = enc_url('/build.php?id=' . (int)$build['id']);
?>

<div class="forum-card">
  <div class="enc-hero">
    <div class="enc-hero__title">
      <h1 style="margin:0;"><?=enc_h($title)?></h1>
      <div class="enc-muted" style="margin-top:6px;">
        <?php if ($author !== ''): ?>Автор: <?=enc_h($author)?> • <?php endif; ?>
        <?=enc_build_visibility_badge($visibility)?>
        <?php if ($updated !== ''): ?>
          <span class="enc-muted" style="margin-left:8px;">обновлено <?=enc_h($updated)?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="enc-hero__actions">
      <?php if ($canEdit): ?>
        <a class="enc-btn enc-btn--primary" href="<?=enc_h(enc_url('/builder.php?id='.(int)$build['id']))?>">Редактировать</a>
      <?php endif; ?>
      <button type="button" class="enc-btn enc-btn--ghost js-copy-link" data-link="<?=enc_h($link)?>">Копировать ссылку</button>
    </div>
  </div>

  <?php $desc = trim((string)(isset($build['description']) ? $build['description'] : '')); ?>
  <?php if ($desc !== ''): ?>
    <div class="enc-card" style="margin-top:12px;">
      <div class="enc-card__body">
        <?=enc_render_bbcode($desc)?>
      </div>
    </div>
  <?php endif; ?>

  <div class="enc-grid enc-grid--slots" style="margin-top:12px;">
    <?php foreach ($slots as $i=>$s): ?>
      <?php $pid = (int)$s['pokemon_id']; ?>
      <?php if ($pid <= 0): continue; endif; ?>

      <?php
        $form = enc_sanitize_form((string)$s['form']);
        $p = (isset($pokemonMap[$pid]) ? $pokemonMap[$pid] : null);
        $formRow = ($form !== '' ? (isset($formMap[$pid.'|'.$form]) ? $formMap[$pid.'|'.$form] : null) : null);

        $name = '';
        $nameRu = '';
        $types = [];
        $baseStats = ['hp'=>0,'atk'=>0,'def'=>0,'satk'=>0,'sdef'=>0,'spd'=>0];

        if ($p) {
          $name = (string)$p['name'];
          $nameRu = (string)$p['name_rus'];
          $types[] = (string)$p['type'];
          if ((string)$p['type_two'] !== '' && (string)$p['type_two'] !== 'not') $types[] = (string)$p['type_two'];
          foreach ($baseStats as $k=>$v) $baseStats[$k] = (int)$p[$k];
        }

        if ($formRow) {
          $types = [(string)$formRow['type']];
          if ((string)$formRow['type_two'] !== '' && (string)$formRow['type_two'] !== 'not') $types[] = (string)$formRow['type_two'];
          foreach ($baseStats as $k=>$v) $baseStats[$k] = (int)$formRow[$k];
        }

        $displayName = trim($nameRu);
        if ($displayName === '') $displayName = trim($name);
        if ($displayName === '') $displayName = 'Покемон #' . $pid;

        $isMega = ($formRow && (int)$formRow['start'] === 0);
        $formLabel = '';
        if ($form !== '') {
          if ($isMega) $formLabel = 'Mega';
          else $formLabel = trim((string)($formRow ? $formRow['name'] : $form));
        }

        $lvl = (int)$s['level'];
        $nat = (string)$s['nature'];
        $ev = (array)$s['ev'];
        $iv = (array)$s['iv'];
        $ivCode = (string)$s['iv_code'];

        $aid = (int)$s['ability_id'];
        $abilityName = '—';
        if ($aid > 0) {
          if (isset($abilityMap[$aid])) {
            $abilityName = trim((string)$abilityMap[$aid]['name_rus']);
            if ($abilityName === '') $abilityName = trim((string)$abilityMap[$aid]['name']);
            if ($abilityName === '') $abilityName = 'Способность #' . $aid;
          } else {
            $abilityName = 'Неизвестная способность';
          }
        }

        // Final stats
        $final = [];
        $final['hp'] = enc_calc_stat((int)$baseStats['hp'], (int)$iv['hp'], (int)$ev['hp'], $lvl, $nat, 'hp');
        $final['atk'] = enc_calc_stat((int)$baseStats['atk'], (int)$iv['atk'], (int)$ev['atk'], $lvl, $nat, 'atk');
        $final['def'] = enc_calc_stat((int)$baseStats['def'], (int)$iv['def'], (int)$ev['def'], $lvl, $nat, 'def');
        $final['satk'] = enc_calc_stat((int)$baseStats['satk'], (int)$iv['satk'], (int)$ev['satk'], $lvl, $nat, 'satk');
        $final['sdef'] = enc_calc_stat((int)$baseStats['sdef'], (int)$iv['sdef'], (int)$ev['sdef'], $lvl, $nat, 'sdef');
        $final['spd'] = enc_calc_stat((int)$baseStats['spd'], (int)$iv['spd'], (int)$ev['spd'], $lvl, $nat, 'spd');

        // EV summary
        $evParts = [];
        $evTotal = 0;
        foreach (['hp','atk','def','satk','sdef','spd'] as $k) {
          $v = (int)(isset($ev[$k]) ? $ev[$k] : 0);
          $evTotal += $v;
          if ($v > 0) $evParts[] = enc_stat_label($k) . ' ' . $v;
        }
        $evText = ($evParts ? ('Усилия: ' . implode(' / ', $evParts) . ' (' . $evTotal . '/510)') : 'Усилия: —');

        $genesLine = enc_genes_line($iv);
        $genesCode = ($ivCode !== '' ? $ivCode : enc_genes_code($iv));
?>

      <div class="enc-card enc-slot-card">
        <div class="enc-slot-card__head">
          <div class="enc-slot-card__sprite">
            <?=enc_sprite_img($pid, $form, 'anim', 88, 'enc-slot-sprite')?>
          </div>
          <div class="enc-slot-card__main">
            <div class="enc-slot-card__name">
              <a class="enc-link" href="<?=enc_h(enc_url('/pokemon.php?id='.$pid))?>"><?=enc_h($displayName)?></a>
              <?php if ($formLabel !== ''): ?>
                <span class="enc-badge <?=($isMega?'enc-badge--mega':'enc-badge--form')?>"><?=enc_h($formLabel)?></span>
              <?php endif; ?>
            </div>
            <div class="enc-slot-card__types">
              <?php foreach ($types as $t): ?><?=enc_type_badge($t)?><?php endforeach; ?>
            </div>
            <div class="enc-slot-card__meta">
              <span class="enc-muted">Уровень <?=enc_h($lvl)?></span>
              <span class="enc-muted"> • Характер: <?=enc_h(enc_nature_ru($nat))?></span>
            </div>
          </div>
        </div>

        <div class="enc-slot-card__body">
          <div class="enc-row">
            <div class="enc-row__label">Способность</div>
            <div class="enc-row__value"><?=enc_h($abilityName)?></div>
          </div>

          <div class="enc-row" style="margin-top:8px;">
            <div class="enc-row__label">Атаки</div>
            <div class="enc-row__value">
              <div class="enc-moves">
                <?php
                  $moves = (array)$s['moves'];
                  for ($mi=0; $mi<4; $mi++):
                    $mid = (int)(isset($moves[$mi]) ? $moves[$mi] : 0);
                    if ($mid <= 0) { ?>
                      <div class="enc-move enc-move--empty">—</div>
                    <?php continue; }

                    $mv = (isset($moveMap[$mid]) ? $moveMap[$mid] : null);
                    $mvName = $mv ? trim((string)$mv['name_rus']) : '';
                    if ($mvName === '' && $mv) $mvName = trim((string)$mv['name']);
                    if ($mvName === '') $mvName = 'Неизвестная атака';

                    $mvType = $mv ? (string)$mv['type'] : '';
                    $mvCat = $mv ? (string)$mv['category'] : '';
                    $pow = $mv ? (int)$mv['power'] : 0;
                    $acc = $mv ? (int)$mv['accuracy'] : 0;
                    $pp  = $mv ? (int)$mv['pp'] : 0;

                    $metaParts = [];
                    if ($pow > 0) $metaParts[] = 'Сила ' . $pow;
                    if ($acc > 0) $metaParts[] = 'Точность ' . $acc;
                    if ($pp > 0) $metaParts[] = 'PP ' . $pp;
                    $metaTxt = ($metaParts ? implode(' • ', $metaParts) : '');
                ?>
                    <div class="enc-move" title="ID атаки: <?=enc_h($mid)?>">
                      <div class="enc-move__line">
                        <?php if ($mv && $mvName !== 'Неизвестная атака'): ?>
                          <a class="enc-link" href="<?=enc_h(enc_url('/move.php?id='.$mid))?>"><?=enc_h($mvName)?></a>
                        <?php else: ?>
                          <span><?=enc_h($mvName)?></span>
                        <?php endif; ?>
                        <?php if ($mvType !== ''): ?><?=enc_type_badge($mvType)?><?php endif; ?>
                        <?=enc_move_cat_badge($mvCat)?>
                      </div>
                      <?php if ($metaTxt !== ''): ?>
                        <div class="enc-muted enc-move__meta"><?=enc_h($metaTxt)?></div>
                      <?php endif; ?>
                    </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>

          <div class="enc-row" style="margin-top:10px;">
            <div class="enc-row__label">Статы</div>
            <div class="enc-row__value">
              <?php
              $bst = 0;
              foreach (['hp','atk','def','satk','sdef','spd'] as $__k) $bst += (int)$baseStats[$__k];
              $baseLine = 'База: '
                .'HP '.(int)$baseStats['hp'].' / '
                .'Atk '.(int)$baseStats['atk'].' / '
                .'Def '.(int)$baseStats['def'].' / '
                .'SpA '.(int)$baseStats['satk'].' / '
                .'SpD '.(int)$baseStats['sdef'].' / '
                .'Spe '.(int)$baseStats['spd']
                .' (BST '.$bst.')';
            ?>
            <div class="enc-muted enc-stats__base"><?=enc_h($baseLine)?></div>
            <div class="enc-stats">
                <?php foreach (['hp','atk','def','satk','sdef','spd'] as $k): ?>
                  <div class="enc-stat">
                    <div class="enc-stat__k"><?=enc_h(enc_stat_label($k))?></div>
                    <div class="enc-stat__v"><?=enc_h((int)$final[$k])?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="enc-row" style="margin-top:10px;">
  <div class="enc-row__label">Усилия и гены</div>
  <div class="enc-row__value">
    <div class="enc-help"><?=enc_h($evText)?></div>
    <div class="enc-help" style="margin-top:6px;"><?=enc_h($genesLine)?></div>
    <div class="enc-help" style="margin-top:6px;">
      <span><span class="enc-muted">Код генов:</span> <code class="enc-code"><?=enc_h($genesCode)?></code></span>
      <button type="button" class="enc-btn enc-btn--mini js-copy" data-copy="<?=enc_h($genesCode)?>">копировать</button>
    </div>
  </div>
</div>

          </div>

        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<script>
(function(){
  const copyBtns = document.querySelectorAll('.js-copy');
  for (const b of copyBtns){
    b.addEventListener('click', async function(){
      const txt = this.getAttribute('data-copy') || '';
      const ok = await (window.encCopyText ? window.encCopyText(txt) : false);
      this.textContent = ok ? 'скопировано' : 'ошибка';
      setTimeout(()=>{ this.textContent='копировать'; }, 1200);
    });
  }

  const linkBtn = document.querySelector('.js-copy-link');
  if (linkBtn){
    linkBtn.addEventListener('click', async function(){
      const txt = this.getAttribute('data-link') || window.location.href;
      const ok = await (window.encCopyText ? window.encCopyText(txt) : false);
      this.textContent = ok ? 'ссылка скопирована' : 'ошибка';
      setTimeout(()=>{ this.textContent='Копировать ссылку'; }, 1400);
    });
  }
})();
</script>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
