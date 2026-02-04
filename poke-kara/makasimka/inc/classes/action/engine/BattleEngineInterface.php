<?php
/**
 * Battle engine abstraction layer.
 * Позволяет переключать движок боя: legacy (ваша текущая реализация) или pokemon-showdown.
 */
interface BattleEngineInterface {
    /**
     * Выполнить один ход/раунд боя.
     * Все массивы передаются по ссылке, чтобы сохранить совместимость с текущей логикой ActionBattle.
     */
    public function runTurn(array &$info_1, array &$info_2, array &$target_1, array &$target_2): void;
}
