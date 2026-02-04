<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';

if (!file_exists($patch_global)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error" => "Ошибка: Файл конфигурации не найден."]);
        exit();
    } else {
        echo "<h1>Ошибка: Файл конфигурации не найден.</h1>";
        exit();
    }
}
require_once($patch_global);

if (isset($mysqli) && method_exists($mysqli, 'set_charset')) {
    $mysqli->set_charset('utf8mb4');
    @$mysqli->query("SET NAMES 'utf8mb4'");
}
// Проверка доступа по ID (только для пользователя с id=4)
session_start();
if (!isset($_SESSION['id']) || $_SESSION['id'] != 4) {
    http_response_code(403);
    die('<div style="margin:60px auto;max-width:420px;padding:36px 22px 30px 22px;background:#fff6;border-radius:19px;box-shadow:0 6px 32px #7050c022;font-family:Nunito,Arial,sans-serif;text-align:center;">
        <span style="display:block;font-size:3.4em;line-height:1;color:#caa2e6;">⛔</span>
        <div style="font-size:1.25em;color:#a184ca;font-weight:bold;margin:9px 0 13px 0;">Доступ запрещён</div>
        <div style="color:#8160a0;font-size:1em;">У вас нет прав для просмотра этой страницы.</div>
        </div>');
}
// Получить следующий id (>= 1000039)
function getNextItemId($mysqli) {
    $res = $mysqli->query("SELECT MAX(id) AS max_id FROM `base_items`");
    $row = $res ? $res->fetch_assoc() : null;
    $maxId = $row ? intval($row['max_id']) : 0;
    return ($maxId < 1000039) ? 1000039 : $maxId + 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $type = isset($_POST['type']) ? trim($_POST['type']) : '';
        $about = isset($_POST['about']) ? trim($_POST['about']) : '';

        // Проверки
        if (empty($name) || empty($type) || empty($about)) {
            echo json_encode(["error" => "Заполните все поля (название, тип, описание)."]);
            exit();
        }

        // Список допустимых типов (можно расширить)
        $allowed_types = ['card','other','boxes','berry','ball','pokeball','medicine','held','tm','key','trophy'];
        if (!in_array($type, $allowed_types)) {
            echo json_encode(["error" => "Неверный тип предмета."]);
            exit();
        }

        // Новый id
        $item_id = getNextItemId($mysqli);

        // Вставка записи
        $stmt = $mysqli->prepare(
            "INSERT INTO `base_items`
            (`id`, `name`, `type`, `about`, `categories`, `weight`, `give`, `dress`, `drop`, `trade`, `use`, `lombard`, `battle`, `info`, `str`, `news`, `tm_id`, `nameEng`, `descriptionEng`, `expiration`, `drop_it`, `rait_it`, `craft`)
            VALUES (?, ?, ?, ?, 0, 1, 'false', 'false', 'false', 'false', 'false', 'false', 0, 0, 0, 0, 0, '', '', 0, '', 'normal', 0)"
        );
        $stmt->bind_param("isss", $item_id, $name, $type, $about);

        if ($stmt->execute()) {
            $img_uploaded = false;
            $img_error = '';

            // === Обработка картинки ===
            if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadDir = $patch_project . '/img/world/items/little/';
                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    $img_error = "Ошибка: Папка для загрузки недоступна для записи!";
                } else {
                    $allowedExt = ['png', 'jpg', 'jpeg', 'webp'];
                    $fileTmp = $_FILES['item_image']['tmp_name'];
                    $fileName = $_FILES['item_image']['name'];
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if (!in_array($ext, $allowedExt)) {
                        $img_error = "Только изображения png, jpg, jpeg, webp.";
                    } else {
                        // Имя файла: 10.39.png для id=1000039
                        $short = $item_id - 1000000; // 39
                        $fileShort = "10." . $short . ".png";
                        $target = $uploadDir . $fileShort;
                        if ($ext !== 'png') {
                            if ($ext === 'jpg' || $ext === 'jpeg') {
                                $srcImg = @imagecreatefromjpeg($fileTmp);
                            } elseif ($ext === 'webp') {
                                $srcImg = @imagecreatefromwebp($fileTmp);
                            } else {
                                $srcImg = false;
                            }
                            if ($srcImg) {
                                imagepng($srcImg, $target);
                                imagedestroy($srcImg);
                                $img_uploaded = true;
                            } else {
                                $img_error = "Ошибка при обработке изображения.";
                            }
                        } else {
                            if (move_uploaded_file($fileTmp, $target)) {
                                $img_uploaded = true;
                            } else {
                                $img_error = "Ошибка сохранения файла.";
                            }
                        }
                    }
                }
            }

            $resp = ["success" => "Предмет успешно добавлен!", "item_id" => $item_id];
            if ($img_uploaded) {
                $resp["image"] = "/img/world/items/little/10." . ($item_id - 1000000) . ".png";
            }
            if ($img_error) $resp["warning"] = $img_error;
            echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["error" => "Ошибка при добавлении предмета: " . $stmt->error]);
        }
        $stmt->close();
    } catch (Throwable $ex) {
        echo json_encode(["error" => "Внутренняя ошибка: ".$ex->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Создание предметов</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no">
    <script src="/js/jquery/jquery.js" type="text/javascript"></script>
    <style>
        body {display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:linear-gradient(135deg,#e9e6ff 0%,#eceaff 100%);}
        .Content {width:370px;background:rgba(255,255,255,0.95);border-radius:18px;padding:30px 30px 24px 30px;box-shadow:0 6px 32px 0 rgba(120,69,183,0.14);}
        .block {margin-bottom:18px;text-align:left;}
        .block label {color:#8859c4;font-size:15px;font-weight:700;}
        .block input,.block select,.block textarea {width:100%;height:38px;border:1.5px solid #d2beff;border-radius:7px;padding:7px 11px;margin-top:7px;font-size:16px;background:#f4f2fc;color:#5d387d;}
        .block textarea {height:72px;}
        .block input[type="file"] {height:auto;padding:5px 11px;}
        .button {width:100%;padding:13px 0;background:linear-gradient(90deg,#a184e6 0%,#8859c4 100%);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:17px;font-weight:700;margin-top:10px;}
        .img-preview {margin:20px auto 2px auto;display:none;flex-direction:column;align-items:center;min-height:96px;}
        .img-preview img {max-width:80px;max-height:80px;border-radius:10px;background:#ececff;border:1.5px solid #d2beff;box-shadow:0 1px 8px #bca7df1a;margin-bottom:5px;}
        .img-preview .img-id-label {color:#a184e6;font-size:14px;margin-bottom:4px;}
        .img-preview .img-id-path {color:#8859c4;font-size:13px;font-style:italic;}
        .img-preview .img-warning {color:#c00;font-size:13px;margin-top:3px;}
    </style>
</head>
<body>
    <div class="Content">
        <h2>Создание предмета</h2>
        <div class="img-preview" id="imgPreview">
            <span class="img-id-label">Превью изображения предмета</span>
            <img id="itemImg" src="" alt="Изображение предмета">
            <span class="img-id-path" id="imgPath"></span>
            <span class="img-warning" id="imgWarning"></span>
        </div>
        <form id="itemForm" enctype="multipart/form-data" accept-charset="UTF-8" autocomplete="off">
            <div class="block">
                <label for="item_name">Название</label>
                <input type="text" id="item_name" name="name" placeholder="Введите название предмета" required>
            </div>
            <div class="block">
                <label for="item_type">Тип предмета</label>
                <select id="item_type" name="type">
                    <option value="medicine">Лекарство</option>
                    <option value="pokeball">Покебол</option>
                    <option value="held">Предмет для держания</option>
                    <option value="tm">ТМ</option>
                    <option value="key">Ключевой предмет</option>
                    <option value="trophy">Трофей</option>
                    <option value="other">Другое</option>
                    <option value="card">Карта</option>
                    <option value="boxes">Бокс</option>
                    <option value="berry">Ягода</option>
                    <option value="ball">Бол</option>
                </select>
            </div>
            <div class="block">
                <label for="item_about">Описание</label>
                <textarea id="item_about" name="about" placeholder="Введите описание предмета" required></textarea>
            </div>
            <div class="block">
                <label for="item_image">Изображение (png, jpg, webp) <span style="color:#aaa;font-weight:400">(опционально)</span></label>
                <input type="file" id="item_image" name="item_image" accept=".png,.jpg,.jpeg,.webp" onchange="showLocalPreview()">
            </div>
            <button type="submit" class="button">Добавить предмет</button>
        </form>
    </div>
    <script>
        function showLocalPreview() {
            let input = document.getElementById('item_image');
            let preview = document.getElementById('imgPreview');
            let img = document.getElementById('itemImg');
            let imgPathSpan = document.getElementById('imgPath');
            let imgWarning = document.getElementById('imgWarning');
            imgWarning.textContent = '';
            if (input.files && input.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    imgPathSpan.textContent = input.files[0].name;
                    preview.style.display = 'flex';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.getElementById('itemForm').onsubmit = function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    let result;
                    try {
                        result = typeof response === "string" ? JSON.parse(response) : response;
                    } catch(e) {
                        alert('Ошибка: Не удалось обработать ответ сервера.');
                        return;
                    }
                    if (result.success) {
                        alert(result.success + (result.warning ? '\n' + result.warning : ''));
                    } else if (result.error) {
                        alert(result.error);
                    }
                    // Показываем загруженное изображение с сервера
                    if (result.item_id) {
                        let fileShort = "10." + (result.item_id - 1000000) + ".png";
                        let serverImg = (result.image || ('/img/world/items/little/' + fileShort)) + '?_=' + Date.now();
                        let img = document.getElementById('itemImg');
                        let imgPathSpan = document.getElementById('imgPath');
                        let imgWarning = document.getElementById('imgWarning');
                        let preview = document.getElementById('imgPreview');
                        img.src = serverImg;
                        imgPathSpan.textContent = serverImg;
                        imgWarning.textContent = result.warning ? result.warning : '';
                        preview.style.display = 'flex';
                        // Очищаем поля кроме картинки
                        document.getElementById('item_name').value = '';
                        document.getElementById('item_about').value = '';
                        document.getElementById('item_image').value = '';
                    }
                },
                error: function(xhr, status, error) {
                    alert("Сетевая ошибка: " + error);
                }
            });
        }
    </script>
</body>
</html>