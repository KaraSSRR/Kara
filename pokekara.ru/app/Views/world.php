<?php
declare(strict_types=1);

use function App\Support\e;

/** @var array $user */
/** @var array|null $currentLocation */
/** @var array|null $activeBattle */
/** @var string|null $open */
?>

<div class="world">
  <div class="world-hero">
    <div class="world-title">
      <div class="kicker">Мир</div>
      <h1><?= e((string)($currentLocation['name'] ?? 'Неизвестная локация')) ?></h1>
      <p class="muted"><?= e((string)($currentLocation['description'] ?? 'Исследуй мир, находи встречи и сражайся.')) ?></p>
    </div>

    <div class="world-actions">
      <?php if (is_array($activeBattle)): ?>
        <a class="btn danger" href="/battle/<?= e((string)$activeBattle['id']) ?>" data-modal="1" data-modal-kind="battle">Продолжить бой</a>
      <?php else: ?>
        <a class="btn primary" href="/battle" data-modal="1" data-modal-kind="battle">Начать бой</a>
      <?php endif; ?>

      <a class="btn" href="/locations/panel" data-modal="1">Путешествие</a>
      <a class="btn" href="/creatures" data-modal="1">Команда</a>
      <a class="btn" href="/inventory" data-modal="1">Инвентарь</a>
    </div>
  </div>

  <div class="world-grid">
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">Встречи</div>
        <div class="muted">MVP</div>
      </div>
      <div class="encounters">
        <button class="encounter" type="button" data-open-modal="/battle" data-modal-kind="battle">
          <span class="enc-ic">⚔️</span>
          <span class="enc-text">
            <span class="enc-name">Дикая встреча</span>
            <span class="enc-sub muted">Открыть бой</span>
          </span>
        </button>

        <button class="encounter" type="button" data-open-modal="/creatures" >
          <span class="enc-ic">🧬</span>
          <span class="enc-text">
            <span class="enc-name">Команда</span>
            <span class="enc-sub muted">Карточки существ</span>
          </span>
        </button>

        <button class="encounter" type="button" data-open-modal="/inventory" >
          <span class="enc-ic">🎒</span>
          <span class="enc-text">
            <span class="enc-name">Инвентарь</span>
            <span class="enc-sub muted">Предметы и экипировка</span>
          </span>
        </button>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">Состояние</div>
        <div class="muted"><?= e((string)($user['username'] ?? '')) ?></div>
      </div>
      <div class="kv">
        <div class="kv-row"><span class="muted">Локация</span><span><?= e((string)($currentLocation['name'] ?? '—')) ?></span></div>
        <div class="kv-row"><span class="muted">ID</span><span>#<?= e((string)($currentLocation['id'] ?? '—')) ?></span></div>
        <div class="kv-row"><span class="muted">Бой</span><span><?= is_array($activeBattle) ? 'активен' : 'нет' ?></span></div>
      </div>
      <div class="hint muted">
        Подсказка: все окна открываются поверх мира. Чат справа — всегда.
      </div>
    </div>
  </div>

  <?php if (is_string($open) && $open !== ''): ?>
    <div class="hidden" id="open-modal-onload" data-url="<?= e($open) ?>"></div>
  <?php endif; ?>
</div>
