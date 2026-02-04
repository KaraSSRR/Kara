<?php
require_once __DIR__ . '/../_inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!$forumInstalled) {
    echo json_encode(['ok'=>0,'error'=>'Forum is not installed'], JSON_UNESCAPED_UNICODE);
    exit;
}

forum_require_login($forumUserId);
csrf_check();

if (!$canUpload) {
    echo json_encode(['ok'=>0,'error'=>'Нет прав на загрузку'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
    echo json_encode(['ok'=>0,'error'=>'Файл не получен'], JSON_UNESCAPED_UNICODE);
    exit;
}

$f = $_FILES['file'];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['ok'=>0,'error'=>'Ошибка загрузки: '.$f['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

$size = (int)($f['size'] ?? 0);
if ($size <= 0 || $size > FORUM_UPLOAD_MAX_BYTES) {
    echo json_encode(['ok'=>0,'error'=>'Размер файла превышает лимит'], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = (string)($f['name'] ?? '');
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$allowed = array_filter(array_map('trim', explode(',', (string)FORUM_ALLOWED_IMAGE_EXT)));
if ($ext === '' || !in_array($ext, $allowed, true)) {
    echo json_encode(['ok'=>0,'error'=>'Разрешены только изображения: '.implode(', ', $allowed)], JSON_UNESCAPED_UNICODE);
    exit;
}

// MIME-check (мягкий)
$mime = '';
if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
        $mime = (string)finfo_file($fi, $f['tmp_name']);
        finfo_close($fi);
    }
}
if ($mime !== '' && !preg_match('~^image/(jpeg|png|gif|webp)~i', $mime)) {
    echo json_encode(['ok'=>0,'error'=>'Файл не похож на изображение'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Дополнительная проверка на валидность картинки
if (function_exists('getimagesize')) {
    $gi = @getimagesize($f['tmp_name']);
    if (!$gi || empty($gi[0]) || empty($gi[1])) {
        echo json_encode(['ok'=>0,'error'=>'Файл не является корректным изображением'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$sub = date('Ymd');
$dir = rtrim(FORUM_UPLOAD_DIR, '/') . '/' . $sub;
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}

$rand = bin2hex(random_bytes(16));
$filename = $rand . '.' . $ext;
$path = $dir . '/' . $filename;

if (!move_uploaded_file($f['tmp_name'], $path)) {
    echo json_encode(['ok'=>0,'error'=>'Не удалось сохранить файл'], JSON_UNESCAPED_UNICODE);
    exit;
}

@chmod($path, 0644);

$url = rtrim(FORUM_UPLOAD_URL_PREFIX, '/') . '/' . $sub . '/' . $filename;

$relPath = 'uploads/' . $sub . '/' . $filename;

// Логируем
$stmt = $mysqli->prepare('INSERT INTO forum_uploads (user_id, post_id, topic_id, path, url, mime, size, created_at) VALUES (?, NULL, NULL, ?, ?, ?, ?, UNIX_TIMESTAMP())');
$stmt->bind_param('isssi', $forumUserId, $relPath, $url, $mime, $size);
$stmt->execute();
$stmt->close();

echo json_encode(['ok'=>1,'url'=>$url], JSON_UNESCAPED_UNICODE);
