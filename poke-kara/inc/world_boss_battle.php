<?php
/**
 * Класс боя с мировым боссом
 * Расширяет ActionBattle для поддержки рейдовой механики
 */

require_once 'action_battle.php';
require_once 'world_boss_manager.php';

class WorldBossBattle extends ActionBattle {
    
    private $instanceId;
    private $bossData;
    private $participantData;
    private $bossTemplate;
    
    /**
     * Конструктор для рейдового боя
     */
    public function __construct($userId, $instanceId) {
        $this->instanceId = (int)$instanceId;
        $this->bossData = [];
        $this->participantData = [];
        $this->bossTemplate = [];
        
        // Получаем данные босса
        $this->bossData = WorldBossManager::getBossInstance($this->instanceId);
        if (!$this->bossData) {
            throw new Exception("Boss instance not found: " . $this->instanceId);
        }
        
        // Получаем шаблон босса
        $this->bossTemplate = $this->getBossTemplate();
        if (!$this->bossTemplate) {
            throw new Exception("Boss template not found for instance: " . $this->instanceId);
        }
        
        // Получаем/создаём участие игрока
        $this->participantData = $this->getOrCreateParticipant((int)$userId);
        
        // Формируем данные для родительского класса
        $userInfo = $this->getUserInfoForBattle((int)$userId);
        $bossInfo = $this->getBossInfoForBattle();
        
        $response = [];
        parent::__construct($userInfo, $bossInfo, $response);
    }
    
    /**
     * Получение шаблона босса
     */
    private function getBossTemplate() {
        if (!Work::$sql) return [];
        
        $stmt = Work::$sql->prepare("
            SELECT wb.* FROM world_bosses wb
            INNER JOIN world_boss_instances wbi ON wbi.boss_id = wb.id
            WHERE wbi.id = ?
        ");
        $stmt->bind_param('i', $this->instanceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result ?: [];
    }
    
    /**
     * Присоединение к рейду
     */
    public function joinBossRaid($userId) {
        $userId = (int)$userId;
        
        if (!WorldBossManager::canUserParticipate($userId, $this->instanceId)) {
            return false;
        }
        
        // Создаём или обновляем участие
        $this->participantData = $this->getOrCreateParticipant($userId);
        
        // Устанавливаем статус игрока
        if (Work::$sql) {
            $stmt = Work::$sql->prepare("
                UPDATE users 
                SET status = 'world_boss', status_id = ? 
                WHERE id = ?
            ");
            $stmt->bind_param('ii', $this->instanceId, $userId);
            $stmt->execute();
            $stmt->close();
        }
        
        return true;
    }
    
    /**
     * Покидание рейда
     */
    public function leaveBossRaid($userId) {
        $userId = (int)$userId;
        
        // Сбрасываем статус игрока
        if (Work::$sql) {
            $stmt = Work::$sql->prepare("
                UPDATE users 
                SET status = 'free', status_id = 0 
                WHERE id = ?
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Переопределяем parser для обработки рейдовых действий
     */
    protected function parser() {
        // Проверяем статус инстанса
        $this->bossData = WorldBossManager::getBossInstance($this->instanceId);
        if (!$this->bossData || !in_array($this->bossData['status'], ['spawning', 'active'])) {
            // Босс уже побеждён или истёк
            $userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
            if ($userId > 0) {
                $this->leaveBossRaid($userId);
            }
            return false;
        }
        
        // Обновляем данные босса в enemyTarget
        $this->updateEnemyTargetWithBossData();
        
        // Стандартная обработка
        return parent::parser();
    }
    
    /**
     * Обновление данных врага актуальными данными босса
     */
    private function updateEnemyTargetWithBossData() {
        if (!empty($this->enemyTarget)) {
            $this->enemyTarget['hp'] = $this->bossData['hp_current'];
            $this->enemyTarget['hp_max'] = $this->bossData['hp_max'];
            $this->enemyTarget['is_world_boss'] = true;
            $this->enemyTarget['instance_id'] = $this->instanceId;
        }
    }
    
    /**
     * Переопределяем обработку раунда для рейдовой механики
     */
    protected function goRound() {
        // Стандартная логика боя
        $result = parent::goRound();
        
        if ($result) {
            // Обрабатываем урон по боссу
            $this->processBossDamage();
            
            // Проверяем победу над боссом
            if ($this->checkBossDefeat()) {
                $this->handleBossDefeat();
                return true;
            }
            
            // Обновляем статистику участника
            $this->updateParticipantStats();
            
            // Проверяем особые механики босса
            $this->handleBossSpecialMechanics();
        }
        
        return $result;
    }
    
    /**
     * Обработка урона по боссу
     */
    private function processBossDamage() {
        $userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
        if ($userId <= 0) return;
        
        $damage = $this->extractDamageFromBattle();
        
        if ($damage > 0) {
            $this->updateBossHP($damage);
            $this->logBossAttack($userId, $damage);
        }
    }
    
    /**
     * Извлечение урона из результатов боя
     */
    private function extractDamageFromBattle() {
        $damage = 0;
        
        // Извлекаем урон из результатов боя
        if (isset($this->enemyTarget['hp_damage'])) {
            $damage = max(0, (int)$this->enemyTarget['hp_damage']);
        } elseif (isset($this->enemyTarget['hp_before']) && isset($this->enemyTarget['hp'])) {
            $damage = max(0, (int)$this->enemyTarget['hp_before'] - (int)$this->enemyTarget['hp']);
        }
        
        return $damage;
    }
    
    /**
     * Обновление HP босса
     */
    private function updateBossHP($damage) {
        $damage = (int)$damage;
        
        if (!Work::$sql) return;
        
        $stmt = Work::$sql->prepare("
            UPDATE world_boss_instances 
            SET 
                hp_current = GREATEST(0, hp_current - ?),
                total_damage_dealt = total_damage_dealt + ?,
                last_attack_time = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('iii', $damage, $damage, $this->instanceId);
        $stmt->execute();
        $stmt->close();
        
        // Обновляем локальные данные
        $this->bossData['hp_current'] = max(0, $this->bossData['hp_current'] - $damage);
        $this->bossData['total_damage_dealt'] += $damage;
        
        // Обновляем данные в enemyTarget для корректного отображения
        if (!empty($this->enemyTarget)) {
            $this->enemyTarget['hp'] = $this->bossData['hp_current'];
        }
    }
    
    /**
     * Логирование атаки по боссу
     */
    private function logBossAttack($userId, $damage) {
        $userId = (int)$userId;
        $damage = (int)$damage;
        
        if (!Work::$sql) return;
        
        $pokemonId = isset($this->userTarget['id']) ? (int)$this->userTarget['id'] : null;
        $attackId = isset($this->userData['targetAtk']) ? (int)$this->userData['targetAtk'] : null;
        
        $hpBefore = $this->bossData['hp_current'] + $damage;
        $hpAfter = $this->bossData['hp_current'];
        
        // Определяем тип атаки
        $attackType = 'physical';
        $isCritical = 0;
        
        if ($attackId && isset($this->userTarget['atkList']['a' . $attackId])) {
            $attackInfo = $this->userTarget['atkList']['a' . $attackId];
            $attackType = isset($attackInfo['category']) ? $attackInfo['category'] : 'physical';
            
            // Проверяем был ли критический удар (из логов боя)
            if (!empty($this->log)) {
                $lastLog = end($this->log);
                if (isset($lastLog['log_status']['critical'])) {
                    $isCritical = 1;
                }
            }
        }
        
        $stmt = Work::$sql->prepare("
            INSERT INTO world_boss_attacks 
            (instance_id, user_id, pokemon_id, attack_id, damage_dealt, is_critical, attack_type, boss_hp_before, boss_hp_after)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iiiiiisii', 
            $this->instanceId, $userId, $pokemonId, $attackId, $damage, $isCritical, $attackType, $hpBefore, $hpAfter
        );
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Проверка победы над боссом
     */
    private function checkBossDefeat() {
        return $this->bossData['hp_current'] <= 0;
    }
    
    /**
     * Обработка победы над боссом
     */
    private function handleBossDefeat() {
        if (!Work::$sql) return;
        
        // Помечаем босса как побеждённого
        $stmt = Work::$sql->prepare("
            UPDATE world_boss_instances 
            SET status = 'defeated', defeat_time = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('i', $this->instanceId);
        $stmt->execute();
        $stmt->close();
        
        // Добавляем в лог информацию о победе
        $this->log[] = [
            'round' => $this->round,
            'log' => ["🎉 Мировой босс {$this->bossData['name']} побеждён!"],
            'log_status' => ['boss_defeated' => true]
        ];
        
        // Запускаем расчёт рейтинга и выдачу наград
        $this->calculateAndDistributeRewards();
        
        // Уведомляем всех участников
        $this->notifyBossDefeat();
        
        // Освобождаем всех участников из боя
        $this->releaseAllParticipants();
    }
    
    /**
     * Особые механики босса (ярость, щиты, спец. атаки)
     */
    private function handleBossSpecialMechanics() {
        if ($this->bossData['hp_current'] <= 0) return;
        
        $hpPercent = ($this->bossData['hp_current'] / $this->bossData['hp_max']) * 100;
        
        // Ярость при низком HP (увеличение урона)
        if ($hpPercent <= 25 && !isset($this->bossData['rage_mode'])) {
            $this->activateBossRage();
        }
        
        // Особые атаки каждые 10% HP
        $hpThreshold = floor($hpPercent / 10) * 10;
        $lastThreshold = isset($this->bossData['last_special_attack']) ? $this->bossData['last_special_attack'] : 100;
        
        if ($hpThreshold < $lastThreshold && $hpThreshold % 10 === 0 && $hpThreshold >= 10) {
            $this->triggerBossSpecialAttack($hpThreshold);
            
            // Сохраняем порог в данных босса
            if (Work::$sql) {
                $stmt = Work::$sql->prepare("
                    UPDATE world_boss_instances 
                    SET last_special_attack = ?
                    WHERE id = ?
                ");
                $stmt->bind_param('ii', $hpThreshold, $this->instanceId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    /**
     * Активация режима ярости босса
     */
    private function activateBossRage() {
        $this->log[] = [
            'round' => $this->round,
            'log' => ["🔥 {$this->bossData['name']} впадает в ярость! Урон увеличен на 50%!"],
            'log_status' => ['boss_rage' => true]
        ];
        
        // Отмечаем что ярость активирована
        $this->bossData['rage_mode'] = true;
    }
    
    /**
     * Запуск особой атаки босса
     */
    private function triggerBossSpecialAttack($hpThreshold) {
        $hpThreshold = (int)$hpThreshold;
        
        $specialAttacks = [
            90 => [
                'message' => 'Босс использует Землетрясение! Всем участникам нанесён урон!',
                'effect' => 'earthquake'
            ],
            80 => [
                'message' => 'Босс создаёт энергетический щит! Получаемый урон снижен на 30%!',
                'effect' => 'shield'
            ],
            70 => [
                'message' => 'Босс призывает защитный барьер! Эффективность атак снижена!',
                'effect' => 'barrier'
            ],
            60 => [
                'message' => 'Босс высвобождает волну энергии! Мощная атака по всем!',
                'effect' => 'energy_wave'
            ],
            50 => [
                'message' => 'Босс восстанавливает часть здоровья!',
                'effect' => 'heal'
            ],
            40 => [
                'message' => 'Босс ускоряется! Двойная атака!',
                'effect' => 'speed_boost'
            ],
            30 => [
                'message' => 'Босс использует разрушительную атаку! Критический урон всем!',
                'effect' => 'devastation'
            ],
            20 => [
                'message' => 'Босс призывает миньонов на помощь!',
                'effect' => 'summon_minions'
            ],
            10 => [
                'message' => 'Последний рывок! Босс атакует с удвоенной силой!',
                'effect' => 'final_stand'
            ]
        ];
        
        $special = isset($specialAttacks[$hpThreshold]) ? $specialAttacks[$hpThreshold] : [
            'message' => 'Босс использует особую способность!',
            'effect' => 'generic'
        ];
        
        $this->log[] = [
            'round' => $this->round,
            'log' => [$special['message']],
            'log_status' => ['boss_special' => $hpThreshold, 'special_effect' => $special['effect']]
        ];
        
        // Применяем эффекты
        $this->applySpecialAttackEffect($special['effect'], $hpThreshold);
    }
    
    /**
     * Применение эффектов особых атак
     */
    private function applySpecialAttackEffect($effect, $hpThreshold) {
        switch ($effect) {
            case 'heal':
                // Лечение босса (10% от макс. HP)
                $healAmount = (int)($this->bossData['hp_max'] * 0.1);
                $newHP = min($this->bossData['hp_max'], $this->bossData['hp_current'] + $healAmount);
                
                if (Work::$sql) {
                    $stmt = Work::$sql->prepare("
                        UPDATE world_boss_instances 
                        SET hp_current = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param('ii', $newHP, $this->instanceId);
                    $stmt->execute();
                    $stmt->close();
                }
                
                $this->bossData['hp_current'] = $newHP;
                
                $this->log[] = [
                    'round' => $this->round,
                    'log' => ["Босс восстановил " . number_format($healAmount) . " HP!"],
                    'log_status' => ['boss_heal' => $healAmount]
                ];
                break;
                
            case 'shield':
            case 'barrier':
                // Эффекты защиты
                $this->addBossEffect($effect, 3); // 3 раунда
                break;
                
            case 'earthquake':
            case 'energy_wave':
            case 'devastation':
                // Урон всем участникам
                $this->addBossEffect($effect, 1); // 1 раунд
                break;
        }
    }
    
    /**
     * Добавление эффекта босса
     */
    private function addBossEffect($effect, $duration) {
        if (!Work::$sql) return;
        
        $stmt = Work::$sql->prepare("
            INSERT INTO battle_effects (battle, name, side, rnd, user)
            VALUES (?, ?, 2, ?, 0)
        ");
        
        $battleId = $this->instanceId; // Используем ID инстанса как battle ID
        $roundEnd = $this->round + $duration;
        
        $stmt->bind_param('isi', $battleId, $effect, $roundEnd);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Получение или создание участника
     */
    private function getOrCreateParticipant($userId) {
        $userId = (int)$userId;
        
        if (!Work::$sql) return [];
        
        $stmt = Work::$sql->prepare("
            SELECT * FROM world_boss_participants 
            WHERE instance_id = ? AND user_id = ?
        ");
        $stmt->bind_param('ii', $this->instanceId, $userId);
        $stmt->execute();
        $participant = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$participant) {
            // Создаём нового участника
            $stmt = Work::$sql->prepare("
                INSERT INTO world_boss_participants 
                (instance_id, user_id, first_attack_time)
                VALUES (?, ?, NOW())
            ");
            $stmt->bind_param('ii', $this->instanceId, $userId);
            $stmt->execute();
            $newId = Work::$sql->insert_id;
            $stmt->close();
            
            // Обновляем счётчик участников
            $stmt = Work::$sql->prepare("
                UPDATE world_boss_instances 
                SET total_participants = total_participants + 1
                WHERE id = ?
            ");
            $stmt->bind_param('i', $this->instanceId);
            $stmt->execute();
            $stmt->close();
            
            // Получаем созданного участника
            $stmt = Work::$sql->prepare("
                SELECT * FROM world_boss_participants WHERE id = ?
            ");
            $stmt->bind_param('i', $newId);
            $stmt->execute();
            $participant = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        
        return $participant ?: [];
    }
    
    /**
     * Обновление статистики участника
     */
    private function updateParticipantStats() {
        $userId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
        if ($userId <= 0 || !Work::$sql) return;
        
        $damage = $this->extractDamageFromBattle();
        $damageTaken = 0;
        
        // Извлекаем полученный урон (если есть)
        if (isset($this->userTarget['hp_damage_received'])) {
            $damageTaken = max(0, (int)$this->userTarget['hp_damage_received']);
        }
        
        $stmt = Work::$sql->prepare("
            UPDATE world_boss_participants 
            SET 
                damage_dealt = damage_dealt + ?,
                damage_taken = damage_taken + ?,
                attacks_count = attacks_count + 1,
                last_attack_time = NOW(),
                participation_duration = GREATEST(
                    participation_duration,
                    TIMESTAMPDIFF(SECOND, first_attack_time, NOW())
                )
            WHERE instance_id = ? AND user_id = ?
        ");
        $stmt->bind_param('iiii', $damage, $damageTaken, $this->instanceId, $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Формирование пользовательских данных для боя
     */
    private function getUserInfoForBattle($userId) {
        $userId = (int)$userId;
        
        if (!Work::$sql) return [];
        
        $stmt = Work::$sql->prepare("
            SELECT id, login, user_group, sex, location, lvl 
            FROM users WHERE id = ?
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $user ?: [];
    }
    
    /**
     * Формирование данных босса для боя
     */
    private function getBossInfoForBattle() {
        // Создаём "виртуального" покемона-босса
        $bossLevel = mt_rand($this->bossTemplate['level_min'], $this->bossTemplate['level_max']);
        
        // Получаем базовые данные покемона
        $pokemonBase = null;
        if (Work::$sql) {
            $pokemonBase = Work::$sql->query("
                SELECT * FROM base_pokemons WHERE id = {$this->bossTemplate['boss_id']}
            ")->fetch_assoc();
        }
        
        if (!$pokemonBase) {
            throw new Exception("Pokemon base data not found for boss: {$this->bossTemplate['boss_id']}");
        }
        
        // Генерируем атаки босса
        $attacks = $this->generateBossAttacks($this->bossTemplate['boss_id'], $bossLevel);
        
        $pokemonData = [
            'id' => 'boss_' . $this->instanceId,
            'basenum' => $this->bossTemplate['boss_id'],
            'name_new' => $this->bossTemplate['name'],
            'name' => $this->bossTemplate['name'],
            'lvl' => $bossLevel,
            'hp' => $this->bossData['hp_current'],
            'hp_max' => $this->bossData['hp_max'],
            'user_id' => 0,
            'wild' => true,
            'catchable' => false,
            'type' => isset($pokemonBase['type']) ? $pokemonBase['type'] : 'normal',
            
            // Атаки
            'attacks' => $attacks,
            'atkList' => $this->generateBossAttackList($attacks),
            
            // Повышенные характеристики для босса
            'base_atk' => $this->bossTemplate['attack_power'],
            'base_def' => $this->bossTemplate['defense_power'],
            'base_hp' => $this->bossData['hp_max'],
            'base_satk' => (int)($pokemonBase['satk'] * 1.5),
            'base_sdef' => (int)($pokemonBase['sdef'] * 1.5),
            'base_spd' => (int)($pokemonBase['spd'] * 1.2),
            
            // Статусы и способности
            'status_list' => [],
            'ability' => isset($pokemonBase['ability1']) ? $pokemonBase['ability1'] : 0,
            
            // Рейдовые флаги
            'is_world_boss' => true,
            'instance_id' => $this->instanceId,
            'rage_mode' => isset($this->bossData['rage_mode']) ? $this->bossData['rage_mode'] : false,
        ];
        
        return [
            'id' => 0,
            'login' => $this->bossTemplate['name'],
            'user_group' => 1,
            'catch' => 0,
            'pokemon' => $pokemonData
        ];
    }
    
    /**
     * Генерация атак для босса
     */
    private function generateBossAttacks($pokemonId, $level) {
        $pokemonId = (int)$pokemonId;
        $level = (int)$level;
        
        if (!Work::$sql) return '1,2,3,4'; // Базовые атаки по умолчанию
        
        // Получаем доступные атаки для покемона
        $attacksQuery = Work::$sql->query("
            SELECT attacks FROM base_attacks_pokemons 
            WHERE pok = {$pokemonId} AND type IN ('lvl', 'start')
            ORDER BY type = 'start' DESC
        ");
        
        $allAttacks = [];
        while ($row = $attacksQuery->fetch_assoc()) {
            $attacks = explode(',', $row['attacks']);
            $allAttacks = array_merge($allAttacks, $attacks);
        }
        
        // Удаляем дубликаты и пустые значения
        $allAttacks = array_unique(array_filter($allAttacks));
        
        // Выбираем 4 случайные атаки
        if (count($allAttacks) > 4) {
            $selectedAttacks = array_rand($allAttacks, 4);
            $bossAttacks = [];
            foreach ($selectedAttacks as $index) {
                $bossAttacks[] = $allAttacks[$index];
            }
        } else {
            $bossAttacks = $allAttacks;
        }
        
        // Дополняем до 4 атак базовыми, если нужно
        while (count($bossAttacks) < 4) {
            $bossAttacks[] = 1; // Базовая атака
        }
        
        return implode(',', array_slice($bossAttacks, 0, 4));
    }
    
    /**
     * Генерация списка атак босса
     */
    private function generateBossAttackList($attacks) {
        $attackList = [];
        $attackIds = explode(',', $attacks);
        
        if (!Work::$sql) return $attackList;
        
        foreach ($attackIds as $index => $attackId) {
            $attackId = (int)$attackId;
            if ($attackId > 0) {
                $attackData = Work::$sql->query("
                    SELECT * FROM base_atk WHERE id = {$attackId}
                ")->fetch_assoc();
                
                if ($attackData) {
                    $attackList['a' . $attackId] = array_merge($attackData, [
                        'id' => $attackId,
                        'attack_num' => $index,
                        'pp_my' => 999, // Босс не ограничен PP
                        'pp_max' => 999
                    ]);
                }
            }
        }
        
        return $attackList;
    }
    
    /**
     * Расчёт и распределение наград
     */
    private function calculateAndDistributeRewards() {
        // Подключаем класс распределения наград
        if (class_exists('BossRewardDistributor')) {
            try {
                BossRewardDistributor::distributeRewards($this->instanceId);
            } catch (Exception $e) {
                error_log("BOSS_REWARDS_ERROR: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Уведомление о победе над боссом
     */
    private function notifyBossDefeat() {
        if (!Work::$sql) return;
        
        $message = "🎉 Мировой босс {$this->bossData['name']} побеждён! Проверьте свои награды.";
        
        $stmt = Work::$sql->prepare("
            SELECT user_id FROM world_boss_participants 
            WHERE instance_id = ?
        ");
        $stmt->bind_param('i', $this->instanceId);
        $stmt->execute();
        $participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Отправляем уведомления участникам
        $month = [1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',
                  7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря'];
        $date = date("d").' '.$month[date("n")].' '.date("Y").'г. в '.date("H").':'.date("i");
        
        foreach ($participants as $participant) {
            $stmt = Work::$sql->prepare("
                INSERT INTO notification (text, user, img, date) 
                VALUES (?, ?, '/img/world/items/little/99.png', ?)
            ");
            $stmt->bind_param('sis', $message, $participant['user_id'], $date);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Освобождение всех участников из боя
     */
    private function releaseAllParticipants() {
        if (!Work::$sql) return;
        
        $stmt = Work::$sql->prepare("
            UPDATE users 
            SET status = 'free', status_id = 0 
            WHERE status = 'world_boss' AND status_id = ?
        ");
        $stmt->bind_param('i', $this->instanceId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Переопределяем метод проигрыша для рейдов
     */
    public function lose($user_lose_id, $lose_type = 'OTHER', $last_atk = false) {
        // В рейдах один игрок не может проиграть весь рейд
        // Просто убираем его из боя
        if ($user_lose_id && $user_lose_id !== true) {
            $this->leaveBossRaid((int)$user_lose_id);
            
            // Добавляем сообщение в лог
            $this->log[] = [
                'round' => $this->round,
                'log' => ['Участник покинул рейд'],
                'log_status' => ['participant_left' => true]
            ];
        }
    }
    
    /**
     * Получение ID инстанса (для внешнего использования)
     */
    public function getInstanceId() {
        return $this->instanceId;
    }
    
    /**
     * Получение данных босса (для внешнего использования)
     */
    public function getBossData() {
        return $this->bossData;
    }
}
?>