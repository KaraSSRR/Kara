<?php
require_once __DIR__ . '/_inc/bootstrap.php';

forum_require_login($forumUserId);

// Обновляем профиль из БД
$stmt = $mysqli->prepare('SELECT user_id, signature, created_at, last_seen_at, topics_count, posts_count FROM forum_users WHERE user_id=? LIMIT 1');
$stmt->bind_param('i', $forumUserId);
$stmt->execute();
$rs = $stmt->get_result();
$forumProfile = $rs ? $rs->fetch_assoc() : $forumProfile;
$stmt->close();

$forumTitle = 'Профиль';
require_once __DIR__ . '/_inc/layout_top.php';

$postsCount = (int)($forumProfile['posts_count'] ?? 0);
$topicsCount = (int)($forumProfile['topics_count'] ?? 0);
$rankTitle = forum_get_rank_title($mysqli, $postsCount);
?>

<div class="forum-card">
  <h1>Профиль</h1>

  <div class="profile-grid">
    <div class="profile-left">
      <div class="profile-box">
        <img class="ava big" src="<?=h(forum_avatar_url($forumUserId))?>" alt="ava">
        <div>
          <div class="login big"><?=h($forumUser['login'] ?? '')?></div>
          <div class="muted">Игровой ранг: <?=h(((int)($forumUser['user_group'] ?? 0) === 1) ? 'Администратор' : (string)($forumUser['rang'] ?? ''))?></div>
          <div class="muted">Форумный ранг: <?=h($rankTitle)?></div>
        </div>
      </div>

      <div class="profile-stats">
        <div class="stat"><span>Тем</span><b><?=$topicsCount?></b></div>
        <div class="stat"><span>Сообщений</span><b><?=$postsCount?></b></div>
        <div class="stat"><span>Создан</span><b><?=date('d.m.Y', (int)($forumProfile['created_at'] ?? time()))?></b></div>
      </div>

      <div class="profile-perms">
        <h3>Права</h3>
        <ul>
          <li>Создавать темы: <b><?= $canPostTopics ? 'Да' : 'Нет' ?></b></li>
          <li>Отвечать: <b><?= $canReply ? 'Да' : 'Нет' ?></b></li>
          <li>Реакции: <b><?= $canReact ? 'Да' : 'Нет' ?></b></li>
          <li>Загрузка: <b><?= $canUpload ? 'Да' : 'Нет' ?></b></li>
          <li>Модерация: <b><?= forum_is_moderator($forumUser, $forumPerms) ? 'Да' : 'Нет' ?></b></li>
        </ul>
      </div>
    </div>

    <div class="profile-right">
      <?php if (!empty($_GET['saved'])): ?>
        <div class="forum-alert success">Сохранено.</div>
      <?php endif; ?>

      <div class="forum-card inner">
        <h2>Подпись</h2>
        <form method="post" action="<?=h(forum_url('/profile_save.php'))?>" class="forum-form">
          <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">

          <div class="bb-toolbar" data-target="sig">
            <button type="button" data-action="b"><b>B</b></button>
            <button type="button" data-action="i"><i>I</i></button>
            <button type="button" data-action="u"><u>U</u></button>
            <button type="button" data-action="s"><s>S</s></button>
            <button type="button" data-action="url">Ссылка</button>
            <button type="button" data-action="img">Картинка</button>
            <button type="button" data-action="code">Код</button>
          </div>

          <label>
            <span>Текст подписи</span>
            <textarea id="sig" name="signature" rows="6" maxlength="<?= (int)FORUM_SIGNATURE_MAX ?>"><?=h((string)($forumProfile['signature'] ?? ''))?></textarea>
            <div class="hint">Подпись отображается под каждым сообщением. Допускаются BBCode и внешние ссылки.</div>
          </label>

          <button class="btn primary" type="submit">Сохранить</button>
        </form>
      </div>

      <div class="forum-card inner">
        <h2>Предпросмотр подписи</h2>
        <div class="post-signature"><?= forum_render_bbcode((string)($forumProfile['signature'] ?? '')) ?></div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
