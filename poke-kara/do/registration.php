<?php
// /do/registration — максимально совместимо со "старой" рабочей регистрацией.
// Ключевая правка: колонка countPoks (у вас так в рабочем файле), но если в БД вдруг count_poks — тоже поддержим.

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global  = $patch_project . '/inc/conf/global.php';

$outErr = function(string $msg, ?string $field=null, int $code=200) use (&$outErr){
    http_response_code($code);
    $resp = ['error'=>1, 'text'=>$msg, 'message'=>$msg];
    if ($field) $resp['field'] = $field;
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
};
$outOk = function(array $data){
    $data['error'] = 0;
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};

if (!file_exists($patch_global)) $outErr('Проблема с файлом конфигурации.');
require_once($patch_global);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $outErr('Неверный метод запроса. Разрешён только POST.');

if (!isset($mysqli) || !($mysqli instanceof mysqli)) $outErr('Ошибка подключения к базе данных.');

if (!isset($_POST['login'], $_POST['password'], $_POST['mail'])) $outErr('Некорректные данные.');

$login    = trim((string)$_POST['login']);
$mail     = trim((string)$_POST['mail']);
$password = trim((string)$_POST['password']);
$dbl      = (string)($_POST['dbl_password'] ?? '');

$baseModel = isset($_POST['baseModel']) ? (int)$_POST['baseModel'] : 0;
$sex = (($_POST['gender'] ?? 'm') === 'm') ? 'm' : 'f';

// Модель
$allowedModels = ($sex === 'f') ? [1,2,3] : [4,5,6];
if (!$baseModel || !in_array($baseModel, $allowedModels, true)) {
    $outErr('Выберите корректную базовую модель!', 'model');
}

// Логин
if ($login === '') $outErr('Логин пустой!', 'login');
if (strlen($login) < 4 || strlen($login) > 16) $outErr('Длина логина должна быть от 4 до 16 символов.', 'login');

// Email
if ($mail === '') $outErr('Почта пустая!', 'mail');
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) $outErr('Некорректный формат почты.', 'mail');

// Пароль
if (strlen($password) < 6 || strlen($password) > 20) $outErr('Длина пароля должна быть от 6 до 20 символов.', 'password');
if ($dbl !== '' && $password !== $dbl) $outErr('Пароли не совпадают.', 'dbl_password');

// Уникальность
$loginEsc = $mysqli->real_escape_string($login);
$checkLogin = $mysqli->query("SELECT 1 FROM `users` WHERE `login` = '{$loginEsc}' LIMIT 1");
if ($checkLogin && $checkLogin->num_rows > 0) $outErr('Данный логин занят!', 'login');

$mailEsc = $mysqli->real_escape_string($mail);
$checkMail = $mysqli->query("SELECT 1 FROM `users` WHERE `email` = '{$mailEsc}' LIMIT 1");
if ($checkMail && $checkMail->num_rows > 0) $outErr('Данная почта занята!', 'mail');

// Рефералка (совместимость: refCode или referal_you)
$referalYou = '';
$refIn = '';
if (!empty($_POST['referal_you'])) $refIn = trim((string)$_POST['referal_you']);
if ($refIn === '' && !empty($_POST['refCode'])) $refIn = trim((string)$_POST['refCode']);

if ($refIn !== '') {
    $refEsc = $mysqli->real_escape_string($refIn);
    $refUser = $mysqli->query("SELECT `id` FROM `users` WHERE `referal` = '{$refEsc}' LIMIT 1")->fetch_assoc();
    if ($refUser) $referalYou = $refIn;
}

// Данные по умолчанию
$passwordHash = md5($password); // совместимо с /do/sign
$rating    = '{"pve": 0, "pvp": 0, "battleCount": 0}';
$countPoks = '{"shine": 0, "normal": 0}';
$dateReg   = time();
$ip        = $_SERVER['REMOTE_ADDR'] ?? '';

// Определяем имя колонки (countPoks или count_poks)
$countCol = null;
$col = $mysqli->query("SHOW COLUMNS FROM `users` LIKE 'countPoks'")->fetch_assoc();
if ($col) $countCol = 'countPoks';
if (!$countCol) {
    $col2 = $mysqli->query("SHOW COLUMNS FROM `users` LIKE 'count_poks'")->fetch_assoc();
    if ($col2) $countCol = 'count_poks';
}
if (!$countCol) {
    // если ни одной нет — честная ошибка с подсказкой
    $outErr('В таблице users нет колонки countPoks/count_poks. Проверьте схему БД.');
}

// INSERT (без status — как в вашем рабочем варианте)
$sql = "INSERT INTO `users` (`login`, `password`, `email`, `sex`, `rating`, `{$countCol}`, `dateReg`, `ip`, `referal_you`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($sql);
if (!$stmt) $outErr('Ошибка подготовки запроса регистрации: '.$mysqli->error);

$stmt->bind_param(
    "ssssssiss",
    $login, $passwordHash, $mail, $sex, $rating, $countPoks, $dateReg, $ip, $referalYou
);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    $outErr('Ошибка при создании пользователя. '.$err);
}

$userId = (int)$stmt->insert_id;
$stmt->close();

// referal code новому пользователю
$referalCode = 'RFL-' . substr(md5(uniqid((string)$userId, true)), 0, 8);
$stmtReferal = $mysqli->prepare("UPDATE `users` SET `referal` = ? WHERE `id` = ?");
if ($stmtReferal) {
    $stmtReferal->bind_param("si", $referalCode, $userId);
    $stmtReferal->execute();
    $stmtReferal->close();
}

// стартовый cloth
$model = $baseModel;
$skin  = $model;
$color = 'a';

$stmtCloth = $mysqli->prepare("INSERT INTO `cloth` (`user`, `model`, `skin`, `color`) VALUES (?, ?, ?, ?)");
if ($stmtCloth) {
    $stmtCloth->bind_param("iiis", $userId, $model, $skin, $color);
    $stmtCloth->execute();
    $stmtCloth->close();
}

$outOk([
    'title'    => 'Регистрация завершена!',
    'message'  => "Добро пожаловать, <b>" . htmlspecialchars($login, ENT_QUOTES, 'UTF-8') . "</b>! Теперь вы можете войти, используя свой логин и пароль.",
    'redirect' => '/'
]);
