<?php
header('Content-Type: text/html; charset=utf-8'); // Устанавливаем правильную кодировку

require_once($_SERVER['DOCUMENT_ROOT'] . '/inc/conf/global.php');

if (!isset($mysqli) || !$mysqli) {
    die('<div class="tra"><span>Ошибка</span> Подключение к базе данных не удалось.</div>');
}

// Проверка на корректность ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('<div class="tra"><span>Ошибка</span> Некорректный ID покемона.</div>');
}

$pokemonId = intval($_GET['id']);
$pokemon = $mysqli->query("SELECT us.*, bp.name_rus FROM user_pokemons AS us 
                           INNER JOIN base_pokemons AS bp ON bp.id = us.basenum 
                           WHERE us.id = $pokemonId")->fetch_assoc();

if (!$pokemon) {
    die('<div class="tra"><span>Ошибка</span> Покемон не найден.</div>');
}

// Определяем пол покемона
$genderIcon = $pokemon['gender'] === 'Мальчик' ? '♂️' : ($pokemon['gender'] === 'Девочка' ? '♀️' : '⚪');

// Определяем статус обмена
$tradeStatus = $pokemon['trade'] == "false" ? '<i class="fas fa-lock"></i> Запрещен' : '<i class="fas fa-check"></i> Разрешен';

// Определяем уровень тренировки
$trainingLevels = [
    0 => "Нет",
    1 => "Начальная",
    2 => "Расширенная",
    3 => "Мастерская",
    4 => "Знаменитая",
    5 => "Легендарная",
    6 => "Именная"
];
$trainingStatus = isset($trainingLevels[$pokemon['tren']]) ? $trainingLevels[$pokemon['tren']] : "Неизвестно";

// Определяем картинку покемона
$pokemonImage = "/img/pokemons/animation/" . $pokemon['basenum'] . ".png";

// Выводим информацию
echo "<div class='pokemon-info-card'>
        <h3>Обмен #{$pokemon['id']}</h3>
        <div class='pokemon-display'>
            <img src='{$pokemonImage}' alt='{$pokemon['name_rus']}' class='pokemon-image'>
            <div class='pokemon-details'>
                <b>#{$pokemon['basenum']} {$pokemon['name_rus']}</b><br>
                <span>Пол: {$genderIcon}</span><br>
                <span>Уровень: {$pokemon['lvl']}</span><br>
                <span>Обмен: {$tradeStatus}</span><br>
                <span>Тренировка: {$trainingStatus}</span><br>
                <span>Опыт: {$pokemon['exp']} / {$pokemon['exp_max']}</span><br>
                <span>Характер: {$pokemon['character']}</span><br>
                <span>Статистика: {$pokemon['stats']}</span><br>
                <span>ID в базе: <b>{$pokemon['id']}</b></span>
            </div>
        </div>
      </div>";
?>

<!-- CSS -->
<style>
.pokemon-info-card {
    background: #e8f5e9;
    border: 2px solid #2e7d32;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    box-shadow: 0px 0px 10px rgba(46, 125, 50, 0.3);
    width: 80%;
    margin: auto;
}

.pokemon-display {
    display: flex;
    align-items: center;
    justify-content: center;
}

.pokemon-image {
    width: 80px;
    height: 80px;
    margin-right: 15px;
}

.pokemon-details {
    text-align: left;
}

.pokemon-details span {
    display: block;
    font-size: 14px;
    color: #2e7d32;
}
</style>
