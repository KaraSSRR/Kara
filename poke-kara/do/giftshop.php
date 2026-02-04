<?php
// --- Подключение конфигов и функций ---
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_func = $patch_project.'/inc/function/Functions.php';

if (!empty($patch_global)) {
    if (!file_exists($patch_global) || !file_exists($patch_func)) {
        die('The problem with the connection files.');
    } else {
        require_once($patch_global);
        require_once($patch_func);
    }
}

// --- Основная логика ---
header('Content-Type: application/json; charset=utf-8');

$response = [];
$type = isset($_POST['type']) ? trim($_POST['type']) : '';

/*
 * Важно:
 * 1) Скрипт ожидает POST запросы.
 * 2) Скрипт использует $_SESSION['id'] как ID текущего пользователя.
 * 3) Для сохранения стиля кода и обратной совместимости оставлена switch-логика и структура ответов.
 */

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 1;
    $response['text'] = 'Неверный метод запроса!';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Защитная проверка подключения к БД
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    $response['error'] = 1;
    $response['text'] = 'Ошибка подключения к базе данных!';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($type) {

    case 'giftshop_list':
        // Список подарков магазина
        $items = [];
        $result = $mysqli->query("SELECT * FROM giftshop_items");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            $response['error'] = 0;
            $response['items'] = $items;
        } else {
            $response['error'] = 1;
            $response['text'] = 'Ошибка загрузки списка подарков!';
        }
        break;

    case 'send_gift':
        // Отправка подарка другому игроку
        $from_user = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
        $to_user   = isset($_POST['to_user']) ? intval($_POST['to_user']) : 0;
        $gift_id   = isset($_POST['gift_id']) ? intval($_POST['gift_id']) : 0;

        $message = '';
        if (isset($_POST['message'])) {
            $message = escapeMe($_POST['message']);
            // Ограничиваем длину сообщения, чтобы не допускать чрезмерных вставок в БД/интерфейс
            if (function_exists('mb_substr')) {
                $message = mb_substr($message, 0, 250, 'UTF-8');
            } else {
                $message = substr($message, 0, 250);
            }
        }

        // Проверка валидности пользователя и подарка
        if (!$from_user || !$to_user || !$gift_id) {
            $response['error'] = 1;
            $response['text'] = 'Ошибка данных!';
            break;
        }
        if ($from_user == $to_user) {
            $response['error'] = 1;
            $response['text'] = 'Нельзя отправить подарок самому себе!';
            break;
        }

        // Проверка существования получателя
        $stmtUser = $mysqli->prepare("SELECT id FROM users WHERE id=? LIMIT 1");
        if ($stmtUser) {
            $stmtUser->bind_param('i', $to_user);
            $stmtUser->execute();
            $resUser = $stmtUser->get_result();
            $userRow = $resUser ? $resUser->fetch_assoc() : null;
            $stmtUser->close();

            if (!$userRow) {
                $response['error'] = 1;
                $response['text'] = 'Пользователь не найден!';
                break;
            }
        } else {
            $response['error'] = 1;
            $response['text'] = 'Ошибка проверки пользователя!';
            break;
        }

        // Проверка баланса и существования подарка
        $gift = null;
        $stmtGift = $mysqli->prepare("SELECT * FROM giftshop_items WHERE id=? LIMIT 1");
        if ($stmtGift) {
            $stmtGift->bind_param('i', $gift_id);
            $stmtGift->execute();
            $resGift = $stmtGift->get_result();
            $gift = $resGift ? $resGift->fetch_assoc() : null;
            $stmtGift->close();
        }

        if (!$gift) {
            $response['error'] = 1;
            $response['text'] = 'Подарок не найден!';
            break;
        }

        // Валидация цены (на случай некорректных данных в таблице)
        $gift_price = isset($gift['price']) ? intval($gift['price']) : 0;
        if ($gift_price <= 0) {
            $response['error'] = 1;
            $response['text'] = 'Некорректная цена подарка!';
            break;
        }

        if (!item_isset(25, $gift_price, $from_user)) {
            $response['error'] = 1;
            $response['text'] = 'Недостаточно драгоценных камней!';
            break;
        }

        // Списываем валюту
        // Вариант улучшения: оборачивать списание и создание подарка в транзакцию, если minus_item использует тот же $mysqli.
        minus_item(25, $gift_price, $from_user);

        // Записываем подарок
        $stmt = $mysqli->prepare("INSERT INTO gifts (from_user, to_user, gift_id, message) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('iiis', $from_user, $to_user, $gift_id, $message);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $response['error'] = 0;
                $response['text'] = 'Подарок отправлен!';
            } else {
                $response['error'] = 1;
                $response['text'] = 'Ошибка отправки подарка!';
            }
            $stmt->close();
        } else {
            $response['error'] = 1;
            $response['text'] = 'Ошибка подготовки запроса!';
        }
        break;

    case 'list_gifts':
        // Список входящих подарков
        $user_id = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
        $gifts = [];

        if (!$user_id) {
            $response['error'] = 1;
            $response['text'] = 'Ошибка авторизации!';
            break;
        }

        $sql = "SELECT g.*, gi.name as gift_name, gi.description, gi.img, u.login as from_login
                FROM gifts g
                LEFT JOIN giftshop_items gi ON gi.id=g.gift_id
                LEFT JOIN users u ON u.id=g.from_user
                WHERE g.to_user=? AND g.status='pending' 
                ORDER BY g.created_at DESC";

        $stmtList = $mysqli->prepare($sql);
        if ($stmtList) {
            $stmtList->bind_param('i', $user_id);
            $stmtList->execute();
            $result = $stmtList->get_result();

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $gifts[] = $row;
                }
                $response['error'] = 0;
                $response['gifts'] = $gifts;
            } else {
                $response['error'] = 1;
                $response['text'] = 'Ошибка загрузки подарков!';
            }
            $stmtList->close();
        } else {
            $response['error'] = 1;
            $response['text'] = 'Ошибка подготовки запроса!';
        }
        break;

    case 'receive_gift':
        // Получение подарка (забрать себе)
        $user_id = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
        $gift_id = isset($_POST['gift_id']) ? intval($_POST['gift_id']) : 0;

        if (!$user_id || !$gift_id) {
            $response['error'] = 1;
            $response['text'] = 'Ошибка данных!';
            break;
        }

        // Проверка, что подарок принадлежит пользователю и еще не получен
        $gift = null;
        $stmtCheck = $mysqli->prepare("SELECT * FROM gifts WHERE id=? AND to_user=? AND status='pending' LIMIT 1");
        if ($stmtCheck) {
            $stmtCheck->bind_param('ii', $gift_id, $user_id);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            $gift = $resCheck ? $resCheck->fetch_assoc() : null;
            $stmtCheck->close();
        }

        if ($gift) {
            $stmtUpd = $mysqli->prepare("UPDATE gifts SET status='received' WHERE id=? AND to_user=? AND status='pending'");
            if ($stmtUpd) {
                $stmtUpd->bind_param('ii', $gift_id, $user_id);
                $stmtUpd->execute();

                if ($stmtUpd->affected_rows > 0) {
                    // Здесь можно добавить логику награды или активации подарка, если нужно
                    // Пример: gift_apply($gift['gift_id'], $user_id, $gift);
                    $response['error'] = 0;
                    $response['text'] = 'Подарок получен!';
                } else {
                    $response['error'] = 1;
                    $response['text'] = 'Подарок не найден!';
                }
                $stmtUpd->close();
            } else {
                $response['error'] = 1;
                $response['text'] = 'Ошибка подготовки запроса!';
            }
        } else {
            $response['error'] = 1;
            $response['text'] = 'Подарок не найден!';
        }
        break;

    default:
        $response['error'] = 1;
        $response['text'] = 'Неверный тип запроса!';
        break;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>