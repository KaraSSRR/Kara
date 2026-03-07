<?php
declare(strict_types=1);

use function App\Support\e;

/** @var array $items */
?>
<div class="card">
  <h2>Инвентарь</h2>
  <?php if (empty($items)): ?>
    <p class="muted">Пусто.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>Предмет</th><th>Категория</th><th>Кол-во</th><th class="muted">Описание</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= e((string)$it['name']) ?></td>
            <td class="muted"><?= e((string)$it['category']) ?></td>
            <td><?= e((string)$it['count']) ?></td>
            <td class="muted"><?= e((string)$it['description']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
