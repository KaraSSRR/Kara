<?php
session_start();

include_once("constants.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/rb/functions_rb.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/makasimka/inc/classes/Info.php');

$response = [];
$response['name'] = 'Помощник';
$response['image'] ='/img/npc/666.png';

if (!isset($mysqli) || !$mysqli) {
    $response['question'] = 'Ошибка подключения к базе данных.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$mysqli->set_charset("utf8mb4");

// ВАЖНО: $npcStep приходит из твоего обработчика NpcDialog(..., step, ...).
// Если не пришёл — проставим "false" (первый экран диалога).
$npcStep = isset($_POST['step']) ? $_POST['step'] : (isset($npcStep) ? $npcStep : "false");

$userId = intval($_SESSION['id'] ?? 0);

if ($userId <= 0) {
    $response['question'] = 'Ошибка авторизации.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$userInfo = $mysqli->query("SELECT id, login, user_group, location, status, status_id FROM users WHERE id = '$userId'")->fetch_assoc();
if (!$userInfo) {
    $response['question'] = 'Пользователь не найден.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Актуальная локация арены
$arenaLocationID  = 8009;
$rbAllowedLocation= 8009;

// Если ушли с арены — чистим РБ-команду и возвращаемся в "idle"
if (isset($userInfo['location']) && (int)$userInfo['location'] !== (int)$rbAllowedLocation) {
    rb_removePokemons($mysqli, $userId);
    $mysqli->query("UPDATE users SET status = 'free', status_id = 0 WHERE id = $userId");
    $response['question']   = 'Ваша команда была удалена, так как вы покинули арену.';
    $response['closeDialog']= true;
    $response['rb_state']   = 'idle';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Если бой помечен окончательным — чистим РБ-команду и переводим в idle
if (!empty($userInfo['status']) && $userInfo['status'] === 'battle' && !empty($userInfo['status_id'])) {
    $battleId  = (int)$userInfo['status_id'];
    $battleRow = $mysqli->query("SELECT * FROM battle WHERE id = $battleId")->fetch_assoc();
    if ($battleRow && !empty($battleRow['end'])) {
        if (!empty($battleRow['arena']) && (int)$battleRow['arena'] === (int)$arenaLocationID) {
            rb_removePokemons($mysqli, $userId);
        }
        $mysqli->query("UPDATE users SET status = 'free', status_id = 0 WHERE id = $userId");
        $response['question']   = 'Бой завершён. Временная команда удалена.';
        $response['closeDialog']= true;
        $response['rb_state']   = 'idle';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($npcStep === "false") {
    // Первый экран диалога
    $response['question'] = 'Участвовать в бою с выданной случайной командой покемонов?';
    $response['answer']   = [
        2 => "Готов принять вызов!",
        3 => "Отказаться и удалить команду"
    ];
    $response['closeDialog'] = true;

    // Текущее состояние очереди для правильной иконки на кнопке
    $inQueue = 0;
    if ($q = $mysqli->query("SELECT 1 FROM rb_queue WHERE user_id = ".(int)$userId." LIMIT 1")) {
        $inQueue = (int)$q->num_rows;
    }
    $response['rb_state'] = $inQueue ? 'queued' : 'idle';

} elseif ($npcStep == 2) {
    // Встать в очередь: выдаём РБ-команду, ставим в очередь,
    // закрываем окно и возвращаем rb_state = queued

    if ((int)$userInfo['location'] !== (int)$rbAllowedLocation) {
        $response['question']    = 'Выдача команды доступна только на арене.';
        $response['closeDialog'] = true;
        $response['rb_state']    = 'idle';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$mysqli->query("UPDATE user_pokemons SET active = 0 WHERE user_id = ".(int)$userId)) {
        $response['question']    = 'Ошибка: не удалось снять покемонов с команды.';
        $response['closeDialog'] = true;
        $response['rb_state']    = 'idle';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        rb_givePokemons($mysqli, $userId);
    } catch (Throwable $e) {
        $response['question']    = 'Ошибка при выдаче команды: ' . $e->getMessage();
        $response['closeDialog'] = true;
        $response['rb_state']    = 'idle';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $now = date('Y-m-d H:i:s');

    // Ищем соперника
    $opponent = $mysqli->query("SELECT user_id FROM rb_queue WHERE user_id != ".(int)$userId." ORDER BY created_at ASC LIMIT 1")->fetch_assoc();

    if ($opponent) {
        // Нашёлся соперник — стартуем бой
        $opponentId = (int)$opponent['user_id'];
        $mysqli->query("DELETE FROM rb_queue WHERE user_id IN (".(int)$userId.", ".(int)$opponentId.")");

        // На всякий — выдадим сопернику команду (если пришёл без неё)
        rb_givePokemons($mysqli, $opponentId);

        // Обновляем инфу
        $userInfo     = $mysqli->query("SELECT id, login, user_group, location FROM users WHERE id = ".(int)$userId)->fetch_assoc();
        $opponentInfo = $mysqli->query("SELECT id, login, user_group, location FROM users WHERE id = ".(int)$opponentId)->fetch_assoc();

        $info_1 = Info::_userInfoBattle($userId, 'pvp', ['uinfo' => $userInfo]);
        $info_2 = Info::_userInfoBattle($opponentId, 'pvp', ['uinfo' => $opponentInfo]);
        if (empty($info_1) || empty($info_2)) {
            $response['question']    = 'Ошибка: не удалось получить покемонов для боя.';
            $response['closeDialog'] = true;
            $response['rb_state']    = 'idle';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $info_1 = Info::_parseData($info_1);
        $info_2 = Info::_parseData($info_2);

        $locationId    = (int)$userInfo['location'];
        $locationData  = $mysqli->query("SELECT region, img_fight, weather FROM base_location WHERE id = $locationId")->fetch_assoc();
        $regionWeather = $mysqli->query("SELECT weather FROM base_region WHERE id = '".(int)$locationData['region']."'")->fetch_assoc();

        $weather       = ((int)$locationData['weather'] !== 0) ? (int)$locationData['weather'] : (int)$regionWeather['weather'];
        $imgFight      = $locationData['img_fight'];
        $weather_round = 10;

        $sql = "INSERT INTO `battle`
            (`round`,`user_1`,`user_2`,`info_1`,`info_2`,`type`,`weather`,`weather_round`,`img`,`arena`)
            VALUES (
                1,
                ".(int)$userId.",
                ".(int)$opponentId.",
                '".$mysqli->real_escape_string($info_1)."',
                '".$mysqli->real_escape_string($info_2)."',
                'pvp',
                '".$weather."',
                '".$weather_round."',
                '".$mysqli->real_escape_string($imgFight)."',
                '".$arenaLocationID."'
            )";
        $mysqli->query($sql);
        $battle_id = (int)$mysqli->insert_id;

        // Стартовый лог
        $mysqli->query("INSERT INTO `battle_log` (`battle`,`round`,`text`,`end`,`user`,`starter`)
                        VALUES ($battle_id, 0, 'Начало боя.<br>', 0, 0, 1)");

        // Проставим статус оба в бой
        $mysqli->query("UPDATE users SET status = 'battle', status_id = $battle_id WHERE id = ".(int)$userId);
        $mysqli->query("UPDATE users SET status = 'battle', status_id = $battle_id WHERE id = ".(int)$opponentId);

        $response['question']    = 'Соперник найден, бой начинается!';
        $response['battle_id']   = $battle_id;
        $response['closeDialog'] = true;

        // Кнопка на карте нам больше не нужна (идёт бой), но формально — уже не очередь
        $response['rb_state']    = 'idle';

    } else {
        // Никого нет — встаём в очередь
        $already = $mysqli->query("SELECT COUNT(*) c FROM rb_queue WHERE user_id = ".(int)$userId)->fetch_assoc();
        if (empty($already['c'])) {
            $mysqli->query("INSERT INTO rb_queue (user_id, created_at) VALUES (".(int)$userId.", '".$mysqli->real_escape_string($now)."')");
        }

        $response['question']    = 'Команда выдана, вы встали в очередь РБ. Окно закрыто — ожидайте авто-старт.';
        $response['closeDialog'] = true;
        $response['rb_state']    = 'queued'; // <<< ВАЖНО: сообщаем фронту, что теперь режим ожидания
    }

} elseif ($npcStep == 3) {
    // Отказ / выход из очереди
    rb_removePokemons($mysqli, $userId);
    $mysqli->query("DELETE FROM rb_queue WHERE user_id = ".(int)$userId);
    $mysqli->query("UPDATE users SET status = 'free', status_id = 0 WHERE id = ".(int)$userId);

    $response['question']    = 'Вы отказались от участия. Команда удалена.';
    $response['closeDialog'] = true;
    $response['rb_state']    = 'idle'; // <<< ВАЖНО: фронту сигнал переключить кнопку на "молнию"

} else {
    $response['question']    = 'Ошибка #1';
    $response['closeDialog'] = true;

    // По умолчанию считаем, что не в очереди
    $response['rb_state']    = 'idle';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
