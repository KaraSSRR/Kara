<?php
require_once __DIR__ . '/BattleEngineInterface.php';
require_once __DIR__ . '/../Battle.php';

/**
 * Legacy engine wrapper: использует текущий класс Battle (как было).
 */
class BattleEngineLegacy implements BattleEngineInterface {
    private $ctx;

    public function __construct($actionBattleContext) {
        $this->ctx = $actionBattleContext;
    }

    public function runTurn(array &$info_1, array &$info_2, array &$target_1, array &$target_2): void {
        // В legacy движке сам конструктор выполняет весь ход.
        new Battle($this->ctx, $info_1, $info_2, $target_1, $target_2);
    }
}
