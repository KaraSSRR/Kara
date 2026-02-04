<?php
declare(strict_types=1);

use function App\Support\e;

/** @var array $user */
/** @var array $location */
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
  </div>
</div>
