<?php
declare(strict_types=1);
?>
<div class="grid">
  <section class="card col-12">
    <div class="hero">
      <div>
        <h1>Browser RPG v2</h1>
        <p class="muted">Одна вкладка · SPA/PJAX · PHP+MySQL. Сейчас активен Milestone 2: Showdown-like карточка существа + бой 1v1 (MVP) с replay.</p>
        <div class="row" style="gap:8px; flex-wrap:wrap; margin-top:10px">
          <span class="chip ok">CSRF + secure sessions</span>
          <span class="chip">IV/EV · nature · happiness</span>
          <span class="chip">Moveset + PP</span>
          <span class="chip">Seeded RNG replay</span>
        </div>
      </div>
      <div class="hero-actions">
        <a class="btn primary" href="/login">Войти</a>
        <a class="btn" href="/register">Создать аккаунт</a>
      </div>
    </div>

    <div class="grid" style="margin-top:12px">
      <div class="card soft col-6">
        <h2>Новости</h2>
        <ul class="list">
          <li><span class="chip">2026-02-02</span> Milestone 2: добавлена карточка существа (IV/EV/nature/статы/ходы) и бой 1v1 (MVP).</li>
          <li><span class="chip">2026-02-01</span> Исправлен роутинг корня <code class="mono">/</code> (Router normalize root).</li>
        </ul>
        <p class="muted">Дальше: рейтинги, PvP, статус-эффекты, предметы, способности.</p>
      </div>

      <div class="card soft col-6">
        <h2>Рейтинги</h2>
        <p class="muted">Сделаем позже. Здесь будет топ игроков/кланов/боёв.</p>
        <div class="kpi-grid" style="margin-top:10px">
          <div class="kpi">
            <div class="kpi-label">Онлайн</div>
            <div class="kpi-value">—</div>
          </div>
          <div class="kpi">
            <div class="kpi-label">Боёв сегодня</div>
            <div class="kpi-value">—</div>
          </div>
          <div class="kpi">
            <div class="kpi-label">Топ-игрок</div>
            <div class="kpi-value">—</div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
