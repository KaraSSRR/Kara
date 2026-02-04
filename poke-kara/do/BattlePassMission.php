<?php
// Сдача покемонов по миссиям БП без NPC 55
// POST: action=pokelist|submit

header('Content-Type: application/json; charset=utf-8');
session_start();

// Надёжное определение корня проекта
$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
if ($root === '' || !is_dir($root.'/inc/conf')) {
    // fallback: /do -> корень на уровень выше
    $root = realpath(__DIR__.'/..');
}

if (!$root || !file_exists($root.'/inc/conf/global.php')) {
    echo json_encode(['error' => 'Ошибка: Путь к проекту не задан [Global::3].']); exit;
}

require_once $root.'/inc/conf/global.php';
if (file_exists($root.'/inc/function/Functions.php')) {
    require_once $root.'/inc/function/Functions.php';
}

$user_id = intval($_SESSION['id'] ?? 0);
if (!$user_id) { echo json_encode(['error'=>'Ошибка авторизации']); exit; }

// Утилиты
function mapGender($g) {
    if ($g === 'male') return 'Мальчик';
    if ($g === 'female') return 'Девочка';
    return $g;
}
function secondsToShortHuman($sec) {
    if ($sec <= 0) return '0с';
    $d = floor($sec / 86400); $sec %= 86400;
    $h = floor($sec / 3600);  $sec %= 3600;
    $m = floor($sec / 60);
    if ($d > 0) return $d.'д '.$h.'ч';
    if ($h > 0) return $h.'ч '.$m.'м';
    return $m.'м';
}

$action = $_POST['action'] ?? '';

if ($action === 'pokelist') {
    $mission_id = intval($_POST['mission_id'] ?? 0);
    if (!$mission_id) { echo json_encode(['error'=>'Некорректная миссия']); exit; }

    $mis = $mysqli->query("
        SELECT um.id AS um_id, um.user_id, um.season_id, um.done, um.expired,
               m.id AS mission_id, m.type, m.text, m.exp, m.count_max,
               m.target_type, m.target_id, m.poke_level, m.poke_gender, m.poke_date_from
        FROM aa_battle_pass_user_mission um
        JOIN aa_battle_pass_mission m ON m.id = um.mission_id
        WHERE um.id = {$mission_id}
          AND um.user_id = {$user_id}
          AND um.done = 0
          AND um.expired = 0
          AND m.target_type = 'poke'
        LIMIT 1
    ")->fetch_assoc();

    if (!$mis) { echo json_encode(['error'=>'Миссия не найдена или недоступна']); exit; }

    $gender = mapGender($mis['poke_gender']);
    $sql = "SELECT id, basenum, name_new, lvl, gender, birthday
            FROM user_pokemons
            WHERE user_id = {$user_id}
              AND basenum = ".intval($mis['target_id']);
    if (!empty($mis['poke_level']))  $sql .= " AND lvl = ".intval($mis['poke_level']);
    if (!empty($gender))             $sql .= " AND gender = '".$mysqli->real_escape_string($gender)."'";

    $res = $mysqli->query($sql);
    $list = [];
    if ($res) {
        while ($p = $res->fetch_assoc()) {
            // Фильтр по дате (если требуется)
            if (!empty($mis['poke_date_from'])) {
                $birthday = @json_decode($p['birthday'] ?? '', false);
                $b_user = is_object($birthday) && isset($birthday->user_id) ? (int)$birthday->user_id : $user_id;
                $b_date = is_object($birthday) && isset($birthday->date)    ? (int)$birthday->date    : PHP_INT_MAX;
                if (!($b_user === $user_id && $b_date > (int)$mis['poke_date_from'])) {
                    continue;
                }
            }
            $list[] = [
                'id'      => (int)$p['id'],
                'basenum' => (int)$p['basenum'],
                'name'    => (string)$p['name_new'],
                'lvl'     => (int)$p['lvl'],
                'gender'  => (string)$p['gender'],
                'img'     => '/img/pokemons/animation/'.$p['basenum'].'.png'
            ];
        }
    }

    if (!$list) {
        echo json_encode(['html'=>'<div class="bpNoPokes">Нет подходящих покемонов для сдачи по этой миссии.</div>', 'pokemons'=>[]], JSON_UNESCAPED_UNICODE);
    } else {
        ob_start();
        echo '<div class="bpPickList">';
        foreach ($list as $pk) {
            echo '<div class="bpPickItem" style="display:flex;align-items:center;gap:8px;margin:6px 0;">
                    <img src="'.$pk['img'].'" class="bpPickImg" style="height:40px">
                    <div class="bpPickTxt" style="flex:1 1 auto;">'.htmlspecialchars($pk['name']).' (ур. '.$pk['lvl'].', '.($pk['gender']=='Мальчик'?'♂':'♀').')</div>
                    <button class="bpPickBtn" onclick="bpMissionSubmit('.$mission_id.','.$pk['id'].')" style="padding:6px 10px;border:0;border-radius:8px;background:#6d6de3;color:#fff;cursor:pointer;">Сдать</button>
                  </div>';
        }
        echo '</div>';
        $html = ob_get_clean();
        echo json_encode(['html'=>$html, 'pokemons'=>$list], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($action === 'submit') {
    $mission_id  = intval($_POST['mission_id'] ?? 0);
    $selected_id = intval($_POST['selected_id'] ?? 0);
    if (!$mission_id || !$selected_id) { echo json_encode(['error'=>'Некорректные параметры']); exit; }

    try {
        $mysqli->begin_transaction();

        // Блокируем миссию пользователя
        $mis = $mysqli->query("
            SELECT um.id AS um_id, um.user_id, um.season_id, um.done, um.expired, um.progress,
                   m.id AS mission_id, m.type, m.text, m.exp, m.count_max,
                   m.target_type, m.target_id, m.poke_level, m.poke_gender, m.poke_date_from
            FROM aa_battle_pass_user_mission um
            JOIN aa_battle_pass_mission m ON m.id = um.mission_id
            WHERE um.id = {$mission_id}
              AND um.user_id = {$user_id}
              AND um.done = 0
              AND um.expired = 0
              AND m.target_type = 'poke'
            FOR UPDATE
        ")->fetch_assoc();

        if (!$mis) { $mysqli->rollback(); echo json_encode(['error'=>'Миссия не найдена или недоступна']); exit; }

        // Проверяем выбранного покемона и блокируем строку
        $gender = mapGender($mis['poke_gender']);
        $sql = "SELECT * FROM user_pokemons
                WHERE id = {$selected_id}
                  AND user_id = {$user_id}
                  AND basenum = ".intval($mis['target_id'])."
                FOR UPDATE";
        if (!empty($mis['poke_level']))  $sql .= " AND lvl = ".intval($mis['poke_level']);
        if (!empty($gender))             $sql .= " AND gender = '".$mysqli->real_escape_string($gender)."'";

        $pk = $mysqli->query($sql)->fetch_assoc();
        if (!$pk) { $mysqli->rollback(); echo json_encode(['error'=>'Покемон не подходит под условия миссии']); exit; }

        // Доп. проверка по дате
        if (!empty($mis['poke_date_from'])) {
            $birthday = @json_decode($pk['birthday'] ?? '', false);
            $b_user = is_object($birthday) && isset($birthday->user_id) ? (int)$birthday->user_id : $user_id;
            $b_date = is_object($birthday) && isset($birthday->date)    ? (int)$birthday->date    : PHP_INT_MAX;
            if (!($b_user === $user_id && $b_date > (int)$mis['poke_date_from'])) {
                $mysqli->rollback(); echo json_encode(['error'=>'Этот покемон не подходит по дате получения']); exit;
            }
        }

        // Удаляем покемона
        $mysqli->query("DELETE FROM user_pokemons WHERE id = ".(int)$pk['id']." AND user_id = {$user_id} LIMIT 1");

        // Обновляем прогресс
        $mysqli->query("UPDATE aa_battle_pass_user_mission SET progress = progress + 1 WHERE id = {$mission_id} AND user_id = {$user_id}");
        $mis2 = $mysqli->query("SELECT progress, count_max FROM aa_battle_pass_user_mission WHERE id = {$mission_id} FOR UPDATE")->fetch_assoc();

        if ($mis2 && (int)$mis2['progress'] >= (int)$mis['count_max']) {
            // Закрываем миссию и начисляем EXP БП
            $mysqli->query("UPDATE aa_battle_pass_user_mission SET done = 1 WHERE id = {$mission_id} AND user_id = {$user_id}");
            $mysqli->query("UPDATE aa_battle_pass_user SET exp_me = exp_me + ".intval($mis['exp'])." WHERE user = {$user_id} AND season_id = ".intval($mis['season_id']));
            $mysqli->query("INSERT INTO aa_battle_pass_history (user_id, season_id, event, event_id, info) VALUES ({$user_id}, ".intval($mis['season_id']).", 'mission_complete', {$mission_id}, 'Завершено: ". $mysqli->real_escape_string($mis['text'])."')");
            $mysqli->commit();
            echo json_encode(['ok'=>true, 'message'=>"Покемон сдан. Задание выполнено!"], JSON_UNESCAPED_UNICODE); exit;
        } else {
            $progress = $mis2 ? (int)$mis2['progress'] : 0;
            $mysqli->commit();
            echo json_encode(['ok'=>true, 'message'=>"Покемон сдан! Прогресс: {$progress} / ".(int)$mis['count_max']], JSON_UNESCAPED_UNICODE); exit;
        }

    } catch (Throwable $e) {
        if ($mysqli->errno) $mysqli->rollback();
        echo json_encode(['error'=>'Ошибка при сдаче: '.$e->getMessage()]); exit;
    }
}

echo json_encode(['error'=>'Unsupported action']);