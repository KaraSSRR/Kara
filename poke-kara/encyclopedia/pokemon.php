<?php
require_once __DIR__ . '/_inc/bootstrap.php';

$id = enc_int((isset($_GET['id']) ? $_GET['id'] : 0), 0);
$formReq = enc_sanitize_form((isset($_GET['form']) ? $_GET['form'] : ''));
$tab = (string)(isset($_GET['tab']) ? $_GET['tab'] : 'info');
$tab = preg_replace('~[^a-z_]+~', '', strtolower($tab));
if ($tab === '') $tab = 'info';

if ($id <= 0) enc_redirect(enc_url('/pokedex.php'));

$stmt = $mysqli->prepare("SELECT * FROM base_pokemons WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$base = enc_stmt_fetch_assoc($stmt);
$stmt->close();

if (!$base) enc_redirect(enc_url('/pokedex.php'));

// forms: normal (start=1) and mega (start=0)
$formsNormal = array();
$formsMega = array();

$q = $mysqli->query("SELECT id_form,name,type,type_two,hp,atk,def,satk,sdef,spd,start
                     FROM base_pokemon_forms
                     WHERE pokemons=".(int)$id." AND start IN (0,1)
                     ORDER BY start DESC, id ASC");
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $r['id_form'] = enc_sanitize_form($r['id_form']);
        if ((int)$r['start'] === 0) $formsMega[] = $r; else $formsNormal[] = $r;
    }
}

function enc_fix_evo_html($html) {
    $html = (string)$html;
    if (trim($html) === '') return '';
    $html = str_replace(array('\\"', "\\'"), array('"', "'"), $html);
    // Old DB used openDex(123) JS helper – keep it compatible by rewriting to location change.
    $html = preg_replace_callback('~openDex\\((\\d+)\\)~', function($m){
        $pid = (int)$m[1];
        return "window.location='" . enc_url('/pokemon.php?id=' . $pid) . "'";
    }, $html);
    return $html;
}

function enc_evo_ids_from_html($html) {
    $ids = array();
    if (preg_match_all('~openDex\\((\\d+)\\)~', (string)$html, $m)) {
        foreach ($m[1] as $v) {
            $n = (int)$v;
            if ($n > 0) $ids[] = $n;
        }
    }
    return $ids;
}

$allForms = array_merge($formsNormal, $formsMega);
$selFormRow = null;
$selForm = '';
$isMega = false;

if ($formReq !== '') {
    foreach ($allForms as $f) {
        if ($f['id_form'] === $formReq) {
            $selFormRow = $f;
            $selForm = $formReq;
            $isMega = ((int)$f['start'] === 0);
            break;
        }
    }
}

// display data from base or selected form
$dispName = (string)($base['name_rus'] ? $base['name_rus'] : $base['name']);
$dispNameEn = (string)$base['name'];

$type1 = (string)$base['type'];
$type2 = (string)$base['type_two'];

$hp = (int)$base['hp']; $atk=(int)$base['atk']; $def=(int)$base['def']; $satk=(int)$base['satk']; $sdef=(int)$base['sdef']; $spd=(int)$base['spd'];

if ($selFormRow) {
    if (trim((string)$selFormRow['name_rus']) !== '' || trim((string)$selFormRow['name']) !== '') {
        $dispName = (string)($selFormRow['name_rus'] ? $selFormRow['name_rus'] : $selFormRow['name']);
    }
    $type1 = (string)$selFormRow['type'];
    $type2 = (string)$selFormRow['type_two'];
    $hp = (int)$selFormRow['hp']; $atk=(int)$selFormRow['atk']; $def=(int)$selFormRow['def']; $satk=(int)$selFormRow['satk']; $sdef=(int)$selFormRow['sdef']; $spd=(int)$selFormRow['spd'];
}

$pageTitle = $dispName . ' #' . enc_pad3($id);
require_once __DIR__ . '/_inc/layout_top.php';

// prev/next
$prev = null; $next = null;
$q = $mysqli->query("SELECT id,name,name_rus FROM base_pokemons WHERE id < ".(int)$id." ORDER BY id DESC LIMIT 1");
if ($q && ($r=$q->fetch_assoc())) $prev = $r;
$q = $mysqli->query("SELECT id,name,name_rus FROM base_pokemons WHERE id > ".(int)$id." ORDER BY id ASC LIMIT 1");
if ($q && ($r=$q->fetch_assoc())) $next = $r;

?>
<div class="container">
  <div class="card" style="margin-bottom:14px;">
    <div class="p-nav">
      <div class="p-nav-side">
        <?php if ($prev): ?>
          <a class="p-nav-item" href="<?=enc_h(enc_url('/pokemon.php?id='.(int)$prev['id']))?>">
            <img class="p-nav-sprite" src="<?=enc_h(enc_sprite_pokedex((int)$prev['id']))?>" alt="">
            <div>
              <div class="muted" style="font-size:12px;">‹ #<?=enc_h(enc_pad3((int)$prev['id']))?></div>
              <div style="font-weight:900;"><?=enc_h($prev['name_rus'] ?: $prev['name'])?></div>
            </div>
          </a>
        <?php else: ?>
          <div class="p-nav-item disabled"><div class="muted">‹ Нет</div></div>
        <?php endif; ?>
      </div>

      <div class="p-nav-center">
        <a class="link" href="<?=enc_h(enc_url('/pokedex.php'))?>">Покедекс</a>
        <span class="muted">/</span>
        <span style="font-weight:900;">#<?=enc_h(enc_pad3($id))?></span>
      </div>

      <div class="p-nav-side" style="justify-content:flex-end;">
        <?php if ($next): ?>
          <a class="p-nav-item" href="<?=enc_h(enc_url('/pokemon.php?id='.(int)$next['id']))?>">
            <div style="text-align:right;">
              <div class="muted" style="font-size:12px;">#<?=enc_h(enc_pad3((int)$next['id']))?> ›</div>
              <div style="font-weight:900;"><?=enc_h($next['name_rus'] ?: $next['name'])?></div>
            </div>
            <img class="p-nav-sprite" src="<?=enc_h(enc_sprite_pokedex((int)$next['id']))?>" alt="">
          </a>
        <?php else: ?>
          <div class="p-nav-item disabled"><div class="muted">Нет ›</div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div style="display:flex; flex-wrap:wrap; gap:18px;">
      <div style="flex:0 0 230px; text-align:center;">
        <div class="poke-big"><?=enc_sprite_img($id, $selForm, 'anim', 96, 'poke-big__img')?></div>
        <div style="margin-top:10px; font-weight:900; font-size:20px;"><?=enc_h($dispName)?></div>
        <?php if ($dispNameEn && strtolower($dispNameEn) !== strtolower($dispName)): ?>
          <div class="muted"><?=enc_h($dispNameEn)?></div>
        <?php endif; ?>
        <div class="type-row" style="justify-content:center; margin-top:10px;">
          <?=enc_type_badge($type1)?>
          <?=enc_type_badge($type2)?>
        </div>

        <?php if ($formsNormal): ?>
          <div class="tabs" style="margin-top:14px; justify-content:center;">
            <a class="tab <?=($selForm===''?'active':'')?>" href="<?=enc_h(enc_url('/pokemon.php?id='.(int)$id.'&tab='.urlencode($tab)))?>">Обычная</a>
            <?php foreach($formsNormal as $f): ?>
              <a class="tab <?=($selForm===$f['id_form']?'active':'')?>"
                 href="<?=enc_h(enc_url('/pokemon.php?id='.(int)$id.'&form='.urlencode($f['id_form']).'&tab='.urlencode($tab)))?>">
                 <?=enc_h($f['name'])?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($formsMega): ?>
          <div style="margin-top:14px; text-align:left;">
            <div class="muted" style="font-weight:900; margin-bottom:8px;">Мега-формы</div>
            <div class="mini-grid">
              <?php foreach($formsMega as $f): ?>
                <a class="mini-card <?=($selForm===$f['id_form']?'active':'')?>"
                   href="<?=enc_h(enc_url('/pokemon.php?id='.(int)$id.'&form='.urlencode($f['id_form']).'&tab='.urlencode($tab)))?>">
                  <?=enc_sprite_img($id, $f['id_form'], 'pokedex', 40, 'mini-sprite')?>
                  <div class="mini-name"><?=enc_h($f['name'])?></div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div style="flex:1; min-width:300px;">
        <div class="tabs" style="margin-top:4px;">
          <a class="tab <?=($tab==='info'?'active':'')?>" href="<?=enc_h(enc_url('/pokemon.php?id='.(int)$id.($selForm!==''?'&form='.urlencode($selForm):'').'&tab=info'))?>">Инфо</a>
          <a class="tab <?=($tab==='stats'?'active':'')?>" href="<?=enc_h(enc_url('/pokemon.php?id='.(int)$id.($selForm!==''?'&form='.urlencode($selForm):'').'&tab=stats'))?>">Статы</a>
          <a class="tab <?=($tab==='moves'?'active':'')?>" href="<?=enc_h(enc_url('/pokemon.php?id='.(int)$id.($selForm!==''?'&form='.urlencode($selForm):'').'&tab=moves'))?>">Атаки</a>
          <a class="tab <?=($tab==='evo'?'active':'')?>" href="<?=enc_h(enc_url('/pokemon.php?id='.(int)$id.($selForm!==''?'&form='.urlencode($selForm):'').'&tab=evo'))?>">Эволюции</a>
          <a class="tab <?=($tab==='builds'?'active':'')?>" href="<?=enc_h(enc_url('/pokemon.php?id='.(int)$id.($selForm!==''?'&form='.urlencode($selForm):'').'&tab=builds'))?>">Сборки</a>
        </div>

        <?php if ($tab === 'stats'): ?>
          <div class="card" style="margin-top:12px;">
            <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-end;">
              <div>
                <div style="font-weight:900; font-size:18px;">Базовые статы <?=($isMega?'(Мега)':'')?></div>
                <?php if ($selForm !== ''): ?><div class="muted">Форма: <?=enc_h($selForm)?></div><?php endif; ?>
              </div>
              <div class="muted">Сумма: <b><?=enc_h($hp+$atk+$def+$satk+$sdef+$spd)?></b></div>
            </div>

            <table class="table" style="margin-top:10px;">
              <tbody>
                <tr><th style="width:120px;">HP</th><td><?=enc_h($hp)?></td></tr>
                <tr><th>ATK</th><td><?=enc_h($atk)?></td></tr>
                <tr><th>DEF</th><td><?=enc_h($def)?></td></tr>
                <tr><th>Sp.ATK</th><td><?=enc_h($satk)?></td></tr>
                <tr><th>Sp.DEF</th><td><?=enc_h($sdef)?></td></tr>
                <tr><th>SPD</th><td><?=enc_h($spd)?></td></tr>
              </tbody>
            </table>
          </div>

        <?php elseif ($tab === 'moves'): ?>
          <?php
            $formKey = ($selForm !== '' ? $selForm : '0');

            // Атаки по уровню (из обучаемых атак)
            $lvlRow = null;
            $stmt = $mysqli->prepare("SELECT attacks, lvl FROM base_attacks_pokemons WHERE pok=? AND type='lvl' AND form=? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('is', $id, $formKey);
                $stmt->execute();
                $lvlRow = enc_stmt_fetch_assoc($stmt);
                $stmt->close();
            }

            if (!$lvlRow && $formKey !== '0') {
                $stmt = $mysqli->prepare("SELECT attacks, lvl FROM base_attacks_pokemons WHERE pok=? AND type='lvl' AND form='0' LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $lvlRow = enc_stmt_fetch_assoc($stmt);
                    $stmt->close();
                }
            }

            $pairs = array();
            if ($lvlRow) {
                $atkIds = array_filter(array_map('intval', explode(',', (string)$lvlRow['attacks'])));
                $lvls   = array_filter(array_map('intval', explode(',', (string)$lvlRow['lvl'])));
                $cnt = min(count($atkIds), count($lvls));
                for ($i=0; $i<$cnt; $i++) $pairs[] = array('id'=>(int)$atkIds[$i], 'lvl'=>(int)$lvls[$i]);
                usort($pairs, function($a,$b){
                    return ((int)$a['lvl'] - (int)$b['lvl']);
                });
            }

            // Какие поля есть в base_atk (под разные дампы)
            $atkCols = array('id','name','name_rus','type','category','power','accuracy','pp');
            $extraCols = array('priority','target','contact','sound','punch','bite','bullet','pulse');
            foreach ($extraCols as $c) {
                if (enc_table_has_column($mysqli, 'base_atk', $c)) $atkCols[] = $c;
            }
            $atkSelect = implode(',', array_map(function($c){
                $c = str_replace('`', '', $c);
                return '`'.$c.'`';
            }, $atkCols));

            // Данные атак (по уровню)
            $moveMap = array();
            if ($pairs) {
                $ids = array();
                foreach ($pairs as $p) $ids[] = (int)$p['id'];
                $ids = array_values(array_unique($ids));
                if ($ids) {
                    $q = $mysqli->query("SELECT ".$atkSelect." FROM base_atk WHERE id IN (".implode(',', array_map('intval',$ids)).")");
                    if ($q) while($r=$q->fetch_assoc()) $moveMap[(int)$r['id']] = $r;
                }
            }

            // ТМ: attac_poke_tm -> base_items(type='tm').info = id атаки
            $tm = enc_tm_move_ids_for_pokemon($mysqli, $id);

            // НМ (если есть в базе)
            $hm = array();
            $hmRow = null;
            $stmt = $mysqli->prepare("SELECT attacks FROM base_attacks_pokemons WHERE pok=? AND type='hm' AND form=? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('is', $id, $formKey);
                $stmt->execute();
                $hmRow = enc_stmt_fetch_assoc($stmt);
                $stmt->close();
            }
            if (!$hmRow && $formKey !== '0') {
                $stmt = $mysqli->prepare("SELECT attacks FROM base_attacks_pokemons WHERE pok=? AND type='hm' AND form='0' LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $hmRow = enc_stmt_fetch_assoc($stmt);
                    $stmt->close();
                }
            }
            if ($hmRow) $hm = array_filter(array_map('intval', explode(',', (string)$hmRow['attacks'])));

            // Данные атак для ТМ/НМ
            $extraIds = array_values(array_unique(array_merge($tm, $hm)));
            $moveMapExtra = array();
            if ($extraIds) {
                $q = $mysqli->query("SELECT ".$atkSelect." FROM base_atk WHERE id IN (".implode(',', array_map('intval',$extraIds)).")");
                if ($q) while($r=$q->fetch_assoc()) $moveMapExtra[(int)$r['id']] = $r;
            }

            $renderMoveCard = function($mid, $m, $kicker) {
                $name = $m ? ($m['name_rus'] ?: $m['name']) : 'Неизвестная атака';
                $href = enc_url('/move.php?id='.(int)$mid);
                $out  = '<div class="enc-carditem">';
                $out .= '<div class="enc-carditem__head">';
                $out .= '<div class="enc-carditem__title">'.($m ? '<a class="link" href="'.enc_h($href).'">'.enc_h($name).'</a>' : '<span title="ID атаки: '.enc_h((int)$mid).'">'.enc_h($name).'</span>').'</div>';
                if ($kicker !== '') $out .= '<div class="muted" style="font-size:12px; white-space:nowrap;">'.enc_h($kicker).'</div>';
                $out .= '</div>';
                $out .= '<div class="enc-carditem__sub">';
                if ($m) {
                    $out .= enc_type_badge((string)$m['type']);
                    $out .= ' ';
                    $out .= enc_move_category_badge((string)$m['category']);
                }
                $out .= '</div>';
                if ($m) $out .= enc_move_param_chips($m);
                $out .= '</div>';
                return $out;
            };
          ?>

          <div class="card" style="margin-top:12px;">
            <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-end;">
              <div>
                <div style="font-weight:900; font-size:18px;">Атаки по уровню</div>
                <?php if ($selForm !== ''): ?><div class="muted">Форма: <?=enc_h($selForm)?></div><?php endif; ?>
              </div>
              <div class="muted"><?=enc_h(count($pairs))?> шт.</div>
            </div>

            <?php if (!$pairs): ?>
              <div class="muted" style="margin-top:10px;">Нет данных об атаках по уровню.</div>
            <?php else: ?>

              <div class="enc-hide-mobile">
                <div class="table-wrap" style="margin-top:10px;">
                  <table class="table">
                    <thead>
                      <tr>
                        <th style="width:84px;">Ур.</th>
                        <th>Атака</th>
                        <th style="width:340px;">Параметры</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach($pairs as $p): $mid=(int)$p['id']; $m=(isset($moveMap[$mid]) ? $moveMap[$mid] : null); ?>
                        <tr>
                          <td><b><?=enc_h((int)$p['lvl'])?></b></td>
                          <td>
                            <?php if($m): ?>
                              <a class="link" href="<?=enc_h(enc_url('/move.php?id='.$mid))?>"><?=enc_h($m['name_rus'] ?: $m['name'])?></a>
                              <div class="type-row" style="margin-top:6px;">
                                <?=enc_type_badge((string)$m['type'])?>
                                <?=enc_move_category_badge((string)$m['category'])?>
                              </div>
                            <?php else: ?>
                              <span title="ID атаки: <?=enc_h($mid)?>">Неизвестная атака</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if($m): ?><?=enc_move_param_chips($m)?><?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="enc-show-mobile" style="margin-top:10px;">
                <div class="enc-cardlist">
                  <?php foreach($pairs as $p): $mid=(int)$p['id']; $m=(isset($moveMap[$mid]) ? $moveMap[$mid] : null); ?>
                    <?=$renderMoveCard($mid, $m, 'Ур. '.(int)$p['lvl'])?>
                  <?php endforeach; ?>
                </div>
              </div>

            <?php endif; ?>
          </div>

          <?php if ($tm || $hm): ?>
            <div class="card" style="margin-top:12px;">
              <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-end;">
                <div style="font-weight:900; font-size:18px;">ТМ / НМ</div>
                <div class="muted">ТМ: <?=enc_h(count($tm))?> • НМ: <?=enc_h(count($hm))?></div>
              </div>

              <div class="enc-hide-mobile">
                <div class="two-col" style="margin-top:10px;">
                  <div>
                    <div class="muted" style="font-weight:900; margin-bottom:6px;">ТМ</div>
                    <?php if (!$tm): ?><div class="muted">—</div><?php else: ?>
                      <div class="enc-cardlist">
                        <?php foreach($tm as $mid): $m=(isset($moveMapExtra[$mid])?$moveMapExtra[$mid]:null); ?>
                          <?=$renderMoveCard($mid, $m, '')?>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div>
                    <div class="muted" style="font-weight:900; margin-bottom:6px;">НМ</div>
                    <?php if (!$hm): ?><div class="muted">—</div><?php else: ?>
                      <div class="enc-cardlist">
                        <?php foreach($hm as $mid): $m=(isset($moveMapExtra[$mid])?$moveMapExtra[$mid]:null); ?>
                          <?=$renderMoveCard($mid, $m, '')?>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="enc-show-mobile" style="margin-top:10px;">
                <?php if ($tm): ?>
                  <div class="muted" style="font-weight:900; margin-bottom:8px;">ТМ</div>
                  <div class="enc-cardlist" style="margin-bottom:14px;">
                    <?php foreach($tm as $mid): $m=(isset($moveMapExtra[$mid])?$moveMapExtra[$mid]:null); ?>
                      <?=$renderMoveCard($mid, $m, '')?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <?php if ($hm): ?>
                  <div class="muted" style="font-weight:900; margin-bottom:8px;">НМ</div>
                  <div class="enc-cardlist">
                    <?php foreach($hm as $mid): $m=(isset($moveMapExtra[$mid])?$moveMapExtra[$mid]:null); ?>
                      <?=$renderMoveCard($mid, $m, '')?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>

            </div>
          <?php endif; ?>

        <?php elseif ($tab === 'evo'): ?>
          <?php
            $prevHtml = (string)((isset($base['prevEvolution']) ? $base['prevEvolution'] : ''));
            $nextHtml = (string)((isset($base['nextEvolution']) ? $base['nextEvolution'] : ''));

            $prevIds = enc_evo_ids_from_html($prevHtml);
            $nextIds = enc_evo_ids_from_html($nextHtml);

            $allIds = array_values(array_unique(array_merge($prevIds, $nextIds)));
            $pokeMap = array();

            if ($allIds) {
                $q = $mysqli->query("SELECT id,name,name_rus FROM base_pokemons WHERE id IN (".implode(',', array_map('intval',$allIds)).")");
                if ($q) while($r=$q->fetch_assoc()) $pokeMap[(int)$r['id']] = $r;
            }

            $renderCards = function($ids) use ($pokeMap) {
                if (!$ids) return '';
                $html = '<div class="evo-grid">';
                foreach ($ids as $pid) {
                    $p = isset($pokeMap[$pid]) ? $pokeMap[$pid] : null;
                    $name = $p ? ($p['name_rus'] ?: $p['name']) : ('#'.$pid);
                    $html .= '<a class="evo-card" href="'.enc_h(enc_url('/pokemon.php?id='.(int)$pid)).'">';
                    $html .= '<img class="evo-sprite" src="'.enc_h(enc_sprite_pokedex((int)$pid)).'" alt="">';
                    $html .= '<div class="evo-meta"><div class="muted">#'.enc_h(enc_pad3((int)$pid)).'</div><div class="evo-name">'.enc_h($name).'</div></div>';
                    $html .= '</a>';
                }
                $html .= '</div>';
                return $html;
            };
          ?>

          <div class="card" style="margin-top:12px;">
            <div style="font-weight:900; font-size:18px;">Эволюции</div>

            <?php if (!$prevIds && !$nextIds && trim($prevHtml)==='' && trim($nextHtml)===''): ?>
              <div class="muted" style="margin-top:10px;">Нет данных об эволюциях.</div>
            <?php else: ?>
              <?php if ($prevIds): ?>
                <div class="muted" style="margin-top:10px; font-weight:900;">Предыдущие стадии</div>
                <?=$renderCards($prevIds)?>
              <?php elseif (trim($prevHtml)!==''): ?>
                <div class="muted" style="margin-top:10px; font-weight:900;">Предыдущие стадии</div>
                <div class="card" style="margin-top:8px;"><?=enc_fix_evo_html($prevHtml)?></div>
              <?php endif; ?>

              <?php if ($nextIds): ?>
                <div class="muted" style="margin-top:14px; font-weight:900;">Следующие стадии</div>
                <?=$renderCards($nextIds)?>
              <?php elseif (trim($nextHtml)!==''): ?>
                <div class="muted" style="margin-top:14px; font-weight:900;">Следующие стадии</div>
                <div class="card" style="margin-top:8px;"><?=enc_fix_evo_html($nextHtml)?></div>
              <?php endif; ?>
            <?php endif; ?>
          </div>

        <?php elseif ($tab === 'builds'): ?>
          <?php
            $viewer = $encyUser;
            $limit = 60;

            // Candidate builds by index table, then filter via enc_can_view_build()
            $q = $mysqli->query("SELECT b.id,b.title,b.description,b.visibility,b.is_recommended,b.sort_order,b.updated_at,b.updated_at_ts,b.created_by,b.shared_user_id,b.team_json,u.login
                                 FROM enc_builds b
                                 JOIN enc_build_pokemon bp ON bp.build_id=b.id
                                 LEFT JOIN users u ON u.id=b.created_by
                                 WHERE bp.pokemon_id=".(int)$id."
                                 GROUP BY b.id
                                 ORDER BY b.is_recommended DESC, b.sort_order DESC, b.updated_at_ts DESC, b.updated_at DESC
                                 LIMIT ".(int)$limit);
            $builds = array();
            if ($q) while($r=$q->fetch_assoc()) {
                $r = enc_build_normalize_row($r);
                if (enc_can_view_build($viewer, $r)) $builds[] = $r;
            }
            $builds = array_slice($builds, 0, 20);
          ?>
          <div class="card" style="margin-top:12px;">
            <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-end;">
              <div style="font-weight:900; font-size:18px;">Сборки с этим покемоном</div>
              <a class="btn" href="<?=enc_h(enc_url('/builds.php?q='.urlencode('#'.$id)))?>">Открыть все</a>
            </div>

            <?php if (!$builds): ?>
              <div class="muted" style="margin-top:10px;">Пока нет доступных сборок с этим покемоном.</div>
            <?php else: ?>
              <div class="build-grid" style="margin-top:12px;">
                <?php foreach($builds as $b): ?>
                  <a class="card" href="<?=enc_h(enc_url('/build.php?id='.(int)$b['id']))?>">
                    <div style="display:flex; justify-content:space-between; gap:10px;">
                      <div style="font-weight:900;"><?=enc_h($b['title'])?></div>
                      <?php if ((int)$b['is_recommended']===1): ?><span class="badge rec">Рекомендовано</span><?php endif; ?>
                    </div>
                    <div class="muted" style="margin-top:6px;">Автор: <?=enc_h($b['login'] ?: ('#'.(int)$b['created_by']))?> • <?=enc_h($b['visibility'])?></div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        <?php else: ?>
          <?php
            // abilities
            $abilitySlots = null;
            $q = $mysqli->query("SELECT slot1,slot2,hidden FROM base_ability_pokemon WHERE id=".(int)$id." LIMIT 1");
            if ($q) $abilitySlots = $q->fetch_assoc();

            $abilityIds = array();
            if ($abilitySlots) {
                foreach (array('slot1','slot2','hidden') as $k) {
                    $v = enc_int((isset($abilitySlots[$k]) ? $abilitySlots[$k] : 0), 0);
                    if ($v>0) $abilityIds[] = $v;
                }
            }
            $abilityIds = array_values(array_unique($abilityIds));
            $abilityMap = array();
            if ($abilityIds) {
                $q = $mysqli->query("SELECT id,name,name_rus FROM base_ability WHERE id IN (".implode(',', array_map('intval',$abilityIds)).")");
                if ($q) while($r=$q->fetch_assoc()) $abilityMap[(int)$r['id']] = $r;
            }
          ?>

          <div class="card" style="margin-top:12px;">
            <div style="font-weight:900; font-size:18px;">Описание</div>
            <div style="margin-top:10px;"><?=enc_render_bbcode((string)((isset($base['about']) ? $base['about'] : '')))?></div>
          </div>

          <div class="two-col" style="margin-top:12px;">
            <div class="card">
              <div style="font-weight:900; font-size:18px;">Способности</div>
              <?php if (!$abilitySlots || !$abilityIds): ?>
                <div class="muted" style="margin-top:10px;">Нет данных.</div>
              <?php else: ?>
                <ul class="list" style="margin-top:10px;">
                  <?php foreach (array('slot1'=>'Обычная 1','slot2'=>'Обычная 2','hidden'=>'Скрытая') as $k=>$label):
                        $aid = enc_int((isset($abilitySlots[$k]) ? $abilitySlots[$k] : 0), 0);
                        if ($aid<=0) continue;
                        $a = isset($abilityMap[$aid]) ? $abilityMap[$aid] : null;
                        $an = $a ? ($a['name_rus'] ?: $a['name']) : ('#'.$aid);
                  ?>
                    <li><span class="muted"><?=enc_h($label)?>:</span> <a class="link" href="<?=enc_h(enc_url('/ability.php?id='.$aid))?>"><?=enc_h($an)?></a></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>

            <div class="card">
              <div style="font-weight:900; font-size:18px;">Параметры</div>
              <table class="table" style="margin-top:10px;">
                <tbody>
                  <tr><th style="width:140px;">Группа опыта</th><td><?=enc_h((int)((isset($base['exp_group']) ? $base['exp_group'] : 0)))?></td></tr>
                  <tr><th>База поимки</th><td><?=enc_h((int)((isset($base['catch_rate']) ? $base['catch_rate'] : 0)))?></td></tr>
                  <tr><th>Шанс поимки</th><td><?=enc_h((int)((isset($base['chanceCatch']) ? $base['chanceCatch'] : 0)))?>%</td></tr>
                  <tr><th>Класс</th><td><?=enc_h((string)((isset($base['class']) ? $base['class'] : '')))?></td></tr>
                </tbody>
              </table>
            </div>
          </div>

        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
