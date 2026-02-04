<?php
header('Content-Type: text/plain; charset=utf-8');
echo "SHOWDOWN_SIM_URL=" . var_export(getenv('SHOWDOWN_SIM_URL'), true) . PHP_EOL;
echo "BATTLE_ENGINE=" . var_export(getenv('BATTLE_ENGINE'), true) . PHP_EOL;
