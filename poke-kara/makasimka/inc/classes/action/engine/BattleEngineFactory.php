<?php
require_once __DIR__ . '/BattleEngineInterface.php';
require_once __DIR__ . '/BattleEngineLegacy.php';
require_once __DIR__ . '/BattleEngineShowdown.php';

/**
 * Выбор движка:
 * - BATTLE_ENGINE=legacy|showdown
 * - или, если задан SHOWDOWN_SIM_URL, включаем showdown автоматически
 */
class BattleEngineFactory {

    public static function create($actionBattleContext): BattleEngineInterface {
        $force = getenv('BATTLE_ENGINE');
        $force = $force ? strtolower(trim($force)) : '';

        if ($force === 'legacy') return new BattleEngineLegacy($actionBattleContext);

        $url = getenv('SHOWDOWN_SIM_URL');
        $url = $url ? trim($url) : '';

        if ($force === 'showdown' || $url) {
            if (!$url) $url = 'http://127.0.0.1:3009';
            return new BattleEngineShowdown($actionBattleContext, $url);
        }

        // По умолчанию — legacy (безопасно для существующего продакшна)
        return new BattleEngineLegacy($actionBattleContext);
    }
}
