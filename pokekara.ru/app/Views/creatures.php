<?php
declare(strict_types=1);

use function App\Support\e;

/** @var array $creatures */

function typeBadge(?string $t): string {
  if (!$t) return '';
  $k = strtolower($t);
  $label = strtoupper($t);
  return '<span class="type type--' . e($k) . '">' . e($label) . '</span>';
}
?>
<div class="grid">
  <section class="card col-12">
    <div class="section-head">
      <div>
        <h1>Существа</h1>
        <p class="muted">Список твоих существ. Открой карточку, чтобы увидеть IV/EV/nature/happiness/статы/ходы.</p>
      </div>
      <div class="section-actions">
        <a class="btn primary" href="/battle">В бой</a>
      </div>
    </div>

    <?php if (empty($creatures)): ?>
      <p class="muted">Нет существ.</p>
    <?php else: ?>
      <div class="grid" style="margin-top:12px">
        <?php foreach ($creatures as $c): ?>
          <?php
            $name = (string)$c['species_name'];
            if (!empty($c['nickname'])) $name .= ' · ' . (string)$c['nickname'];
            $t1 = (string)$c['type1'];
            $t2 = $c['type2'] ? (string)$c['type2'] : null;
            $hp = (int)$c['current_hp'];
            $max = (int)($c['max_hp'] ?? $hp);
            if ($max <= 0) $max = max(1, $hp);
            $pct = (int)floor(($hp / $max) * 100);
            $pct = max(0, min(100, $pct));
          ?>
          <div class="card soft col-6">
            <div class="row between">
              <div>
                <div class="title"><a href="/creatures/<?= e((string)$c['id']) ?>"><?= e($name) ?></a></div>
                <div class="muted">ID #<?= e((string)$c['id']) ?> · Ур. <?= e((string)$c['level']) ?> · EXP <?= e((string)($c['exp'] ?? 0)) ?></div>
              </div>
              <div class="row" style="gap:8px; flex-wrap:wrap; justify-content:flex-end">
                <?= typeBadge($t1) ?>
                <?= typeBadge($t2) ?>
              </div>
            </div>

            <div class="row" style="gap:8px; flex-wrap:wrap; margin-top:10px">
              <span class="chip">Nature: <?= e(strtoupper((string)($c['nature'] ?? 'hardy'))) ?></span>
              <span class="chip">Happiness: <?= e((string)($c['happiness'] ?? 0)) ?></span>
              <span class="chip">Ability: <?= e((string)($c['ability_name'] ?? '—')) ?></span>
            </div>

            <div class="hp" style="margin-top:10px">
              <div class="hp-bar"><span style="width:<?= e((string)$pct) ?>%"></span></div>
              <div class="hp-text"><?= e((string)$hp) ?> / <?= e((string)$max) ?> HP</div>
            </div>

            <div class="form-actions" style="margin-top:12px">
              <a class="btn primary" href="/creatures/<?= e((string)$c['id']) ?>">Карточка</a>
              <a class="btn" href="/battle">В бой</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
