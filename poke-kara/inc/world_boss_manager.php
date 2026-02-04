<?php
/**
 * Менеджер мировых боссов
 * Отвечает за спавн, управление и базовые операции
 */

class WorldBossManager {
    
    /**
     * Спавн запланированных боссов по расписанию
     */
    public static function spawnScheduledBosses(): void {
        global $mysqli;
        if (!$mysqli) return;
        
        $now = new DateTime();
        $currentHour = (int)$now->format('H');
        $currentMinute = (int)$now->format('i');
        
        // Получаем всех активных боссов
        $stmt = $mysqli->prepare("
            SELECT * FROM world_bosses 
            WHERE is_active = 1
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($boss = $result->fetch_assoc()) {
            if (self::shouldSpawnNow($boss, $now)) {
                $instanceId = self::spawnBoss((int)$boss['id'], (int)$boss['location_id']);
                if ($instanceId) {
                    error_log("WORLD_BOSS: Spawned boss {$boss['name']} (ID: $instanceId) at location {$boss['location_id']}");
                }
            }
        }
        $stmt->close();
    }
    
    /**
     * Проверяет, нужно ли спавнить босса сейчас
     */
    private static function shouldSpawnNow(array $boss, DateTime $now): bool {
        global $mysqli;
        if (!$mysqli) return false;
        
        // Проверяем, есть ли уже активный инстанс этого босса
        $stmt = $mysqli->prepare("
            SELECT id FROM world_boss_instances 
            WHERE boss_id = ? AND status IN ('spawning', 'active')
            LIMIT 1
        ");
        $stmt->bind_param('i', $boss['id']);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            return false; // Уже есть активный
        }
        
        // Проверяем cooldown с последнего спавна
        $stmt = $mysqli->prepare("
            SELECT spawn_time FROM world_boss_instances 
            WHERE boss_id = ? 
            ORDER BY spawn_time DESC LIMIT 1
        ");
        $stmt->bind_param('i', $boss['id']);
        $stmt->execute();
        $lastSpawn = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($lastSpawn) {
            $cooldownEnd = new DateTime($lastSpawn['spawn_time']);
            $cooldownEnd->add(new DateInterval('PT' . $boss['cooldown_minutes'] . 'M'));
            
            if ($now < $cooldownEnd) {
                return false; // Ещё cooldown
            }
        }
        
        // Простая проверка расписания (каждые N часов)
        return self::matchesSchedule($boss['spawn_schedule'], $now);
    }
    
    /**
     * Проверка соответствия расписанию (упрощённая версия cron)
     */
    private static function matchesSchedule(string $schedule, DateTime $now): bool {
        // Пример: "0 */6 * * *" означает каждые 6 часов в 0 минут
        // Упрощённая реализация для основных случаев
        
        if (empty($schedule)) return false;
        
        $parts = explode(' ', $schedule);
        if (count($parts) < 5) return false;
        
        $minute = $parts[0];    // 0-59
        $hour   = $parts[1];    // 0-23 или */N
        
        $currentMinute = (int)$now->format('i');
        $currentHour   = (int)$now->format('H');
        
        // Проверяем минуты
        if ($minute !== '*' && (int)$minute !== $currentMinute) {
            return false;
        }
        
        // Проверяем часы
        if ($hour === '*') {
            return true; // Каждый час
        }
        
        if (strpos($hour, '*/') === 0) {
            // Каждые N часов
            $interval = (int)substr($hour, 2);
            return ($currentHour % $interval) === 0;
        }
        
        if (strpos($hour, ',') !== false) {
            // Конкретные часы: "12,18"
            $hours = array_map('intval', explode(',', $hour));
            return in_array($currentHour, $hours);
        }
        
        // Конкретный час
        return (int)$hour === $currentHour;
    }
    
    /**
     * Создание инстанса босса
     */
    public static function spawnBoss(int $bossId, int $locationId, bool $adminSpawn = false): ?int {
        global $mysqli;
        if (!$mysqli) return null;
        
        $stmt = $mysqli->prepare("SELECT * FROM world_bosses WHERE id = ? AND is_active = 1");
        $stmt->bind_param('i', $bossId);
        $stmt->execute();
        $boss = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$boss) return null;
        
        // Для админ-спавна - проверяем есть ли уже активный босс в локации
        if ($adminSpawn) {
            $stmt = $mysqli->prepare("
                SELECT id FROM world_boss_instances 
                WHERE location_id = ? AND status IN ('spawning', 'active')
                LIMIT 1
            ");
            $stmt->bind_param('i', $locationId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($existing) {
                return null; // Уже есть активный босс в этой локации
            }
        }
        
        $now = date('Y-m-d H:i:s');
        $expireTime = date('Y-m-d H:i:s', time() + ($boss['duration_minutes'] * 60));
        
        $stmt = $mysqli->prepare("
            INSERT INTO world_boss_instances 
            (boss_id, location_id, hp_current, hp_max, status, spawn_time, expire_time, admin_spawned)
            VALUES (?, ?, ?, ?, 'active', ?, ?, ?)
        ");
        $adminFlag = $adminSpawn ? 1 : 0;
        $stmt->bind_param('iiiissi', 
            $bossId, $locationId, $boss['hp_max'], $boss['hp_max'], $now, $expireTime, $adminFlag
        );
        
        if ($stmt->execute()) {
            $instanceId = $mysqli->insert_id;
            $stmt->close();
            
            // Уведомляем игроков в локации
            self::notifyPlayersInLocation($locationId, $boss['name'], $adminSpawn);
            
            return $instanceId;
        }
        
        $stmt->close();
        return null;
    }
    
    /**
     * Принудительное удаление босса (для администратора)
     */
    public static function despawnBoss(int $instanceId, int $adminId): bool {
        global $mysqli;
        if (!$mysqli) return false;
        
        $stmt = $mysqli->prepare("
            UPDATE world_boss_instances 
            SET status = 'admin_removed', despawned_by = ?, despawn_time = NOW()
            WHERE id = ? AND status IN ('spawning', 'active')
        ");
        $stmt->bind_param('ii', $adminId, $instanceId);
        $success = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        
        if ($success) {
            // Логируем действие админа
            error_log("WORLD_BOSS: Admin $adminId manually despawned boss instance $instanceId");
            
            // Уведомляем участников рейда
            self::notifyRaidDisbanded($instanceId);
        }
        
        return $success;
    }
    
    /**
     * Удаление просроченных боссов
     */
    public static function despawnExpiredBosses(): void {
        global $mysqli;
        if (!$mysqli) return;
        
        $now = date('Y-m-d H:i:s');
        
        // Находим просроченных
        $stmt = $mysqli->prepare("
            SELECT id, boss_id FROM world_boss_instances 
            WHERE status IN ('spawning', 'active') AND expire_time <= ?
        ");
        $stmt->bind_param('s', $now);
        $stmt->execute();
        $expired = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($expired as $instance) {
            // Помечаем как истёкшего
            $stmt = $mysqli->prepare("
                UPDATE world_boss_instances 
                SET status = 'expired', despawn_time = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param('i', $instance['id']);
            $stmt->execute();
            $stmt->close();
            
            error_log("WORLD_BOSS: Boss instance {$instance['id']} expired");
        }
    }
    
    /**
     * Получение активных боссов (исправленная версия)
     */
    public static function getActiveBosses(?int $locationId = null): array {
        global $mysqli;
        if (!$mysqli) return [];
        
        $sql = "
            SELECT 
                wbi.id,
                wbi.boss_id,
                wbi.location_id,
                wbi.hp_current,
                wbi.hp_max,
                wbi.status,
                wbi.spawn_time,
                wbi.expire_time,
                wbi.total_damage_dealt,
                wbi.total_participants,
                COALESCE(wb.name, CONCAT('Босс #', wbi.boss_id)) as name,
                COALESCE(wb.description, 'Мировой босс') as description,
                COALESCE(wb.level_min, 1) as level_min,
                COALESCE(wb.level_max, 100) as level_max,
                COALESCE(wb.max_participants, 30) as max_participants,
                COALESCE(bl.name, CONCAT('Локация ', wbi.location_id)) as location_name
            FROM world_boss_instances wbi
            LEFT JOIN world_bosses wb ON wb.id = wbi.boss_id
            LEFT JOIN base_location bl ON bl.id = wbi.location_id
            WHERE wbi.status IN ('spawning', 'active')
            AND wbi.expire_time > NOW()
        ";
        
        $params = [];
        $types = '';
        
        if ($locationId !== null) {
            $sql .= " AND wbi.location_id = ?";
            $params[] = $locationId;
            $types .= 'i';
        }
        
        $sql .= " ORDER BY wbi.spawn_time DESC";
        
        try {
            if (!empty($params)) {
                $stmt = $mysqli->prepare($sql);
                if (!$stmt) {
                    error_log("WORLD_BOSS: Prepare failed: " . $mysqli->error);
                    return [];
                }
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $mysqli->query($sql);
            }
            
            if (!$result) {
                error_log("WORLD_BOSS: Query failed: " . $mysqli->error);
                return [];
            }
            
            $bosses = [];
            while ($row = $result->fetch_assoc()) {
                // Убеждаемся что все необходимые поля присутствуют
                $boss = [
                    'id' => (int)$row['id'],
                    'boss_id' => (int)$row['boss_id'],
                    'location_id' => (int)$row['location_id'],
                    'hp_current' => (int)$row['hp_current'],
                    'hp_max' => (int)$row['hp_max'],
                    'status' => $row['status'],
                    'spawn_time' => $row['spawn_time'],
                    'expire_time' => $row['expire_time'],
                    'total_damage_dealt' => (int)($row['total_damage_dealt'] ?? 0),
                    'total_participants' => (int)($row['total_participants'] ?? 0),
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'level_min' => (int)$row['level_min'],
                    'level_max' => (int)$row['level_max'],
                    'max_participants' => (int)$row['max_participants'],
                    'location_name' => $row['location_name']
                ];
                
                $bosses[] = $boss;
            }
            
            if (isset($stmt)) {
                $stmt->close();
            }
            
            error_log("WORLD_BOSS: Found " . count($bosses) . " active bosses" . ($locationId ? " in location $locationId" : ""));
            
            return $bosses;
            
        } catch (Exception $e) {
            error_log("WORLD_BOSS: Exception in getActiveBosses: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение данных конкретного инстанса
     */
    public static function getBossInstance(int $instanceId): ?array {
        global $mysqli;
        if (!$mysqli) return null;
        
        $stmt = $mysqli->prepare("
            SELECT 
                wbi.*,
                COALESCE(wb.name, CONCAT('Босс #', wbi.boss_id)) as name,
                COALESCE(wb.description, 'Мировой босс') as description,
                COALESCE(wb.level_min, 1) as level_min,
                COALESCE(wb.level_max, 100) as level_max,
                COALESCE(wb.max_participants, 30) as max_participants,
                wb.base_rewards,
                wb.rank_rewards,
                wb.first_kill_rewards,
                COALESCE(bl.name, CONCAT('Локация ', wbi.location_id)) as location_name
            FROM world_boss_instances wbi
            LEFT JOIN world_bosses wb ON wb.id = wbi.boss_id
            LEFT JOIN base_location bl ON bl.id = wbi.location_id
            WHERE wbi.id = ?
        ");
        $stmt->bind_param('i', $instanceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result ?: null;
    }
    
    /**
     * Проверка возможности участия игрока
     */
    public static function canUserParticipate(int $userId, int $instanceId): bool {
        global $mysqli;
        if (!$mysqli) return false;
        
        // Проверяем существование инстанса
        $instance = self::getBossInstance($instanceId);
        if (!$instance || !in_array($instance['status'], ['spawning', 'active'])) {
            return false;
        }
        
        // Проверяем уровень игрока
        $stmt = $mysqli->prepare("SELECT lvl, status FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$user) return false;
        
        $userLevel = (int)$user['lvl'];
        if ($userLevel < $instance['level_min'] || $userLevel > $instance['level_max']) {
            return false;
        }
        
        // Проверяем, не находится ли игрок в обычном бою
        if ($user['status'] !== 'free') {
            return false;
        }
        
        // Проверяем лимит участников
        if ($instance['total_participants'] >= $instance['max_participants']) {
            // Проверяем, участвует ли уже этот игрок
            $stmt = $mysqli->prepare("
                SELECT id FROM world_boss_participants 
                WHERE instance_id = ? AND user_id = ?
            ");
            $stmt->bind_param('ii', $instanceId, $userId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            return (bool)$existing; // Может участвовать только если уже присоединился
        }
        
        return true;
    }
    
    /**
     * Присоединение к рейду
     */
    public static function joinBossRaid(int $userId, int $instanceId): bool {
        global $mysqli;
        if (!$mysqli) return false;
        
        if (!self::canUserParticipate($userId, $instanceId)) {
            return false;
        }
        
        // Проверяем, не участвует ли уже
        $stmt = $mysqli->prepare("
            SELECT id FROM world_boss_participants 
            WHERE instance_id = ? AND user_id = ?
        ");
        $stmt->bind_param('ii', $instanceId, $userId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            return true; // Уже участвует
        }
        
        // Начинаем транзакцию
        $mysqli->begin_transaction();
        
        try {
            // Добавляем участника
            $stmt = $mysqli->prepare("
                INSERT INTO world_boss_participants 
                (instance_id, user_id, join_time, damage_dealt, participation_score)
                VALUES (?, ?, NOW(), 0, 0)
            ");
            $stmt->bind_param('ii', $instanceId, $userId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to add participant");
            }
            $stmt->close();
            
            // Увеличиваем счётчик участников
            $stmt = $mysqli->prepare("
                UPDATE world_boss_instances 
                SET total_participants = total_participants + 1 
                WHERE id = ?
            ");
            $stmt->bind_param('i', $instanceId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update participant count");
            }
            $stmt->close();
            
            // Устанавливаем статус игрока в бой
            $stmt = $mysqli->prepare("
                UPDATE users 
                SET status = 'world_boss', status_id = ? 
                WHERE id = ?
            ");
            $stmt->bind_param('ii', $instanceId, $userId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user status");
            }
            $stmt->close();
            
            $mysqli->commit();
            return true;
            
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("WORLD_BOSS: Failed to join raid: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Покидание рейда
     */
    public static function leaveBossRaid(int $userId, int $instanceId): bool {
        global $mysqli;
        if (!$mysqli) return false;
        
        // Начинаем транзакцию
        $mysqli->begin_transaction();
        
        try {
            // Удаляем участника
            $stmt = $mysqli->prepare("
                DELETE FROM world_boss_participants 
                WHERE instance_id = ? AND user_id = ?
            ");
            $stmt->bind_param('ii', $instanceId, $userId);
            $success = $stmt->execute() && $stmt->affected_rows > 0;
            $stmt->close();
            
            if (!$success) {
                throw new Exception("User not participating in raid");
            }
            
            // Уменьшаем счётчик участников
            $stmt = $mysqli->prepare("
                UPDATE world_boss_instances 
                SET total_participants = GREATEST(0, total_participants - 1) 
                WHERE id = ?
            ");
            $stmt->bind_param('i', $instanceId);
            $stmt->execute();
            $stmt->close();
            
            // Возвращаем игрока в свободное состояние
            $stmt = $mysqli->prepare("
                UPDATE users 
                SET status = 'free', status_id = 0 
                WHERE id = ?
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
            
            $mysqli->commit();
            return true;
            
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("WORLD_BOSS: Failed to leave raid: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Уведомление игроков в локации о появлении босса
     */
    private static function notifyPlayersInLocation(int $locationId, string $bossName, bool $adminSpawn = false): void {
        global $mysqli;
        if (!$mysqli) return;
        
        $prefix = $adminSpawn ? "⚡ Администратор" : "🔥";
        $message = "{$prefix} В локации появился мировой босс: {$bossName}! Присоединяйтесь к рейду!";
        
        try {
            $stmt = $mysqli->prepare("
                SELECT id FROM users 
                WHERE location = ? AND status = 'free'
            ");
            $stmt->bind_param('i', $locationId);
            $stmt->execute();
            $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            foreach ($users as $user) {
                // Добавляем уведомление через базовую систему уведомлений
                $stmt = $mysqli->prepare("
                    INSERT INTO user_notice (touser_id, info, type) 
                    VALUES (?, ?, 'default')
                ");
                $stmt->bind_param('is', $user['id'], $message);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("WORLD_BOSS: Failed to notify players: " . $e->getMessage());
        }
    }
    
    /**
     * Уведомление о роспуске рейда
     */
    private static function notifyRaidDisbanded(int $instanceId): void {
        global $mysqli;
        if (!$mysqli) return;
        
        $message = "⚠️ Рейд против мирового босса был досрочно завершён администратором.";
        
        try {
            $stmt = $mysqli->prepare("
                SELECT user_id FROM world_boss_participants 
                WHERE instance_id = ?
            ");
            $stmt->bind_param('i', $instanceId);
            $stmt->execute();
            $participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            foreach ($participants as $participant) {
                // Возвращаем игрока в свободное состояние
                $stmt = $mysqli->prepare("
                    UPDATE users 
                    SET status = 'free', status_id = 0 
                    WHERE id = ?
                ");
                $stmt->bind_param('i', $participant['user_id']);
                $stmt->execute();
                $stmt->close();
                
                // Отправляем уведомление
                $stmt = $mysqli->prepare("
                    INSERT INTO user_notice (touser_id, info, type) 
                    VALUES (?, ?, 'default')
                ");
                $stmt->bind_param('is', $participant['user_id'], $message);
                $stmt->execute();
                $stmt->close();
            }
            
            // Удаляем всех участников
            $stmt = $mysqli->prepare("
                DELETE FROM world_boss_participants WHERE instance_id = ?
            ");
            $stmt->bind_param('i', $instanceId);
            $stmt->execute();
            $stmt->close();
            
        } catch (Exception $e) {
            error_log("WORLD_BOSS: Failed to disband raid: " . $e->getMessage());
        }
    }
    
    /**
     * Получение участников рейда с рейтингом
     */
    public static function getBossParticipants(int $instanceId, int $limit = 50): array {
        global $mysqli;
        if (!$mysqli) return [];
        
        try {
            $stmt = $mysqli->prepare("
                SELECT 
                    wbp.*,
                    u.login,
                    u.user_group
                FROM world_boss_participants wbp
                INNER JOIN users u ON u.id = wbp.user_id
                WHERE wbp.instance_id = ?
                ORDER BY wbp.participation_score DESC, wbp.damage_dealt DESC
                LIMIT ?
            ");
            $stmt->bind_param('ii', $instanceId, $limit);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("WORLD_BOSS: Failed to get participants: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение списка всех шаблонов боссов (для админ-панели)
     */
    public static function getAllBossTemplates(): array {
        global $mysqli;
        if (!$mysqli) return [];
        
        try {
            $result = $mysqli->query("
                SELECT 
                    wb.*,
                    COUNT(wbi.id) as total_spawns,
                    MAX(wbi.spawn_time) as last_spawn
                FROM world_bosses wb
                LEFT JOIN world_boss_instances wbi ON wb.id = wbi.boss_id
                GROUP BY wb.id
                ORDER BY wb.level_min ASC, wb.name ASC
            ");
            
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Exception $e) {
            error_log("WORLD_BOSS: Failed to get templates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Создание нового шаблона босса (для администратора)
     */
    public static function createBossTemplate(array $data): ?int {
        global $mysqli;
        if (!$mysqli) return null;
        
        $required = ['name', 'boss_id', 'level_min', 'level_max', 'hp_max', 'location_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return null;
            }
        }
        
        try {
            $stmt = $mysqli->prepare("
                INSERT INTO world_bosses 
                (boss_id, location_id, name, description, level_min, level_max, hp_max, 
                 attack_power, defense_power, spawn_schedule, duration_minutes, 
                 cooldown_minutes, max_participants, base_rewards, rank_rewards, 
                 first_kill_rewards, is_active, created_at, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?)
            ");
            
            $stmt->bind_param('iissiiiiisiissssi',
                $data['boss_id'],
                $data['location_id'],
                $data['name'],
                $data['description'] ?? '',
                $data['level_min'],
                $data['level_max'],
                $data['hp_max'],
                $data['attack_power'] ?? 1000,
                $data['defense_power'] ?? 500,
                $data['spawn_schedule'] ?? '0 */6 * * *',
                $data['duration_minutes'] ?? 60,
                $data['cooldown_minutes'] ?? 360,
                $data['max_participants'] ?? 30,
                $data['base_rewards'] ?? '{}',
                $data['rank_rewards'] ?? '{}',
                $data['first_kill_rewards'] ?? '{}',
                $data['created_by'] ?? 0
            );
            
            if ($stmt->execute()) {
                $id = $mysqli->insert_id;
                $stmt->close();
                return $id;
            }
            
            $stmt->close();
            return null;
        } catch (Exception $e) {
            error_log("WORLD_BOSS: Failed to create template: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Административная статистика
     */
    public static function getAdminStats(): array {
        global $mysqli;
        if (!$mysqli) return [];
        
        $stats = [];
        
        try {
            // Активные боссы
            $result = $mysqli->query("
                SELECT COUNT(*) as count FROM world_boss_instances 
                WHERE status IN ('spawning', 'active')
            ");
            $stats['active_bosses'] = $result ? $result->fetch_assoc()['count'] : 0;
            
            // Всего участников сейчас
            $result = $mysqli->query("
                SELECT COUNT(DISTINCT user_id) as count 
                FROM world_boss_participants wbp
                INNER JOIN world_boss_instances wbi ON wbp.instance_id = wbi.id
                WHERE wbi.status IN ('spawning', 'active')
            ");
            $stats['active_participants'] = $result ? $result->fetch_assoc()['count'] : 0;
            
            // Статистика за последние 24 часа
            $result = $mysqli->query("
                SELECT 
                    COUNT(*) as spawned_today,
                    COUNT(CASE WHEN status = 'defeated' THEN 1 END) as defeated_today
                FROM world_boss_instances 
                WHERE spawn_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $todayStats = $result ? $result->fetch_assoc() : ['spawned_today' => 0, 'defeated_today' => 0];
            $stats = array_merge($stats, $todayStats);
            
        } catch (Exception $e) {
            error_log("WORLD_BOSS: Failed to get stats: " . $e->getMessage());
        }
        
        return $stats;
    }
}
?>