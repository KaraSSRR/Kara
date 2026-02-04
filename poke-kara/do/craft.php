<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
$patch_func = $patch_project.'/inc/function/Functions.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
        require_once($patch_global);
        require_once($patch_func);
    }
}

// Функции избранного
if (isset($_POST['toggleFavorite']) && isset($_POST['item_id'])) {
    $itemId = intval($_POST['item_id']);
    $userId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
    if ($itemId > 0 && $userId > 0) {
        $q = $mysqli->query("SELECT 1 FROM user_favorites WHERE user_id = $userId AND item_id = $itemId LIMIT 1");
        if ($q && $q->num_rows > 0) {
            $mysqli->query("DELETE FROM user_favorites WHERE user_id = $userId AND item_id = $itemId");
            echo json_encode(['result' => 'removed']);
        } else {
            $mysqli->query("INSERT IGNORE INTO user_favorites (user_id, item_id) VALUES ($userId, $itemId)");
            echo json_encode(['result' => 'added']);
        }
    } else {
        echo json_encode(['result' => 'error']);
    }
    exit;
}
function isFavorite($itemId) {
    if (!isset($_SESSION['id']) || !$_SESSION['id']) return false;
    global $mysqli;
    $userId = intval($_SESSION['id']);
    $itemId = intval($itemId);
    $q = $mysqli->query("SELECT 1 FROM user_favorites WHERE user_id = $userId AND item_id = $itemId LIMIT 1");
    return ($q && $q->num_rows > 0);
}
function favBtn($itemId) {
    $isFav = isFavorite($itemId);
    return '<span class="FavBtn'.($isFav?' active':'').'" onclick="event.stopPropagation();toggleFavorite('.$itemId.',this)">'.($isFav?'★':'☆').'</span>';
}

$type = escapeMe($_POST["category"]) ? escapeMe($_POST["category"]) : escapeMe($_POST["item"]);
$response = [];

switch ($type) {
    // ---------------------- ИЗБРАННОЕ ----------------------
    case 'favorites':
        $userId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
        if ($userId > 0) {
            $result = $mysqli->query("SELECT item_id FROM user_favorites WHERE user_id = $userId");
            $favorites = [];
            while ($row = $result->fetch_assoc()) { $favorites[] = intval($row['item_id']); }
            if ($favorites) {
                $response['html'] = '';
                foreach ($favorites as $favId) {
                    $response['html'] .= '
                    <div class="CraftObject" onclick="craftCategory('.$favId.',1)">
                        <div class="CraftImg" style="background-image: url(/img/world/items/little/'.$favId.'.png);"></div>
                        '.favBtn($favId).'
                    </div>';
                }
            } else {
                $response['html'] = '<div class="no-favorites">Нет избранных рецептов.</div>';
            }
        } else {
            $response['html'] = '<div class="no-favorites">Авторизуйтесь, чтобы видеть избранное.</div>';
        }
        break;

    // ---------------------- КАТЕГОРИИ ----------------------
    case 'all':
        $response['html'] =
            '<div class="CraftCtgText">~ Усилители ~</div>'
            // held / усилители
            . craftObj(127)  // Магнит
            . craftObj(129)  // Водный амулет
            . craftObj(131)  // Заострённый клюв
            . craftObj(133)  // Уголь
            . craftObj(137)  // Шёлковый шарф
            . craftObj(139)  // Чудесное семя
            . craftObj(140)  // Прочный камень
            . craftObj(132)  // Сухой лёд
            . craftObj(143)  // Чёрный пояс
            . craftObj(141)  // Серебряная пыль
            . craftObj(145)  // Остатки
            . craftObj(128)  // Мешочек с землёй
            . craftObj(134)  // Ядовитый шип
            . craftObj(100)  // Броня
            // эволюционные камни
            . '<div class="CraftCtgText">~ Эволюционные камни ~</div>'
            . craftObj(80).craftObj(81).craftObj(82).craftObj(83).craftObj(84).craftObj(85).craftObj(86).
            '<div class="CraftCtgText">~ Квестовые предметы ~</div>'
            .craftObj(5026); break;

    case 'held':
        $response['html'] =
              craftObj(127)
            . craftObj(129)
            . craftObj(131)
            . craftObj(133)
            . craftObj(137)
            . craftObj(139)
            . craftObj(140)
            . craftObj(132)
            . craftObj(143)
            . craftObj(141)
            . craftObj(145)
            . craftObj(128)
            . craftObj(134)
            . craftObj(100);
        break;

    case 'evolver':
        $response['html'] = craftObj(80).craftObj(81).craftObj(82).craftObj(83).craftObj(84).craftObj(85).craftObj(86);
        break;

    // ---- устаревшие разделы: показываем заглушку (на фронте больше не используются) ----
    case 'potion':;break;
    case 'ball':;break;
    case 'medicine':;break;
    case 'etc':;break;
        $response['html'] = craftObj(5026);
        break;
    case 'attacks':
        $response['html'] = '<div class="no-category">Категория недоступна.</div>';
        break;

    // ---------------------- КАРТОЧКИ РЕЦЕПТОВ ----------------------
    // HELD / усилители
    case 127: $response['html'] = cardCurr(127,"Магнит","Предмет для удержания. Усиливает Электро-атаки.","Искры x5, Эссенция (к) x25, Загадочные камушки x1"); break;
    case 129: $response['html'] = cardCurr(129,"Водный амулет","Предмет для удержания. Усиливает Водные атаки.","Капелька воды x10, Нитка x8, Ткань тонкая x8"); break;
    case 131: $response['html'] = cardCurr(131,"Заострённый клюв","Предмет для удержания. Усиливает Летающие атаки.","Клюв x7, Эссенция (к) x12"); break;
    case 133: $response['html'] = cardCurr(133,"Уголь","Предмет для удержания. Усиливает Огненные атаки.","Угольки x3, Искорки x5"); break;
    case 137: $response['html'] = cardCurr(137,"Шёлковый шарф","Предмет для удержания. Усиливает Нормальные атаки.","Нитки x10, Ткань тонкая x15"); break;
    case 139: $response['html'] = cardCurr(139,"Чудесное семя","Предмет для удержания. Усиливает Растительные атаки.","Детки какнеи x3, Листики x5, Эссенция (к) x3"); break;
    case 140: $response['html'] = cardCurr(140,"Прочный камень","Предмет для удержания. Повышает стойкость.","Осколок лиственного камня x3"); break;
    case 132: $response['html'] = cardCurr(132,"Сухой лёд","Предмет для удержания. Усиливает Ледяные атаки.","Ледяной осколок x4, Горсть снега x5"); break;
    case 143: $response['html'] = cardCurr(143,"Чёрный пояс","Предмет для удержания. Усиливает Боевое.","Ткань тонкая x10, Краситель чёрный x4, Эссенция (к) x7"); break;
    case 141: $response['html'] = cardCurr(141,"Серебряная пыль","Предмет для удержания. Усиливает Насекомое.","Жало Драпиона x1, Яд Эканса x1, Эссенция (к) x5"); break;
    case 145: $response['html'] = cardCurr(145,"Остатки","Предмет для удержания. Медленно восстанавливает HP.","Яблоко x5, Ткань тонкая x4"); break;
    case 128: $response['html'] = cardCurr(128,"Мешочек с землёй","Предмет для удержания. Усиливает Землю.","Плодородная земля x3, Эссенция (к) x2"); break;
    case 134: $response['html'] = cardCurr(134,"Ядовитый шип","Предмет для удержания. Усиливает Яд.","Яд Эканса x1, Эссенция (к) x4"); break;
    case 100: $response['html'] = cardCurr(100,"Броня","Тяжёлый защитный модуль. Повышает защитные показатели.","Рога Агрона x10, Эссенция (к) x20"); break;

// КВЕСТЫ
        case 5026: $response['html'] = cardCurr(5026,"Калибровочный модуль","Собранный модуль для калибровки инкубатора. Используется техником питомника.","Перо Муркроу x4, Пластина Арона x2, Кристалл Карбинка x3, Тлеющая грива Пониты x1, Солнечное семя Санкерна x5"); break;

// ЭВОЛЮЦИОННЫЕ КАМНИ
    case 80: $response['html'] = cardCurr(80,"Громовой камень","Камень с силой молнии. Эволюционирует некоторых покемонов.","Осколок громового камня x3"); break;
    case 81: $response['html'] = cardCurr(81,"Водный камень","Камень, наполненный силой моря.","Осколок водного камня x3"); break;
    case 82: $response['html'] = cardCurr(82,"Лиственный камень","Камень с силой лесов.","Осколок лиственного камня x3"); break;
    case 83: $response['html'] = cardCurr(83,"Огненный камень","Невероятно горячий камень.","Осколок огненного камня x3"); break;
    case 84: $response['html'] = cardCurr(84,"Лунный камень","Таинственный осколок луны.","Осколок лунного камня x3"); break;
    case 85: $response['html'] = cardCurr(85,"Солнечный камень","Оранжевый камень в форме солнца.","Осколок солнечного камня x3"); break;
    case 86: $response['html'] = cardCurr(86,"Сумрачный камень","Камень сумерек.","Осколок сумрачного камня x3"); break;

    // Прочее/дефолт
    default:
        $response['html'] = '<div class="no-category">Неизвестная категория или рецепт.</div>';
        break;
}

// ---------------------- ФУНКЦИИ ДЛЯ ВЫВОДА ----------------------
function craftObj($id) {
    return '<div class="CraftObject" onclick="craftCategory('.$id.',1)">
        <div class="CraftImg" style="background-image: url(/img/world/items/little/'.$id.'.png);"></div>
        '.favBtn($id).'
    </div>';
}
function cardCurr($id, $name, $about, $need) {
    $needItems = '';
    foreach (explode(',', $need) as $el) { $needItems .= '<div>'.trim($el).'</div>'; }
    return '
    <div class="Name">'.$name.' '.favBtn($id).'</div>
    <div class="Image"><img id="imgItem" src="/img/world/items/little/'.$id.'.png"></div>
    <div class="About">'.$about.'</div>
    <div class="hr"></div>
    <div class="Conditions">
        Необходимо:<br>
        '.$needItems.'
    </div>
    <div class="Buttons">
        <input id="CountCraft" type="number" value="1" placeholder="Количество">
        <div onclick="craftItem('.$id.')">Создать</div>
    </div>';
}

echo json_encode($response);
