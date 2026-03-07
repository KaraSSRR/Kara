<?php
declare(strict_types=1);

use function App\Support\e;

/** @var string $_csrf */
?>
<div class="grid">
  <div class="col-6">
    <div class="card">
      <h2>Вход</h2>
      <form method="post" action="/login">
        <input type="hidden" name="_csrf" value="<?= e($_csrf) ?>">
        <div class="form-row">
          <label>Логин или Email</label>
          <input class="input" name="login" autocomplete="username" required>
        </div>
        <div class="form-row" style="margin-top:10px">
          <label>Пароль</label>
          <input class="input" name="password" type="password" autocomplete="current-password" required>
        </div>
        <div class="form-actions">
          <button class="btn primary" type="submit">Войти</button>
          <a class="btn" href="/register">Регистрация</a>
        </div>
      </form>
    </div>
  </div>
  <div class="col-6">
    <div class="card">
      <h2>API (JSON)</h2>
      <p class="muted">POST /api/login: login, password → {ok,data,user_id}</p>
      <pre class="muted" style="white-space:pre-wrap;margin:0">curl -s -X POST http://localhost:8000/api/login \
  -d 'login=demo&password=demo12345'</pre>
    </div>
  </div>
</div>
