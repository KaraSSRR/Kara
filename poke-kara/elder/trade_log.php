<style>
body {
    background: #f4f4f4; /* Светло-серый фон */
    color: #2d4f2f; /* Темно-зеленый текст */
    font-family: Arial, sans-serif;
    text-align: left;
    padding: 20px;
}

h2 {
    color: #228b22;
    text-align: center;
    font-size: 24px;
}

.search-container {
    text-align: center;
    margin-bottom: 20px;
}

#search-input {
    width: 60%;
    padding: 10px;
    font-size: 16px;
    border: 2px solid #228b22;
    border-radius: 5px;
    outline: none;
}

.tra {
    background: #ffffff;
    padding: 15px;
    margin: 10px 0;
    border-radius: 6px;
    border-left: 5px solid #228b22;
    box-shadow: 0 0 10px rgba(34, 139, 34, 0.2);
    transition: 0.3s;
}

.tra:hover {
    background: #e6ffe6;
    box-shadow: 0 0 15px rgba(34, 139, 34, 0.4);
}

.tra > span {
    text-align: center;
    display: block;
    font-size: 20px;
    font-weight: bold;
    color: #228b22;
}

.itemIsset {
    display: inline-block;
    width: 50px;
    height: 50px;
    background-size: cover;
    margin-right: 10px;
}

.poke-id {
    cursor: pointer;
    color: #228b22;
    font-weight: bold;
    text-decoration: underline;
}

.poke-id:hover {
    color: #2d6b2f;
}

#pokemon-info {
    display: none;
    background: #e6ffe6;
    padding: 15px;
    border-radius: 6px;
    border: 2px solid #228b22;
    margin-top: 10px;
}
</style>
<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project . '/inc/conf/global.php';
$patch_func = $patch_project . '/inc/function/Functions.php';

if (!file_exists($patch_global) || !file_exists($patch_func)) {
    die('<div class="tra"><span>Ошибка</span> Файл конфигурации не найден.</div>');
}

require_once($patch_global);
require_once($patch_func);

if (!isset($mysqli) || !$mysqli) {
    die('<div class="tra"><span>Ошибка</span> Подключение к базе данных не удалось.</div>');
}
// Проверка доступа по ID (только для пользователя с id=4)
session_start();
if (!isset($_SESSION['id']) || $_SESSION['id'] != 4) {
    http_response_code(403);
    die('<div style="margin:60px auto;max-width:420px;padding:36px 22px 30px 22px;background:#fff6;border-radius:19px;box-shadow:0 6px 32px #7050c022;font-family:Nunito,Arial,sans-serif;text-align:center;">
        <span style="display:block;font-size:3.4em;line-height:1;color:#caa2e6;">⛔</span>
        <div style="font-size:1.25em;color:#a184ca;font-weight:bold;margin:9px 0 13px 0;">Доступ запрещён</div>
        <div style="color:#8160a0;font-size:1em;">У вас нет прав для просмотра этой страницы.</div>
        </div>');
}

// Фильтр поиска
$searchQuery = "";
if (!empty($_GET['search'])) {
    $search = $mysqli->real_escape_string($_GET['search']);
    $searchQuery = "AND (users.login LIKE '%$search%' OR log_game.info LIKE '%$search%')";
}

$trade = $mysqli->query("SELECT log_game.* FROM log_game 
                         INNER JOIN users ON log_game.user_id = users.id
                         WHERE log_game.type = 'trade' $searchQuery
                         ORDER BY log_game.id DESC");

if (!$trade) {
    die('<div class="tra"><span>Ошибка SQL</span> ' . htmlspecialchars($mysqli->error) . '</div>');
}

?>

<!-- CSS -->
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    text-align: center;
}

.container {
    width: 80%;
    margin: auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0px 0px 10px rgba(0, 128, 0, 0.3);
}

h2 {
    color: #2e7d32;
}

.tra {
    background: #e8f5e9;
    border: 2px solid #2e7d32;
    padding: 10px;
    margin: 10px 0;
    border-radius: 6px;
    box-shadow: 0px 0px 5px rgba(0, 128, 0, 0.2);
    text-align: left;
}

.poke-id {
    color: #2e7d32;
    cursor: pointer;
    font-weight: bold;
}

.poke-id:hover {
    text-decoration: underline;
}

.search-box {
    margin-bottom: 15px;
    padding: 5px;
    width: 300px;
    border: 1px solid #2e7d32;
    border-radius: 4px;
}
</style>

<div class="container">
    <h2>История обменов</h2>

    <!-- Поле поиска -->
    <input type="text" id="search" class="search-box" placeholder="Поиск по логину или обмену">
    <button onclick="searchTrade()">🔍 Поиск</button>

    <div id="trade-list">
        <?php
        if ($trade->num_rows === 0) {
            echo '<div class="tra"><span>Нет данных</span> В таблице `log_game` нет записей с `type = "trade"`.</div>';
        } else {
            while ($log = $trade->fetch_assoc()) {
                $info1 = json_decode($log['info'], true);

                if (!is_array($info1) || !isset($info1['user_to']) || !isset($info1['objects'])) {
                    echo '<div class="tra"><span>Ошибка JSON</span> Некорректные данные в записи #'.intval($log['id']).': '.htmlspecialchars($log['info']).'</div>';
                    continue;
                }

                $user1 = $mysqli->query('SELECT login FROM users WHERE id = ' . intval($log['user_id']))->fetch_assoc();
                $user2 = $mysqli->query('SELECT login FROM users WHERE id = ' . intval($info1['user_to']))->fetch_assoc();

                if (!$user1 || !$user2) {
                    echo '<div class="tra"><span>Ошибка</span> Пользователи не найдены.</div>';
                    continue;
                }

                echo '<div class="tra">';
                echo '<span><b>Обмен #'.intval($log['id']).'</b></span><br>';

                foreach ($info1['objects'] as $val) {
                    if (!isset($val['type'], $val['id'], $val['name'], $val['count'])) {
                        echo '<div class="tra"><span>Ошибка данных</span> Некорректные данные объекта в записи #'.intval($log['id']).'</div>';
                        continue;
                    }

                    if ($val['type'] === 'poke') {
                        $pokeImage = "/img/pokemons/animation/".$val['number'].".png";
                        echo "<div class='itemIsset' style='background-image: url($pokeImage);'></div> 
                              <b>#".htmlspecialchars($val['number'])." ".htmlspecialchars($val['name'])."</b>
                              <span class='poke-id' onclick='showPokemonInfo(".intval($val['id']).")'> (ID: ".intval($val['id']).")</span>";
                    } elseif ($val['type'] === 'item') {
                        $itemImage = "/img/world/items/little/".$val['id'].".png";
                        echo "<div class='itemIsset' style='background-image: url($itemImage);'></div> 
                              <b>".htmlspecialchars($val['name'])."</b> x".intval($val['count']);
                    }

                    echo " - передал <b>".htmlspecialchars($user2['login'])."</b> тренеру <b>".htmlspecialchars($user1['login'])."</b><br>";
                }

                echo '</div>';
            }
        }
        ?>
    </div>
</div>

<!-- Блок для информации о покемоне -->
<div id="pokemon-info"></div>

<!-- JavaScript -->
<script>
function showPokemonInfo(pokeId) {
    fetch('/get_pokemon_info.php?id=' + pokeId)
        .then(response => response.text())
        .then(data => {
            document.getElementById('pokemon-info').innerHTML = data;
            document.getElementById('pokemon-info').style.display = "block";
        })
        .catch(error => console.error('Ошибка:', error));
}

// Поиск по логам обмена
function searchTrade() {
    let searchValue = document.getElementById('search').value;
    window.location.href = '?search=' + encodeURIComponent(searchValue);
}
</script>
