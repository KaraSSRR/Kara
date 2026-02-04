<?php
header('Content-Type: application/json');

// Гарантируем корректную работу сессии (на одном домене, с одним именем)
session_name('PHPSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'poke-kara.ru', // только основной домен!
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Пусть к проекту
if (!isset($patch_project) || empty($patch_project)) {
    $patch_project = $_SERVER['DOCUMENT_ROOT'];
}
require_once $patch_project . '/inc/conf/global.php';

// Универсальный user_id (user_id или id)
$userId = 0;
if (!empty($_SESSION['user_id'])) {
    $userId = intval($_SESSION['user_id']);
} elseif (!empty($_SESSION['id'])) {
    $userId = intval($_SESSION['id']);
}
if (!$userId) {
    echo json_encode(['error' => ['text' => 'Вы не авторизованы!']]);
    exit;
}

// POST only (можно поддерживать как JSON, так и обычную форму)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => ['text' => 'Метод не разрешён']]);
    exit;
}

// Получаем сумму (работает и с application/x-www-form-urlencoded, и с json)
$amount = 0;
if (isset($_POST['amount'])) {
    $amount = intval($_POST['amount']);
} else {
    $raw = file_get_contents('php://input');
    $data = @json_decode($raw, true);
    if (is_array($data) && isset($data['amount'])) {
        $amount = intval($data['amount']);
    }
}
if ($amount < 10 || $amount > 100000 || $amount % 10 !== 0) {
    echo json_encode(['error' => ['text' => 'Сумма должна быть целым числом от 10 до 100000 и кратна 10']]);
    exit;
}

// Антифлуд
if (!empty($_SESSION['last_recharge_request']) && time() - $_SESSION['last_recharge_request'] < 5) {
    echo json_encode(['error' => ['text' => 'Слишком часто! Подождите несколько секунд.']]);
    exit;
}
$_SESSION['last_recharge_request'] = time();

$merchant_id = 63544;
$secret1 = '0@b5mztKzzb5g)c';
$currency = 'RUB';

// order_id только число!
$order_id = $userId . time(); // Например: 41751880187

// Подпись по новой формуле: md5("ID:Сумма:Секретное слово:Валюта:Номер заказа")
$sign = md5("$merchant_id:$amount:$secret1:$currency:$order_id");

// Новый актуальный домен: https://pay.fk.money/
$url = "https://pay.fk.money/?m=$merchant_id&oa=$amount&o=$order_id&s=$sign&currency=$currency&lang=ru&us_id=$userId&pay=" . urlencode('Оплатить');

// Логирование (в лог также добавлен sign для отладки)
file_put_contents(
    __DIR__ . '/recharge.log',
    date('Y-m-d H:i:s') . "|user:$userId|amount:$amount|order:$order_id|sign:$sign|ip:{$_SERVER['REMOTE_ADDR']}\n",
    FILE_APPEND
);

// Ответ
echo json_encode(['ok' => true, 'url' => $url]);