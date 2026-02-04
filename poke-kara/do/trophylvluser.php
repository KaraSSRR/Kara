<?
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';

if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        _setError('The problem with the connection files.');
    }else{
        require_once($patch_global);
    }
}
$gl_user = $mysqli->query('SELECT * FROM `users` WHERE `id` = '.$_SESSION['id'])->fetch_assoc();
if(!empty($_POST['trophy_lvl'])){
    $lvl = escapeMe($_POST['trophy_lvl']);
    $gl_user_tr = $mysqli->query('SELECT * FROM `base_trophy_user` WHERE `user` = '.$_SESSION['id'].' and `lvl` = '.$lvl)->fetch_assoc();
    if($lvl < $gl_user['lvl']){
        if (!$gl_user_tr && $lvl != 0) {
    if ($lvl == 1) {
        itemAdd(1, 15000);
        itemAdd(10, 15);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x15.000</b><br>
                <img src="/img/world/items/little/10.png" class="item">Стимулятор <b>x15</b>';
    } elseif ($lvl == 2) {
        itemAdd(26, 5);
        itemAdd(3, 10);
        $tpl = '<img src="/img/world/items/little/26.png" class="item">Желтая конфета (0ев) <b>x5</b><br>
                <img src="/img/world/items/little/3.png" class="item">Грейтбол <b>x10</b>';
    } elseif ($lvl == 3) {
        itemAdd(1, 30000);
        itemAdd(27, 5);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x30.000</b><br>
                <img src="/img/world/items/little/27.png" class="item">Голубая конфета (1ев) <b>x5</b>';
    } elseif ($lvl == 4) {
        itemAdd(1, 30000);
        itemAdd(13, 10);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x30.000</b><br>
                <img src="/img/world/items/little/13.png" class="item">Стимпак <b>x10</b>';
    } elseif ($lvl == 5) {
        itemAdd(1, 25000);
        itemAdd(4, 10);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x25.000</b><br>
                <img src="/img/world/items/little/4.png" class="item">Ультрабол <b>x10</b>';
    } elseif ($lvl == 6) {
        itemAdd(1, 50000);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x50.000</b>';
    } elseif ($lvl == 7) {
        itemAdd(197, 1);
        $tpl = '<img src="/img/world/items/little/197.png" class="item">Набор тренировки <b>x1</b><br>
                <b>Открыта тренировка покемонов!</b>';
    } elseif ($lvl == 8) {
        itemAdd(181, 1);
        $tpl = '<img src="/img/world/items/little/181.png" class="item">Малый усилитель ловли <b>x1</b>';
    } elseif ($lvl == 9) {
        itemAdd(183, 1);
        $tpl = '<img src="/img/world/items/little/183.png" class="item">Малый усилитель монет <b>x1</b>';
    } elseif ($lvl == 10) {
        itemAdd(29, 5);
        $tpl = '<img src="/img/world/items/little/29.png" class="item">Фиолетовая конфета (3ев) <b>x5</b>';
    } elseif ($lvl == 11) {
        itemAdd(62, 1);
        $tpl = '<img src="/img/world/items/little/62.png" class="item">Даркбол <b>x1</b>';
    } elseif ($lvl == 12) {
        itemAdd(59, 5);
        $tpl = '<img src="/img/world/items/little/59.png" class="item">Френдбол <b>x5</b>';
    } elseif ($lvl == 13) {
        itemAdd(82, 1);
        $tpl = '<img src="/img/world/items/little/82.png" class="item">Лиственный камень <b>x1</b>';
    } elseif ($lvl == 14) {
        itemAdd(245, 1);
        $tpl = '<img src="/img/world/items/little/245.png" class="item">Кекс <b>x1</b>';
    } elseif ($lvl == 15) {
        itemAdd(243, 2);
        $tpl = '<img src="/img/world/items/little/243.png" class="item">Маленький кейс витаминов <b>x2</b>';
    } elseif ($lvl == 16) {
        itemAdd(153, 1);
        $tpl = '<img src="/img/world/items/little/153.png" class="item">Защитные очки <b>x1</b>';
    } elseif ($lvl == 17) {
        itemAdd(1, 50000);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x50.000</b>';
    } elseif ($lvl == 18) {
        itemAdd(154, 1);
        $tpl = '<img src="/img/world/items/little/154.png" class="item">Зеленый шарф <b>x1</b>';
    } elseif ($lvl == 19) {
        itemAdd(197, 1);
        $tpl = '<img src="/img/world/items/little/197.png" class="item">Набор тренировки <b>x1</b>';
    } elseif ($lvl == 20) {
        itemAdd(62, 1);
        $n = rand(125, 141); // Рандомный стаб усилитель
        itemAdd($n, 1);
        // Получаем название стаб усилителя
        $stab_names = [
            125 => 'Гнутая ложка',
            126 => 'Клык дракона',
            127 => 'Магнит',
            128 => 'Мелкий песок',
            129 => 'Водный амулет',
            130 => 'Обрывок заклинаний',
            131 => 'Острый клюв',
            132 => 'Сухой лед',
            133 => 'Уголь',
            134 => 'Ядовитый шип',
            135 => 'Черные очки',
            136 => 'Черный пояс',
            137 => 'Шелковый шарф',
            138 => 'Волшебная пыль',
            139 => 'Чудесное семя',
            140 => 'Твердый камень',
            141 => 'Серебряная пыль'
        ];
        $stab_name = isset($stab_names[$n]) ? $stab_names[$n] : 'Стаб усилитель';
        $tpl = '<img src="/img/world/items/little/62.png" class="item">Даркбол <b>x1</b><br>
                <img src="/img/world/items/little/' . $n . '.png" class="item">'.$stab_name.' <b>x1</b>';
    } elseif ($lvl == 21) {
        itemAdd(124, 2);
        $tpl = '<img src="/img/world/items/little/124.png" class="item">Амурит <b>x2</b>';
    } elseif ($lvl == 22) {
        itemAdd(183, 1);
        $tpl = '<img src="/img/world/items/little/183.png" class="item">Малый усилитель монет <b>x1</b>';
    } elseif ($lvl == 23) {
        itemAdd(245, 1);
        $tpl = '<img src="/img/world/items/little/245.png" class="item">Кекс <b>x1</b>';
    } elseif ($lvl == 24) {
        itemAdd(1, 50000);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x50.000</b>';
    } elseif ($lvl == 25) {
        itemAdd(246, 1);
        $tpl = '<img src="/img/world/items/little/246.png" class="item">Сладкий кекс <b>x1</b>';
    } elseif ($lvl == 26) {
        itemAdd(1, 50000);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x50.000</b>';
    } elseif ($lvl == 27) {
        itemAdd(243, 2);
        $tpl = '<img src="/img/world/items/little/243.png" class="item">Маленький кейс витаминов <b>x2</b>';
    } elseif ($lvl == 28) {
        itemAdd(1017, 1);
        $tpl = '<img src="/img/world/items/little/1017.png" class="item">TM17 - Экран света <b>x1</b>';
    } elseif ($lvl == 29) {
        itemAdd(26, 5);
        itemAdd(3, 10);
        $tpl = '<img src="/img/world/items/little/26.png" class="item">Желтая конфета (0ев) <b>x5</b><br>
                <img src="/img/world/items/little/3.png" class="item">Грейтбол <b>x10</b>';
    } elseif ($lvl == 30) {
        itemAdd(255, 1);
        $tpl = '<img src="/img/world/items/little/255.png" class="item">Корень априкорна <b>x1</b>';
    } elseif ($lvl == 31) {
        itemAdd(1034, 1);
        $tpl = '<img src="/img/world/items/little/1034.png" class="item">TM34 - Солнечный день <b>x1</b>';
    } elseif ($lvl == 32) {
        itemAdd(1, 75000);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x75.000</b>';
    } elseif ($lvl == 33) {
        itemAdd(56, 1); // Мастербол
        $tpl = '<img src="/img/world/items/little/56.png" class="item">Мастербол <b>x1</b>';
    } elseif ($lvl == 34) {
        itemAdd(181, 1);
        itemAdd(183, 1);
        $tpl = '<img src="/img/world/items/little/181.png" class="item">Малый усилитель ловли <b>x1</b><br>
                <img src="/img/world/items/little/183.png" class="item">Малый усилитель монет <b>x1</b>';
    } elseif ($lvl == 35) {
        itemAdd(267, 1);
        $tpl = '<img src="/img/world/items/little/267.png" class="item">Портативный инкубатор <b>x1</b>';
    } elseif ($lvl == 36) {
        itemAdd(1, 75000);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x75.000</b>';
    } elseif ($lvl == 37) {
        itemAdd(1, 75000);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x75.000</b>';
    } elseif ($lvl == 38) {
        itemAdd(255, 1);
        $tpl = '<img src="/img/world/items/little/255.png" class="item">Корень априкорна <b>x1</b>';
    } elseif ($lvl == 39) {
        itemAdd(4, 20);
        $tpl = '<img src="/img/world/items/little/4.png" class="item">Ультрабол <b>x20</b>';
    } elseif ($lvl == 40) {
        itemAdd(245, 1);
        $tpl = '<img src="/img/world/items/little/245.png" class="item">Кекс <b>x1</b>';
    } elseif ($lvl == 41) {
        itemAdd(29, 5);
        $tpl = '<img src="/img/world/items/little/29.png" class="item">Фиолетовая конфета (3ев) <b>x5</b>';
    } elseif ($lvl == 42) {
        itemAdd(1, 75000);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x75.000</b>';
    } elseif ($lvl == 43) {
        itemAdd(56, 2); // Мастербол
        $tpl = '<img src="/img/world/items/little/56.png" class="item">Мастербол <b>x2</b>';
    } elseif ($lvl == 44) {
        itemAdd(107, 1);
        $tpl = '<img src="/img/world/items/little/107.png" class="item">Эволвер счастья <b>x1</b>';
    } elseif ($lvl == 45) {
        itemAdd(246, 1);
        $tpl = '<img src="/img/world/items/little/246.png" class="item">Сладкий кекс <b>x1</b>';
    } elseif ($lvl == 46) {
        itemAdd(62, 1);
        $tpl = '<img src="/img/world/items/little/62.png" class="item">Даркбол <b>x1</b>';
    } elseif ($lvl == 47) {
        itemAdd(1, 100000);
        $tpl = '<img src="/img/world/items/little/1.png" class="item">Монета <b>x100.000</b>';
    } elseif ($lvl == 48) {
        itemAdd(187, 1);
        $tpl = '<img src="/img/world/items/little/187.png" class="item">Приманка <b>x1</b>';
    } elseif ($lvl == 49) {
        itemAdd(198, 1);
        $tpl = '<img src="/img/world/items/little/198.png" class="item">Набор ослаблений <b>x1</b>';
    } elseif ($lvl == 50) {
        newPokemon(418, $_SESSION['id'], 5, false, 0, '26,26,26,26,26,26', 1, false, false, false, false, true);
        $tpl = '<img src="/img/pokemons/animation/418.png"> #418 Буизель';
    }

    $mysqli->query("INSERT INTO `base_trophy_user` (`user`, `lvl`) VALUES ('" . $_SESSION['id'] . "', '" . $lvl . "')");
            $response['html'] = "Награда за <b>".$lvl."</b> уровень тренера!";
            $response['error'] = "success";
            $response['plus'] = $tpl;
        }else{
            $response['html'] = "Вы уже получили награду за этот уровень!";
        $response['error'] = "error";
        }
    }else{
        $response['html'] = "Ваш уровень недостаточно большой!";
        $response['error'] = "error";
    }
}

echo json_encode($response);
?>