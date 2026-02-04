<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';
$patch_func = $patch_project . '/inc/function/Functions.php';
require_once($patch_global);
require_once($patch_func);
require_once($patch_project . '/do/bundles.php'); // <-- подключаем наборы

$item  = isset($_POST["item"]) ? (int)$_POST["item"] : 0;
$count = isset($_POST["count"]) ? (int)$_POST["count"] : 0;
if ($count < 1) $count = 1;
if ($count > 99) $count = 99;
$response = [];

$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
if ($action === 'limit_info' && $item) {
    $is = $mysqli->query("SELECT `user_limit`,`limit_period` FROM `aquarits` WHERE `item` = " . $item . " LIMIT 1")->fetch_assoc();

    $user_limit = isset($is['user_limit']) ? (int)$is['user_limit'] : 0;
    $limit_period = isset($is['limit_period']) ? (string)$is['limit_period'] : '';

    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['id']) ? intval($_SESSION['id']) : 0);

    $already_bought = 0;
    if ($user_limit > 0 && $user_id && $limit_period !== '') {
        $period_sql = '';
        if ($limit_period == 'once') {
            $period_sql = "";
        } elseif ($limit_period == 'day') {
            $period_sql = "AND DATE(date) = CURDATE()";
        } elseif ($limit_period == 'week') {
            $period_sql = "AND YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($limit_period == 'month') {
            $period_sql = "AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        } elseif ($limit_period == 'year') {
            $period_sql = "AND YEAR(date) = YEAR(CURDATE())";
        }

        $sql = "SELECT IFNULL(SUM(count),0) FROM shop_log WHERE user_id = $user_id AND item_id = $item $period_sql";
        $already_bought = (int)$mysqli->query($sql)->fetch_row()[0];
    }

    $remaining = ($user_limit > 0) ? max(0, $user_limit - $already_bought) : null;

    echo json_encode([
        'error' => 'success',
        'item_id' => (int)$item,
        'user_limit' => (int)$user_limit,
        'limit_period' => (string)$limit_period,
        'already_bought' => (int)$already_bought,
        'remaining' => $remaining,
    ]);
    exit;
}


if ($item) {
    $ib = $mysqli->query("SELECT * FROM `base_items` WHERE `id` = " . $item)->fetch_assoc();
    $is = $mysqli->query("SELECT * FROM `aquarits` WHERE `item` = " . $item)->fetch_assoc();
    
    // --- ДОБАВЛЯЕМ ЛОГИКУ АКЦИОННОЙ ЦЕНЫ ---
    $price = isset($is['price']) ? intval($is['price']) : 0;
    $sale_price = (isset($is['sale_price']) && $is['sale_price'] !== null) ? intval($is['sale_price']) : null;
    $real_price = ($sale_price && $sale_price > 0 && $sale_price < $price) ? $sale_price : $price;
    $sell = $count * $real_price;
    // --- КОНЕЦ ЛОГИКИ АКЦИОННОЙ ЦЕНЫ ---

    // --- ДОБАВЛЯЕМ ЛОГИКУ ОГРАНИЧЕНИЙ ДЛЯ КОНКРЕТНОГО ИГРОКА ---
    // Получаем лимиты из aquarits
    $user_limit = isset($is['user_limit']) ? (int)$is['user_limit'] : 0;
    $limit_period = isset($is['limit_period']) ? $is['limit_period'] : null;
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['id']) ? intval($_SESSION['id']) : 0);

    // Проверяем лимит если задан
    if ($user_limit > 0 && $user_id && $limit_period) {
        // Определяем период
        $period_sql = '';
        if ($limit_period == 'once') {
            // Покупка только 1 раз за все время
            $period_sql = "";
        } elseif ($limit_period == 'day') {
            $period_sql = "AND DATE(date) = CURDATE()";
        } elseif ($limit_period == 'week') {
            $period_sql = "AND YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($limit_period == 'month') {
            $period_sql = "AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        } elseif ($limit_period == 'year') {
            $period_sql = "AND YEAR(date) = YEAR(CURDATE())";
        }

        // Считаем сколько уже купил
        // Ожидается таблица shop_log с полями: user_id, item_id, count, date
        $sql = "SELECT IFNULL(SUM(count),0) FROM shop_log WHERE user_id = $user_id AND item_id = $item $period_sql";
        $already_bought = (int)$mysqli->query($sql)->fetch_row()[0];

        if ($already_bought + $count > $user_limit) {
            $response['error'] = 'error';
            // Сообщение в зависимости от периода
            if ($limit_period == 'once') {
                $response['text'] = 'Вы можете купить этот предмет только один раз!';
            } elseif ($limit_period == 'day') {
                $response['text'] = 'Можно купить не более ' . $user_limit . ' шт. этого предмета в сутки!';
            } elseif ($limit_period == 'week') {
                $response['text'] = 'Можно купить не более ' . $user_limit . ' шт. этого предмета в неделю!';
            } elseif ($limit_period == 'month') {
                $response['text'] = 'Можно купить не более ' . $user_limit . ' шт. этого предмета в месяц!';
            } elseif ($limit_period == 'year') {
                $response['text'] = 'Можно купить не более ' . $user_limit . ' шт. этого предмета в год!';
            } else {
                $response['text'] = 'Превышено ограничение на покупку этого предмета!';
            }
            echo json_encode($response);
            exit;
        }
    }
    // --- КОНЕЦ ЛОГИКИ ОГРАНИЧЕНИЙ ---

    if ($is) {
        $is_count = isset($is['count']) ? (int)$is['count'] : 0;
        $is_unlimited = ($is_count === 999);

        if ($is_count > 0) {
            if (item_isset(25, $sell)) {
                if ($is['count'] >= $count) {

                    $c = $is_unlimited ? 999 : ($is_count - $count);

                    // Если это набор (есть в $bundles)
                    if (isset($bundles[$item])) {
                        for ($i = 0; $i < $count; $i++) { // если покупают несколько наборов
                            foreach ($bundles[$item] as $bundleItem) {
                                itemAdd($bundleItem['item_id'], $bundleItem['count']);
                            }
                        }
                        $response['plus'] = 'Получен набор:<br>';
                        foreach ($bundles[$item] as $bundleItem) {
                            $baseItem = $mysqli->query("SELECT name FROM base_items WHERE id = " . $bundleItem['item_id'])->fetch_assoc();
                            $response['plus'] .= '<img src="/img/world/items/little/'. $bundleItem['item_id'] .'.png" class="item"> ' . $baseItem['name'] . ' <b>x' . ($bundleItem['count'] * $count) . '</b><br>';
                        }
                    } else {
                        // Обычная покупка одного предмета
                        itemAdd($item, $count);
                        $response['plus'] = '<img src="/img/world/items/little/' . $item . '.png" class="item"> ' . $ib['name'] . ' <b>x' . $count . '</b><br>';
                    }

                    minus_item(25, $sell);
                    if ($is['count'] != 999) {
                        $mysqli->query("UPDATE `aquarits` SET `count` = '$c' WHERE `item` = '$item'");
                    }

                    // --- Логируем покупку для лимитов ---
                    if ($user_limit > 0 && $user_id && $limit_period) {
                        $mysqli->query("INSERT INTO shop_log (user_id, item_id, count, date) VALUES ($user_id, $item, $count, NOW())");
                    }
                    // --- конец логирования ---

                    $response['error'] = 'success';
                    $response['text'] = 'Успешная покупка!';
                    $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x' . $sell . '</b><br>';

                    $bal = item_isset(25, 1);
                    $response['balance'] = $bal ? (int)$bal : 0;
                    $response['item_id'] = (int)$item;
                    $response['stock_left'] = $is_unlimited ? 999 : (int)$c;

                } else {
                    $c = $is_count;
                    $sell = $c * $real_price;

                    // Если это набор (есть в $bundles)
                    if (isset($bundles[$item])) {
                        for ($i = 0; $i < $c; $i++) {
                            foreach ($bundles[$item] as $bundleItem) {
                                itemAdd($bundleItem['item_id'], $bundleItem['count']);
                            }
                        }
                        $response['plus'] = 'Получен набор:<br>';
                        foreach ($bundles[$item] as $bundleItem) {
                            $baseItem = $mysqli->query("SELECT name FROM base_items WHERE id = " . $bundleItem['item_id'])->fetch_assoc();
                            $response['plus'] .= '<img src="/img/world/items/little/'. $bundleItem['item_id'] .'.png" class="item"> ' . $baseItem['name'] . ' <b>x' . ($bundleItem['count'] * $c) . '</b><br>';
                        }
                    } else {
                        itemAdd($item, $c);
                        $response['plus'] = '<img src="/img/world/items/little/' . $item . '.png" class="item"> ' . $ib['name'] . ' <b>x' . $c . '</b><br>';
                    }

                    minus_item(25, $sell);
                    $mysqli->query("UPDATE `aquarits` SET `count` = 0 WHERE `item` = '$item' ");

                    // --- Логируем покупку для лимитов ---
                    if ($user_limit > 0 && $user_id && $limit_period) {
                        $mysqli->query("INSERT INTO shop_log (user_id, item_id, count, date) VALUES ($user_id, $item, $c, NOW())");
                    }
                    // --- конец логирования ---

                    $response['error'] = 'success';
                    $response['text'] = 'Успешная покупка!';
                    $response['minus'] = '<img src="/img/world/items/little/25.png" class="item"> Драгоценный камень <b>x' . $sell . '</b><br>';

                    $bal = item_isset(25, 1);
                    $response['balance'] = $bal ? (int)$bal : 0;
                    $response['item_id'] = (int)$item;
                    $response['stock_left'] = (int)0;
                }
            } else {
                $response['error'] = 'error';
                $response['text'] = 'У вас не хватает драгоценных камней!';
            }
        } else {
            $response['error'] = 'error';
            $response['text'] = 'Данный предмет закончился!';
        }
    } else {
        $response['error'] = 'error';
        $response['text'] = 'Данный предмет не продается!';
    }
}

echo json_encode($response);
?>