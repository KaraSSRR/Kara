<?php
declare(strict_types=1);

use function App\Support\e;

/** @var array $user */
/** @var array $location */
/** @var array $creatures */
/** @var array|null $active_battle */
$current = (int)($user['current_location_id'] ?? 0);
?>
<div class="grid">
  <div class="col-12">
    <div class="card">
      <h2><?= e((string)$location['name']) ?></h2>
      <div class="muted">slug: <?= e((string)$location['slug']) ?> · регион: <?= e((string)$location['region']) ?> · PvE: <?= $location['is_pve'] ? 'да' : 'нет' ?></div>
      <p style="margin-top:10px"><?= e((string)$location['description']) ?></p>

      <div class="form-actions">
        <a class="btn" href="/locations">Назад</a>
        <?php if ((int)$location['id'] === $current): ?>
          <span class="badge">ты здесь</span>
        <?php else: ?>
          <form method="post" action="/locations/<?= e((string)$location['id']) ?>/travel" style="margin:0">
            <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
            <button class="btn primary" type="submit">Сделать текущей</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($active_battle)): ?>
      <div class="card soft" style="margin-top:12px">
        <div class="notice err">У тебя уже есть активный бой. Заверши его перед исследованием.</div>
        <a class="btn primary" href="/battle/<?= e((string)$active_battle['id']) ?>">Открыть бой</a>
      </div>
    <?php else: ?>
      <div class="card soft" style="margin-top:12px">
        <h3>Исследовать локацию</h3>
        <p class="muted">Запускает PvE-энкаунтер на основе таблицы encounters для текущей локации.</p>
        <?php if (empty($creatures)): ?>
          <p class="muted">Нет существ для исследования.</p>
        <?php else: ?>
          <form method="post" action="/locations/<?= e((string)$location['id']) ?>/explore">
            <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
            <label class="label">Выбери существо</label>
            <select name="creature_id" class="select">
              <?php foreach ($creatures as $c): ?>
                <?php
                  $name = (string)$c['species_name'];
                  if (!empty($c['nickname'])) $name .= ' · ' . (string)$c['nickname'];
                  $hp = (int)$c['current_hp'];
                ?>
                <option value="<?= e((string)$c['id']) ?>" <?= $hp > 0 ? '' : 'disabled' ?>>
                  <?= e($name) ?> · Ур. <?= e((string)$c['level']) ?> · HP <?= e((string)$hp) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-actions" style="margin-top:10px">
              <button class="btn primary" type="submit">Исследовать</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
