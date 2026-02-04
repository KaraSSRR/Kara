<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        echo json_encode(['html' => '<div class="ErrorMessage">Ошибка подключения к базе.</div>']);
        exit;
    } else {
        require_once($patch_global);
    }
}

header('Content-Type: application/json');
ini_set('display_errors', 1); error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;

// Получаем все коллекции
$collections = [];
$res = $mysqli->query("SELECT id, name FROM sticker_collections ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $collections[] = $row;
}

$col_id = 0;
// приоритет: GET > POST > первая коллекция
if (isset($_GET['col_id'])) {
    $col_id = intval($_GET['col_id']);
} elseif (isset($_POST['col_id'])) {
    $col_id = intval($_POST['col_id']);
} elseif (!empty($collections)) {
    $col_id = intval($collections[0]['id']);
}

$col = $mysqli->query("SELECT * FROM sticker_collections WHERE id = $col_id")->fetch_assoc();
if (!$col) {
    echo json_encode(['html' => '<div class="ErrorMessage">Коллекция не найдена.</div>']);
    exit;
}

// Получаем стикеры и их наличие у пользователя
$stickers = [];
$q = $mysqli->query("
    SELECT s.id, s.name, s.image, IF(us.sticker_id IS NULL, 0, 1) AS owned
    FROM stickers s
    LEFT JOIN user_stickers us ON us.sticker_id = s.id AND us.user_id = $user_id
    WHERE s.collection_id = {$col['id']}
    ORDER BY s.id
");
while ($row = $q->fetch_assoc()) {
    $row['owned'] = (int)$row['owned'];
    $stickers[] = $row;
}

// Позиции для разных коллекций
$positions_by_count = [
    8 => [
        [38, 70], [150, 85], [264, 70],
        [49, 180], [165, 185], [270, 178],
        [38, 285], [154, 295]
    ],
    9 => [
        [38, 70], [150, 85], [264, 70],
        [49, 180], [165, 185], [270, 178],
        [38, 285], [154, 295], [265, 295]
    ],
    10 => [
        [38, 70], [150, 85], [264, 70],
        [49, 160], [110, 180], [210, 178], [270, 178],
        [38, 285], [154, 295], [265, 295]
    ],
];

$positions = [];
if (isset($positions_by_count[count($stickers)])) {
    $positions = $positions_by_count[count($stickers)];
} else {
    $cols = ceil(sqrt(count($stickers)));
    $cell_w = 110;
    $cell_h = 110;
    $offset_x = 20;
    $offset_y = 40;
    for ($i = 0; $i < count($stickers); $i++) {
        $row = floor($i / $cols);
        $colx = $i % $cols;
        $positions[] = [
            $offset_x + $colx * $cell_w,
            $offset_y + $row * $cell_h
        ];
    }
}

$stick_size = 90;
$img_size = 80;
$stickers_container_width = 400;
$stickers_container_height = 400;

// Формируем HTML
$tpl = '';
$tpl .= '<div class="album-book">';
$tpl .=    '<div class="album-collections-tabs" style="display:flex;gap:8px;margin:0 0 18px 18px;">';
foreach ($collections as $c) {
    $is_active = ($c['id'] == $col['id']) ? 'album-tab-active' : '';
    $tpl .= '<button class="album-tab '.$is_active.'" data-colid="'.$c['id'].'" type="button" style="padding:7px 16px;border-radius:9px;border:1px solid #e2cfab;background:'.($is_active?'#f3e2c1':'#fcf7ee').';cursor:pointer;font-weight:bold;">'.htmlspecialchars($c['name']).'</button>';
}
$tpl .=    '</div>';
$tpl .=    '<div class="album-title">Коллекция ' . htmlspecialchars($col['name']) . '</div>';
$tpl .=    '<div class="album-stickers-free" style="width:'.$stickers_container_width.'px;height:'.$stickers_container_height.'px;position:relative;margin:0 auto;">';
$tpl .=        '<div class="stickers-bg"></div>';

$owned = 0;
for ($i = 0; $i < count($positions); $i++) {
    $s = isset($stickers[$i]) ? $stickers[$i] : null;
    if (!$s) continue;
    if ($s['owned']) $owned++;
    $folder = strtolower($col['name']);
    $imgPath = '/img/stickers/' . $folder . '/' . $s['image'] . '.png';
    list($x, $y) = $positions[$i];
    $style = 'left:'.$x.'px;top:'.$y.'px;width:'.$stick_size.'px;height:'.$stick_size.'px;';
    $imgStyle = 'width:'.$img_size.'px;height:'.$img_size.'px;object-fit:contain;';
    if ($s['owned']) {
        $tpl .= '<div class="sticker" style="'.$style.'"><img src="' . htmlspecialchars($imgPath) . '" alt="' . htmlspecialchars($s['name']) . '" style="'.$imgStyle.'"></div>';
    } else {
        $tpl .= '<div class="sticker sticker-silhouette" style="'.$style.'"><img src="' . htmlspecialchars($imgPath) . '" alt="" style="'.$imgStyle.'"></div>';
    }
}
$tpl .=    '</div>';
$tpl .=    '<div class="album-progress">Прогресс: ' . $owned . ' / ' . count($stickers) . '</div>';
$tpl .= '</div>';

echo json_encode(['html' => $tpl]);
exit;
?>