<?php
require_once __DIR__ . '/_inc/bootstrap.php';

enc_require_login($encyUserId);

if (!$encyInstalled) {
  $pageTitle='Редактор сборок';
  require_once __DIR__ . '/_inc/layout_top.php';
  ?>
  <div class="forum-card">
    <h1 style="margin:0 0 6px;">Редактор сборок</h1>
    <div class="muted">Энциклопедия сейчас недоступна. Обратитесь к администратору.</div>
  </div>
  <?php
  require_once __DIR__ . '/_inc/layout_bottom.php';
  exit;
}

$buildId = enc_int((isset($_GET['id']) ? $_GET['id'] : 0), 0);
$isEdit = ($buildId > 0);

$viewer = $encyUser;

$build = null;
if ($isEdit) {
  $stmt = $mysqli->prepare("SELECT b.*, u.login FROM enc_builds b LEFT JOIN users u ON u.id=b.created_by WHERE b.id=? LIMIT 1");
  $stmt->bind_param('i', $buildId);
  $stmt->execute();
  $build = enc_stmt_fetch_assoc($stmt);
  $stmt->close();

  if (!$build) {
    http_response_code(404);
    die('Сборка не найдена');
  }

  $build = enc_build_normalize_row($build);
  if (!enc_can_edit_build($viewer, $build)) {
    http_response_code(403);
    die('Нет доступа');
  }
}

// Defaults
$title = $isEdit ? (string)$build['title'] : '';
$description = $isEdit ? (string)$build['description'] : '';
$visibility = $isEdit ? (string)$build['visibility'] : 'private';
$sharedUserId = $isEdit ? enc_int((isset($build['shared_user_id']) ? $build['shared_user_id'] : 0), 0) : 0;

$slots = $isEdit ? enc_team_slots_from_json((string)$build['team_json']) : enc_team_slots_from_json('{"v":2,"slots":[]}');

// Access rules (team_json.access)
$accessView = '';
$accessEdit = '';
$accessDeny = '';
if ($isEdit) {
  $rules = enc_build_access_from_team_json((string)$build['team_json']);
  // Store back as joined string for UI
  $accessView = implode(', ', (isset($rules['view']) ? $rules['view'] : []));
  $accessEdit = implode(', ', (isset($rules['edit']) ? $rules['edit'] : []));
  $accessDeny = implode(', ', (isset($rules['deny']) ? $rules['deny'] : []));
}

$errors = [];
$doneId = 0;

function enc_builder_allowed_moves(mysqli $mysqli, $pokemonId, $formReq) {
  $pokemonId = (int)$pokemonId;
  $formReq = enc_sanitize_form($formReq);
  $formDb = ($formReq === '') ? '0' : $formReq;

  $types = ['lvl','tm','hm'];
  $allowed = [];
  $hasAny = false;

  foreach ($types as $t) {
    $stmt = $mysqli->prepare("SELECT attacks FROM base_attacks_pokemons WHERE pok=? AND type=? AND form=? LIMIT 1");
    $stmt->bind_param('iss', $pokemonId, $t, $formDb);
    $stmt->execute();
    $row = enc_stmt_fetch_assoc($stmt);
    $stmt->close();

    if ($row) $hasAny = true;

    if (!$row && $formDb !== '0') {
      $stmt = $mysqli->prepare("SELECT attacks FROM base_attacks_pokemons WHERE pok=? AND type=? AND form='0' LIMIT 1");
      $stmt->bind_param('is', $pokemonId, $t);
      $stmt->execute();
      $row = enc_stmt_fetch_assoc($stmt);
      $stmt->close();
    }

    if ($row) {
      $att = (string)$row['attacks'];
      $att = trim($att);
      if ($att !== '') {
        foreach (preg_split('~\s*,\s*~', $att) as $p) {
          $p = trim((string)$p);
          if ($p === '' || !preg_match('~^\d+$~', $p)) continue;
          $allowed[(int)$p] = true;
        }
      }
    }
  }

  
  // TM moves come from the game's TM tables (attac_poke_tm + base_items.info),
  // not from base_attacks_pokemons in some server schemas.
  $tmItems = enc_tm_move_ids_for_pokemon($mysqli, $pokemonId);
  if (!empty($tmItems)) {
    $hasAny = true;
    foreach ($tmItems as $mid) {
      $mid = (int)$mid;
      if ($mid > 0) $allowed[$mid] = true;
    }
  }

return [$allowed, $hasAny];
}

function enc_builder_allowed_abilities(mysqli $mysqli, $pokemonId) {
  $pokemonId = (int)$pokemonId;
  $q = $mysqli->query("SELECT slot1,slot2,hidden FROM base_ability_pokemon WHERE id={$pokemonId} LIMIT 1");
  $row = $q ? $q->fetch_assoc() : null;
  $allowed = [];
  if ($row) {
    foreach (['slot1','slot2','hidden'] as $k) {
      $v = (int)((isset($row[$k]) ? $row[$k] : 0));
      if ($v > 0) $allowed[$v] = true;
    }
  }
  return $allowed;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  enc_csrf_check();

  $title = trim((string)(isset($_POST['title']) ? $_POST['title'] : ''));
  $description = trim((string)(isset($_POST['description']) ? $_POST['description'] : ''));
  $visibility = strtolower(trim((string)(isset($_POST['visibility']) ? $_POST['visibility'] : 'private')));
  if (!in_array($visibility, ['public','private','shared'], true)) $visibility = 'private';

  $sharedUserId = enc_int((isset($_POST['shared_user_id']) ? $_POST['shared_user_id'] : 0), 0);
  if ($visibility !== 'shared') $sharedUserId = 0;

  $accessView = trim((string)(isset($_POST['access_view']) ? $_POST['access_view'] : ''));
  $accessEdit = trim((string)(isset($_POST['access_edit']) ? $_POST['access_edit'] : ''));
  $accessDeny = trim((string)(isset($_POST['access_deny']) ? $_POST['access_deny'] : ''));

  if ($title === '') {
    $title = $isEdit ? ('Сборка #' . (int)$buildId) : 'Новая сборка';
  }
  if (mb_strlen($title) > 120) $errors[] = 'Название слишком длинное (макс. 120 символов).';

  // Parse slots
  $postedSlots = (isset($_POST['slots']) && is_array($_POST['slots'])) ? $_POST['slots'] : [];
  $newSlots = [];
  $pokemonIdsNew = [];

  for ($i=1; $i<=6; $i++) {
    $s = (isset($postedSlots[$i]) && is_array($postedSlots[$i])) ? $postedSlots[$i] : [];

    $pid = enc_int((isset($s['pokemon_id']) ? $s['pokemon_id'] : 0), 0);
    $form = enc_sanitize_form((isset($s['form']) ? $s['form'] : ''));

    $level = enc_int((isset($s['level']) ? $s['level'] : 100), 100);
    if ($level < 1) $level = 1;
    if ($level > 100) $level = 100;

    $nature = enc_sanitize_nature((isset($s['nature']) ? $s['nature'] : 'hardy'));

    $ev = enc_norm_ev_array((isset($s['ev']) && is_array($s['ev'])) ? $s['ev'] : []);
    $iv = enc_norm_iv_array((isset($s['iv']) && is_array($s['iv'])) ? $s['iv'] : []);

    $ivCodeRaw = (string)(isset($s['iv_code']) ? $s['iv_code'] : '');
    $parsedIv = enc_parse_iv_code($ivCodeRaw);
    if (is_array($parsedIv)) $iv = enc_norm_iv_array($parsedIv);
    $ivCode = enc_iv_code_from_array($iv);

    $abilityId = enc_int((isset($s['ability_id']) ? $s['ability_id'] : 0), 0);

    $movesRaw = (isset($s['moves']) && is_array($s['moves'])) ? $s['moves'] : [];
    $moves = [];
    foreach ($movesRaw as $mv) {
      $mv = (int)$mv;
      if ($mv > 0) $moves[] = $mv;
    }
    if (count($moves) > 4) $moves = array_slice($moves, 0, 4);

    // Validation
    $evTotal = 0;
    foreach (enc_stat_keys() as $k) {
      $ev[$k] = (int)$ev[$k];
      $iv[$k] = (int)$iv[$k];

      if ($ev[$k] < 0) $ev[$k] = 0;
      if ($ev[$k] > 252) $ev[$k] = 252;
      if ($iv[$k] < 0) $iv[$k] = 0;
      if ($iv[$k] > 31) $iv[$k] = 31;
      $evTotal += $ev[$k];
    }
    if ($evTotal > 510) {
      $errors[] = "Слот {$i}: сумма EV превышает 510.";
    }

    if ($pid > 0) {
      $pokemonIdsNew[] = $pid;

      // Validate form exists
      if ($form !== '') {
        $stmt = $mysqli->prepare("SELECT id FROM base_pokemon_forms WHERE pokemons=? AND id_form=? LIMIT 1");
        $stmt->bind_param('is', $pid, $form);
        $stmt->execute();
        $row = enc_stmt_fetch_assoc($stmt);
        $ok = ($row ? true : false);
        $stmt->close();
        if (!$ok) {
          $errors[] = "Слот {$i}: неизвестная форма.";
        }
      }

      // Validate ability
      if ($abilityId > 0) {
        $allowedA = enc_builder_allowed_abilities($mysqli, $pid);
        if (!isset($allowedA[$abilityId])) {
          $errors[] = "Слот {$i}: недопустимая способность.";
        }
      }

      // Validate moves
      if ($moves) {
        [$allowedM, $formHasMoves] = enc_builder_allowed_moves($mysqli, $pid, $form);
        // If a move isn't present in the computed allowed list (schema differences for TM/HM),
        // allow it if it exists in base_atk; otherwise keep it as a hard error.
        foreach ($moves as $mv) {
          if (!isset($allowedM[$mv])) {
            $okMove = false;
            $stmt = $mysqli->prepare("SELECT id FROM base_atk WHERE id=? LIMIT 1");
            if ($stmt) {
              $stmt->bind_param('i', $mv);
              $stmt->execute();
              $row = enc_stmt_fetch_assoc($stmt);
              $stmt->close();
              if ($row) $okMove = true;
            }
            if (!$okMove) {
              $errors[] = "Слот {$i}: неизвестная атака (ID {$mv}).";
            }
          }
        }
      }
} else {
      // Empty slot: ignore ability/moves/form
      $form = '';
      $abilityId = 0;
      $moves = [];
    }

    $newSlots[] = [
      'pokemon_id' => $pid,
      'form' => $form,
      'ability_id' => $abilityId,
      'moves' => $moves,
      'level' => $level,
      'nature' => $nature,
      'ev' => $ev,
      'iv' => $iv,
      'iv_code' => $ivCode,
    ];
  }

  $pokemonIdsNew = array_values(array_unique($pokemonIdsNew));

  if (empty($errors)) {
    $team = [
      'v' => 2,
      'meta' => ['owner_id' => (int)$encyUserId],
      'slots' => $newSlots,
      'access' => [
        'view' => $accessView,
        'edit' => $accessEdit,
        'deny' => $accessDeny,
      ],
    ];

    $teamJson = json_encode($team, JSON_UNESCAPED_UNICODE);
    if ($teamJson === false) {
      $errors[] = 'Ошибка сериализации team_json.';
    } else {
      $now = time();
      $hasTs = enc_build_has_ts_cols($mysqli);

      if ($isEdit) {
        if ($hasTs) {
          $stmt = $mysqli->prepare("UPDATE enc_builds SET title=?, description=?, team_json=?, visibility=?, shared_user_id=?, updated_at_ts=? WHERE id=? LIMIT 1");
          $stmt->bind_param('ssssiii', $title, $description, $teamJson, $visibility, $sharedUserId, $now, $buildId);
        } else {
          $stmt = $mysqli->prepare("UPDATE enc_builds SET title=?, description=?, team_json=?, visibility=?, shared_user_id=? WHERE id=? LIMIT 1");
          $stmt->bind_param('ssssii', $title, $description, $teamJson, $visibility, $sharedUserId, $buildId);
        }
        $stmt->execute();
        $stmt->close();
        $doneId = $buildId;
      } else {
        if ($hasTs) {
          $stmt = $mysqli->prepare("INSERT INTO enc_builds (title,description,team_json,visibility,shared_user_id,created_by,created_at_ts,updated_at_ts) VALUES (?,?,?,?,?,?,?,?)");
          $stmt->bind_param('ssssiiii', $title, $description, $teamJson, $visibility, $sharedUserId, $encyUserId, $now, $now);
        } else {
          $stmt = $mysqli->prepare("INSERT INTO enc_builds (title,description,team_json,visibility,shared_user_id,created_by) VALUES (?,?,?,?,?,?)");
          $stmt->bind_param('ssssii', $title, $description, $teamJson, $visibility, $sharedUserId, $encyUserId);
        }
        $stmt->execute();
        $doneId = (int)$stmt->insert_id;
        $stmt->close();
      }

      // Update build index (enc_build_pokemon)
      if ($doneId > 0 && $encyHasBuildIndex) {
        $mysqli->query("DELETE FROM enc_build_pokemon WHERE build_id=".(int)$doneId);
        $ins = $mysqli->prepare("INSERT INTO enc_build_pokemon (build_id, slot, pokemon_id, form) VALUES (?,?,?,?)");
        if ($ins) {
          foreach ($newSlots as $idx => $s) {
            $slotNum = $idx + 1;
            $pid = (int)$s['pokemon_id'];
            if ($pid <= 0) continue;
            $frm = (string)$s['form'];
            $ins->bind_param('iiis', $doneId, $slotNum, $pid, $frm);
            $ins->execute();
          }
          $ins->close();
        }
      }

      // Update popularity (derived)
      if ($doneId > 0 && $encyHasPopularity && $encyHasBuildIndex) {
        $idsToRecalc = [];

        if ($isEdit) {
          $oldSlots = enc_team_slots_from_json((string)$build['team_json']);
          foreach ($oldSlots as $s) {
            $pid = (int)$s['pokemon_id'];
            if ($pid > 0) $idsToRecalc[$pid] = true;
          }
        }
        foreach ($pokemonIdsNew as $pid) $idsToRecalc[(int)$pid] = true;

        $ids = array_keys($idsToRecalc);
        if ($ids) {
          $in = implode(',', array_map('intval', $ids));
          $q = $mysqli->query("SELECT bp.pokemon_id, COUNT(DISTINCT bp.build_id) AS cnt
                              FROM enc_build_pokemon bp
                              JOIN enc_builds b ON b.id=bp.build_id
                              WHERE b.visibility='public' AND bp.pokemon_id IN ($in)
                              GROUP BY bp.pokemon_id");
          $cntMap = [];
          if ($q) while ($r = $q->fetch_assoc()) $cntMap[(int)$r['pokemon_id']] = (int)$r['cnt'];

          foreach ($ids as $pid) {
            $cnt = (int)(isset($cntMap[(int)$pid]) ? $cntMap[(int)$pid] : 0);
            enc_popularity_upsert($mysqli, $pid, $cnt);
          }
        }
      }

      if ($doneId > 0) {
        enc_redirect(enc_url('/build.php?id=' . $doneId));
      }
    }
  }

  // For re-render after errors
  $slots = $newSlots;
}

// Prefill shared user label
$sharedUserLabel = '';
if ($sharedUserId > 0) {
  $u = enc_fetch_user($mysqli, $sharedUserId);
  if ($u) $sharedUserLabel = (string)$u['login'];
}

// Prefill pokemon names for slot inputs
$pokemonNameMap = [];
$pids = [];
foreach ($slots as $s) {
  $pid = (int)$s['pokemon_id'];
  if ($pid > 0) $pids[] = $pid;
}
$pids = array_values(array_unique($pids));
if ($pids) {
  $in = implode(',', array_map('intval', $pids));
  $q = $mysqli->query("SELECT id,name,name_rus FROM base_pokemons WHERE id IN ($in)");
  if ($q) while ($r = $q->fetch_assoc()) {
    $pid = (int)$r['id'];
    $nm = (string)($r['name_rus'] ?: $r['name']);
    $pokemonNameMap[$pid] = '#'.enc_pad3($pid).' '.$nm;
  }
}

$natures = enc_natures_data();

$pageTitle = $isEdit ? 'Редактирование сборки' : 'Создать сборку';
$encExtraScripts = [enc_url('/assets/builder_adv.js')];

require_once __DIR__ . '/_inc/layout_top.php';
?>

<script>
  window.ENC_NATURES = <?=json_encode($natures, JSON_UNESCAPED_UNICODE)?>;
</script>

<div class="forum-card">
  <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-end;">
    <div>
      <h1 style="margin:0 0 6px;"><?=enc_h($pageTitle)?></h1>
      <div class="muted">Редактор v2: 6 слотов, формы, усилия и гены, характеры и предпросмотр статов.</div>
    </div>
    <?php if ($isEdit): ?>
      <a class="enc-btn enc-btn--ghost" href="<?=enc_h(enc_url('/build.php?id='.(int)$buildId))?>">К сборке</a>
    <?php endif; ?>
  </div>

  <?php if ($errors): ?>
    <div class="forum-alert danger" style="margin-top:12px;">
      <?php foreach ($errors as $e): ?>
        <div><?=enc_h($e)?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="js-builder-v2" style="margin-top:12px;">
    <input type="hidden" name="csrf" value="<?=enc_h(enc_csrf_token())?>">

    <div class="enc-card" style="margin-top:10px;">
      <div class="enc-card__title">Параметры сборки</div>
      <div class="enc-card__body">
        <div class="enc-grid" style="grid-template-columns: 1.4fr 0.6fr;">
          <div>
            <label class="enc-label">Название</label>
            <input class="enc-input" type="text" name="title" value="<?=enc_h($title)?>" maxlength="120" placeholder="Новая сборка">
          </div>
          <div>
            <label class="enc-label">Видимость</label>
            <select class="enc-input" name="visibility" id="buildVisibility">
              <option value="private" <?=($visibility==='private'?'selected':'')?>>Приватная</option>
              <option value="public" <?=($visibility==='public'?'selected':'')?>>Публичная</option>
              <option value="shared" <?=($visibility==='shared'?'selected':'')?>>По приглашению</option>
            </select>
          </div>
        </div>

        <div style="margin-top:10px;" id="sharedUserBlock">
          <label class="enc-label">Доступ по приглашению</label>
          <input class="enc-input" type="text" data-ac="user" data-target-id="shared_user_id" placeholder="Введите логин" value="<?=enc_h($sharedUserLabel)?>">
          <input type="hidden" name="shared_user_id" id="shared_user_id" value="<?=enc_h((int)$sharedUserId)?>">
          <div class="enc-help">Используется только для видимости «По приглашению».</div>
        </div>

        <div style="margin-top:10px;">
          <label class="enc-label">Описание</label>
          <textarea class="enc-input" name="description" rows="4" placeholder="Кратко опишите идею сборки (BBCode поддерживается)"><?=enc_h($description)?></textarea>
        </div>

        <details class="enc-advanced" style="margin-top:12px;">
          <summary>Расширенные правила доступа</summary>
          <div class="enc-grid" style="margin-top:10px; grid-template-columns:1fr 1fr 1fr;">
            <div>
              <label class="enc-label">Просмотр</label>
              <input class="enc-input" type="text" name="access_view" value="<?=enc_h($accessView)?>" placeholder="auth, id:123, /^mod_/i">
            </div>
            <div>
              <label class="enc-label">Редактирование</label>
              <input class="enc-input" type="text" name="access_edit" value="<?=enc_h($accessEdit)?>" placeholder="auth, id:123, /^mod_/i">
            </div>
            <div>
              <label class="enc-label">Запрет</label>
              <input class="enc-input" type="text" name="access_deny" value="<?=enc_h($accessDeny)?>" placeholder="guest, /^ban_/i">
            </div>
          </div>
          <div class="enc-help" style="margin-top:8px;">
            Для обычных сборок можно не трогать. Продвинутые правила проверяются при открытии сборки и не показываются в листинге.
          </div>
        </details>
      </div>
    </div>

    <div class="js-builder-msg enc-error" style="display:none; margin-top:12px;"></div>

    <div class="builder-layout" style="margin-top:12px;">
      <aside class="builder-sidebar">
        <div class="builder-sidebar__title">Команда</div>
        <div class="builder-team">
          <?php for ($i=1; $i<=6; $i++):
            $s = isset($slots[$i-1]) ? $slots[$i-1] : null;
            if (!$s) $s = ['pokemon_id'=>0,'form'=>'','ability_id'=>0,'moves'=>[],'level'=>100,'nature'=>'hardy','ev'=>enc_norm_ev_array([]),'iv'=>enc_norm_iv_array([]),'iv_code'=>''];
            $pid = (int)$s['pokemon_id'];
            $pLabel = $pid>0 && isset($pokemonNameMap[$pid]) ? preg_replace('~^#\d{3}\s+~','',$pokemonNameMap[$pid]) : '';
            $teamName = $pLabel !== '' ? $pLabel : ('Слот '.$i);
            $frm = (string)$s['form'];
            $sub = $frm !== '' ? ('Форма: '.$frm) : 'Обычная форма';
          ?>
            <button type="button" class="builder-team-item js-team-item" data-slot="<?=$i?>">
              <img class="builder-team-sprite js-team-sprite" data-slot="<?=$i?>" src="<?=enc_h(ENC_SPRITE_PLACEHOLDER)?>" alt="">
              <div class="builder-team-meta">
                <div class="builder-team-name js-team-name" data-slot="<?=$i?>"><?=enc_h($teamName)?></div>
                <div class="builder-team-sub js-team-sub" data-slot="<?=$i?>"><?=enc_h($sub)?></div>
              </div>
            </button>
          <?php endfor; ?>
        </div>
        <div class="enc-help" style="margin-top:10px;">
          Выберите слот слева. Справа — редактор выбранного покемона.
        </div>
      </aside>

      <section class="builder-editor">
        <?php for ($i=1; $i<=6; $i++):
          $s = isset($slots[$i-1]) ? $slots[$i-1] : null;
          if (!$s) $s = ['pokemon_id'=>0,'form'=>'','ability_id'=>0,'moves'=>[],'level'=>100,'nature'=>'hardy','ev'=>enc_norm_ev_array([]),'iv'=>enc_norm_iv_array([]),'iv_code'=>''];
          $pid = (int)$s['pokemon_id'];
          $pLabelRaw = $pid>0 && isset($pokemonNameMap[$pid]) ? $pokemonNameMap[$pid] : '';
          $pLabel = preg_replace('~^#\d{3}\s+~','',$pLabelRaw);
          $form = (string)$s['form'];
          $abilityId = (int)$s['ability_id'];
          $level = (int)$s['level'];
          $nature = (string)$s['nature'];
          $ev = (isset($s['ev']) ? $s['ev'] : enc_norm_ev_array([]));
          $iv = (isset($s['iv']) ? $s['iv'] : enc_norm_iv_array([]));
          $ivCode = (string)$s['iv_code'];
          $moves = (isset($s['moves']) && is_array($s['moves'])) ? $s['moves'] : [];
          while (count($moves) < 4) $moves[] = 0;

          $statNames = ['hp'=>'HP','atk'=>'Atk','def'=>'Def','satk'=>'SpA','sdef'=>'SpD','spd'=>'Spe'];
        ?>
          <div class="builder-slot-panel js-builder-slot" data-slot="<?=$i?>">
            <div class="enc-card">
              <div class="enc-card__title">Слот <?=$i?></div>
              <div class="enc-card__body">
                <div class="builder-slot-top">
                  <img class="builder-sprite js-sprite" src="<?=enc_h(ENC_SPRITE_PLACEHOLDER)?>" alt="" onerror="this.style.opacity='0.2'">
                  <div class="builder-slot-top__main">
                    <label class="enc-label">Покемон</label>
                    <input class="enc-input js-pokemon-name" type="text" data-ac="pokemon" data-ac-hide-id="1" data-target-id="slot_pokemon_<?=$i?>" placeholder="Начните вводить имя…" value="<?=enc_h($pLabel)?>">
                    <input class="js-pokemon-id" type="hidden" name="slots[<?=$i?>][pokemon_id]" id="slot_pokemon_<?=$i?>" value="<?=enc_h($pid)?>">
                    <div class="enc-help">Выберите из подсказок — формы, способности и атаки загрузятся автоматически.</div>
                  </div>
                </div>

                <div class="builder-slot-layout" style="margin-top:12px;">
                  <div class="builder-col-left">
                    <div class="enc-card inner">
                      <div class="enc-card__title-sm">Детали</div>

                      <div class="builder-slot-controls" style="margin-top:10px;">
                        <div>
                          <label class="enc-label">Форма</label>
                          <select class="enc-input js-form" name="slots[<?=$i?>][form]">
                            <option value="" <?=($form===''?'selected':'')?>>Обычная</option>
                            <?php if ($form !== ''): ?>
                              <option value="<?=enc_h($form)?>" selected><?=enc_h($form)?></option>
                            <?php endif; ?>
                          </select>
                        </div>
                        <div>
                          <label class="enc-label">Способность</label>
                          <select class="enc-input js-ability" name="slots[<?=$i?>][ability_id]">
                            <option value="0">—</option>
                            <?php if ($abilityId > 0): ?>
                              <option value="<?=enc_h($abilityId)?>" selected>Загрузка…</option>
                            <?php endif; ?>
                          </select>
                        </div>
                        <div>
                          <label class="enc-label">Уровень</label>
                          <input class="enc-input js-level" type="number" name="slots[<?=$i?>][level]" min="1" max="100" value="<?=enc_h($level ?: 100)?>">
                        </div>
                      </div>

                      <div style="margin-top:10px;">
                        <label class="enc-label">Характер</label>
                        <select class="enc-input js-nature" name="slots[<?=$i?>][nature]">
                          <?php foreach ($natures as $k=>$d): ?>
                            <option value="<?=enc_h($k)?>" <?=($nature===$k?'selected':'')?>><?=enc_h($d['ru'])?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>

                    <div class="enc-card inner" style="margin-top:12px;">
                      <div class="enc-card__title-sm">Атаки</div>
                      <div class="builder-moves-grid" style="margin-top:10px;">
                        <?php for ($m=1; $m<=4; $m++): $mv=(int)$moves[$m-1]; ?>
                          <div>
                            <label class="enc-label">Атака <?=$m?></label>
                            <select class="enc-input js-move" name="slots[<?=$i?>][moves][]">
                              <option value="0">—</option>
                              <?php if ($mv > 0): ?>
                                <option value="<?=enc_h($mv)?>" selected>Загрузка…</option>
                              <?php endif; ?>
                            </select>
                          </div>
                        <?php endfor; ?>
                      </div>
                      <div class="enc-help" style="margin-top:8px;">
                        Списки формируются по доступности атак (уровень/ТМ/НМ).
                      </div>
                    </div>

                    <div class="enc-card inner" style="margin-top:12px;">
                      <div class="enc-card__title-sm">Доступные атаки</div>
                      <input class="enc-input js-move-dex-q" type="text" placeholder="Поиск по атакам…" style="margin-top:10px;">
                      <div class="builder-move-dex" style="margin-top:10px;">
                        <table class="enc-table">
                          <thead>
                            <tr>
                              <th style="width:84px;">Источник</th>
                              <th>Атака</th>
                              <th style="width:280px;">Параметры</th>
                            </tr>
                          </thead>
                          <tbody class="js-move-dex-body"></tbody>
                        </table>
                      </div>
                      <div class="enc-help" style="margin-top:8px;">Список нужен для быстрого выбора и сверки параметров.</div>
                    </div>
                  </div>

                  <div class="builder-col-right">
                    <div class="enc-card inner">
                      <div class="enc-card__title-sm">Статы</div>
                      <div class="builder-stats-list" style="margin-top:10px;">
                        <?php foreach (['hp','atk','def','satk','sdef','spd'] as $k): ?>
                          <div class="builder-stat-row">
                            <div class="builder-stat-k"><?=enc_h($statNames[$k])?></div>
                            <div class="builder-stat-bar"><div class="builder-stat-fill js-stat-bar" data-stat="<?=enc_h($k)?>"></div></div>
                            <div class="builder-stat-v"><b class="js-stat-out" data-stat="<?=enc_h($k)?>">—</b></div>
                            <div class="builder-stat-base">база <span class="js-base-stat" data-stat="<?=enc_h($k)?>">—</span></div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    <div class="enc-card inner" style="margin-top:12px;">
                      <div class="builder-card-head">
                        <div class="enc-card__title-sm">EV</div>
                        <div class="builder-ev-summary">Осталось: <b class="js-ev-left">510</b> • Итого: <b class="js-ev-total">0</b>/510</div>
                      </div>

                      <div class="builder-ev-rows" style="margin-top:10px;">
                        <?php foreach (['hp','atk','def','satk','sdef','spd'] as $k): ?>
                          <div class="builder-ev-row">
                            <div class="builder-ev-k"><?=enc_h($statNames[$k])?></div>
                            <input class="enc-input builder-ev-num js-ev" type="number" name="slots[<?=$i?>][ev][<?=enc_h($k)?>]" data-stat="<?=enc_h($k)?>" min="0" max="252" step="4" value="<?=enc_h((int)$ev[$k])?>">
                            <input class="builder-ev-range js-ev-range" type="range" data-stat="<?=enc_h($k)?>" min="0" max="252" step="4" value="<?=enc_h((int)$ev[$k])?>">
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    <div class="enc-card inner" style="margin-top:12px;">
                      <div class="enc-card__title-sm">Гены</div>
                      <div class="builder-iv-rows" style="margin-top:10px;">
                        <?php foreach (['hp','atk','def','satk','sdef','spd'] as $k): ?>
                          <div class="builder-iv-row">
                            <div class="builder-iv-k"><?=enc_h($statNames[$k])?></div>
                            <input class="enc-input builder-iv-num js-iv" type="number" name="slots[<?=$i?>][iv][<?=enc_h($k)?>]" data-stat="<?=enc_h($k)?>" min="0" max="31" step="1" value="<?=enc_h((int)$iv[$k])?>">
                            <input class="builder-iv-range js-iv-range" type="range" data-stat="<?=enc_h($k)?>" min="0" max="31" step="1" value="<?=enc_h((int)$iv[$k])?>">
                          </div>
                        <?php endforeach; ?>
                      </div>

                      <div class="builder-iv-presets">
                        <button class="enc-btn enc-btn--ghost" data-iv-preset="all31" type="button">Все 31</button>
                        <button class="enc-btn enc-btn--ghost" data-iv-preset="0atk" type="button">Atk = 0</button>
                        <button class="enc-btn enc-btn--ghost" data-iv-preset="0spe" type="button">Spe = 0</button>
                        <button class="enc-btn enc-btn--ghost" data-iv-preset="all0" type="button">Все 0</button>
                      </div>

                      <div style="margin-top:10px;">
                        <label class="enc-label">Код генов</label>
                        <div class="builder-iv-code-row">
                          <input class="enc-input js-iv-code" type="text" name="slots[<?=$i?>][iv_code]" value="<?=enc_h($ivCode)?>" placeholder="31/31/31/31/31/31">
                          <button class="enc-btn enc-btn--ghost js-copy-iv" type="button">Копировать</button>
                        </div>
                        <div class="enc-help">Можно копировать/вставлять. Поля синхронизируются при выходе из поля.</div>
                      </div>
                    </div>

                  </div>
                </div>

              </div>
            </div>
          </div>
        <?php endfor; ?>
      </section>
    </div>
<div style="margin-top:12px; display:flex; gap:10px; align-items:center;">
      <button class="enc-btn enc-btn--primary" type="submit"><?=($isEdit ? 'Обновить' : 'Сохранить')?></button>
      <a class="enc-btn enc-btn--ghost" href="<?=enc_h(enc_url('/builds.php'))?>">К списку сборок</a>
      <div class="muted">Если некоторые слоты пустые, перед сохранением появится предупреждение.</div>
    </div>
  </form>
</div>

<script>
  // По приглашению visibility toggle
  (function(){
    const vis = document.getElementById('buildVisibility');
    const block = document.getElementById('sharedUserBlock');
    function upd(){
      if (!vis || !block) return;
      block.style.display = (vis.value === 'shared') ? 'block' : 'none';
    }
    if (vis) vis.addEventListener('change', upd);
    upd();
  })();
</script>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
