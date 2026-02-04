<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_func = $patch_project.'/inc/function/Functions.php';

if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        die('The problem with the connection files.');
    } else {
        require_once($patch_global);
        require_once($patch_func);
    }
}

// id кейса и ключа (можно использовать в нескольких кейсах)
$case_item_id = 456; // id предмета кейса
$key_item_id  = 457; // id предмета ключа
$case_name = 'Стартовый';

$return = [];

// Проверяем наличие кейса и ключа (по 1 шт.)
if (item_isset($case_item_id, 1) && item_isset($key_item_id, 1)) {

    // Списываем кейс и ключ через minus_item (ваша функция)
    minus_item($case_item_id, 1);
    minus_item($key_item_id, 1);

    // --- Новый ЧЕСТНЫЙ розыгрыш по шансам в процентах ---
    // 1. Получаем все призы и считаем сумму шансов (шансы в процентах, сумма должна быть 100)
    $case_items = [];
    $total_chance = 0;
    $res = $mysqli->query('SELECT * FROM `Box_case` WHERE `case_num` = ' . $case_item_id);
    while ($row = $res->fetch_assoc()) {
        $case_items[] = $row;
        $total_chance += $row['chance'];
    }

    // 2. Проверяем, что сумма шансов равна 100 (или скорректируйте под свою бизнес-логику)
    if ($total_chance != 100) {
        $return['error'] = 'Ошибка: сумма шансов не равна 100! Сейчас: '.$total_chance;
        echo json_encode($return);
        exit;
    }

    // 3. Розыгрыш по диапазону
    $rand = rand(1, 100);
    $current = 0;
    $case = null;
    foreach ($case_items as $row) {
        $current += $row['chance'];
        if ($rand <= $current) {
            $case = $row;
            break;
        }
    }

    if (!$case) {
        $return['error'] = 'Приз не найден!';
        echo json_encode($return);
        exit;
    }

    $t = $case['type']; // 'item' или 'pok'
    $name = '';
    $id = 0;

    if ($t == 'item') {
        $info = $mysqli->query('SELECT `name` FROM `base_items` WHERE `id` = ' . intval($case['object_id']))->fetch_assoc();
        $name = $info ? $info['name'] : '';
        $id = $case['object_id'];
    } else { // pok
        $info = $mysqli->query('SELECT `name_rus` FROM `base_pokemons` WHERE `id` = ' . intval($case['object_id']))->fetch_assoc();
        $name = "#" . numbPok($case['object_id']) . " " . ($info ? $info['name_rus'] : '');
        $id = $case['object_id'];
    }

    // Универсальный подсчет количества
    $count = (isset($case['min_amount']) && isset($case['max_amount']) && $case['min_amount'] > 0 && $case['max_amount'] > 0)
        ? rand($case['min_amount'], $case['max_amount'])
        : ((isset($case['count']) && $case['count'] > 0) ? $case['count'] : 1);

   $return = [
    'id'    => $id,
    'name'  => $name,
    'pok'   => ($t == 'pok') ? $case['object_id'] : 0,
    'count' => $count,
    'type'  => $t,
];

// Выдача награды
if ($t == 'item') {
    itemAdd($id, $count);
    $prizeName = $name; // название предмета
    $prizeImg  = '/img/world/items/little/' . $id . '.png';
} else {
    newPokemon($id, $_SESSION['id'], 1, 28, 0, 'true', 0, false, false, false, false, true);
    $prizeName = $name; // название покемона
    $prizeImg  = '/img/pokemons/sprite/normal/' . $id . '.gif';
}

$return['minus'] = '<img src="/img/world/items/little/' . $case_item_id . '.png" class="item"> Кейс <<' . $case_name . '>> <b>x1</b><br>'
    . '<img src="/img/world/items/little/' . $key_item_id . '.png" class="item"> Уникальный ключ <b>x1</b>';

// Здесь формируем строку с названием и количеством выигрыша (можно добавить картинку)
$return['text'] = 'Вы выиграли: <img src="' . $prizeImg . '" class="item" style="vertical-align:middle;max-height:22px;margin-right:4px;"> '
    . '<b>' . $prizeName . '</b>' . ($count > 1 ? ' x' . $count : '');

} else {
    $return['error'] = 'У вас нет кейса или ключа!';
}

echo json_encode($return);
?>