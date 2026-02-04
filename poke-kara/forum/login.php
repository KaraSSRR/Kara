<?php
require_once __DIR__ . '/_inc/bootstrap.php';

$next = (string)($_GET['next'] ?? forum_url('/'));
if ($forumUserId > 0) {
    redirect($next);
}

$forumTitle = 'Вход';
require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card">
  <h1>Вход</h1>
  <div class="muted">Используется тот же аккаунт, что и в игре. После авторизации сможете создавать темы, отвечать и ставить реакции — в зависимости от прав.</div>

  <div id="loginMsg" class="forum-alert" style="display:none;"></div>

  <form id="loginForm" class="forum-form" onsubmit="return false;">
    <label>
      <span>Логин</span>
      <input type="text" name="login" autocomplete="username" required>
    </label>
    <label>
      <span>Пароль</span>
      <input type="password" name="password" autocomplete="current-password" required>
    </label>
    <button class="btn primary" type="submit">Войти</button>
    <a class="btn" href="/registration.php">Регистрация</a>
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
        location.href = next || <?=json_encode(forum_url('/'))?>;
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
