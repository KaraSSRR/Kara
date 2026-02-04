<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if (!empty($patch_global)) {
    if (!file_exists($patch_global)) {
        die('The problem with the connection files.');
    } else {
        require_once($patch_global);
    }
}

$type = isset($_POST['type']) ? $_POST['type'] : '';
$response = [];

// Массив лидеров: id, стадион, ник, регион, уровень, id значка, путь к значку
$leaders = [
    [
        'id' => 79,
        'stadium' => 'Травяной стадион',
        'leader' => 'Kara',
        'region' => 'Калос',
        'level' => 40,
        'badge_id' => 101,
        'badge_img' => '/img/badges/grass.png',
    ],
    [
        'id' => 118,
        'stadium' => 'Волшебный стадион',
        'leader' => '',
        'region' => 'Калос',
        'level' => 40,
        'badge_id' => 102,
        'badge_img' => '/img/badges/fairy.png',
    ],
    [
        'id' => 134,
        'stadium' => 'Каменный стадион',
        'leader' => '',
        'region' => 'Алола',
        'level' => 50,
        'badge_id' => 103,
        'badge_img' => '/img/badges/rock.png',
    ],
    [
        'id' => 262,
        'stadium' => 'Стадион жуков',
        'leader' => '',
        'region' => 'Алола',
        'level' => 50,
        'badge_id' => 104,
        'badge_img' => '/img/badges/bug.png',
    ],
    [
        'id' => 312,
        'stadium' => 'Нормальный стадион',
        'leader' => '',
        'region' => 'Канто',
        'level' => 50,
        'badge_id' => 105,
        'badge_img' => '/img/world/items/little/5.1.png',
    ],
    [
        'id' => 410,
        'stadium' => 'Электрический стадион',
        'leader' => '',
        'region' => 'Калос',
        'level' => 60,
        'badge_id' => 106,
        'badge_img' => '/img/badges/electric.png',
    ],
    [
        'id' => 841,
        'stadium' => 'Ядовитый стадион',
        'leader' => '',
        'region' => 'Джото',
        'level' => 70,
        'badge_id' => 107,
        'badge_img' => '/img/badges/poison.png',
    ],
    [
        'id' => 917,
        'stadium' => 'Воздушный стадион',
        'leader' => '',
        'region' => 'Джото',
        'level' => 80,
        'badge_id' => 108,
        'badge_img' => '/img/badges/flying.png',
    ],
    [
        'id' => 1001,
        'stadium' => 'Призрачный стадион',
        'leader' => '',
        'region' => 'Синно',
        'level' => 90,
        'badge_id' => 109,
        'badge_img' => '/img/badges/ghost.png',
    ],
    [
        'id' => 1002,
        'stadium' => 'Водный стадион',
        'leader' => '',
        'region' => 'Канто',
        'level' => 70,
        'badge_id' => 110,
        'badge_img' => '/img/badges/water.png',
    ],
    [
        'id' => 1003,
        'stadium' => 'Земляной стадион',
        'leader' => '',
        'region' => 'Хоэнн',
        'level' => 60,
        'badge_id' => 111,
        'badge_img' => '/img/world/items/little/5.6.png',
    ],
    [
        'id' => 1004,
        'stadium' => 'Стальной стадион',
        'leader' => '',
        'region' => 'Калос',
        'level' => 80,
        'badge_id' => 112,
        'badge_img' => '/img/badges/steel.png',
    ],
    // ... Добавьте остальных лидеров по аналогии ...
];

// Функция для автоматического пути к аватарке по нику
function getAvatarPath($leaderName)
{
    $file = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $leaderName));
    $file = preg_replace('/[^a-z0-9]+/', '_', $file);
    $file = trim($file, '_');
    return "/img/avatars/mini/$file.png";
}

$user = $mysqli->query('SELECT * FROM users WHERE id = ' . $_SESSION['id'])->fetch_assoc();

switch ($type) {
    case "champions_league":
        $tpl = <<<HTML
<div class="champions-header">
    <h2>Лига Чемпионов </h2>
    <div class="champions-desc"> <a href="#">Подробнее…</a></div>
</div>
<div class="champions-grid">
HTML;

        foreach ($leaders as $info) {
            $avatar = getAvatarPath($info['leader']);

            // Получен ли значок (badge)
            $hasBadge = $mysqli->query("SELECT 1 FROM items_users WHERE user = {$_SESSION['id']} AND item_id = {$info['badge_id']} LIMIT 1")->fetch_row();

            // Есть ли подтвержденная заявка (type=2)
            $confirmed = $mysqli->query("SELECT 1 FROM gym_log WHERE user = {$_SESSION['id']} AND gym = {$info['id']} AND type = 2 LIMIT 1")->fetch_row();

            $badgeClass = $hasBadge ? 'champion-badge-owned' : 'champion-badge-locked';

            // Кнопка "Записаться": если нет значка и нет подтверждения
            $canApply = !$hasBadge && !$confirmed;
            $applyDisabled = $canApply ? 'class="btn"' : 'class="btn disabled" disabled';

            // Кнопка "Телепорт": если есть подтверждение
            $canTeleport = $confirmed;
            $teleportDisabled = $canTeleport ? '' : 'disabled title="Доступно после подтверждения заявки"';

            $tpl .= '<div class="champion-card">';
            $tpl .= '<div class="champion-stadium">' . htmlspecialchars($info['stadium']) . '</div>';
            $tpl .= '<div class="champion-leadername">' . htmlspecialchars($info['leader']) . '</div>';
            $tpl .= '<img class="champion-avatar" src="' . $avatar . '" alt="' . $info['leader'] . '" onerror="this.src=\'/img/avatars/mini/5.png\'">';
            $tpl .= '<div class="champion-badge-bg">';
            $tpl .=    '<img class="champion-badge ' . $badgeClass . '" src="' . $info['badge_img'] . '" alt="значок">';
            $tpl .= '</div>';
            $tpl .= '<div class="champion-meta">' . htmlspecialchars($info['region']) . ', ' . $info['level'] . ' ур.</div>';
            $tpl .= '<div class="champion-buttons">';
            $tpl .=    '<button ' . $applyDisabled . ' onclick="championApply(' . $info['id'] . ')">Записаться</button> ';
            $tpl .=    '<button class="btn" onclick="teleportToStadium(' . $info['id'] . ')" ' . $teleportDisabled . '>Телепорт</button>';
            $tpl .= '</div>';
            $tpl .= '</div>';
        }
        $tpl .= '</div>';
        $response['html'] = $tpl;
        break;

    case "champion_apply":
        $leader_id = intval($_POST['leader_id']);
        // Найдём лидера по id для badge_id
        $info = null;
        foreach ($leaders as $l) if ($l['id'] == $leader_id) $info = $l;
        if (!$info) {
            $response['html'] = "Гим-лидер не найден!";
            $response['error'] = "error";
            break;
        }
        $badgeId = $info['badge_id'];

        // Проверки
        if ($user['lvl'] < 15) {
            $response['html'] = "Ваш уровень меньше 15!";
            $response['error'] = "error";
            break;
        }
        $item = $mysqli->query('SELECT * FROM items_users WHERE user = ' . $_SESSION['id'] . ' AND item_id = ' . $badgeId)->fetch_assoc();
        if ($item) {
            $response['html'] = "У вас уже есть этот значок!";
            $response['error'] = "error";
            break;
        }
        $confirmed = $mysqli->query("SELECT 1 FROM gym_log WHERE user = {$_SESSION['id']} AND gym = $leader_id AND type = 2 LIMIT 1")->fetch_row();
        if ($confirmed) {
            $response['html'] = "Вам уже подтверждена заявка на этот стадион!";
            $response['error'] = "error";
            break;
        }
        $bd = $mysqli->query('SELECT * FROM gym_log WHERE user = ' . $_SESSION['id'] . ' ORDER BY id DESC LIMIT 1')->fetch_assoc();
        $time = time() - 3600 * 36;
        if (isset($bd) && ($bd['type'] == 0 || $bd['time'] >= $time)) {
            $response['html'] = "У вас есть не закрытая заявка или для следующей заявки не прошло 36 часов!";
            $response['error'] = "error";
            break;
        }
        $gym = $mysqli->query('SELECT * FROM users WHERE id = ' . $leader_id . ' AND user_group = 5')->fetch_assoc();
        if (!$gym) {
            $response['html'] = "Гим-лидер не найден!";
            $response['error'] = "error";
            break;
        }

        // Подать заявку (type=0)
        $t = time();
        $mysqli->query("INSERT INTO gym_log (`user`,`gym`,`time`,`type`) VALUES ('" . $user['id'] . "','" . $leader_id . "','" . $t . "','0') ");
        $response['html'] = "Заявка успешно подана, ожидайте ответ от Гим-Лидера!";
        $response['error'] = "success";

        // Оповещение для лидера
        $month = array(1 => 'Января', 2 => 'Февраля', 3 => 'Марта', 4 => 'Апреля', 5 => 'Мая', 6 => 'Июня', 7 => 'Июля', 8 => 'Августа', 9 => 'Сентября', 10 => 'Октября', 11 => 'Ноября', 12 => 'Декабря');
        $dayToday = date("d");
        $monthToday = $month[date("n")];
        $YearToday = date("Y");
        $date = $dayToday . ' ' . $monthToday . ' ' . $YearToday . 'г. в ' . date("H") . ':' . date("i");
        $text = 'У вас новый кандидат на сражение на стадионе. Проверьте панель';
        $mysqli->query("INSERT INTO notification (`text`,`user`,`img`,`date`) VALUES ('" . $text . "','" . $leader_id . "','/img/world/items/little/223.png','" . $date . "')");

        break;

    case "teleport":
        $leader_id = intval($_POST['leader_id']);
        // Найдём лидера по id для badge_id
        $info = null;
        foreach ($leaders as $l) if ($l['id'] == $leader_id) $info = $l;
        if (!$info) {
            $response['html'] = "Гим-лидер не найден!";
            $response['error'] = "error";
            break;
        }
        // Проверяем подтвержденную заявку (type=2)
        $confirmed = $mysqli->query("SELECT 1 FROM gym_log WHERE user = {$_SESSION['id']} AND gym = $leader_id AND type = 2 LIMIT 1")->fetch_row();
        if (!$confirmed) {
            $response['html'] = "Телепорт доступен только после подтверждения заявки лидером!";
            $response['error'] = "error";
            break;
        }
        $response['url'] = "/world/stadium/" . $leader_id;
        $response['html'] = "Телепортация...";
        $response['error'] = "success";
        break;

    default:
        $response['html'] = "Неизвестный запрос";
        $response['error'] = "error";
        break;
}

echo json_encode($response);
?>