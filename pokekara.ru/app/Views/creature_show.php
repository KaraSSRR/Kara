<?php
declare(strict_types=1);

use function App\Support\e;
use App\Domain\Game\StatCalculator;

/** @var array $creature */

$c = $creature;

function typeBadge(?string $t): string {
  if (!$t) return '';
  $k = strtolower($t);
  $label = strtoupper($t);
  return '<span class="type type--' . e($k) . '">' . e($label) . '</span>';
}

$base = $c['base_stats'] ?? [];
$iv = $c['iv_arr'] ?? [];
$ev = $c['ev_arr'] ?? [];
$final = $c['final_stats'] ?? [];

$nature = (string)($c['nature'] ?? 'hardy');
$mods = StatCalculator::natureModifiers($nature);

$statOrder = [
  'hp' => 'HP',
  'atk' => 'ATK',
  'def' => 'DEF',
  'spa' => 'SPA',
  'spd' => 'SPD',
  'spe' => 'SPE',
];

$name = (string)($c['species_name'] ?? 'Существо');
if (!empty($c['nickname'])) $name .= ' · ' . (string)$c['nickname'];

$hp = (int)($c['current_hp'] ?? 0);
$mx = (int)($c['max_hp'] ?? ($final['hp'] ?? 1));
$mx = max(1, $mx);
$pct = (int)floor(($hp / $mx) * 100);
$pct = max(0, min(100, $pct));
?>
<div class="grid">
  <section class="card col-12">
    <div class="section-head">
      <div>
        <h1><?= e($name) ?></h1>
        <p class="muted">ID #<?= e((string)$c['id']) ?> · Ур. <?= e((string)$c['level']) ?> · EXP <?= e((string)$c['exp']) ?></p>
      </div>
      <div class="section-actions">
        <a class="btn" href="/creatures">К списку</a>
        <form method="post" action="/battle/start" style="margin:0">
          <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
          <input type="hidden" name="creature_id" value="<?= e((string)$c['id']) ?>">
          <button class="btn primary" type="submit" <?= $hp > 0 ? '' : 'disabled' ?>>В бой</button>
        </form>
      </div>
    </div>

    <div class="row" style="gap:10px; flex-wrap:wrap; margin-top:10px">
      <?= typeBadge((string)($c['type1'] ?? '')) ?>
      <?= typeBadge($c['type2'] ? (string)$c['type2'] : null) ?>
      <span class="chip">Nature: <strong><?= e(strtoupper($nature)) ?></strong></span>
      <span class="chip">Happiness: <strong><?= e((string)$c['happiness']) ?></strong></span>
      <span class="chip">Ability: <strong><?= e((string)($c['ability_name'] ?? '—')) ?></strong></span>
      <?php if (!empty($c['held_item_name'])): ?>
        <span class="chip">Item: <strong><?= e((string)$c['held_item_name']) ?></strong></span>
      <?php else: ?>
        <span class="chip muted">Item: —</span>
      <?php endif; ?>
    </div>

    <div class="hp" style="margin-top:12px">
      <div class="hp-bar"><span style="width:<?= e((string)$pct) ?>%"></span></div>
      <div class="hp-text"><?= e((string)$hp) ?> / <?= e((string)$mx) ?> HP</div>
    </div>

    <div class="grid" style="margin-top:14px">
      <div class="card soft col-6">
        <h2>Статы</h2>
        <p class="muted" style="margin-top:-6px">Финальные статы считаются по формуле Gen9-like (уровень + IV/EV + nature).</p>

        <table class="table table-compact">
          <thead>
            <tr>
              <th>STAT</th>
              <th class="muted">Base</th>
              <th class="muted">IV</th>
              <th class="muted">EV</th>
              <th>Final</th>
              <th class="muted">Nature</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($statOrder as $k => $label): ?>
              <?php
                $nm = $mods[$k] ?? 1.0;
                $nmTxt = $nm === 1.1 ? '+10%' : ($nm === 0.9 ? '-10%' : '—');
              ?>
              <tr>
                <td><strong><?= e($label) ?></strong></td>
                <td class="muted"><?= e((string)($base[$k] ?? 0)) ?></td>
                <td class="muted"><?= e((string)($iv[$k] ?? 0)) ?></td>
                <td class="muted"><?= e((string)($ev[$k] ?? 0)) ?></td>
                <td><?= e((string)($final[$k] ?? 0)) ?></td>
                <td class="muted"><?= e($nmTxt) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="card soft col-6">
        <h2>Ходы (moveset)</h2>
        <p class="muted" style="margin-top:-6px">До 4 ходов · PP сохраняется.</p>

        <?php if (empty($c['moves'])): ?>
          <p class="muted">Пусто. Применить миграцию 002 и сиды moves.</p>
        <?php else: ?>
          <div class="move-grid">
            <?php foreach ($c['moves'] as $m): ?>
              <?php
                $ppc = (int)$m['pp_current']; $ppm = (int)$m['pp_max'];
                $power = $m['power'] === null ? '—' : (string)$m['power'];
                $acc = $m['accuracy'] === null ? '∞' : ((string)$m['accuracy'] . '%');
                $pri = (int)($m['priority'] ?? 0);
              ?>
              <div class="card move-static">
                <div class="row between">
                  <div class="move-name"><?= e((string)$m['name']) ?></div>
                  <div class="muted">slot <?= e((string)$m['slot']) ?></div>
                </div>
                <div class="row" style="gap:8px; flex-wrap:wrap; margin-top:8px">
                  <?= typeBadge((string)$m['type']) ?>
                  <span class="chip chip-cat chip-cat--<?= e(strtolower((string)$m['category'])) ?>"><?= e(strtoupper((string)$m['category'])) ?></span>
                  <?php if ($pri !== 0): ?><span class="chip">PRIO <?= e((string)$pri) ?></span><?php endif; ?>
                </div>
                <div class="move-meta" style="margin-top:10px">
                  <div><span class="muted">PP</span> <?= e((string)$ppc) ?>/<?= e((string)$ppm) ?></div>
                  <div><span class="muted">PWR</span> <?= e($power) ?></div>
                  <div><span class="muted">ACC</span> <?= e($acc) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($c['ability_description'])): ?>
      <div class="card soft" style="margin-top:12px">
        <div class="kicker">Ability</div>
        <div class="title"><?= e((string)$c['ability_name']) ?></div>
        <p class="muted"><?= e((string)$c['ability_description']) ?></p>
      </div>
    <?php endif; ?>

    <div class="card soft" style="margin-top:12px">
      <div class="kicker">Эволюции</div>
      <?php if (empty($c['evolutions'])): ?>
        <p class="muted">Эволюций пока нет или условия не заданы.</p>
      <?php else: ?>
        <ul class="list">
          <?php foreach ($c['evolutions'] as $evo): ?>
            <li>
              <strong><?= e((string)$evo['to_species_name']) ?></strong>
              <?php if (!empty($evo['condition_text'])): ?>
                <span class="muted">· <?= e((string)$evo['condition_text']) ?></span>
              <?php elseif ($evo['method'] === 'level' && $evo['min_level']): ?>
                <span class="muted">· Уровень <?= e((string)$evo['min_level']) ?></span>
              <?php elseif ($evo['method'] === 'item' && !empty($evo['item_name'])): ?>
                <span class="muted">· Предмет: <?= e((string)$evo['item_name']) ?></span>
              <?php else: ?>
                <span class="muted">· Способ: <?= e((string)$evo['method']) ?></span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </section>
</div>
