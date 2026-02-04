<?php
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
        require_once($patch_global);
    }
}

$type = escapeMe($_POST['type']);
$user = $mysqli->query('SELECT * FROM users WHERE id = '.$_SESSION['id'])->fetch_assoc();
$response = [];

// Только для user id = 4 (админ)
if ($user['id'] != 4) {
    $response['html'] = '<div class="error">У вас нет доступа к этому разделу.</div>';
    echo json_encode($response);
    exit;
}

switch($type) {
    case "panel":
        // Основная панель администратора
        $tpl = '<div class="Header">
                    <div class="Name">Панель администратора</div>
                    <div class="Close" onclick="closeLittleModal()"><i class="fas fa-times"></i></div>
                </div>
                <div class="content admin-panel-content">
                    <div class="admin-panel-row">
                        <button class="btn" onclick="sendAdminCommand(\'tpGym\')">🔀 Телепорт у гім</button>
                        <button class="btn" onclick="editGymText()">📝 Редагувати гім</button>
                    </div>
                    <div class="admin-panel-row">
                        <button class="btn" onclick="giveEgg()">🥚 Видати яйце</button>
                        <button class="btn" onclick="givePokemon()">🎁 Видати покемона</button>
                        <button class="btn" onclick="giveTrophy()">🏆 Видати трофей</button>
                    </div>
                    <div class="admin-panel-row">
                        <button class="btn" onclick="addTournament()">📅 Додати турнір</button>
                        <button class="btn" onclick="updateTourPoints()">➕ Додати очки туру</button>
                    </div>
                </div>
                <script>
                // JS-функции для админ-панели (inline для LittleModal)
                function sendAdminCommand(type, extra = []) {
                    $.post("/do/DolznPanel.php", { type, val: extra }, function(res) {
                        alert((res && res.response && res.response.text) ? res.response.text : "Невідомо");
                    }, "json");
                }
                function editGymText() {
                    const newText = prompt("Введіть новий текст для гіму:");
                    if (newText) sendAdminCommand("textGym", [newText]);
                }
                function giveEgg() {
                    const login = prompt("Кому видати яйце? (введіть логін)");
                    if (login) sendAdminCommand("giveEgg", [login]);
                }
                function givePokemon() {
                    const login = prompt("Логін гравця:");
                    const pokeId = prompt("ID покемона:");
                    if (login && pokeId) sendAdminCommand("givePok", [login, pokeId]);
                }
                function giveTrophy() {
                    const login = prompt("Логін гравця:");
                    const trophyId = prompt("ID трофею:");
                    if (login && trophyId) sendAdminCommand("giveTrophy", [login, trophyId]);
                }
                function addTournament() {
                    const name = prompt("Назва турніру");
                    const lvl = prompt("Рівень");
                    const count = prompt("Кількість учасників");
                    sendAdminCommand("addTur", [name, lvl, count, "", "", "", "", "", "", ""]);
                }
                function updateTourPoints() {
                    const login = prompt("Гравець");
                    const points = prompt("Кількість очок");
                    sendAdminCommand("turScore", [login, points]);
                }
                </script>
                <style>
                .admin-panel-content {
                    display: flex;
                    flex-direction: column;
                    gap: 18px;
                    padding: 12px 4px 2px 4px;
                }
                .admin-panel-row {
                    display: flex;
                    gap: 12px;
                    flex-wrap: wrap;
                    justify-content: flex-start;
                }
                .admin-panel-content .btn {
                    padding: 12px 18px;
                    font-size: 15px;
                    border: none;
                    background: linear-gradient(90deg,#e0e0e6 80%,#f4f2fa 100%);
                    border-radius: 7px;
                    cursor: pointer;
                    font-family: inherit;
                    font-weight: 600;
                    color: #4a5799;
                    box-shadow: 0 1.5px 8px #e2e6ff33;
                    letter-spacing: 0.01em;
                    transition: background 0.17s, color 0.14s, box-shadow 0.17s;
                }
                .admin-panel-content .btn:hover,
                .admin-panel-content .btn:focus {
                    background: linear-gradient(90deg, #7b6ee6 0%, #4a90e2 100%);
                    color: #fff;
                    box-shadow: 0 4px 18px 0 #bba8e8, 0 2px 14px #f5f3ff44;
                    outline: none;
                }
                </style>
        ';
        $response['html'] = $tpl;
        break;

    // Примеры обработки команд — можно расширять
    case "tpGym":
        // Здесь должна быть логика телепортации в гим
        $response['response']['text'] = "Телепорт выполнен.";
        break;
    case "textGym":
        // Пример: сохранить текст гим-лидера
        $newText = isset($_POST['val'][0]) ? escapeMe($_POST['val'][0]) : '';
        // Пример сохранения $newText в БД (логика зависит от вашей БД)
        $response['response']['text'] = "Текст гіма оновлено: $newText";
        break;
    case "giveEgg":
        // Пример: выдача яйца игроку
        $login = isset($_POST['val'][0]) ? escapeMe($_POST['val'][0]) : '';
        // Тут ваша логика выдачи
        $response['response']['text'] = "Яйце выдано игроку $login";
        break;
    case "givePok":
        $login = isset($_POST['val'][0]) ? escapeMe($_POST['val'][0]) : '';
        $pokeId = isset($_POST['val'][1]) ? intval($_POST['val'][1]) : 0;
        // Тут ваша логика выдачи покемона
        $response['response']['text'] = "Покемон ID $pokeId выдан игроку $login";
        break;
    case "giveTrophy":
        $login = isset($_POST['val'][0]) ? escapeMe($_POST['val'][0]) : '';
        $trophyId = isset($_POST['val'][1]) ? intval($_POST['val'][1]) : 0;
        // Тут ваша логика выдачи трофея
        $response['response']['text'] = "Трофей ID $trophyId выдан игроку $login";
        break;
    case "addTur":
        $name = isset($_POST['val'][0]) ? escapeMe($_POST['val'][0]) : '';
        $lvl = isset($_POST['val'][1]) ? escapeMe($_POST['val'][1]) : '';
        $count = isset($_POST['val'][2]) ? escapeMe($_POST['val'][2]) : '';
        // Тут ваша логика добавления турнира
        $response['response']['text'] = "Турнир $name (уровень $lvl, участников $count) добавлен.";
        break;
    case "turScore":
        $login = isset($_POST['val'][0]) ? escapeMe($_POST['val'][0]) : '';
        $points = isset($_POST['val'][1]) ? escapeMe($_POST['val'][1]) : '';
        // Тут ваша логика добавления очков турнира
        $response['response']['text'] = "Игроку $login начислено $points очков в турнире.";
        break;
    default:
        $response['html'] = "Неизвестный запрос";
        $response['error'] = "error";
        break;
}

echo json_encode($response);
?>