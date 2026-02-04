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

$idCase = isset($_POST['idCase']) ? intval($_POST['idCase']) : 0;
$n = ($idCase == 456) ? "кейса <<Стартовый>>" : "кейса";

$caseList = [];
$return = [];

// Получаем все призы для данного кейса из новой структуры
$case = $mysqli->query('SELECT * FROM `Box_case` WHERE `case_num` = ' . $idCase . ' ORDER BY id ASC');
if ($case && $case->num_rows > 0) {
    while ($cas = $case->fetch_assoc()) {
        $name = '';
        $img = '';
        if ($cas['type'] === 'item') {
            // Получаем название предмета
            $itemInfo = $mysqli->query('SELECT `name` FROM `base_items` WHERE `id` = ' . intval($cas['object_id']))->fetch_assoc();
            $name = $itemInfo ? $itemInfo['name'] : '';
            $img = '/img/world/items/little/' . intval($cas['object_id']) . '.png';
        } elseif ($cas['type'] === 'pok') {
            // Покемон
            $pokeInfo = $mysqli->query('SELECT `name_rus` FROM `base_pokemons` WHERE `id` = ' . intval($cas['object_id']))->fetch_assoc();
            $name = $pokeInfo ? $pokeInfo['name_rus'] : '';
            $img = '/img/pokemons/pokedex/' . intval($cas['object_id']) . '.png';
        }
        $caseList[] = [
            'id'     => $cas['object_id'],
            'type'   => $cas['type'],
            'img'    => $img,
            'name'   => $name,
            'count'  => $cas['count'],
            'rarity' => $cas['rarity'],
            'min_amount' => $cas['min_amount'],
            'max_amount' => $cas['max_amount']
        ];
    }

    // Делаем "карусель" — включаем все призы, гарантированно без повторов (для JS flip/анимации)
    // Фронтенд сам может рандомизировать порядок и повторять если нужно
    $return = [
        'list'     => $caseList,
        'carusel'  => $caseList, // Без лишнего дублирования: реальный список призов
        'nameCase' => $n
    ];
} else {
    $return['error'] = "Ошибка! Ничего не найдено для этого кейса.";
}

echo json_encode($return);
?>