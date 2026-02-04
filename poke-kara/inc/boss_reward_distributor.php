<?php
/**
 * Система распределения наград за рейдовых боссов
 * Расчёт рейтинга участников и выдача соответствующих наград
 */

class BossRewardDistributor {
    
    /**
     * Обработка завершённых рейдов (вызывается из cron)
     */
    public static function processCompletedRaids(): void {
        if (!Work::$sql) return;
        
        // Находим боссов, которые побеждены, но награды ещё не выданы
        $stmt = Work::$sql->prepare("
            SELECT wbi.id, wbi.boss_id, wb.name
            FROM world_boss_instances wbi
            INNER JOIN world_bosses wb ON wb.id = wbi.boss_id
            WHERE wbi.status = 'defeated' 
            AND wbi.defeat_time IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM world_boss_participants wbp 
                WHERE wbp.instance_id = wbi.id AND wbp.rewards_given = 1
                LIMIT 1
            )
            ORDER BY wbi.defeat_time ASC
            LIMIT 10
        ");
        
        $stmt->execute();
        $completedRaids = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($completedRaids as $raid) {
            try {
                self::distributeRewards((int)$raid['id']);
                error_log("BOSS_REWARDS: Distributed rewards for raid {$raid['id']} ({$raid['name']})");
            } catch (Exception $e) {
                error_log("BOSS_REWARDS_ERROR: Failed to distribute rewards for raid {$raid['id']}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Распределение наград конкретного рейда
     */
    public static function distributeRewards(int $instanceId): void {
        if (!Work::$sql) return;
        
        // Получаем данные инстанса
        $instance = WorldBossManager::getBossInstance($instanceId);
        if (!$instance || $instance['status'] !== 'defeated') {
            return;
        }
        
        // Рассчитываем рейтинг всех участников
        self::calculateParticipationScores($instanceId, $instance);
        
        // Получаем участников с рейтингом
        $participants = self::getRankedParticipants($instanceId);
        
        if (empty($participants)) return;
        
        // Распределяем награды
        foreach ($participants as $rank => $participant) {
            $rewards = self::calculateIndividualRewards(
                $participant, 
                $rank + 1, 
                count($participants),
                $instance
            );
            
            if (!empty($rewards)) {
                self::giveRewardsToUser((int)$participant['user_id'], $rewards, $instance['name']);
            }
            
            // Отмечаем как получившего награды
            $stmt = Work::$sql->prepare("
                UPDATE world_boss_participants 
                SET rewards_given = 1, rank_position = ? 
                WHERE id = ?
            ");
            $stmt->bind_param('ii', $rank + 1, $participant['id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Уведомляем о завершении
        self::notifyRaidCompletion($instanceId, $participants[0] ?? null);
    }
    
    /**
     * Расчёт очков участия для всех участников рейда
     */
    private static function calculateParticipationScores(int $instanceId, array $instance): void {
        $stmt = Work::$sql->prepare("
            SELECT wbp.*, u.lvl as user_level
            FROM world_boss_participants wbp
            INNER JOIN users u ON u.id = wbp.user_id
            WHERE wbp.instance_id = ?
        ");
        $stmt->bind_param('i', $instanceId);
        $stmt->execute();
        $participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        if (empty($participants)) return;
        
        // Общие показатели для нормализации
        $totalDamage = max(1, (int)$instance['total_damage_dealt']);
        $maxDuration = 0;
        $maxAttacks = 0;
        
        foreach ($participants as $p) {
            $maxDuration = max($maxDuration, (int)$p['participation_duration']);
            $maxAttacks = max($maxAttacks, (int)$p['attacks_count']);
        }
        
        $maxDuration = max(1, $maxDuration);
        $maxAttacks = max(1, $maxAttacks);
        
        // Рассчитываем очки для каждого участника
        foreach ($participants as $participant) {
            $score = self::calculateParticipationScore($participant, [
                'total_damage' => $totalDamage,
                'max_duration' => $maxDuration,
                'max_attacks' => $maxAttacks,
                'boss_level' => $instance['level_max'] ?? 100
            ]);
            
            // Сохраняем очки
            $stmt = Work::$sql->prepare("
                UPDATE world_boss_participants 
                SET participation_score = ? 
                WHERE id = ?
            ");
            $stmt->bind_param('di', $score, $participant['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Расчёт очков участия конкретного игрока
     */
    private static function calculateParticipationScore(array $participant, array $context): float {
        $damageWeight = 0.35;    // 35% - урон
        $timeWeight = 0.25;      // 25% - время участия  
        $attackWeight = 0.20;    // 20% - количество атак
        $bonusWeight = 0.20;     // 20% - бонусы (уровень, постоянство)
        
        // 1. Очки за урон (логарифмическая шкала)
        $damageRatio = (int)$participant['damage_dealt'] / $context['total_damage'];
        $damageScore = min(100, 50 * log10(1 + ($damageRatio * 100)));
        
        // 2. Очки за время участия
        $timeRatio = (int)$participant['participation_duration'] / $context['max_duration'];
        $timeScore = min(100, $timeRatio * 100);
        
        // 3. Очки за количество атак (активность)
        $attackRatio = (int)$participant['attacks_count'] / $context['max_attacks'];
        $attackScore = min(100, $attackRatio * 100);
        
        // 4. Бонусные очки
        $bonusScore = 0;
        
        // Бонус за высокий уровень игрока
        $userLevel = (int)($participant['user_level'] ?? 1);
        $bossLevel = (int)$context['boss_level'];
        if ($userLevel >= $bossLevel * 0.8) {
            $bonusScore += 25; // Соответствующий уровень
        } elseif ($userLevel >= $bossLevel * 0.6) {
            $bonusScore += 15; // Приемлемый уровень
        }
        
        // Бонус за постоянство (долгое участие)
        $duration = (int)$participant['participation_duration'];
        if ($duration >= 1800) { // 30+ минут
            $bonusScore += 20;
        } elseif ($duration >= 900) { // 15+ минут  
            $bonusScore += 10;
        }
        
        // Бонус за высокую активность атак
        if ((int)$participant['attacks_count'] >= 50) {
            $bonusScore += 15;
        } elseif ((int)$participant['attacks_count'] >= 25) {
            $bonusScore += 8;
        }
        
        $bonusScore = min(100, $bonusScore);
        
        // Итоговый расчёт
        $totalScore = ($damageScore * $damageWeight) +
                      ($timeScore * $timeWeight) +
                      ($attackScore * $attackWeight) +
                      ($bonusScore * $bonusWeight);
        
        return round($totalScore, 2);
    }
    
    /**
     * Получение участников с рейтингом
     */
    private static function getRankedParticipants(int $instanceId): array {
        $stmt = Work::$sql->prepare("
            SELECT wbp.*, u.login, u.user_group
            FROM world_boss_participants wbp
            INNER JOIN users u ON u.id = wbp.user_id
            WHERE wbp.instance_id = ?
            ORDER BY wbp.participation_score DESC, wbp.damage_dealt DESC
        ");
        $stmt->bind_param('i', $instanceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Расчёт наград для конкретного участника
     */
    private static function calculateIndividualRewards(array $participant, int $rank, int $totalParticipants, array $instance): array {
        $baseRewards = json_decode($instance['base_rewards'] ?? '{}', true) ?: [];
        $rankRewards = json_decode($instance['rank_rewards'] ?? '{}', true) ?: [];
        
        $rewards = $baseRewards;
        
        // Процентили для рейтинговых наград
        $topPercent = ($rank / $totalParticipants) * 100;
        
        if ($topPercent <= 5) { // Топ 5%
            $rewards = array_merge_recursive($rewards, $rankRewards['top5'] ?? []);
        } elseif ($topPercent <= 10) { // Топ 10%
            $rewards = array_merge_recursive($rewards, $rankRewards['top10'] ?? []);
        } elseif ($topPercent <= 25) { // Топ 25%
            $rewards = array_merge_recursive($rewards, $rankRewards['top25'] ?? []);
        } elseif ($topPercent <= 50) { // Топ 50%
            $rewards = array_merge_recursive($rewards, $rankRewards['top50'] ?? []);
        }
        
        // Масштабируем награды по очкам участия
        $scoreMultiplier = max(0.5, min(2.0, 1.0 + (($participant['participation_score'] - 50) / 100)));
        
        // Применяем множитель к количественным наградам
        if (isset($rewards['money'])) {
            $rewards['money'] = (int)round($rewards['money'] * $scoreMultiplier);
        }
        
        if (isset($rewards['items']) && is_array($rewards['items'])) {
            foreach ($rewards['items'] as &$item) {
                if (isset($item['count'])) {
                    $item['count'] = max(1, (int)round($item['count'] * $scoreMultiplier));
                }
            }
        }
        
        // Первое место получает дополнительные награды
        if ($rank === 1 && !empty($instance['first_kill_rewards'])) {
            $firstKillRewards = json_decode($instance['first_kill_rewards'], true) ?: [];
            $rewards = array_merge_recursive($rewards, $firstKillRewards);
        }
        
        return $rewards;
    }
    
    /**
     * Выдача наград игроку
     */
    private static function giveRewardsToUser(int $userId, array $rewards, string $bossName): void {
        $rewardsList = [];
        
        // Деньги
        if (!empty($rewards['money'])) {
            $money = (int)$rewards['money'];
            if (function_exists('itemAdd')) {
                itemAdd(1, $money, $userId);
                $rewardsList[] = number_format($money) . ' Генкар';
            }
        }
        
        // Предметы
        if (!empty($rewards['items']) && is_array($rewards['items'])) {
            foreach ($rewards['items'] as $item) {
                if (isset($item['id'], $item['count'])) {
                    $itemId = (int)$item['id'];
                    $count = (int)$item['count'];
                    
                    if ($itemId > 0 && $count > 0) {
                        if (function_exists('itemAdd')) {
                            itemAdd($itemId, $count, $userId);
                            
                            // Получаем название предмета
                            $itemInfo = Work::$sql->query("SELECT name FROM base_items WHERE id = {$itemId}")->fetch_assoc();
                            $itemName = $itemInfo['name'] ?? "Предмет #{$itemId}";
                            $rewardsList[] = "{$itemName} x{$count}";
                        }
                    }
                }
            }
        }
        
        // Уведомление игроку
        if (!empty($rewardsList)) {
            $rewardsText = implode(', ', $rewardsList);
            $message = "🎉 Вы получили награды за участие в рейде против {$bossName}: {$rewardsText}";
            
            // Добавляем уведомление
            $month = [1=>'Января',2=>'Февраля',3=>'Марта',4=>'Апреля',5=>'Мая',6=>'Июня',7=>'Июля',8=>'Августа',9=>'Сентября',10=>'Октября',11=>'Ноября',12=>'Декабря'];
            $date = date("d").' '.$month[date("n")].' '.date("Y").'г. в '.date("H").':'.date("i");
            
            $stmt = Work::$sql->prepare("
                INSERT INTO notification (text, user, img, date) 
                VALUES (?, ?, '/img/world/items/little/99.png', ?)
            ");
            $stmt->bind_param('sis', $message, $userId, $date);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Уведомление о завершении рейда
     */
    private static function notifyRaidCompletion(int $instanceId, ?array $topPlayer): void {
        if (!$topPlayer) return;
        
        $instance = WorldBossManager::getBossInstance($instanceId);
        if (!$instance) return;
        
        $participantCount = Work::$sql->query("
            SELECT COUNT(*) as count FROM world_boss_participants 
            WHERE instance_id = {$instanceId}
        ")->fetch_assoc()['count'] ?? 0;
        
        $totalDamage = number_format($instance['total_damage_dealt']);
        $winnerName = htmlspecialchars($topPlayer['login'] ?? 'Неизвестный');
        
        // Можно добавить в новости или глобальный чат
        $newsText = "🔥 Мировой босс {$instance['name']} побеждён! Участников: {$participantCount}. Урон: {$totalDamage}. Лучший: {$winnerName}";
        
        // Логируем для истории
        error_log("BOSS_COMPLETION: {$newsText}");
    }
}
?>