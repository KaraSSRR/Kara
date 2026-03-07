<?php
declare(strict_types=1);

use function App\Support\e;

/** @var array|null $active_battle */
/** @var array|null $active_location */
/** @var array $creatures */
?>
<div class="grid">
  <section class="card col-12">
    <div class="section-head">
      <div>
        <h1>Бой (1v1)</h1>
        <p class="muted">MVP-движок: приоритет, скорость, точность/уклонение, STAB, тип-эффективность, crit, RNG seed + replay.</p>
      </div>
      <div class="section-actions">
        <?php if ($active_battle): ?>
          <a class="btn primary" href="/battle/<?= e((string)$active_battle['id']) ?>">Продолжить</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($active_battle): ?>
      <div class="card soft" style="margin-top:12px">
        <div class="row between">
          <div>
            <div class="kicker">Активный бой</div>
            <div class="title">#<?= e((string)$active_battle['id']) ?> <span class="chip">turn <?= e((string)$active_battle['turn']) ?></span></div>
            <div class="muted">Статус: <?= e((string)$active_battle['status']) ?></div>
            <?php if (!empty($active_location)): ?>
              <div class="muted">Локация: <strong><?= e((string)$active_location['name']) ?></strong></div>
            <?php else: ?>
              <div class="muted">Локация: —</div>
            <?php endif; ?>
          </div>
          <div class="row" style="gap:10px">
            <a class="btn primary" href="/battle/<?= e((string)$active_battle['id']) ?>">Открыть бой</a>
            <a class="btn" href="/api/battle/<?= e((string)$active_battle['id']) ?>" data-no-spa="1" target="_blank" rel="noopener">Replay JSON</a>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div style="margin-top:14px">
      <h2>Начать бой</h2>
      <?php if (empty($creatures)): ?>
        <p class="muted">Нет существ. Зарегистрируйся заново или добавь сиды.</p>
      <?php else: ?>
        <div class="grid">
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
              $canFight = $hp > 0;
            ?>
            <div class="card soft col-6">
              <div class="row between">
                <div>
                  <div class="title">
                    <a href="/creatures/<?= e((string)$c['id']) ?>"><?= e($name) ?></a>
                  </div>
                  <div class="muted">Ур. <?= e((string)$c['level']) ?> · <?= e(strtoupper($t1)) ?><?= $t2 ? ' / ' . e(strtoupper($t2)) : '' ?></div>
                </div>
                <div class="row" style="gap:10px; align-items:flex-start">
                  <span class="chip"><?= e((string)($c['ability_name'] ?? '—')) ?></span>
                </div>
              </div>

              <div class="hp" style="margin-top:10px">
                <div class="hp-bar"><span style="width:<?= e((string)$pct) ?>%"></span></div>
                <div class="hp-text"><?= e((string)$hp) ?> / <?= e((string)$max) ?> HP</div>
              </div>

              <div class="form-actions" style="margin-top:12px">
                <form method="post" action="/battle/start" style="margin:0">
                  <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
                  <input type="hidden" name="creature_id" value="<?= e((string)$c['id']) ?>">
                  <button class="btn primary" type="submit" <?= $canFight ? '' : 'disabled' ?>>В бой</button>
                </form>
                <a class="btn" href="/creatures/<?= e((string)$c['id']) ?>">Карточка</a>
              </div>
              <?php if (!$canFight): ?>
                <p class="muted" style="margin:10px 0 0 0">Существо без HP — восстанови позже (механика лечения добавим в следующих milestone).</p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
