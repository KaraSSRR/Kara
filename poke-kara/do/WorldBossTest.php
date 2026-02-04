<?php
// Финальный рабочий роутер для мировых боссов с системой битв
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Функция для вывода JSON
function output($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ПРАВИЛЬНОЕ подключение по вашему методу
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';

if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        output(['success' => false, 'error' => 'The problem with the connection files.', 'path' => $patch_global]);
    } else {
        require_once($patch_global);
    }
} else {
    output(['success' => false, 'error' => 'Empty global path']);
}

// Проверяем подключение к БД
if (!isset($mysqli) || !$mysqli) {
    output(['success' => false, 'error' => 'No database connection after including global.php']);
}

// Проверяем авторизацию
if (!isset($_SESSION['id']) || !$_SESSION['id']) {
    output(['success' => false, 'error' => 'Не авторизован', 'session_id' => $_SESSION['id'] ?? 'not set']);
}

$userId = clearInt($_SESSION['id']);
$action = $_POST['action'] ?? $_GET['action'] ?? 'unknown';

// Функция создания боя с мировым боссом (полностью адаптирована под вашу систему)
function createWorldBossBattle($userId, $instanceId, $raidData) {
    global $mysqli;
    
    try {
        // Формируем info_1 (игрок) и info_2 (мировой босс) по вашей системе
        $playerInfo = Info::_userInfoBattle($userId, 'world_boss');
        
        // Создаём мирового босса как "дикого" покемона с особыми характеристиками
        $worldBossData = [[
            'basenum' => $raidData['boss_id'], 
            'lvl' => 100, 
            'numb' => 0, 
            'boss' => 1,  // Помечаем как босса
            'catch' => 0, // Нельзя поймать
            'name' => $raidData['name'],
            'hp_current' => $raidData['hp_current'],
            'hp_max' => $raidData['hp_max'],
            'stats_multiplier' => 3.0,  // Увеличенные статы
            'world_boss_instance_id' => $instanceId // Добавляем связь с рейдом
        ]];
        
        $bossInfo = Info::_userInfoBattle(0, 'world_boss', ['npc' => $worldBossData]);
        
        $info_1 = json_encode($playerInfo, JSON_UNESCAPED_UNICODE);
        $info_2 = json_encode($bossInfo, JSON_UNESCAPED_UNICODE);
        
        $weather = 1;
        $weather_round = 0;
        $imgFight = '/img/battle/world_boss_bg.jpg'; // Особый фон для мирового босса
        $arenaLocationID = $raidData['location_id'];
        
        // Создаём бой БЕЗ столбцов turn и time_start (их нет в вашей таблице)
        $query = "INSERT INTO `battle`
            (`round`, `user_1`, `user_2`, `info_1`, `info_2`, `type`, `weather`, `weather_round`, `img`, `arena`, `world_boss_instance_id`)
            VALUES (
                1,
                $userId,
                0,
                '".$mysqli->real_escape_string($info_1)."',
                '".$mysqli->real_escape_string($info_2)."',
                'world_boss',
                '$weather',
                '$weather_round',
                '$imgFight',
                '$arenaLocationID',
                '$instanceId'
            )";
        
        if (!$mysqli->query($query)) {
            return ['success' => false, 'error' => 'Ошибка создания боя: ' . $mysqli->error];
        }
        
        $battle_id = $mysqli->insert_id;
        
        // Добавляем запись в battle_log
        $mysqli->query("INSERT INTO `battle_log` 
            (`battle`, `round`, `text`, `end`, `user`, `starter`) 
            VALUES ($battle_id, 0, 'Бой с мировым боссом {$raidData['name']} начинается!<br>', 0, $userId, 1)");
        
        // Обновляем статус пользователя
        $mysqli->query("UPDATE users SET status = 'battle', status_id = $battle_id WHERE id = $userId");
        
        return [
            'success' => true,
            'battle_id' => $battle_id,
            'message' => 'Бой с мировым боссом начался!'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Исключение при создании боя: ' . $e->getMessage()];
    }
}

// Действие: обработка результата боя с мировым боссом
if ($action === 'battle_result') {
    $battleId = clearInt($_POST['battle_id'] ?? 0);
    $result = $_POST['result'] ?? ''; // 'win', 'lose', 'draw'
    $damageDealt = clearInt($_POST['damage_dealt'] ?? 0);
    $finalBossHp = clearInt($_POST['final_boss_hp'] ?? 0);
    
    if ($battleId <= 0) {
        output(['success' => false, 'error' => 'Неверный ID боя']);
    }
    
    try {
        // Получаем данные боя
        $battleQuery = $mysqli->query("
            SELECT b.*, wbi.hp_current, wbi.hp_max, wbi.id as instance_id, wbi.name
            FROM battle b
            LEFT JOIN world_boss_instances wbi ON wbi.id = b.world_boss_instance_id
            WHERE b.id = $battleId AND b.user_1 = $userId AND b.type = 'world_boss'
        ");
        
        if (!$battleQuery || $battleQuery->num_rows == 0) {
            output(['success' => false, 'error' => 'Бой с мировым боссом не найден']);
        }
        
        $battle = $battleQuery->fetch_assoc();
        $instanceId = $battle['instance_id'];
        
        if (!$instanceId) {
            output(['success' => false, 'error' => 'Инстанс мирового босса не найден']);
        }
        
        // Начинаем транзакцию
        $mysqli->begin_transaction();
        
        try {
            // Обновляем урон участника
            $updateParticipant = $mysqli->query("
                UPDATE world_boss_participants 
                SET damage_dealt = damage_dealt + $damageDealt,
                    participation_score = participation_score + " . max(1, (int)($damageDealt / 1000)) . "
                WHERE instance_id = $instanceId AND user_id = $userId
            ");
            
            if (!$updateParticipant) {
                throw new Exception("Ошибка обновления участника: " . $mysqli->error);
            }
            
            // Обновляем HP босса
            $newBossHp = max(0, $finalBossHp);
            $totalDamageDealt = $battle['hp_max'] - $newBossHp;
            
            $updateBoss = $mysqli->query("
                UPDATE world_boss_instances 
                SET hp_current = $newBossHp,
                    total_damage_dealt = $totalDamageDealt
                WHERE id = $instanceId
            ");
            
            if (!$updateBoss) {
                throw new Exception("Ошибка обновления босса: " . $mysqli->error);
            }
            
            // Проверяем, побеждён ли босс
            $bossDefeated = false;
            if ($newBossHp <= 0) {
                $mysqli->query("
                    UPDATE world_boss_instances 
                    SET status = 'defeated' 
                    WHERE id = $instanceId
                ");
                $bossDefeated = true;
                
                // Уведомляем всех участников о победе
                $participantsQuery = $mysqli->query("
                    SELECT user_id FROM world_boss_participants 
                    WHERE instance_id = $instanceId
                ");
                
                if ($participantsQuery) {
                    while ($participant = $participantsQuery->fetch_assoc()) {
                        // Здесь можно добавить уведомления участникам
                    }
                }
            }
            
            // Возвращаем игрока в статус рейда
            $mysqli->query("UPDATE users SET status = 'world_boss', status_id = NULL WHERE id = $userId");
            
            $mysqli->commit();
            
            $message = $bossDefeated ? 
                "🎉 Мировой босс {$battle['name']} побеждён! Урон: $damageDealt" : 
                "⚔️ Бой завершён! Нанесено урона: $damageDealt";
            
            output([
                'success' => true,
                'message' => $message,
                'damage_dealt' => $damageDealt,
                'boss_hp_remaining' => $newBossHp,
                'boss_hp_max' => $battle['hp_max'],
                'boss_defeated' => $bossDefeated,
                'total_damage_dealt' => $totalDamageDealt,
                'hp_percentage' => round(($newBossHp / $battle['hp_max']) * 100, 2)
            ]);
            
        } catch (Exception $e) {
            $mysqli->rollback();
            output(['success' => false, 'error' => 'Ошибка обработки результата: ' . $e->getMessage()]);
        }
        
    } catch (Exception $e) {
        output(['success' => false, 'error' => 'Исключение при обработке результата: ' . $e->getMessage()]);
    }
}

// Действие: отладка
if ($action === 'debug') {
    $debug = [];
    
    // Проверяем пользователя
    try {
        $result = $mysqli->query("SELECT id, login, location, lvl, status FROM users WHERE id = $userId");
        $debug['user'] = $result ? $result->fetch_assoc() : null;
    } catch (Exception $e) {
        $debug['user_error'] = $e->getMessage();
    }
    
    // Проверяем таблицы
    $tables = ['world_bosses', 'world_boss_instances', 'world_boss_participants', 'battle'];
    foreach ($tables as $table) {
        try {
            $result = $mysqli->query("SHOW TABLES LIKE '$table'");
            $debug['tables'][$table] = $result && $result->num_rows > 0;
            
            if ($debug['tables'][$table]) {
                $countResult = $mysqli->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $countResult->fetch_assoc();
                $debug['table_counts'][$table] = $count['count'];
            }
        } catch (Exception $e) {
            $debug['tables'][$table] = false;
            $debug['table_errors'][$table] = $e->getMessage();
        }
    }
    
    // Проверяем структуру battle
    try {
        $result = $mysqli->query("SHOW COLUMNS FROM `battle`");
        $debug['battle_columns'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Exception $e) {
        $debug['battle_columns_error'] = $e->getMessage();
    }
    
    // Проверяем активных боссов детально
    try {
        $result = $mysqli->query("
            SELECT wbi.*, wb.name, wb.level_min, wb.level_max 
            FROM world_boss_instances wbi 
            LEFT JOIN world_bosses wb ON wb.id = wbi.boss_id 
            WHERE wbi.status IN ('spawning', 'active')
        ");
        $debug['active_bosses_raw'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Проверяем участников
        $result3 = $mysqli->query("
            SELECT wbp.*, u.login 
            FROM world_boss_participants wbp 
            LEFT JOIN users u ON u.id = wbp.user_id 
            ORDER BY wbp.join_time DESC LIMIT 10
        ");
        $debug['recent_participants'] = $result3 ? $result3->fetch_all(MYSQLI_ASSOC) : [];
        
        // Проверяем активные бои с боссами
        $result4 = $mysqli->query("
            SELECT b.*, wbi.name as boss_name
            FROM battle b
            LEFT JOIN world_boss_instances wbi ON wbi.id = b.world_boss_instance_id
            WHERE b.type = 'world_boss' AND b.user_1 = $userId
            ORDER BY b.id DESC LIMIT 5
        ");
        $debug['user_boss_battles'] = $result4 ? $result4->fetch_all(MYSQLI_ASSOC) : [];
        
    } catch (Exception $e) {
        $debug['active_bosses_error'] = $e->getMessage();
    }
    
    output(['success' => true, 'debug' => $debug, 'action' => $action]);
}

// Действие: создание таблиц
if ($action === 'create_tables') {
    try {
        // Создаём таблицу world_bosses
        $sql1 = "CREATE TABLE IF NOT EXISTS `world_bosses` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `boss_id` int(11) NOT NULL DEFAULT 150,
            `location_id` int(11) NOT NULL DEFAULT 1,
            `name` varchar(255) NOT NULL DEFAULT 'Тестовый босс',
            `level_min` int(11) DEFAULT 1,
            `level_max` int(11) DEFAULT 100,
            `hp_max` bigint(20) NOT NULL DEFAULT 500000,
            `duration_minutes` int(11) DEFAULT 60,
            `max_participants` int(11) DEFAULT 30,
            `is_active` tinyint(1) DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        
        $result1 = $mysqli->query($sql1);
        
        // Создаём таблицу world_boss_instances
        $sql2 = "CREATE TABLE IF NOT EXISTS `world_boss_instances` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `boss_id` int(11) NOT NULL DEFAULT 1,
            `location_id` int(11) NOT NULL DEFAULT 1,
            `hp_current` bigint(20) NOT NULL DEFAULT 500000,
            `hp_max` bigint(20) NOT NULL DEFAULT 500000,
            `status` varchar(20) DEFAULT 'active',
            `spawn_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `expire_time` datetime NOT NULL DEFAULT '2025-12-31 23:59:59',
            `total_participants` int(11) DEFAULT 0,
            `total_damage_dealt` bigint(20) DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        
        $result2 = $mysqli->query($sql2);
        
        // Создаём таблицу world_boss_participants
        $sql3 = "CREATE TABLE IF NOT EXISTS `world_boss_participants` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `instance_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `join_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `damage_dealt` bigint(20) DEFAULT 0,
            `participation_score` int(11) DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_participation` (`instance_id`, `user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        
        $result3 = $mysqli->query($sql3);
        
        // Проверяем и добавляем столбец для связи с мировыми боссами в таблицу battle
        $checkColumn = $mysqli->query("SHOW COLUMNS FROM `battle` LIKE 'world_boss_instance_id'");
        if ($checkColumn->num_rows == 0) {
            $sql4 = "ALTER TABLE `battle` ADD COLUMN `world_boss_instance_id` int(11) DEFAULT NULL";
            $result4 = $mysqli->query($sql4);
            
            if (!$result4) {
                output(['success' => false, 'error' => 'Ошибка добавления столбца world_boss_instance_id: ' . $mysqli->error]);
            }
        }
        
        if ($result1 && $result2 && $result3) {
            output(['success' => true, 'message' => 'Все таблицы созданы успешно с адаптацией под вашу систему битв']);
        } else {
            output(['success' => false, 'error' => 'Ошибка создания таблиц: ' . $mysqli->error]);
        }
        
    } catch (Exception $e) {
        output(['success' => false, 'error' => 'Исключение при создании таблиц: ' . $e->getMessage()]);
    }
}

// Действие: создание тестового босса
if ($action === 'create_test') {
    try {
        // Сначала добавляем шаблон босса
        $mysqli->query("INSERT IGNORE INTO world_bosses (id, name, boss_id, location_id, hp_max, level_min, level_max, max_participants) 
                       VALUES (1, 'Тестовый Мьюту', 150, 1, 500000, 1, 100, 30)");
        
        // Теперь создаём инстанс с далёким сроком истечения
        $expireTime = date('Y-m-d H:i:s', time() + 86400); // +24 часа
        $spawnTime = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO world_boss_instances (boss_id, location_id, hp_current, hp_max, status, spawn_time, expire_time, total_participants, total_damage_dealt) 
                VALUES (1, 1, 500000, 500000, 'active', '$spawnTime', '$expireTime', 0, 0)";
        
        $result = $mysqli->query($sql);
        
        if ($result) {
            $instanceId = $mysqli->insert_id;
            
            // Проверяем что босс действительно создался
            $checkQuery = $mysqli->query("
                SELECT wbi.*, wb.name 
                FROM world_boss_instances wbi 
                LEFT JOIN world_bosses wb ON wb.id = wbi.boss_id 
                WHERE wbi.id = $instanceId
            ");
            $createdBoss = $checkQuery ? $checkQuery->fetch_assoc() : null;
            
            output([
                'success' => true, 
                'message' => 'Тестовый босс "Мьюту" создан в локации 1!',
                'instance_id' => $instanceId,
                'expire_time' => $expireTime,
                'spawn_time' => $spawnTime,
                'created_boss' => $createdBoss
            ]);
        } else {
            output(['success' => false, 'error' => 'Ошибка создания инстанса: ' . $mysqli->error]);
        }
        
    } catch (Exception $e) {
        output(['success' => false, 'error' => 'Исключение при создании босса: ' . $e->getMessage()]);
    }
}

// Действие: список боссов
if ($action === 'list') {
    try {
        $sql = "SELECT 
                wbi.id,
                wbi.boss_id,
                wbi.location_id,
                wbi.hp_current,
                wbi.hp_max,
                wbi.status,
                wbi.spawn_time,
                wbi.expire_time,
                wbi.total_participants,
                wbi.total_damage_dealt,
                COALESCE(wb.name, 'Тестовый босс') as name,
                COALESCE(wb.level_min, 1) as level_min,
                COALESCE(wb.level_max, 100) as level_max,
                COALESCE(wb.max_participants, 30) as max_participants,
                CONCAT('Локация ', wbi.location_id) as location_name,
                (wbi.expire_time > NOW()) as not_expired,
                TIMESTAMPDIFF(SECOND, NOW(), wbi.expire_time) as seconds_left,
                ROUND((wbi.hp_current / wbi.hp_max) * 100, 2) as hp_percentage
            FROM world_boss_instances wbi
            LEFT JOIN world_bosses wb ON wb.id = wbi.boss_id
            WHERE wbi.status = 'active'
            ORDER BY wbi.id DESC";
        
        $result = $mysqli->query($sql);
        $bosses = [];
        $allBosses = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $allBosses[] = $row;
                
                // Добавляем в активных только не просроченных
                if ($row['not_expired']) {
                    $bosses[] = $row;
                }
            }
        }
        
        // Получаем данные пользователя
        $userResult = $mysqli->query("SELECT location, lvl FROM users WHERE id = $userId");
        $userData = $userResult ? $userResult->fetch_assoc() : ['location' => 1, 'lvl' => 50];
        
        output([
            'success' => true,
            'bosses' => $bosses,
            'all_bosses_with_expired' => $allBosses,
            'count' => count($bosses),
            'total_in_db' => count($allBosses),
            'user_level' => (int)$userData['lvl'],
            'user_location' => (int)$userData['location'],
            'sql_query' => $sql,
            'current_time' => date('Y-m-d H:i:s'),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        output(['success' => false, 'error' => 'Ошибка получения списка: ' . $e->getMessage()]);
    }
}

// Действие: присоединение к рейду
if ($action === 'join') {
    $instanceId = clearInt($_POST['instance_id'] ?? 0);
    
    if ($instanceId <= 0) {
        output(['success' => false, 'error' => 'Неверный ID рейда']);
    }
    
    try {
        // Проверяем существование инстанса
        $instanceQuery = $mysqli->query("
            SELECT wbi.*, wb.name, wb.level_min, wb.level_max, wb.max_participants
            FROM world_boss_instances wbi
            LEFT JOIN world_bosses wb ON wb.id = wbi.boss_id
            WHERE wbi.id = $instanceId AND wbi.status = 'active' AND wbi.expire_time > NOW()
        ");
        
        if (!$instanceQuery || $instanceQuery->num_rows == 0) {
            output(['success' => false, 'error' => 'Рейд не найден, уже завершён или истёк']);
        }
        
        $instance = $instanceQuery->fetch_assoc();
        
        // Получаем данные пользователя
        $userQuery = $mysqli->query("SELECT lvl, status FROM users WHERE id = $userId");
        if (!$userQuery) {
            output(['success' => false, 'error' => 'Ошибка получения данных пользователя']);
        }
        
        $user = $userQuery->fetch_assoc();
        $userLevel = (int)$user['lvl'];
        
        // Проверяем уровень
        if ($userLevel < $instance['level_min'] || $userLevel > $instance['level_max']) {
            output(['success' => false, 'error' => "Ваш уровень ($userLevel) не подходит для этого босса (требуется {$instance['level_min']}-{$instance['level_max']})"]);
        }
        
        // Проверяем, не участвует ли уже
        $existingParticipant = $mysqli->query("
            SELECT id FROM world_boss_participants 
            WHERE instance_id = $instanceId AND user_id = $userId
        ");
        
        if ($existingParticipant && $existingParticipant->num_rows > 0) {
            output(['success' => true, 'message' => 'Вы уже участвуете в этом рейде!', 'already_joined' => true]);
        }
        
        // Проверяем лимит участников
        if ($instance['total_participants'] >= $instance['max_participants']) {
            output(['success' => false, 'error' => 'Рейд переполнен (участников: ' . $instance['total_participants'] . '/' . $instance['max_participants'] . ')']);
        }
        
        // Начинаем транзакцию
        $mysqli->begin_transaction();
        
        try {
            // Добавляем участника
            $addParticipant = $mysqli->query("
                INSERT INTO world_boss_participants 
                (instance_id, user_id, join_time, damage_dealt, participation_score)
                VALUES ($instanceId, $userId, NOW(), 0, 0)
            ");
            
            if (!$addParticipant) {
                throw new Exception("Ошибка добавления участника: " . $mysqli->error);
            }
            
            // Увеличиваем счётчик участников
            $updateInstance = $mysqli->query("
                UPDATE world_boss_instances 
                SET total_participants = total_participants + 1 
                WHERE id = $instanceId
            ");
            
            if (!$updateInstance) {
                throw new Exception("Ошибка обновления счётчика: " . $mysqli->error);
            }
            
            // Устанавливаем статус игрока в рейд
            $updateUser = $mysqli->query("
                UPDATE users 
                SET status = 'world_boss' 
                WHERE id = $userId
            ");
            
            if (!$updateUser) {
                throw new Exception("Ошибка обновления статуса пользователя: " . $mysqli->error);
            }
            
            $mysqli->commit();
            
            output([
                'success' => true,
                'message' => 'Вы успешно присоединились к рейду против ' . $instance['name'] . '!',
                'instance_id' => $instanceId,
                'boss_name' => $instance['name'],
                'participants' => $instance['total_participants'] + 1
            ]);
            
        } catch (Exception $e) {
            $mysqli->rollback();
            output(['success' => false, 'error' => 'Ошибка присоединения: ' . $e->getMessage()]);
        }
        
    } catch (Exception $e) {
        output(['success' => false, 'error' => 'Исключение при присоединении: ' . $e->getMessage()]);
    }
}

// Действие: начать бой с боссом
if ($action === 'start_battle') {
    $instanceId = clearInt($_POST['instance_id'] ?? 0);
    
    if ($instanceId <= 0) {
        output(['success' => false, 'error' => 'Неверный ID рейда']);
    }
    
    try {
        // Проверяем что пользователь участвует в рейде
        $participantCheck = $mysqli->query("
            SELECT wbp.*, wbi.*, wb.name, wb.boss_id
            FROM world_boss_participants wbp
            INNER JOIN world_boss_instances wbi ON wbi.id = wbp.instance_id
            INNER JOIN world_bosses wb ON wb.id = wbi.boss_id
            WHERE wbp.instance_id = $instanceId 
            AND wbp.user_id = $userId 
            AND wbi.status = 'active'
        ");
        
        if (!$participantCheck || $participantCheck->num_rows == 0) {
            output(['success' => false, 'error' => 'Вы не участвуете в этом рейде или рейд неактивен']);
        }
        
        $raidData = $participantCheck->fetch_assoc();
        
        // Проверяем что пользователь не в другом бою (проверяем только незавершённые бои)
        $battleCheck = $mysqli->query("
            SELECT id, type FROM battle 
            WHERE (user_1 = $userId OR user_2 = $userId) 
            AND type IN ('pve', 'pvp', 'world_boss', 'wild')
            AND (answer IS NULL OR answer = '')
            ORDER BY id DESC LIMIT 1
        ");
        
        if ($battleCheck && $battleCheck->num_rows > 0) {
            $existingBattle = $battleCheck->fetch_assoc();
            output([
                'success' => false, 
                'error' => 'Вы уже участвуете в активном бою (ID: ' . $existingBattle['id'] . ', тип: ' . $existingBattle['type'] . '). Завершите его сначала.',
                'existing_battle_id' => $existingBattle['id'],
                'battle_url' => '/battle?id=' . $existingBattle['id']
            ]);
        }
        
        // Создаём бой с мировым боссом
        $battleResult = createWorldBossBattle($userId, $instanceId, $raidData);
        
        if ($battleResult['success']) {
            output([
                'success' => true,
                'message' => $battleResult['message'],
                'battle_id' => $battleResult['battle_id'],
                'battle_url' => '/battle?id=' . $battleResult['battle_id'],
                'boss_name' => $raidData['name']
            ]);
        } else {
            output(['success' => false, 'error' => $battleResult['error']]);
        }
        
    } catch (Exception $e) {
        output(['success' => false, 'error' => 'Ошибка запуска боя: ' . $e->getMessage()]);
    }
}

// Действие: покидание рейда
if ($action === 'leave') {
    $instanceId = clearInt($_POST['instance_id'] ?? 0);
    
    if ($instanceId <= 0) {
        output(['success' => false, 'error' => 'Неверный ID рейда']);
    }
    
    try {
        // Проверяем участие
        $participantCheck = $mysqli->query("
            SELECT id FROM world_boss_participants 
            WHERE instance_id = $instanceId AND user_id = $userId
        ");
        
        if (!$participantCheck || $participantCheck->num_rows == 0) {
            output(['success' => false, 'error' => 'Вы не участвуете в этом рейде']);
        }
        
        // Начинаем транзакцию
        $mysqli->begin_transaction();
        
        try {
            // Удаляем участника
            $removeParticipant = $mysqli->query("
                DELETE FROM world_boss_participants 
                WHERE instance_id = $instanceId AND user_id = $userId
            ");
            
            if (!$removeParticipant) {
                throw new Exception("Ошибка удаления участника: " . $mysqli->error);
            }
            
            // Уменьшаем счётчик участников
            $updateInstance = $mysqli->query("
                UPDATE world_boss_instances 
                SET total_participants = GREATEST(0, total_participants - 1) 
                WHERE id = $instanceId
            ");
            
            if (!$updateInstance) {
                throw new Exception("Ошибка обновления счётчика: " . $mysqli->error);
            }
            
            // Возвращаем игрока в свободное состояние
            $updateUser = $mysqli->query("
                UPDATE users 
                SET status = 'free' 
                WHERE id = $userId
            ");
            
            if (!$updateUser) {
                throw new Exception("Ошибка обновления статуса пользователя: " . $mysqli->error);
            }
            
            $mysqli->commit();
            
            output([
                'success' => true,
                'message' => 'Вы покинули рейд'
            ]);
            
        } catch (Exception $e) {
            $mysqli->rollback();
            output(['success' => false, 'error' => 'Ошибка при покидании рейда: ' . $e->getMessage()]);
        }
        
    } catch (Exception $e) {
        output(['success' => false, 'error' => 'Исключение при покидании рейда: ' . $e->getMessage()]);
    }
}

// Действие: получение данных конкретного рейда
if ($action === 'instance') {
    $instanceId = clearInt($_GET['instance_id'] ?? 0);
    
    if ($instanceId <= 0) {
        output(['success' => false, 'error' => 'Неверный ID рейда']);
    }
    
    try {
        // Получаем данные инстанса
        $instanceQuery = $mysqli->query("
            SELECT wbi.*, wb.name, wb.level_min, wb.level_max, wb.max_participants,
                   ROUND((wbi.hp_current / wbi.hp_max) * 100, 2) as hp_percentage
            FROM world_boss_instances wbi
            LEFT JOIN world_bosses wb ON wb.id = wbi.boss_id
            WHERE wbi.id = $instanceId
        ");
        
        if (!$instanceQuery || $instanceQuery->num_rows == 0) {
            output(['success' => false, 'error' => 'Рейд не найден']);
        }
        
        $instance = $instanceQuery->fetch_assoc();
        
        // Получаем участников
        $participantsQuery = $mysqli->query("
            SELECT wbp.*, u.login, u.user_group
            FROM world_boss_participants wbp
            INNER JOIN users u ON u.id = wbp.user_id
            WHERE wbp.instance_id = $instanceId
            ORDER BY wbp.participation_score DESC, wbp.damage_dealt DESC
            LIMIT 50
        ");
        
        $participants = [];
        if ($participantsQuery) {
            while ($participant = $participantsQuery->fetch_assoc()) {
                $participants[] = $participant;
            }
        }
        
        // Проверяем участвует ли текущий пользователь
        $userParticipating = false;
        foreach ($participants as $participant) {
            if ($participant['user_id'] == $userId) {
                $userParticipating = true;
                break;
            }
        }
        
        output([
            'success' => true,
            'instance' => $instance,
            'participants' => $participants,
            'user_participating' => $userParticipating,
            'participants_count' => count($participants),
            'current_user_id' => $userId
        ]);
        
    } catch (Exception $e) {
        output(['success' => false, 'error' => 'Ошибка получения данных рейда: ' . $e->getMessage()]);
    }
}

// Действие: удаление просроченных
if ($action === 'cleanup') {
    try {
        // Помечаем просроченных как expired
        $result = $mysqli->query("
            UPDATE world_boss_instances 
            SET status = 'expired' 
            WHERE status = 'active' 
            AND expire_time <= NOW()
        ");
        
        $affected = $mysqli->affected_rows;
        
        output([
            'success' => true,
            'message' => "Обновлено $affected просроченных боссов",
            'affected_rows' => $affected
        ]);
        
    } catch (Exception $e) {
        output(['success' => false, 'error' => 'Ошибка очистки: ' . $e->getMessage()]);
    }
}

// Действие: удаление конкретного босса (для тестирования)
if ($action === 'delete_boss') {
    $instanceId = clearInt($_POST['instance_id'] ?? 0);
    
    if ($instanceId <= 0) {
        output(['success' => false, 'error' => 'Неверный ID босса']);
    }
    
    try {
        // Удаляем участников
        $mysqli->query("DELETE FROM world_boss_participants WHERE instance_id = $instanceId");
        
        // Удаляем связанные бои
        $mysqli->query("DELETE FROM battle WHERE world_boss_instance_id = $instanceId");
        
        // Удаляем инстанс
        $result = $mysqli->query("DELETE FROM world_boss_instances WHERE id = $instanceId");
        
        if ($result) {
            output([
                'success' => true,
                'message' => 'Босс и связанные данные удалены'
            ]);
        } else {
            output(['success' => false, 'error' => 'Ошибка удаления: ' . $mysqli->error]);
        }
        
    } catch (Exception $e) {
        output(['success' => false, 'error' => 'Исключение при удалении: ' . $e->getMessage()]);
    }
}

// Неизвестное действие
output([
    'success' => false, 
    'error' => 'Неизвестное действие: ' . $action, 
    'available_actions' => [
        'debug', 'create_tables', 'create_test', 'list', 'join', 'leave', 
        'instance', 'start_battle', 'battle_result', 'cleanup', 'delete_boss'
    ],
    'user_id' => $userId,
    'current_time' => date('Y-m-d H:i:s')
]);
?>