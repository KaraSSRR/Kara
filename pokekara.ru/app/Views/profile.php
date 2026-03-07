<?php
declare(strict_types=1);

use function App\Support\e;

/** @var array|null $user */
/** @var array $creatures */
/** @var array|null $location */

function hpPct(int $hp, int $max): int {
  $max = max(1, $max);
  $pct = (int)floor(($hp / $max) * 100);
  return max(0, min(100, $pct));
}
?>
<div class="grid">
  <section class="card col-12">
    <div class="section-head">
      <div>
        <h1>Профиль</h1>
        <p class="muted">Твой аккаунт, текущая локация и команда.</p>
      </div>
      <div class="section-actions">
        <a class="btn primary" href="/battle">В бой</a>
        <a class="btn" href="/creatures">Существа</a>
      </div>
    </div>

    <div class="grid" style="margin-top:12px">
      <div class="card soft col-6">
        <h2>Игрок</h2>
        <?php if ($user): ?>
          <div class="row between" style="gap:10px">
            <div>
              <div class="title"><?= e((string)$user['username']) ?></div>
              <div class="muted"><?= e((string)$user['email']) ?></div>
              <div class="muted">ID: <?= e((string)$user['id']) ?> · Создан: <?= e((string)$user['created_at']) ?></div>
              <div class="muted">Последний вход: <?= e((string)($user['last_login_at'] ?? '')) ?></div>
            </div>
            <div style="text-align:right">
              <div class="kicker">Локация</div>
              <div class="title" style="font-size:16px">
                <?php if ($location): ?>
                  <?= e((string)$location['name']) ?>
                <?php else: ?>
                  <span class="muted">не выбрана</span>
                <?php endif; ?>
              </div>
              <div style="margin-top:8px">
                <a class="btn" href="/locations">Сменить</a>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <div class="form-actions" style="margin-top:12px">
          <a class="btn" href="/inventory">Инвентарь</a>
          <a class="btn" href="/locations">Локации</a>
        </div>
      </div>

      <div class="card soft col-6">
        <h2>Команда</h2>
        <?php if (empty($creatures)): ?>
          <p class="muted">Пока пусто.</p>
        <?php else: ?>
          <div class="stack">
            <?php foreach ($creatures as $c): ?>
              <?php
                $hp = (int)$c['current_hp'];
                $mx = (int)($c['max_hp'] ?? $hp);
                if ($mx <= 0) $mx = max(1, $hp);
                $pct = hpPct($hp, $mx);
                $name = (string)$c['species_name'];
                if (!empty($c['nickname'])) $name .= ' · ' . (string)$c['nickname'];
              ?>
              <div class="row between stack-item">
                <div>
                  <div class="title" style="font-size:16px">
                    <a href="/creatures/<?= e((string)$c['id']) ?>"><?= e($name) ?></a>
                  </div>
                  <div class="muted">Ур. <?= e((string)$c['level']) ?> · Nature <?= e(strtoupper((string)($c['nature'] ?? 'hardy'))) ?></div>
                </div>
                <div style="min-width:160px">
                  <div class="hp">
                    <div class="hp-bar"><span style="width:<?= e((string)$pct) ?>%"></span></div>
                    <div class="hp-text"><?= e((string)$hp) ?>/<?= e((string)$mx) ?> HP</div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="form-actions" style="margin-top:12px">
          <a class="btn primary" href="/creatures">Открыть всех</a>
          <a class="btn" href="/battle">В бой</a>
        </div>
      </div>
    </div>

    <div class="card soft" style="margin-top:12px">
      <div class="kicker">Milestone 2</div>
      <div class="title">Battle Engine MVP 1v1</div>
      <p class="muted">State + actions + logs хранятся в БД; replay детерминирован seed. Дальше — статусы, смена существ, предметы, способности и режимы (2v2, PvP).</p>
    </div>
  </section>
</div>
