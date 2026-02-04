<?php
/**
 * Роутер для мировых боссов
 * Обрабатывает все AJAX запросы связанные с системой мировых боссов
 */

// Устанавливаем буферизацию вывода
ob_start();

// Запускаем сессию, если она ещё не запущена
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Функция завершения работы с выводом ошибки в JSON-формате
function jsonError($message, $code = null) {
    if (ob_get_level()) ob_end_clean();
    
    $response = [
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($code) {
        $response['error_code'] = $code;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем что переменная $patch_project установлена (должна быть из global.php)
if (!isset($patch_project)) {
    // Пытаемся определить путь автоматически
    $patch_project = $_SERVER['DOCUMENT_ROOT'];
}

// Пути к необходимым файлам согласно вашей структуре
$requiredFiles = [
    $patch_project . '/inc/conf/const.php',
    $patch_project . '/inc/function/Functions.php', 
    $patch_project . '/inc/conf/connect.php'
];

// Проверяем существование файлов
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        jsonError("Отсутствует необходимый файл: $file", 'MISSING_FILE');
    }
}

// Подключаем файлы если они ещё не подключены
foreach ($requiredFiles as $file) {
    if (!in_array(realpath($file), get_included_files())) {
        require_once $file;
    }
}

// Подключаем файл global.php если он есть в makasimka
$globalPath = $patch_project . '/makasimka/inc/conf/global.php';
if (file_exists($globalPath) && !in_array(realpath($globalPath), get_included_files())) {
    require_once $globalPath;
}

// Проверяем подключение к базе данных
if (!isset($mysqli) || !$mysqli) {
    jsonError('Ошибка подключения к базе данных', 'DB_CONNECTION_ERROR');
}

// Подключаем класс WorldBossManager
$worldBossManagerPath = $patch_project . '/inc/world_boss_manager.php';
if (!file_exists($worldBossManagerPath)) {
    jsonError('Файл менеджера мировых боссов не найден', 'MANAGER_NOT_FOUND');
}

require_once $worldBossManagerPath;

// Проверяем авторизацию
if (!isset($_SESSION['id']) || !$_SESSION['id']) {
    jsonError('Доступ запрещён. Необходима авторизация.', 'ACCESS_DENIED');
}

// Проверяем существование класса
if (!class_exists('WorldBossManager')) {
    jsonError('Система мировых боссов временно недоступна', 'SYSTEM_UNAVAILABLE');
}

// Очищаем буфер для JSON вывода
if (ob_get_level()) ob_end_clean();

$response = ['success' => false, 'timestamp' => date('Y-m-d H:i:s')];
$userId = (int)$_SESSION['id'];

// Функция для установки ошибки
function setError($message, $code = null) {
    global $response;
    $response['success'] = false;
    $response['error'] = $message;
    if ($code) {
        $response['error_code'] = $code;
    }
}

// Функция для логирования (упрощённая)
function logUserAction($userId, $action, $details = '') {
    global $mysqli;
    // Попытка логирования без критичности
    try {
        if ($mysqli && method_exists($mysqli, 'prepare')) {
            $stmt = $mysqli->prepare("
                INSERT IGNORE INTO user_actions_log (user_id, action_type, action_details, timestamp) 
                VALUES (?, 'world_boss', ?, NOW())
            ");
            if ($stmt) {
                $actionData = json_encode(['action' => $action, 'details' => $details]);
                $stmt->bind_param('is', $userId, $actionData);
                $stmt->execute();
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        // Игнорируем ошибки логирования
        error_log("Logging error: " . $e->getMessage());
    }
}

// Функция для проверки прав администратора
function isAdmin($userId) {
    global $mysqli;
    if (!$mysqli) return false;
    
    try {
        $stmt = $mysqli->prepare("SELECT user_group FROM users WHERE id = ?");
        if (!$stmt) return false;
        
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result && in_array($result['user_group'], ['admin', 'moderator', 'Администратор']);
    } catch (Exception $e) {
        return false;
    }
}

try {
    // Определяем действие
    $action = '';
    if (isset($_POST['action'])) {
        $action = trim($_POST['action']);
    } elseif (isset($_GET['action'])) {
        $action = trim($_GET['action']);
    }

    if (empty($action)) {
        setError('Не указано действие', 'NO_ACTION');
        throw new Exception('Action parameter is required');
    }

    // Получение списка активных боссов
    if ($action === 'list') {
        // Получаем текущую локацию пользователя
        $stmt = $mysqli->prepare("SELECT location, lvl, status FROM users WHERE id = ?");
        if (!$stmt) {
            setError('Ошибка подготовки запроса', 'QUERY_PREPARE_ERROR');
        } else {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $userData = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$userData) {
                setError('Данные пользователя не найдены', 'USER_NOT_FOUND');
            } else {
                $locationId = (int)$userData['location'];
                $userLevel = (int)$userData['lvl'];
                
                // Получаем активных боссов
                $activeBosses = WorldBossManager::getActiveBosses($locationId);
                
                // Фильтруем боссов по уровню пользователя
                $availableBosses = array_filter($activeBosses, function($boss) use ($userLevel) {
                    return $userLevel >= ($boss['level_min'] ?? 1) && $userLevel <= ($boss['level_max'] ?? 100);
                });
                
                $response['bosses'] = array_values($availableBosses);
                $response['all_bosses'] = $activeBosses; // Все боссы для информации
                $response['user_level'] = $userLevel;
                $response['user_location'] = $locationId;
                $response['success'] = true;
                $response['count'] = count($availableBosses);
                $response['total_count'] = count($activeBosses);
                
                logUserAction($userId, 'list_bosses', "Location: $locationId, Found: " . count($activeBosses));
            }
        }
    }
    
    // Присоединение к рейду
    elseif ($action === 'join') {
        $instanceId = (int)($_POST['instance_id'] ?? 0);
        
        if ($instanceId <= 0) {
            setError('Неверный ID рейда', 'INVALID_INSTANCE_ID');
        } else {
            // Проверяем существование инстанса
            $instance = WorldBossManager::getBossInstance($instanceId);
            if (!$instance) {
                setError('Рейд не найден или уже завершён', 'INSTANCE_NOT_FOUND');
            } elseif (!in_array($instance['status'], ['spawning', 'active'])) {
                setError('Рейд недоступен для присоединения', 'INSTANCE_INACTIVE');
            } else {
                // Проверяем возможность участия
                if (!WorldBossManager::canUserParticipate($userId, $instanceId)) {
                    setError('Вы не можете участвовать в этом рейде. Проверьте уровень, статус или лимит участников.', 'CANNOT_PARTICIPATE');
                } else {
                    // Пытаемся присоединиться к рейду
                    if (WorldBossManager::joinBossRaid($userId, $instanceId)) {
                        $response['success'] = true;
                        $response['message'] = 'Вы успешно присоединились к рейду против ' . ($instance['name'] ?? 'босса') . '!';
                        $response['instance_id'] = $instanceId;
                        $response['boss_name'] = $instance['name'] ?? 'Неизвестный босс';
                        
                        // Возвращаем обновлённые данные инстанса
                        $updatedInstance = WorldBossManager::getBossInstance($instanceId);
                        $response['instance'] = $updatedInstance;
                        
                        logUserAction($userId, 'join_raid', "Instance: $instanceId, Boss: " . ($instance['name'] ?? 'unknown'));
                    } else {
                        setError('Не удалось присоединиться к рейду. Возможно, рейд уже завершён или переполнен.', 'JOIN_FAILED');
                    }
                }
            }
        }
    }
    
    // Покидание рейда
    elseif ($action === 'leave') {
        $instanceId = (int)($_POST['instance_id'] ?? 0);
        
        if ($instanceId <= 0) {
            setError('Неверный ID рейда', 'INVALID_INSTANCE_ID');
        } else {
            $instance = WorldBossManager::getBossInstance($instanceId);
            if (!$instance) {
                setError('Рейд не найден', 'INSTANCE_NOT_FOUND');
            } else {
                if (WorldBossManager::leaveBossRaid($userId, $instanceId)) {
                    $response['success'] = true;
                    $response['message'] = 'Вы покинули рейд против ' . ($instance['name'] ?? 'босса');
                    
                    logUserAction($userId, 'leave_raid', "Instance: $instanceId, Boss: " . ($instance['name'] ?? 'unknown'));
                } else {
                    setError('Не удалось покинуть рейд. Возможно, вы не участвуете в нём.', 'LEAVE_FAILED');
                }
            }
        }
    }
    
    // Получение данных конкретного рейда
    elseif ($action === 'instance') {
        $instanceId = (int)($_GET['instance_id'] ?? 0);
        
        if ($instanceId <= 0) {
            setError('Неверный ID рейда', 'INVALID_INSTANCE_ID');
        } else {
            $instance = WorldBossManager::getBossInstance($instanceId);
            if (!$instance) {
                setError('Рейд не найден', 'INSTANCE_NOT_FOUND');
            } else {
                $participants = WorldBossManager::getBossParticipants($instanceId, 50);
                
                // Проверяем участвует ли текущий пользователь
                $userParticipation = array_filter($participants, function($p) use ($userId) {
                    return $p['user_id'] == $userId;
                });
                
                $response['instance'] = $instance;
                $response['participants'] = $participants;
                $response['user_participating'] = !empty($userParticipation);
                $response['success'] = true;
                
                logUserAction($userId, 'view_instance', "Instance: $instanceId");
            }
        }
    }
    
    // Статистика боссов
    elseif ($action === 'stats') {
        if (method_exists('WorldBossManager', 'getAdminStats')) {
            $stats = WorldBossManager::getAdminStats();
            $response['stats'] = $stats;
            $response['success'] = true;
        } else {
            setError('Система статистики недоступна', 'STATS_UNAVAILABLE');
        }
    }
    
    // Административные функции (только для админов)
    elseif (strpos($action, 'admin_') === 0 && !isAdmin($userId)) {
        setError('Недостаточно прав для выполнения этого действия', 'INSUFFICIENT_PERMISSIONS');
    }
    
    elseif ($action === 'admin_spawn' && isAdmin($userId)) {
        $bossId = (int)($_POST['boss_id'] ?? 0);
        $locationId = (int)($_POST['location_id'] ?? 0);
        
        if ($bossId <= 0 || $locationId <= 0) {
            setError('Неверные параметры для спавна босса', 'INVALID_SPAWN_PARAMS');
        } else {
            $instanceId = WorldBossManager::spawnBoss($bossId, $locationId, true);
            if ($instanceId) {
                $response['success'] = true;
                $response['message'] = 'Босс успешно заспавнен администратором';
                $response['instance_id'] = $instanceId;
                
                logUserAction($userId, 'admin_spawn', "Boss: $bossId, Location: $locationId, Instance: $instanceId");
            } else {
                setError('Не удалось заспавнить босса. Проверьте параметры или наличие активных боссов в локации.', 'SPAWN_FAILED');
            }
        }
    }
    
    // Неизвестное действие
    else {
        setError('Неизвестное действие: ' . $action, 'UNKNOWN_ACTION');
        $response['available_actions'] = ['list', 'join', 'leave', 'instance', 'stats'];
        if (isAdmin($userId)) {
            $response['available_actions'][] = 'admin_spawn';
            $response['available_actions'][] = 'admin_despawn';
        }
    }
    
} catch (Exception $e) {
    error_log("WORLD_BOSS_ERROR [User: $userId, Action: " . ($action ?? 'unknown') . "]: " . $e->getMessage());
    error_log("WORLD_BOSS_TRACE: " . $e->getTraceAsString());
    
    if (!isset($response['error'])) {
        setError('Произошла ошибка сервера. Попробуйте позже.', 'SERVER_ERROR');
    }
    
    // В режиме отладки показываем подробную ошибку
    if ((defined('DEBUG') && DEBUG) || isAdmin($userId)) {
        $response['debug'] = [
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ];
    }
}

// Устанавливаем правильные заголовки
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Возвращаем JSON-ответ
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
?>