<?php
declare(strict_types=1);

use function App\Support\e;

/** @var string $_csrf */
?>
<div class="grid">
  <div class="col-6">
    <div class="card">
      <h2>Регистрация</h2>
      <form method="post" action="/register">
        <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
        <div class="form-row">
          <label>Логин</label>
          <input class="input" name="username" autocomplete="username" required placeholder="латиница/цифры/_">
        </div>
        <div class="form-row" style="margin-top:10px">
          <label>Email</label>
          <input class="input" name="email" type="email" autocomplete="email" required>
        </div>
        <div class="form-row" style="margin-top:10px">
          <label>Пароль</label>
          <input class="input" name="password" type="password" autocomplete="new-password" required>
        </div>
        <div class="form-actions">
          <button class="btn primary" type="submit">Создать аккаунт</button>
          <a class="btn" href="/login">Уже есть аккаунт</a>
        </div>
      </form>
    </div>
  </div>
  <div class="col-6">
    <div class="card">
      <h2>Правила MVP</h2>
      <ul class="muted">
        <li>Стартовый питомец (ур.5) + 3 зелья</li>
        <li>Локация старта: #1</li>
        <li>Дальше будет боёвка (Milestone 2)</li>
      </ul>
    </div>
  </div>
</div>
