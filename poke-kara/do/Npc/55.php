<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/conf/global.php';

$npcId = 55;
$query = $mysqli->query("SELECT `name`, `image` FROM `base_npc` WHERE `id` = $npcId");
$npc = $query->fetch_assoc();

$response = [];
$response['name'] = $npc ? $npc['name'] : 'Неизвестный NPC';
$response['image'] = $npc ? htmlspecialchars($npc['image']) : '/img/default-npc.png';

$user_id = intval($_SESSION['id'] ?? 0);
if (!$user_id) {
    $response['error'] = 'Ошибка авторизации!';
    echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
}

$season_id = 0;
// Активный сезон (как в battlepass.php)
$qSeason = $mysqli->query("SELECT id FROM aa_battle_pass_season WHERE is_active = 1 LIMIT 1");
if ($qSeason) {
    $r = $qSeason->fetch_assoc();
    if ($r && isset($r['id'])) $season_id = (int)$r['id'];
}
// Fallback: последний сезон пользователя (если активный не найден)
if (!$season_id) {
    $qUserSeason = $mysqli->query("SELECT season_id FROM aa_battle_pass_user WHERE user = {$user_id} ORDER BY season_id DESC LIMIT 1");
    if ($qUserSeason) {
        $r = $qUserSeason->fetch_assoc();
        if ($r && isset($r['season_id'])) $season_id = (int)$r['season_id'];
    }
}
if (!$season_id) $season_id = 0;

// Получаем параметры шага и выбранных объектов
$npcStep     = intval($_POST['step'] ?? 0);
$mission_id  = intval($_POST['mission_id'] ?? 0);
$selected_id = intval($_POST['selected_id'] ?? 0);

// --- КОСТЫЛЬ: если step = id миссии, значит это выбор миссии!
if ($npcStep > 3 && $mission_id == 0) {
    $mission_id = $npcStep;
    $npcStep = 1;
}

// === 0. Приветствие и выбор миссии (только миссии на покемонов)
if ($npcStep === 0) {
    $response['question'] = 'Привет! Выбери миссию для сдачи:';
    $q = $mysqli->query("
        SELECT um.id, m.text
        FROM aa_battle_pass_user_mission um
        JOIN aa_battle_pass_mission m ON m.id = um.mission_id
        WHERE um.user_id = $user_id
          AND um.season_id = $season_id
          AND um.expired = 0
          AND um.done = 0
          AND m.target_type = 'poke'
          AND (um.expires_at IS NULL OR um.expires_at > NOW())
    ");
    $missions = [];
    while ($row = $q->fetch_assoc()) $missions[$row['id']] = $row['text'];
    if (!$missions) {
        $response['question'] = 'Нет активных миссий для сдачи покемонов.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }
    $response['answer'] = $missions;
    echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
}

// === 1. Показываем выбор сдаваемого покемона
if ($npcStep === 1 && $mission_id) {
    $mis = $mysqli->query("
        SELECT um.*, m.text, m.target_type, m.target_id, m.poke_level, m.poke_gender, m.poke_date_from, m.count_max, m.exp, m.season_id
        FROM aa_battle_pass_user_mission um
        JOIN aa_battle_pass_mission m ON m.id = um.mission_id
        WHERE um.id = $mission_id
          AND um.user_id = $user_id
          AND um.season_id = $season_id
          AND um.expired = 0
          AND um.done = 0
          AND m.target_type = 'poke'
        LIMIT 1
    ")->fetch_assoc();
    if (!$mis) {
        $response['question'] = 'Миссия не найдена или уже сдана!';
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }
    ob_start();

    // --- gender mapping для user_pokemons (БД) и миссии
    $gender_map = ['male' => 'Мальчик', 'female' => 'Девочка'];
    $poke_gender = $mis['poke_gender'];
    if (isset($gender_map[$poke_gender])) $poke_gender = $gender_map[$poke_gender];

    $sql = "SELECT id, basenum, name_new, lvl, gender, birthday FROM user_pokemons WHERE user_id = $user_id AND basenum = ".intval($mis['target_id']);
    if ($mis['poke_level'])  $sql .= " AND lvl = ".intval($mis['poke_level']);
    if ($poke_gender) $sql .= " AND gender = '".addslashes($poke_gender)."'";
    $list = $mysqli->query($sql);
    $found = false;
    if ($list && $list->num_rows) {
        echo '<div>Выберите покемона для сдачи:</div>';
        while($p = $list->fetch_assoc()) {
            $match = true;
            if ($mis['poke_date_from'] && $p['birthday']) {
                $birthday = @json_decode($p['birthday']);
                if (!isset($birthday->date) || $birthday->date < intval($mis['poke_date_from'])) {
                    $match = false;
                }
            }
            if ($match) {
                $found = true;
                echo '<div style="margin:6px 0;">
                    <img src="/img/pokemons/animation/'.$p['basenum'].'.png" style="height:34px;vertical-align:middle;">
                    '.$p['name_new'].' (ур. '.$p['lvl'].', '.($p['gender']=='Мальчик'?'♂':'♀').')
                    <button onclick="npcBattlePassSubmit('.$mis['id'].', '.$p['id'].')">Сдать этого</button>
                </div>';
            }
        }
    }
    if (!$found) {
        echo 'У вас нет подходящих покемонов для сдачи!';
    }
    $response['question'] = ob_get_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
}

// === 2. Сдача покемона
if ($npcStep === 2 && $mission_id && $selected_id) {
    $mis = $mysqli->query("
        SELECT um.*, m.text, m.target_type, m.target_id, m.poke_level, m.poke_gender, m.poke_date_from, m.count_max, m.exp, m.season_id
        FROM aa_battle_pass_user_mission um
        JOIN aa_battle_pass_mission m ON m.id = um.mission_id
        WHERE um.id = $mission_id
          AND um.user_id = $user_id
          AND um.season_id = $season_id
          AND um.expired = 0
          AND um.done = 0
          AND m.target_type = 'poke'
        LIMIT 1
    ")->fetch_assoc();
    if (!$mis) {
        $response['question'] = 'Миссия не найдена или уже сдана!';
        echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
    }

    // gender mapping для user_pokemons (БД) и миссии
    $gender_map = ['male' => 'Мальчик', 'female' => 'Девочка'];
    $poke_gender = $mis['poke_gender'];
    if (isset($gender_map[$poke_gender])) $poke_gender = $gender_map[$poke_gender];

    $sql = "SELECT * FROM user_pokemons WHERE id = $selected_id AND user_id = $user_id AND basenum = ".intval($mis['target_id']);
    if ($mis['poke_level'])  $sql .= " AND lvl = ".intval($mis['poke_level']);
    if ($poke_gender) $sql .= " AND gender = '".addslashes($poke_gender)."'";
    $pk_bd = $mysqli->query($sql)->fetch_assoc();
    $ok = false;
    if ($pk_bd) {
        $birthday = @json_decode($pk_bd['birthday']);
        if (isset($birthday->user_id) && $birthday->user_id == $user_id) {
            if (!$mis['poke_date_from'] || (isset($birthday->date) && $birthday->date > $mis['poke_date_from'])) {
                $ok = true;
            }
        }
    }
    if ($ok) {
        // Удаляем покемона и обновляем прогресс
        $mysqli->query("DELETE FROM user_pokemons WHERE id = ".$pk_bd['id']." AND user_id = $user_id LIMIT 1");
        $mysqli->query("UPDATE aa_battle_pass_user_mission SET progress = progress + 1 WHERE id = $mission_id AND user_id = $user_id");
        $mis2 = $mysqli->query("SELECT progress FROM aa_battle_pass_user_mission WHERE id = $mission_id AND user_id = $user_id LIMIT 1")->fetch_assoc();
        $need = (int)$mis['count_max'];
        $prog = (int)($mis2['progress'] ?? 0);
        if ($prog >= $need) {
            $mysqli->query("UPDATE aa_battle_pass_user_mission SET done = 1 WHERE id = $mission_id AND user_id = $user_id");
            $mysqli->query("UPDATE aa_battle_pass_user SET exp_me = exp_me + ".intval($mis['exp'])." WHERE user = $user_id AND season_id = $season_id");
            $response['question'] = "Я забрал покемона и засчитал тебе задание. Поздравляю! Проверь страничку заданий.";
        } else {
            $response['question'] = "Я забрал покемона! Прогресс: ".$prog." / ".$need;
        }
    } else {
        $response['question'] = 'У тебя нет подходящего покемона в команде!';
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;
}

// По умолчанию — ошибка
$response['question'] = 'Ошибка. Попробуйте снова.';
echo json_encode($response, JSON_UNESCAPED_UNICODE); exit;