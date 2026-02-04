<?php
require_once __DIR__ . '/_inc/bootstrap.php';

$next = (string)((isset($_GET['next']) ? $_GET['next'] : enc_url('/')));
if ($encyUserId > 0) {
    enc_redirect($next);
}

$pageTitle = 'Вход';
require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <h1>Вход</h1>
  <div class="muted">Используется тот же аккаунт, что и в игре. После входа вы сможете создавать сборки и управлять ими.</div>

  <div id="loginMsg" class="forum-alert danger" style="display:none; margin-top:12px;"></div>

  <form id="loginForm" class="form-row" onsubmit="return false;" style="margin-top:12px;">
    <div class="field">
      <label>Логин</label>
      <input name="login" autocomplete="username" required>
    </div>
    <div class="field">
      <label>Пароль</label>
      <input name="password" type="password" autocomplete="current-password" required>
    </div>
    <div class="field" style="flex:0 0 160px;">
      <label>&nbsp;</label>
      <button class="btn primary" type="submit" style="width:100%;">Войти</button>
    </div>
    <div class="field" style="flex:0 0 160px;">
      <label>&nbsp;</label>
      <a class="btn" href="/registration.php" style="width:100%; display:inline-flex; justify-content:center;">Регистрация</a>
    </div>
  </form>
</div>

<script>
(function(){
  const form = document.getElementById('loginForm');
  const msg = document.getElementById('loginMsg');
  const next = <?=json_encode($next)?>;

  function show(text, ok){
    msg.style.display = 'block';
    msg.className = 'forum-alert ' + (ok ? 'success' : 'danger');
    msg.textContent = text;
  }

  form.addEventListener('submit', async () => {
    const fd = new FormData(form);
    try {
      const res = await fetch('/do/sign', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
      const data = await res.json();
      if (data && data.error === 0) {
        location.href = next || <?=json_encode(enc_url('/'))?>;
      } else {
        show((data && data.text) ? (Array.isArray(data.text) ? data.text.join(' ') : String(data.text)) : 'Ошибка входа', false);
      }
    } catch(e){
      show('Ошибка сети', false);
    }
  });
})();
</script>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
