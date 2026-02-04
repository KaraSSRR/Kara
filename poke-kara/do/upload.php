<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function respond($status, $message, $notifyType, $notifyTitle, $notifyText, $extra = []) {
    $out = [
        "status"  => $status,
        "message" => $message,
        "notify"  => [
            "type"  => $notifyType,
            "title" => $notifyTitle,
            "text"  => $notifyText
        ]
    ];
    if (!empty($extra)) {
        // добавляем поля в notify или корень — как передадите
        foreach ($extra as $k => $v) {
            // по вашей схеме "image" лежит внутри notify
            $out["notify"][$k] = $v;
        }
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['id'])) {
    respond(
        "error",
        "Вы не авторизованы.",
        "error",
        "Ошибка",
        "Вы должны войти в аккаунт, чтобы сменить аватар."
    );
}

$userId = (int)$_SESSION['id'];
if ($userId <= 0) {
    respond(
        "error",
        "Некорректный пользователь.",
        "error",
        "Ошибка",
        "Некорректный идентификатор пользователя."
    );
}

$uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/img/avatars/mini/';

// создаём папку (и проверяем права)
if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true)) {
        respond(
            "error",
            "Не удалось создать каталог загрузки.",
            "error",
            "Ошибка",
            "Сервер не смог создать каталог для аватаров. Проверьте права на запись."
        );
    }
}
if (!is_writable($uploadDir)) {
    respond(
        "error",
        "Каталог недоступен для записи.",
        "error",
        "Ошибка",
        "Каталог аватаров недоступен для записи. Проверьте права (chmod) и владельца."
    );
}

// проверка файла
if (
    !isset($_FILES['file']) ||
    !isset($_FILES['file']['tmp_name']) ||
    !is_uploaded_file($_FILES['file']['tmp_name'])
) {
    respond(
        "error",
        "Файл не передан.",
        "error",
        "Ошибка загрузки",
        "Файл не был передан на сервер. Проверьте форму и имя поля (name=\"file\")."
    );
}

if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    // более точные сообщения по коду ошибки
    $err = (int)$_FILES['file']['error'];
    $human = "Произошла ошибка при загрузке файла.";
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        $human = "Файл слишком большой для текущих настроек сервера.";
    } elseif ($err === UPLOAD_ERR_PARTIAL) {
        $human = "Файл был загружен не полностью.";
    } elseif ($err === UPLOAD_ERR_NO_FILE) {
        $human = "Файл не выбран.";
    }
    respond(
        "error",
        "Ошибка при загрузке файла (код: {$err}).",
        "error",
        "Ошибка загрузки",
        $human . " Попробуйте ещё раз."
    );
}

// лимит 3 МБ
$maxBytes = 3 * 1024 * 1024; // 3MB
$size = (int)$_FILES['file']['size'];
if ($size <= 0 || $size > $maxBytes) {
    respond(
        "error",
        "Слишком большой файл.",
        "error",
        "Неверный размер",
        "Размер изображения должен быть не больше 3 МБ."
    );
}

// проверка, что это изображение (getimagesize работает надёжно, в т.ч. с телефонов)
$tmp = $_FILES['file']['tmp_name'];
$imageInfo = @getimagesize($tmp);
if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
    respond(
        "error",
        "Файл не является корректным изображением.",
        "error",
        "Неверный файл",
        "Загруженный файл не является корректным изображением."
    );
}

$w = (int)$imageInfo[0];
$h = (int)$imageInfo[1];

// защита от “бомб” по пикселям (чтобы не убить память при декодировании)
$maxSide   = 8000;          // максимально допустимая сторона
$maxPixels = 40 * 1000 * 1000; // 40MP
if ($w <= 0 || $h <= 0 || $w > $maxSide || $h > $maxSide || ($w * $h) > $maxPixels) {
    respond(
        "error",
        "Слишком большое изображение.",
        "error",
        "Неверные параметры",
        "Изображение слишком большое по разрешению. Уменьшите его и попробуйте снова."
    );
}

// Доп. проверка MIME (по возможности)
$mime = '';
if (function_exists('finfo_open')) {
    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $mime = (string)@finfo_file($finfo, $tmp);
        @finfo_close($finfo);
    }
}
if (!$mime && !empty($imageInfo['mime'])) {
    $mime = (string)$imageInfo['mime'];
}
$mime = strtolower(trim($mime));

// На этом этапе НЕ режем “по списку форматов”, т.к. вы просили “все форматы”.
// Но если это вообще не image/* — сразу отказываем.
if ($mime && strpos($mime, 'image/') !== 0) {
    respond(
        "error",
        "Неверный тип файла.",
        "error",
        "Неверный файл",
        "Разрешены только изображения."
    );
}

// декодируем картинку максимально универсально
$bin = @file_get_contents($tmp);
if ($bin === false) {
    respond(
        "error",
        "Не удалось прочитать файл.",
        "error",
        "Ошибка",
        "Сервер не смог прочитать загруженный файл. Попробуйте ещё раз."
    );
}

$img = @imagecreatefromstring($bin);
if (!$img) {
    respond(
        "error",
        "Формат изображения не поддерживается.",
        "error",
        "Неверный формат",
        "Этот формат изображения не поддерживается на сервере. Попробуйте PNG/JPG/WebP."
    );
}

// ресайз (по большей стороне до 256, пропорции сохраняем)
$maxSize = 256;
$srcW = imagesx($img);
$srcH = imagesy($img);
if ($srcW <= 0 || $srcH <= 0) {
    imagedestroy($img);
    respond(
        "error",
        "Некорректные параметры изображения.",
        "error",
        "Ошибка",
        "Не удалось обработать изображение. Попробуйте другое."
    );
}

$scale = min($maxSize / $srcW, $maxSize / $srcH, 1);
$newW  = max(1, (int)round($srcW * $scale));
$newH  = max(1, (int)round($srcH * $scale));

$resized = imagecreatetruecolor($newW, $newH);
if (!$resized) {
    imagedestroy($img);
    respond(
        "error",
        "Ошибка обработки изображения.",
        "error",
        "Ошибка",
        "Не удалось создать холст для ресайза. Попробуйте другое изображение."
    );
}

// сохраняем прозрачность (для PNG/GIF/WebP)
imagealphablending($resized, false);
imagesavealpha($resized, true);
$transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
imagefilledrectangle($resized, 0, 0, $newW, $newH, $transparent);

if (!@imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH)) {
    imagedestroy($img);
    imagedestroy($resized);
    respond(
        "error",
        "Ошибка ресайза изображения.",
        "error",
        "Ошибка",
        "Не удалось изменить размер изображения. Попробуйте другое."
    );
}

// путь сохранения (всегда PNG)
$fileName = $userId . '.png';
$filePath = $uploadDir . $fileName;

// пишем атомарно: во временный файл -> rename
$tmpOut = $filePath . '.tmp';

// Удаляем старые файлы (на случай “исторических” расширений)
@unlink($uploadDir . $userId . '.png');
@unlink($uploadDir . $userId . '.jpg');
@unlink($uploadDir . $userId . '.jpeg');
@unlink($uploadDir . $userId . '.gif');
@unlink($uploadDir . $userId . '.webp');

$ok = @imagepng($resized, $tmpOut, 6); // 0-9 (чем больше — тем сильнее сжатие)
imagedestroy($img);
imagedestroy($resized);

if (!$ok) {
    @unlink($tmpOut);
    respond(
        "error",
        "Не удалось сохранить файл.",
        "error",
        "Ошибка сохранения",
        "Не удалось сохранить изображение на сервере. Проверьте права на каталог."
    );
}

if (!@rename($tmpOut, $filePath)) {
    @unlink($tmpOut);
    respond(
        "error",
        "Не удалось завершить сохранение файла.",
        "error",
        "Ошибка сохранения",
        "Не удалось переименовать временный файл. Проверьте права на каталог."
    );
}

@chmod($filePath, 0644);

// cache-busting, чтобы сразу увидеть обновление
$cacheBuster = time();

respond(
    "success",
    "Файл успешно загружен.",
    "success",
    "Аватар обновлён!",
    "Ваш новый аватар успешно загружен.<br><span style='color:#888;font-size:13px'>Если изменения не видны — обновите страницу (Ctrl+F5) или очистите кеш.</span>",
    [
        "image" => "/img/avatars/mini/" . $fileName . "?v=" . $cacheBuster
    ]
);
?>
