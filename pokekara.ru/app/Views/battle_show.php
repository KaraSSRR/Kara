<?php
declare(strict_types=1);

use function App\Support\e;

/** @var array $battle */
/** @var array $state */
/** @var array $logs */
/** @var array|null $location */

$p1 = $state['p1'] ?? [];
$p2 = $state['p2'] ?? [];

function typeBadge(?string $t): string {
  if (!$t) return '';
  $k = strtolower($t);
  $label = strtoupper($t);
  return '<span class="type type--' . e($k) . '">' . e($label) . '</span>';
}

function catBadge(?string $cat): string {
  if (!$cat) return '';
  $k = strtolower($cat);
  $label = strtoupper($cat);
  return '<span class="chip chip-cat chip-cat--' . e($k) . '">' . e($label) . '</span>';
}

function hpPct(int $hp, int $max): int {
  $max = max(1, $max);
  $pct = (int)floor(($hp / $max) * 100);
  return max(0, min(100, $pct));
}

$turn = (int)($state['turn'] ?? 0);
$finished = !empty($state['finished']);
$winner = $state['winner'] ?? null;
?>
<div class="grid">
  <section class="card col-12">
    <div class="section-head">
      <div>
        <h1>Бой #<?= e((string)$battle['id']) ?></h1>
        <p class="muted">Turn: <?= e((string)$turn) ?> · Seed: <span class="mono"><?= e((string)$battle['seed']) ?></span></p>
        <p class="muted" style="margin-top:-4px">Локация: <?= !empty($location) ? '<strong>' . e((string)$location['name']) . '</strong>' : '—' ?></p>
      </div>
      <div class="section-actions">
        <a class="btn" href="/battle">К списку</a>
        <a class="btn" href="/api/battle/<?= e((string)$battle['id']) ?>" data-no-spa="1" target="_blank" rel="noopener">Replay JSON</a>
      </div>
    </div>

    <?php if ($finished): ?>
      <div class="notice ok" style="margin-top:12px">
        <strong>Бой завершён.</strong>
        <?php if ($winner === 'p1'): ?>
          Победа игрока 🎉
        <?php elseif ($winner === 'p2'): ?>
          Победа противника.
        <?php else: ?>
          Ничья.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="battle-grid" style="margin-top:12px">
      <!-- Player -->
      <div class="card soft">
        <div class="row between">
          <div>
            <div class="kicker">Ты</div>
            <div class="title">
              <?= e((string)($p1['name'] ?? '—')) ?>
              <?php if (!empty($p1['nickname'])): ?><span class="muted">· <?= e((string)$p1['nickname']) ?></span><?php endif; ?>
            </div>
            <div class="muted">Ур. <?= e((string)($p1['level'] ?? '1')) ?></div>
          </div>
          <div class="row" style="gap:8px; flex-wrap:wrap; justify-content:flex-end">
            <?= typeBadge($p1['types'][0] ?? null) ?>
            <?= typeBadge($p1['types'][1] ?? null) ?>
          </div>
        </div>

        <div class="hp" style="margin-top:10px">
          <?php $hp = (int)($p1['hp'] ?? 0); $mx = (int)($p1['max_hp'] ?? 1); $pct = hpPct($hp, $mx); ?>
          <div class="hp-bar"><span style="width:<?= e((string)$pct) ?>%"></span></div>
          <div class="hp-text"><?= e((string)$hp) ?> / <?= e((string)$mx) ?> HP</div>
        </div>

        <div class="row" style="gap:8px; flex-wrap:wrap; margin-top:10px">
          <span class="chip">Ability: <?= e((string)($p1['ability']['name'] ?? '—')) ?></span>
          <?php if (!empty($p1['item']['name'])): ?>
            <span class="chip">Item: <?= e((string)$p1['item']['name']) ?></span>
          <?php else: ?>
            <span class="chip muted">Item: —</span>
          <?php endif; ?>
          <?php if (!empty($p1['status']['major'])): ?>
            <span class="chip">Status: <?= e((string)$p1['status']['major']) ?></span>
          <?php else: ?>
            <span class="chip muted">Status: —</span>
          <?php endif; ?>
        </div>

        <div class="row" style="gap:8px; flex-wrap:wrap; margin-top:10px">
          <span class="chip">ATK <?= e((string)($p1['boosts']['atk'] ?? 0)) ?></span>
          <span class="chip">DEF <?= e((string)($p1['boosts']['def'] ?? 0)) ?></span>
          <span class="chip">SPA <?= e((string)($p1['boosts']['spa'] ?? 0)) ?></span>
          <span class="chip">SPD <?= e((string)($p1['boosts']['spd'] ?? 0)) ?></span>
          <span class="chip">SPE <?= e((string)($p1['boosts']['spe'] ?? 0)) ?></span>
        </div>

        <h2 style="margin-top:14px">Ходы</h2>
        <p class="muted" style="margin-top:-6px">Противник выбирает ход случайно (MVP). PP расходуется, точность/крит/рандом детерминированы seed.</p>

        <div class="move-grid">
          <?php foreach (($p1['moves'] ?? []) as $m): ?>
            <?php
              $slot = (int)$m['slot'];
              $ppc = (int)$m['pp_current']; $ppm = (int)$m['pp_max'];
              $disabled = $finished || $ppc <= 0 || (int)($p1['hp'] ?? 0) <= 0 || (int)($p2['hp'] ?? 0) <= 0;
              $power = $m['power'] === null ? '—' : (string)$m['power'];
              $acc = $m['accuracy'] === null ? '∞' : ((string)$m['accuracy'] . '%');
              $pri = (int)($m['priority'] ?? 0);
            ?>
            <form method="post" action="/battle/<?= e((string)$battle['id']) ?>/move" class="move-card" style="margin:0">
              <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
              <input type="hidden" name="slot" value="<?= e((string)$slot) ?>">
              <button type="submit" class="move-btn" <?= $disabled ? 'disabled' : '' ?>>
                <div class="row between" style="gap:10px; align-items:flex-start">
                  <div>
                    <div class="move-name"><?= e((string)$m['name']) ?></div>
                    <div class="row" style="gap:8px; flex-wrap:wrap; margin-top:6px">
                      <?= typeBadge((string)$m['type']) ?>
                      <?= catBadge((string)$m['category']) ?>
                      <?php if ($pri !== 0): ?><span class="chip">PRIO <?= e((string)$pri) ?></span><?php endif; ?>
                    </div>
                  </div>
                  <div class="move-meta">
                    <div><span class="muted">PP</span> <?= e((string)$ppc) ?>/<?= e((string)$ppm) ?></div>
                    <div><span class="muted">PWR</span> <?= e($power) ?></div>
                    <div><span class="muted">ACC</span> <?= e($acc) ?></div>
                  </div>
                </div>
              </button>
            </form>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Opponent -->
      <div class="card soft">
        <div class="row between">
          <div>
            <div class="kicker">Противник</div>
            <div class="title"><?= e((string)($p2['name'] ?? '—')) ?></div>
            <div class="muted">Ур. <?= e((string)($p2['level'] ?? '1')) ?></div>
          </div>
          <div class="row" style="gap:8px; flex-wrap:wrap; justify-content:flex-end">
            <?= typeBadge($p2['types'][0] ?? null) ?>
            <?= typeBadge($p2['types'][1] ?? null) ?>
          </div>
        </div>

        <div class="hp" style="margin-top:10px">
          <?php $hp2 = (int)($p2['hp'] ?? 0); $mx2 = (int)($p2['max_hp'] ?? 1); $pct2 = hpPct($hp2, $mx2); ?>
          <div class="hp-bar hp-bar--enemy"><span style="width:<?= e((string)$pct2) ?>%"></span></div>
          <div class="hp-text"><?= e((string)$hp2) ?> / <?= e((string)$mx2) ?> HP</div>
        </div>

        <div class="row" style="gap:8px; flex-wrap:wrap; margin-top:10px">
          <span class="chip">Ability: <?= e((string)($p2['ability']['name'] ?? '—')) ?></span>
          <?php if (!empty($p2['status']['major'])): ?>
            <span class="chip">Status: <?= e((string)$p2['status']['major']) ?></span>
          <?php else: ?>
            <span class="chip muted">Status: —</span>
          <?php endif; ?>
        </div>

        <h2 style="margin-top:14px">Наблюдение</h2>
        <div class="kpi-grid">
          <div class="kpi">
            <div class="kpi-label">ATK</div>
            <div class="kpi-value"><?= e((string)($p2['stats']['atk'] ?? '—')) ?></div>
          </div>
          <div class="kpi">
            <div class="kpi-label">DEF</div>
            <div class="kpi-value"><?= e((string)($p2['stats']['def'] ?? '—')) ?></div>
          </div>
          <div class="kpi">
            <div class="kpi-label">SPA</div>
            <div class="kpi-value"><?= e((string)($p2['stats']['spa'] ?? '—')) ?></div>
          </div>
          <div class="kpi">
            <div class="kpi-label">SPD</div>
            <div class="kpi-value"><?= e((string)($p2['stats']['spd'] ?? '—')) ?></div>
          </div>
          <div class="kpi">
            <div class="kpi-label">SPE</div>
            <div class="kpi-value"><?= e((string)($p2['stats']['spe'] ?? '—')) ?></div>
          </div>
          <div class="kpi">
            <div class="kpi-label">Boost ATK</div>
            <div class="kpi-value"><?= e((string)($p2['boosts']['atk'] ?? 0)) ?></div>
          </div>
          <div class="kpi">
            <div class="kpi-label">Boost DEF</div>
            <div class="kpi-value"><?= e((string)($p2['boosts']['def'] ?? 0)) ?></div>
          </div>
          <div class="kpi">
            <div class="kpi-label">Boost SPA</div>
            <div class="kpi-value"><?= e((string)($p2['boosts']['spa'] ?? 0)) ?></div>
          </div>
          <div class="kpi">
            <div class="kpi-label">Boost SPD</div>
            <div class="kpi-value"><?= e((string)($p2['boosts']['spd'] ?? 0)) ?></div>
          </div>
          <div class="kpi">
            <div class="kpi-label">Boost SPE</div>
            <div class="kpi-value"><?= e((string)($p2['boosts']['spe'] ?? 0)) ?></div>
          </div>
        </div>

        <h2 style="margin-top:14px">Лог боя</h2>
        <?php if (empty($logs)): ?>
          <p class="muted">Лога пока нет. Сделай ход.</p>
        <?php else: ?>
          <div class="log">
            <?php $curTurn = null; ?>
            <?php foreach ($logs as $l): ?>
              <?php $t = (int)($l['turn'] ?? 0); ?>
              <?php if ($curTurn !== $t): $curTurn = $t; ?>
                <div class="log-turn">Turn <?= e((string)$curTurn) ?></div>
              <?php endif; ?>
              <div class="log-line"><?= e((string)$l['message']) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
