<?php
require_once __DIR__ . '/../_inc/bootstrap.php';
if (!$encyIsAdmin) { http_response_code(403); die('Admin only'); }
$pageTitle = 'Админ';
require_once __DIR__ . '/../_inc/layout_top.php';
?>

<div class="forum-card">
  <h1 style="margin:0 0 6px;">Админка</h1>
  <div class="muted">Управление контентом энциклопедии.</div>
</div>

<div class="grid" style="margin-top:12px;">
  <a class="card" href="<?=enc_h(enc_url('/admin/guide.php'))?>">
    <h3>Путеводитель</h3>
    <div class="muted">Создание и редактирование страниц.</div>
  </a>
  <a class="card" href="<?=enc_h(enc_url('/admin/builds.php'))?>">
    <h3>Сборки</h3>
    <div class="muted">Выбор рекомендованных сборок и приоритет.</div>
  </a>
  <a class="card" href="<?=enc_h(enc_url('/admin/repair_builds.php'))?>">
    <h3>Обслуживание</h3>
    <div class="muted">Пересборка индексов сборок и популярности.</div>
  </a>
</div>

<?php require_once __DIR__ . '/../_inc/layout_bottom.php'; ?>
