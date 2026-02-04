<?php
// tournament_admin_panel.php
// Панель управления турнирами для куратора (user_group == 10)

session_start();
require_once 'init.php'; // Основной инит (DB, $user и т.д.)

// Проверка CSRF токена для всех POST-запросов
function checkCSRF() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['html' => 'Ошибка безопасности: неверный CSRF токен', 'error' => 'error']);
        exit;
    }
}

// Функция для логирования действий администратора
function logAdminAction($action, $details = '') {
    global $mysqli, $user;
    $action = escapeMe($action);
    $details = escapeMe($details);
    $admin_id = (int)$user['id'];
    $ip = escapeMe($_SERVER['REMOTE_ADDR']);
    $time = time();
    
    $mysqli->query("INSERT INTO admin_logs (admin_id, action, details, ip, timestamp) 
                   VALUES ($admin_id, '$action', '$details', '$ip', $time)");
}

// Генерация CSRF токена, если его нет
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header('Content-Type: application/json; charset=utf-8');

// Проверка авторизации и прав
if (!isset($user['user_group']) || $user['user_group'] != 10) {
    echo json_encode(['html' => "<div class='st-empty'>Нет доступа к панели управления турнирами.</div>"]);
    exit;
}

// ОБРАБОТКА POST-ЗАПРОСОВ (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Дополнительная защита для всех операций изменения данных
    if (in_array($action, ['add_tournament', 'set_active', 'set_place', 'set_result', 'give_medal', 'finish_tournament'])) {
        checkCSRF();
    }

    switch ($action) {
        case 'add_tournament':
            $name = escapeMe($_POST['name']);
            $description = escapeMe($_POST['description'] ?? '');
            $start_time = strtotime($_POST['start_time']);
            $end_time = isset($_POST['end_time']) && $_POST['end_time'] ? strtotime($_POST['end_time']) : ($start_time + 86400); // По умолчанию +1 день
            
            // Проверка данных
            if (empty($name) || $start_time <= 0) {
                echo json_encode(['html' => "Ошибка: название и время начала обязательны", 'error' => "error"]);
                exit;
            }
            
            // Вставка в БД
            $result = $mysqli->query("INSERT INTO tournaments (name, description, start_time, end_time, active, status, created_at) 
                                     VALUES ('$name', '$description', $start_time, $end_time, 1, 0, ".time().")");
            
            if ($result) {
                $tournament_id = $mysqli->insert_id;
                logAdminAction('add_tournament', "ID: $tournament_id, Name: $name");
                echo json_encode([
                    'html' => "Турнир успешно добавлен!", 
                    'error' => "success"
                ]);
            } else {
                echo json_encode([
                    'html' => "Ошибка при добавлении турнира: " . $mysqli->error, 
                    'error' => "error"
                ]);
            }
            exit;
            
        case 'set_active':
            $id = intval($_POST['id']);
            $active = intval($_POST['active']);
            
            // Проверка валидности id и статуса
            if ($id <= 0 || !in_array($active, [0, 1, 2])) {
                echo json_encode(['html' => "Неверные параметры", 'error' => "error"]);
                exit;
            }
            
            $result = $mysqli->query("UPDATE tournaments SET active=$active WHERE id=$id");
            
            if ($result) {
                $status_text = ['Неактивный', 'Активный', 'Завершён'][$active];
                logAdminAction('update_tournament_status', "ID: $id, Status: $status_text");
                echo json_encode([
                    'html' => "Статус турнира обновлён на \"$status_text\"!", 
                    'error' => "success"
                ]);
            } else {
                echo json_encode([
                    'html' => "Ошибка при обновлении статуса: " . $mysqli->error, 
                    'error' => "error"
                ]);
            }
            exit;
            
        case 'finish_tournament':
            $id = intval($_POST['id']);
            
            // Проверка существования турнира
            $tournament = $mysqli->query("SELECT * FROM tournaments WHERE id=$id")->fetch_assoc();
            if (!$tournament) {
                echo json_encode(['html' => "Турнир не найден", 'error' => "error"]);
                exit;
            }
            
            $result = $mysqli->query("UPDATE tournaments SET active=2 WHERE id=$id");
            
            if ($result) {
                logAdminAction('finish_tournament', "ID: $id, Name: {$tournament['name']}");
                echo json_encode([
                    'html' => "Турнир \"{$tournament['name']}\" успешно завершён!", 
                    'error' => "success"
                ]);
            } else {
                echo json_encode([
                    'html' => "Ошибка при завершении турнира: " . $mysqli->error, 
                    'error' => "error"
                ]);
            }
            exit;
            
        case 'manage_tournament':
            $tournament_id = intval($_POST['id']);
            $tournament = $mysqli->query("SELECT * FROM tournaments WHERE id=$tournament_id")->fetch_assoc();
            
            if (!$tournament) {
                echo json_encode(['html' => "Турнир не найден!"]);
                exit;
            }
            
            // Формируем шаблон панели управления турниром
            $tpl = "<div class='tournament-admin-container'>";
            $tpl .= "<div class='st-panel-h2'>Управление турниром: ".htmlspecialchars($tournament['name'])."</div>";
            
            // Информация о турнире
            $tpl .= "<div class='tournament-info'>
                <div><strong>Начало:</strong> ".date("d.m.Y H:i", $tournament['start_time'])."</div>
                <div><strong>Статус:</strong> ".(
                    $tournament['active'] == 0 ? '<span class="status-inactive">Неактивный</span>' : 
                    ($tournament['active'] == 1 ? '<span class="status-active">Активный</span>' : 
                                                '<span class="status-finished">Завершён</span>')
                )."</div>
            </div>";
            
            // Кнопки управления
            $tpl .= "<div class='tournament-actions'>";
            if ($tournament['active'] != 2) {
                $tpl .= "<button class='st-btn st-btn-red' onclick='finishTournament({$tournament['id']})'>Завершить турнир</button>";
            }
            $tpl .= "<button class='st-btn st-btn-blue' onclick='exportResults({$tournament['id']})'>Экспорт результатов</button>";
            $tpl .= "</div>";
            
            // Список участников
            $res = $mysqli->query("SELECT tu.id, u.id as user_id, u.login, tu.place, tu.result, 
                                  (SELECT COUNT(*) FROM items_users WHERE user=u.id AND trophy=1) as medals
                                  FROM tournaments_users tu 
                                  JOIN users u ON tu.user_id=u.id 
                                  WHERE tu.tournament_id=$tournament_id
                                  ORDER BY IFNULL(tu.place, 999), u.login");
            
            if ($res->num_rows > 0) {
                $tpl .= "<div class='participants-header'>Список участников (".$res->num_rows.")</div>";
                $tpl .= "<table class='st-admin-table participants-table'>
                    <tr>
                        <th>ID</th>
                        <th>Участник</th>
                        <th>Место</th>
                        <th>Результат</th>
                        <th>Медалей</th>
                        <th>Действия</th>
                    </tr>";
                
                while ($row = $res->fetch_assoc()) {
                    $rowClass = '';
                    if ($row['place'] == 1) $rowClass = 'gold-place';
                    elseif ($row['place'] == 2) $rowClass = 'silver-place';
                    elseif ($row['place'] == 3) $rowClass = 'bronze-place';
                    
                    $tpl .= "<tr class='$rowClass'>
                        <td>{$row['user_id']}</td>
                        <td><a href='/profile/{$row['login']}' target='_blank'>{$row['login']}</a></td>
                        <td>
                            <input type='number' min='1' max='100' class='place-input' value='".($row['place'] ?: '')."' 
                                onchange='setPlace({$row['id']}, this.value)'>
                        </td>
                        <td>
                            <input type='text' class='result-input' value='".htmlspecialchars($row['result'] ?: '')."' 
                                onchange='setResult({$row['id']}, this.value)'>
                        </td>
                        <td>{$row['medals']}</td>
                        <td>
                            <div class='action-buttons'>
                                <button class='st-btn st-mini' onclick='giveMedal({$row['id']})'>
                                    <i class='fas fa-medal'></i> Выдать медаль
                                </button>
                                <button class='st-btn st-mini st-btn-red' onclick='removeParticipant({$row['id']})'>
                                    <i class='fas fa-user-minus'></i>
                                </button>
                            </div>
                        </td>
                    </tr>";
                }
                $tpl .= "</table>";
            } else {
                $tpl .= "<div class='st-empty'>Участники не найдены</div>";
            }
            
            // JavaScript для управления участниками
            $tpl .= "</div>
            <script>
            function setPlace(tu_id, val) {
                if (val === '') return;
                showLoading();
                $.post('/do/tournament_admin_panel.php', {
                    action: 'set_place',
                    tu_id: tu_id,
                    place: val,
                    csrf_token: '{$_SESSION['csrf_token']}'
                }, function(resp) {
                    hideLoading();
                    if (resp.error && resp.error !== 'success') {
                        showNotification(resp.html, 'error');
                    } else {
                        showNotification('Место обновлено', 'success');
                    }
                }, 'json').fail(function() {
                    hideLoading();
                    showNotification('Ошибка соединения', 'error');
                });
            }
            
            function setResult(tu_id, val) {
                showLoading();
                $.post('/do/tournament_admin_panel.php', {
                    action: 'set_result',
                    tu_id: tu_id,
                    result: val,
                    csrf_token: '{$_SESSION['csrf_token']}'
                }, function(resp) {
                    hideLoading();
                    if (resp.error && resp.error !== 'success') {
                        showNotification(resp.html, 'error');
                    } else {
                        showNotification('Результат обновлен', 'success');
                    }
                }, 'json').fail(function() {
                    hideLoading();
                    showNotification('Ошибка соединения', 'error');
                });
            }
            
            function giveMedal(tu_id) {
                if (!confirm('Вы уверены, что хотите выдать медаль этому участнику?')) return;
                
                showLoading();
                $.post('/do/tournament_admin_panel.php', {
                    action: 'give_medal',
                    tu_id: tu_id,
                    csrf_token: '{$_SESSION['csrf_token']}'
                }, function(resp) {
                    hideLoading();
                    showNotification(resp.html, resp.error === 'success' ? 'success' : 'error');
                }, 'json').fail(function() {
                    hideLoading();
                    showNotification('Ошибка соединения', 'error');
                });
            }
            
            function finishTournament(id) {
                if (!confirm('Вы уверены, что хотите завершить турнир? Это действие нельзя отменить.')) return;
                
                showLoading();
                $.post('/do/tournament_admin_panel.php', {
                    action: 'finish_tournament',
                    id: id,
                    csrf_token: '{$_SESSION['csrf_token']}'
                }, function(resp) {
                    hideLoading();
                    showNotification(resp.html, resp.error === 'success' ? 'success' : 'error');
                    if (resp.error === 'success') {
                        // Обновляем страницу через 1.5 секунды
                        setTimeout(function() { 
                            manageTournament(id);
                        }, 1500);
                    }
                }, 'json').fail(function() {
                    hideLoading();
                    showNotification('Ошибка соединения', 'error');
                });
            }
            
            function exportResults(id) {
                window.open('/do/tournament_export.php?id=' + id + '&csrf_token={$_SESSION['csrf_token']}', '_blank');
            }
            
            function removeParticipant(tu_id) {
                if (!confirm('Вы уверены, что хотите удалить этого участника из турнира?')) return;
                
                // Здесь будет код для удаления участника
                showNotification('Функция удаления участника в разработке', 'info');
            }
            
            // Вспомогательные функции для UI
            function showLoading() {
                if ($('#admin-loading').length === 0) {
                    $('body').append('<div id=\"admin-loading\" class=\"admin-loading\">Загрузка...</div>');
                }
                $('#admin-loading').show();
            }
            
            function hideLoading() {
                $('#admin-loading').hide();
            }
            
            function showNotification(message, type) {
                // Если есть своя система уведомлений, используйте ее
                // Иначе используем простое решение
                const notificationId = 'admin-notification-' + Date.now();
                const notification = $('<div id=\"' + notificationId + '\" class=\"admin-notification ' + type + '\">' + 
                                     message + '<span class=\"close-notification\">&times;</span></div>');
                
                $('body').append(notification);
                
                notification.find('.close-notification').click(function() {
                    $('#' + notificationId).fadeOut(300, function() { $(this).remove(); });
                });
                
                setTimeout(function() {
                    $('#' + notificationId).fadeOut(500, function() { $(this).remove(); });
                }, 5000);
            }
            </script>";
            
            echo json_encode(['html' => $tpl]);
            exit;
            
        case 'set_place':
            $tu_id = intval($_POST['tu_id']);
            $place = intval($_POST['place']);
            
            // Проверка данных
            if ($tu_id <= 0 || $place <= 0) {
                echo json_encode(['html' => "Неверные параметры", 'error' => "error"]);
                exit;
            }
            
            // Получаем информацию об участнике для лога
            $participant = $mysqli->query("SELECT tu.tournament_id, u.login FROM tournaments_users tu 
                                           JOIN users u ON tu.user_id=u.id WHERE tu.id=$tu_id")->fetch_assoc();
            
            $result = $mysqli->query("UPDATE tournaments_users SET place=$place WHERE id=$tu_id");
            
            if ($result) {
                logAdminAction('set_place', "Турнир ID: {$participant['tournament_id']}, Участник: {$participant['login']}, Место: $place");
                echo json_encode(['html' => "Место обновлено!", 'error' => "success"]);
            } else {
                echo json_encode(['html' => "Ошибка при обновлении: " . $mysqli->error, 'error' => "error"]);
            }
            exit;
            
        case 'set_result':
            $tu_id = intval($_POST['tu_id']);
            $result = escapeMe($_POST['result']);
            
            // Проверка данных
            if ($tu_id <= 0) {
                echo json_encode(['html' => "Неверный ID участника", 'error' => "error"]);
                exit;
            }
            
            // Получаем информацию об участнике для лога
            $participant = $mysqli->query("SELECT tu.tournament_id, u.login FROM tournaments_users tu 
                                           JOIN users u ON tu.user_id=u.id WHERE tu.id=$tu_id")->fetch_assoc();
            
            $updateResult = $mysqli->query("UPDATE tournaments_users SET result='$result' WHERE id=$tu_id");
            
            if ($updateResult) {
                logAdminAction('set_result', "Турнир ID: {$participant['tournament_id']}, Участник: {$participant['login']}, Результат: $result");
                echo json_encode(['html' => "Результат обновлен!", 'error' => "success"]);
            } else {
                echo json_encode(['html' => "Ошибка при обновлении: " . $mysqli->error, 'error' => "error"]);
            }
            exit;
            
        case 'give_medal':
            $tu_id = intval($_POST['tu_id']);
            
            // Получаем информацию об участнике
            $user_row = $mysqli->query("SELECT tu.user_id, tu.place, u.login, t.name as tournament_name 
                                       FROM tournaments_users tu
                                       JOIN users u ON tu.user_id=u.id
                                       JOIN tournaments t ON tu.tournament_id=t.id
                                       WHERE tu.id=$tu_id")->fetch_assoc();
            
            if (!$user_row) {
                echo json_encode(['html' => "Участник не найден", 'error' => "error"]);
                exit;
            }
            
            // Определяем тип медали в зависимости от места
            $place = intval($user_row['place']);
            if ($place <= 0 || $place > 3) {
                echo json_encode(['html' => "Медали выдаются только за 1-3 места", 'error' => "error"]);
                exit;
            }
            
            // ID медали зависит от занятого места (1 - золото, 2 - серебро, 3 - бронза)
            $medal_id = 1000000 + $place;  // Используем ваш формат ID медалей
            $medal_names = [1 => 'золотую', 2 => 'серебряную', 3 => 'бронзовую'];
            
            // Проверяем, не выдавалась ли уже медаль
            $existing = $mysqli->query("SELECT id FROM items_users 
                                       WHERE user={$user_row['user_id']} AND item_id=$medal_id AND trophy=1 
                                       AND tournament_id={$tu_id}")->fetch_assoc();
            
            if ($existing) {
                echo json_encode(['html' => "Этому участнику уже выдана медаль за это место", 'error' => "error"]);
                exit;
            }
            
            // Выдаем медаль
            $result = $mysqli->query("INSERT INTO items_users (user, item_id, count, trophy, tournament_id, award_date) 
                                     VALUES ({$user_row['user_id']}, $medal_id, 1, 1, $tu_id, ".time().")");
            
            if ($result) {
                logAdminAction('give_medal', "Участник: {$user_row['login']}, Место: $place, Турнир: {$user_row['tournament_name']}");
                echo json_encode([
                    'html' => "Выдана {$medal_names[$place]} медаль участнику {$user_row['login']}!", 
                    'error' => "success"
                ]);
            } else {
                echo json_encode(['html' => "Ошибка при выдаче медали: " . $mysqli->error, 'error' => "error"]);
            }
            exit;
    }
}

// ГЛАВНАЯ ПАНЕЛЬ КУРАТОРА (GET-запрос)
$tpl = "<div class='st-admin-panel tournaments-admin'>";

// Панель добавления турнира
$tpl .= "<div class='admin-section'>
    <div class='st-admin-h2'>Добавить новый турнир</div>
    <form id='add-tournament-form' class='st-admin-form'>
        <div class='form-group'>
            <label for='tournament_name'>Название турнира:</label>
            <input id='tournament_name' class='st-select' type='text' name='name' placeholder='Название турнира' required>
        </div>
        
        <div class='form-group'>
            <label for='tournament_desc'>Описание (опционально):</label>
            <textarea id='tournament_desc' class='st-select' name='description' placeholder='Описание турнира'></textarea>
        </div>
        
        <div class='form-row'>
            <div class='form-group half'>
                <label for='tournament_start'>Начало турнира:</label>
                <input id='tournament_start' class='st-select' type='datetime-local' name='start_time' required>
            </div>
            <div class='form-group half'>
                <label for='tournament_end'>Окончание (опционально):</label>
                <input id='tournament_end' class='st-select' type='datetime-local' name='end_time'>
            </div>
        </div>
        
        <input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}'>
        <button type='submit' class='st-btn st-btn-primary'>
            <i class='fas fa-plus'></i> Создать турнир
        </button>
    </form>
</div>
<div class='st-hr'></div>";

// Фильтры для турниров
$tpl .= "<div class='admin-section'>
    <div class='st-admin-h2'>Управление турнирами</div>
    <div class='tournament-filters'>
        <div class='filter-group'>
            <label>Статус:</label>
            <select id='status-filter' class='st-select' onchange='filterTournaments()'>
                <option value='all'>Все</option>
                <option value='1'>Активные</option>
                <option value='2'>Завершенные</option>
                <option value='0'>Неактивные</option>
            </select>
        </div>
        <div class='filter-group'>
            <label>Поиск:</label>
            <input type='text' id='search-filter' class='st-select' placeholder='Название турнира' onkeyup='filterTournaments()'>
        </div>
    </div>";

// Список турниров
$res = $mysqli->query('SELECT t.*, 
                      (SELECT COUNT(*) FROM tournaments_users WHERE tournament_id=t.id) as participants 
                      FROM tournaments t 
                      ORDER BY t.start_time DESC');

$tpl .= "<div class='tournaments-list-container'>
    <table class='st-admin-table tournaments-table' id='tournaments-table'>
        <thead>
            <tr>
                <th>ID</th>
                <th>Турнир</th>
                <th>Дата начала</th>
                <th>Дата окончания</th>
                <th>Участников</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>";

$status_names = [0 => 'Неактивный', 1 => 'Активный', 2 => 'Завершён'];
$status_classes = [0 => 'status-inactive', 1 => 'status-active', 2 => 'status-finished'];

while ($row = $res->fetch_assoc()) {
    // Определяем класс строки в зависимости от статуса
    $rowClass = 'tournament-row status-' . $row['active'];
    
    // Селект для смены статуса
    $status = "<select class='st-select st-mini tournament-status-select' data-id='{$row['id']}' onchange='setTournamentActive({$row['id']}, this.value)'>";
    foreach ($status_names as $val => $text) {
        $status .= "<option value='$val' " . ($row['active'] == $val ? 'selected' : '') . ">$text</option>";
    }
    $status .= "</select>";
    
    $tpl .= "<tr class='$rowClass' data-status='{$row['active']}'>
        <td>{$row['id']}</td>
        <td class='tournament-name'>" . htmlspecialchars($row['name']) . "</td>
        <td>" . date("d.m.Y H:i", $row['start_time']) . "</td>
        <td>" . (isset($row['end_time']) && $row['end_time'] > 0 ? date("d.m.Y H:i", $row['end_time']) : '-') . "</td>
        <td class='participants-count'>{$row['participants']}</td>
        <td class='tournament-status'>
            <span class='status-badge {$status_classes[$row['active']]}'>
                {$status_names[$row['active']]}
            </span>
            $status
        </td>
        <td>
            <div class='action-buttons'>
                <button class='st-btn st-btn-primary st-mini' onclick='manageTournament({$row['id']})'>
                    <i class='fas fa-cog'></i> Управление
                </button>
                " . ($row['active'] != 2 ? "<button class='st-btn st-btn-red st-mini' onclick='confirmFinishTournament({$row['id']})'>
                    <i class='fas fa-flag-checkered'></i>
                </button>" : "") . "
            </div>
        </td>
    </tr>";
}

$tpl .= "</tbody></table>
    <div id='no-tournaments-message' class='st-empty' style='display: none;'>Турниры не найдены</div>
</div>";

// Закрываем секцию и панель
$tpl .= "</div></div>";

// Добавляем стили и скрипты
$tpl .= "<style>
.tournaments-admin {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}
.admin-section {
    margin-bottom: 20px;
    background-color: #fff;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.st-admin-h2 {
    font-size: 1.5em;
    margin-bottom: 15px;
    color: #4a4a4a;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 8px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}
.form-row {
    display: flex;
    gap: 15px;
}
.form-group.half {
    flex: 1;
}
.st-select, input[type='text'], input[type='number'], textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}
.st-select:focus, input[type='text']:focus, input[type='number']:focus, textarea:focus {
    border-color: #6d5fa0;
    outline: none;
}
.tournament-filters {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}
.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}
.tournaments-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.tournaments-table th {
    background-color: #f5f5f5;
    text-align: left;
    padding: 10px;
    font-weight: 600;
    color: #444;
}
.tournaments-table td {
    padding: 12px 10px;
    border-top: 1px solid #eee;
}
.tournament-name {
    font-weight: 500;
    color: #333;
}
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}
.status-active {
    background-color: #e6f7e6;
    color: #2e7d32;
}
.status-inactive {
    background-color: #f5f5f5;
    color: #757575;
}
.status-finished {
    background-color: #e3f2fd;
    color: #1565c0;
}
.tournament-status-select {
    display: none;
    width: auto;
    min-width: 120px;
}
.tournament-status:hover .status-badge {
    display: none;
}
.tournament-status:hover .tournament-status-select {
    display: inline-block;
}
.st-btn {
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
    background-color: #6d5fa0;
    color: white;
}
.st-btn:hover {
    background-color: #5d509a;
}
.st-btn-primary {
    background-color: #6d5fa0;
    color: white;
}
.st-btn-red {
    background-color: #e53935;
    color: white;
}
.st-btn-red:hover {
    background-color: #d32f2f;
}
.st-btn-blue {
    background-color: #1976d2;
    color: white;
}
.st-btn-blue:hover {
    background-color: #1565c0;
}
.st-mini {
    padding: 5px 10px;
    font-size: 12px;
}
.action-buttons {
    display: flex;
    gap: 5px;
}
.tournament-info {
    background-color: #f9f9f9;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
}
.tournament-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.participants-header {
    font-size: 16px;
    font-weight: 500;
    margin: 15px 0 10px;
    color: #444;
}
.participants-table th, .participants-table td {
    padding: 10px 8px;
}
.place-input, .result-input {
    width: 80px;
    text-align: center;
}
.gold-place {
    background-color: rgba(255, 215, 0, 0.1);
}
.silver-place {
    background-color: rgba(192, 192, 192, 0.1);
}
.bronze-place {
    background-color: rgba(205, 127, 50, 0.1);
}
.admin-loading {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    z-index: 9999;
}
.admin-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 6px;
    background-color: #333;
    color: white;
    z-index: 9999;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    max-width: 350px;
}
.admin-notification.success {
    background-color: #43a047;
}
.admin-notification.error {
    background-color: #e53935;
}
.admin-notification.info {
    background-color: #1976d2;
}
.close-notification {
    cursor: pointer;
    font-size: 20px;
    font-weight: bold;
}
.tournament-admin-container {
    padding: 10px;
}
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }
    .tournament-filters {
        flex-direction: column;
    }
    .tournament-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Инициализация при загрузке
$(document).ready(function() {
    // Устанавливаем текущую дату и время для полей дат по умолчанию
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const nowStr = now.toISOString().slice(0, 16);
    $('#tournament_start').val(nowStr);
    
    // Через неделю для даты окончания
    const nextWeek = new Date();
    nextWeek.setDate(nextWeek.getDate() + 7);
    nextWeek.setMinutes(nextWeek.getMinutes() - nextWeek.getTimezoneOffset());
    const nextWeekStr = nextWeek.toISOString().slice(0, 16);
    $('#tournament_end').val(nextWeekStr);
});

// Обработка формы добавления турнира
$('#add-tournament-form').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_tournament');
    
    showLoading();
    
    $.ajax({
        url: '/do/tournament_admin_panel.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(resp) {
            hideLoading();
            showNotification(resp.html, resp.error === 'success' ? 'success' : 'error');
            
            if (resp.error === 'success') {
                // Очищаем форму и обновляем страницу через 1.5 секунды
                $('#add-tournament-form')[0].reset();
                setTimeout(function() {
                    location.reload();
                }, 1500);
            }
        },
        error: function() {
            hideLoading();
            showNotification('Ошибка соединения с сервером', 'error');
        }
    });
});

// Функция изменения статуса турнира
function setTournamentActive(id, value) {
    showLoading();
    
    $.post('/do/tournament_admin_panel.php', {
        action: 'set_active',
        id: id,
        active: value,
        csrf_token: '{$_SESSION['csrf_token']}'
    }, function(resp) {
        hideLoading();
        
        if (resp.error === 'success') {
            showNotification(resp.html, 'success');
            
            // Обновляем внешний вид строки без перезагрузки
            const row = $('tr[data-id=\"' + id + '\"]');
            row.removeClass('status-0 status-1 status-2').addClass('status-' + value);
            
            // Обновляем статус и текст
            const statusNames = ['Неактивный', 'Активный', 'Завершён'];
            const statusClasses = ['status-inactive', 'status-active', 'status-finished'];
            
            row.find('.status-badge')
               .removeClass('status-inactive status-active status-finished')
               .addClass(statusClasses[value])
               .text(statusNames[value]);
               
            row.attr('data-status', value);
            
            // При необходимости обновляем кнопки
            if (value == 2) {
                row.find('.st-btn-red').remove();
            }
        } else {
            showNotification(resp.html || 'Ошибка при обновлении статуса', 'error');
        }
    }, 'json').fail(function() {
        hideLoading();
        showNotification('Ошибка соединения с сервером', 'error');
    });
}

// Функция для фильтрации турниров
function filterTournaments() {
    const statusFilter = $('#status-filter').val();
    const searchFilter = $('#search-filter').val().toLowerCase();
    let visibleCount = 0;
    
    $('#tournaments-table tbody tr').each(function() {
        const row = $(this);
        const status = row.data('status').toString();
        const name = row.find('.tournament-name').text().toLowerCase();
        
        const statusMatch = statusFilter === 'all' || status === statusFilter;
        const nameMatch = name.includes(searchFilter);
        
        if (statusMatch && nameMatch) {
            row.show();
            visibleCount++;
        } else {
            row.hide();
        }
    });
    
    // Показываем или скрываем сообщение о пустом списке
    if (visibleCount === 0) {
        $('#no-tournaments-message').show();
    } else {
        $('#no-tournaments-message').hide();
    }
}

// Функция управления турниром
function manageTournament(id) {
    showLoading();
    
    $.post('/do/tournament_admin_panel.php', {
        action: 'manage_tournament',
        id: id
    }, function(resp) {
        hideLoading();
        
        // Открываем модальное окно с информацией о турнире
        // Используем существующую функцию openModal или создаем модальное окно
        $('.LittleModal').html(resp.html).fadeIn(200);
        
        // Если нет встроенной функции для модальных окон:
        if ($('.tournament-modal-overlay').length === 0) {
            $('body').append('<div class=\"tournament-modal-overlay\" onclick=\"closeModal()\"></div>');
        }
        $('.tournament-modal-overlay').fadeIn(200);
        
    }, 'json').fail(function() {
        hideLoading();
        showNotification('Ошибка соединения с сервером', 'error');
    });
}

// Функция подтверждения завершения турнира
function confirmFinishTournament(id) {
    if (confirm('Вы уверены, что хотите завершить турнир? Это действие нельзя отменить.')) {
        finishTournament(id);
    }
}

// Функция завершения турнира
function finishTournament(id) {
    showLoading();
    
    $.post('/do/tournament_admin_panel.php', {
        action: 'finish_tournament',
        id: id,
        csrf_token: '{$_SESSION['csrf_token']}'
    }, function(resp) {
        hideLoading();
        
        showNotification(resp.html, resp.error === 'success' ? 'success' : 'error');
        
        if (resp.error === 'success') {
            // Обновляем страницу через 1.5 секунды
            setTimeout(function() {
                if ($('.LittleModal').is(':visible')) {
                    // Если открыто модальное окно, обновляем только его
                    manageTournament(id);
                } else {
                    location.reload();
                }
            }, 1500);
        }
    }, 'json').fail(function() {
        hideLoading();
        showNotification('Ошибка соединения с сервером', 'error');
    });
}

// Закрытие модального окна
function closeModal() {
    $('.LittleModal').fadeOut(200);
    $('.tournament-modal-overlay').fadeOut(200);
}

// Вспомогательные функции для UI
function showLoading() {
    if ($('#admin-loading').length === 0) {
        $('body').append('<div id=\"admin-loading\" class=\"admin-loading\">Загрузка...</div>');
    }
    $('#admin-loading').show();
}

function hideLoading() {
    $('#admin-loading').hide();
}

function showNotification(message, type = 'info') {
    // Если есть своя система уведомлений, используйте ее
    // Иначе используем простое решение
    const notificationId = 'admin-notification-' + Date.now();
    const notification = $('<div id=\"' + notificationId + '\" class=\"admin-notification ' + type + '\">' + 
                         message + '<span class=\"close-notification\">&times;</span></div>');
    
    $('body').append(notification);
    
    notification.find('.close-notification').click(function() {
        $('#' + notificationId).fadeOut(300, function() { $(this).remove(); });
    });
    
    setTimeout(function() {
        $('#' + notificationId).fadeOut(500, function() { $(this).remove(); });
    }, 5000);
}
</script>";

echo json_encode(['html' => $tpl]);
exit;