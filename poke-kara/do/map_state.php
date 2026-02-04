<?php
// battle/map_state.php
// Возвращает HTML-содержимое карты боя для AJAX-обновления с расширенной информацией

require_once($_SERVER['DOCUMENT_ROOT'].'/rb/functions_rb.php');
session_start();

$userId = intval($_SESSION['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(403);
    echo '<div class="battle-error">Ошибка авторизации</div>';
    exit;
}

// Получаем id активного боя пользователя
$user = $mysqli->query("SELECT status, status_id FROM users WHERE id = $userId")->fetch_assoc();
if (!$user || $user['status'] !== 'battle' || !$user['status_id']) {
    echo '<div class="battle-error">Вы не в бою!</div>';
    exit;
}
$battleId = intval($user['status_id']);

// Получаем актуальное состояние боя
$battle = $mysqli->query("SELECT * FROM battle WHERE id = $battleId")->fetch_assoc();
if (!$battle) {
    echo '<div class="battle-error">Бой не найден</div>';
    exit;
}

// Получаем участников боя
$participants = [];
$result = $mysqli->query("SELECT u.id, u.login, u.team, b.side, b.active_pokemon_id 
                          FROM battle_users b 
                          JOIN users u ON u.id = b.user_id 
                          WHERE b.battle_id = $battleId");
while ($row = $result->fetch_assoc()) {
    $participants[$row['side']][] = $row;
}

// Получаем активных покемонов каждой стороны
$pokemons = [];
foreach ($participants as $side => $users) {
    foreach ($users as $userInfo) {
        $poke = $mysqli->query("SELECT p.id, p.name, p.hp, p.max_hp, p.status, p.level
                                FROM pokemons p
                                WHERE p.id = ".intval($userInfo['active_pokemon_id']))
                                ->fetch_assoc();
        if ($poke) {
            $pokemons[$side][] = [
                'player' => $userInfo['login'],
                'name' => $poke['name'],
                'level' => $poke['level'],
                'hp' => $poke['hp'],
                'max_hp' => $poke['max_hp'],
                'status' => $poke['status']
            ];
        }
    }
}

// Получаем лог последнего хода или состояния карты
$log = $mysqli->query("SELECT text FROM battle_log WHERE battle = $battleId ORDER BY round DESC, id DESC LIMIT 1")->fetch_assoc();

$mapHTML = '';
$mapHTML .= '<div class="battle-status">';
$mapHTML .= '<b>Раунд:</b> ' . intval($battle['round']) . '<br>';
$mapHTML .= '<b>Погода:</b> ' . htmlspecialchars($battle['weather']) . '<br>';
$mapHTML .= '</div>';

// Визуализация состояния сторон/покемонов
$mapHTML .= '<div class="battle-sides">';
foreach ($pokemons as $side => $list) {
    $mapHTML .= '<div class="battle-side"><b>' . htmlspecialchars(ucfirst($side)) . ':</b><ul>';
    foreach ($list as $p) {
        $hp_percent = $p['max_hp'] > 0 ? round($p['hp'] / $p['max_hp'] * 100) : 0;
        $mapHTML .= '<li>';
        $mapHTML .= '<span class="player">' . htmlspecialchars($p['player']) . '</span> — ';
        $mapHTML .= '<span class="pokemon">' . htmlspecialchars($p['name']) . ' (ур. ' . intval($p['level']) . ')</span> ';
        $mapHTML .= '<span class="hp">HP: ' . intval($p['hp']) . '/' . intval($p['max_hp']) . ' (' . $hp_percent . '%)</span>';
        if (!empty($p['status'])) {
            $mapHTML .= ' <span class="status">[' . htmlspecialchars($p['status']) . ']</span>';
        }
        $mapHTML .= '</li>';
    }
    $mapHTML .= '</ul></div>';
}
$mapHTML .= '</div>';

if ($log) {
    $mapHTML .= '<div class="battle-log">'.nl2br(htmlspecialchars($log['text'])).'</div>';
}

echo $mapHTML;
?>