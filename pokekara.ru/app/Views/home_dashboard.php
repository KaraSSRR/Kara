<?php
declare(strict_types=1);

use function App\Support\e;

/** @var array|null $user */
?>
<div class="grid">
  <section class="card col-12">
    <div class="hero">
      <div>
        <h1>Панель игрока</h1>
        <p class="muted">С возвращением<?= $user && isset($user['username']) ? ', ' . e((string)$user['username']) : '' ?>. Milestone 2 уже здесь: карточка существа + бой 1v1 (MVP).</p>
      </div>
      <div class="hero-actions">
        <a class="btn primary" href="/battle">В бой</a>
        <a class="btn" href="/creatures">Существа</a>
      </div>
    </div>

    <div class="grid" style="margin-top:12px">
      <div class="card soft col-6">
        <h2>Быстрый доступ</h2>
        <div class="form-actions">
          <a class="btn primary" href="/me">Профиль</a>
          <a class="btn" href="/creatures">Существа</a>
          <a class="btn" href="/battle">Бой</a>
          <a class="btn" href="/inventory">Инвентарь</a>
          <a class="btn" href="/locations">Локации</a>
        </div>
      </div>

      <div class="card soft col-6">
        <h2>Что есть в Milestone 2</h2>
        <ul class="list">
          <li><span class="chip ok">Showdown-like модель</span> IV/EV, природа, счастье, ability, held item, moveset + PP.</li>
          <li><span class="chip">Battle 1v1</span> приоритет, скорость, точность/уклонение, STAB, тип-эффективность, crit, RNG seed.</li>
          <li><span class="chip">Replay</span> хранение state + actions + logs (можно воспроизводить по seed).</li>
        </ul>
      </div>
    </div>
  </section>
</div>
