<?php
require_once __DIR__ . '/_inc/bootstrap.php';

$id = enc_int((isset($_GET['id']) ? $_GET['id'] : 0), 0);
if ($id<=0) { http_response_code(404); die('Предмет не найден'); }

$stmt = $mysqli->prepare("SELECT * FROM base_items WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = enc_stmt_fetch_assoc($stmt);
$stmt->close();
if (!$item) { http_response_code(404); die('Предмет не найден'); }

$pageTitle = $item['name'] . ' #' . $id;
require_once __DIR__ . '/_inc/layout_top.php';
?>

<div class="forum-card enc-hero">
  <div style="display:flex; gap:10px; align-items:center;">
    <?=enc_item_little_img($id, $item['name'], 'item-mini big')?>
    <div>
      <h1 class="enc-hero__title" style="margin:0 0 6px;" title="ID предмета: <?=enc_h($id)?>"><?=enc_h($item['name'])?></h1>
  <div class="enc-chip-row"><span class="enc-chip enc-chip--muted">Тип: <b><?=enc_h(enc_item_type_ru((string)((isset($item['type']) ? $item['type'] : ''))))?></b></span><?php $w = (int)((isset($item['weight']) ? $item['weight'] : 0)); if ($w>0): ?><span class="enc-chip enc-chip--muted">Вес: <b><?=enc_h($w)?></b></span><?php endif; ?></div>
    </div>
  </div>
</div>

<div class="forum-card" style="margin-top:12px;">
  <h2 style="margin:0 0 10px;">Описание</h2>
  <div><?=enc_render_richtext((string)((isset($item['about']) ? $item['about'] : '')))?></div>
</div>

<div class="forum-card" style="margin-top:12px;">
  <h2 style="margin:0 0 10px;">Параметры</h2>
  <table class="table">
    <tbody>
      <tr><th>Категория</th><td><?=enc_h((int)((isset($item['categories']) ? $item['categories'] : 0)))?></td></tr>
      <tr><th>Можно дарить</th><td><?=enc_h(enc_bool_ru((string)((isset($item['give']) ? $item['give'] : ''))))?></td></tr>
      <tr><th>Можно обменять</th><td><?=enc_h(enc_bool_ru((string)((isset($item['trade']) ? $item['trade'] : ''))))?></td></tr>
      <tr><th>Используемый</th><td><?=enc_h(enc_bool_ru((string)((isset($item['use']) ? $item['use'] : ''))))?></td></tr>
      <tr><th>Бой</th><td><?=enc_h(enc_bool_ru((int)((isset($item['battle']) ? $item['battle'] : 0))))?></td></tr>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/_inc/layout_bottom.php'; ?>
