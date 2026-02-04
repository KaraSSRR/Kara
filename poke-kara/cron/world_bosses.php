<?php
/**
 * Cron-задача для управления мировыми боссами
 * Запускать каждую минуту: * * * * * /usr/bin/php /path/to/your/site/cron/world_bosses.php
 */

// Подключаем основную конфигурацию
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/inc/world_boss_manager.php';

// Логирование старта
error_log("WORLD_BOSS_CRON: Starting at " . date('Y-m-d H:i:s'));

try {
    // 1. Спавним запланированных боссов
    WorldBossManager::spawnScheduledBosses();
    
    // 2. Удаляем просроченных боссов
    WorldBossManager::despawnExpiredBosses();
    
    // 3. Обрабатываем завершённые рейды (награды)
    if (class_exists('BossRewardDistributor')) {
        BossRewardDistributor::processCompletedRaids();
    }
    
    error_log("WORLD_BOSS_CRON: Completed successfully");
    
} catch (Exception $e) {
    error_log("WORLD_BOSS_CRON: Error - " . $e->getMessage());
}
?>