<?php
declare(strict_types=1);

use function App\Support\e;

/** @var array $user */
/** @var array $locations */
$current = (int)($user['current_location_id'] ?? 0);
?>
<div class="modal-pane">
  <div class="pane-head">
    <div>
      <div class="kicker">Мир</div>
      <h2>Путешествие</h2>
      <div class="muted">Выбери новую локацию. Во время активного боя перемещение запрещено.</div>
    </div>
  </div>

  <div class="pane-body">
    <table class="table">
      <thead>
        <tr><th>#</th><th>Название</th><th>Регион</th><th>PvE</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($locations as $loc): ?>
          <tr>
            <td><?= e((string)$loc['id']) ?></td>
            <td>
              <a href="/locations/<?= e((string)$loc['id']) ?>" data-modal="1">
                <?= e((string)$loc['name']) ?>
              </a>
            </td>
            <td class="muted"><?= e((string)$loc['region']) ?></td>
            <td class="muted"><?= $loc['is_pve'] ? 'да' : 'нет' ?></td>
            <td>
              <?php if ((int)$loc['id'] === $current): ?>
                <span class="badge">текущая</span>
              <?php else: ?>
                <form method="post" action="/locations/<?= e((string)$loc['id']) ?>/travel" style="margin:0" data-no-spa="0">
                  <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
                  <button class="btn" type="submit">Перейти</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
