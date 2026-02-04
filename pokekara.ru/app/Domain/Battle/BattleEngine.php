<?php
declare(strict_types=1);

namespace App\Domain\Battle;

final class BattleEngine {
    /** @return array{state:array, logs:array<int,array{turn:int,seq:int,message:string,payload:array}>} */
    public static function step(array $state, array $p1Action, array $p2Action, Rng $rng): array {
        $turn = (int)($state['turn'] ?? 0) + 1;
        $state['turn'] = $turn;

        $logs = [];
        $seq = 0;

        // Resolve chosen moves
        [$p1Move, $p1Slot] = self::resolveMove($state['p1'], $p1Action);
        [$p2Move, $p2Slot] = self::resolveMove($state['p2'], $p2Action);

        if (!$p1Move) {
            $logs[] = self::log($turn, $seq++, 'p1 has no moves left.', ['side' => 'p1']);
        }
        if (!$p2Move) {
            $logs[] = self::log($turn, $seq++, 'p2 has no moves left.', ['side' => 'p2']);
        }

        // Determine move order
        $order = self::order($state, $p1Move, $p2Move, $rng);

        foreach ($order as $side) {
            if (!self::isAlive($state['p1']) || !self::isAlive($state['p2'])) break;

            if ($side === 'p1' && $p1Move) {
                $res = self::useMove($state, 'p1', 'p2', $p1Move, $p1Slot, $rng, $turn, $seq);
                $state = $res['state']; $logs = array_merge($logs, $res['logs']); $seq = $res['seq'];
            } elseif ($side === 'p2' && $p2Move) {
                $res = self::useMove($state, 'p2', 'p1', $p2Move, $p2Slot, $rng, $turn, $seq);
                $state = $res['state']; $logs = array_merge($logs, $res['logs']); $seq = $res['seq'];
            }
        }

        // Finish condition
        if (!self::isAlive($state['p1']) || !self::isAlive($state['p2'])) {
            $winner = self::isAlive($state['p1']) ? 'p1' : (self::isAlive($state['p2']) ? 'p2' : 'draw');
            $state['finished'] = true;
            $state['winner'] = $winner;
            $logs[] = self::log($turn, $seq++, 'Battle finished.', ['winner' => $winner]);
        }

        $state['rng_state'] = $rng->getState();
        return ['state' => $state, 'logs' => $logs];
    }

    private static function isAlive(array $side): bool {
        return (int)($side['hp'] ?? 0) > 0;
    }

    /** @return array{0:array|null,1:int|null} */
    private static function resolveMove(array $side, array $action): array {
        $slot = isset($action['slot']) ? (int)$action['slot'] : null;
        if ($slot !== null) {
            foreach (($side['moves'] ?? []) as $m) {
                if ((int)$m['slot'] === $slot && (int)$m['pp_current'] > 0) return [$m, $slot];
            }
            return [null, $slot];
        }

        // If no slot specified, pick first available
        foreach (($side['moves'] ?? []) as $m) {
            if ((int)$m['pp_current'] > 0) return [$m, (int)$m['slot']];
        }
        return [null, null];
    }

    /** @return array{0:'p1'|'p2',1:'p1'|'p2'} */
    private static function order(array $state, ?array $p1Move, ?array $p2Move, Rng $rng): array {
        $p1Pri = $p1Move ? (int)($p1Move['priority'] ?? 0) : -99;
        $p2Pri = $p2Move ? (int)($p2Move['priority'] ?? 0) : -99;

        if ($p1Pri !== $p2Pri) return ($p1Pri > $p2Pri) ? ['p1','p2'] : ['p2','p1'];

        $p1Spe = self::effectiveStat($state['p1'], 'spe');
        $p2Spe = self::effectiveStat($state['p2'], 'spe');

        if ($p1Spe !== $p2Spe) return ($p1Spe > $p2Spe) ? ['p1','p2'] : ['p2','p1'];

        // Tie-break: RNG
        return ($rng->nextInt(2) === 0) ? ['p1','p2'] : ['p2','p1'];
    }

    private static function effectiveStat(array $side, string $stat): int {
        $stat = strtolower($stat);
        $base = (int)(($side['stats'][$stat] ?? 0));
        $stage = (int)(($side['boosts'][$stat] ?? 0));
        $m = self::statStageMultiplier($stage);
        return (int)floor($base * $m);
    }

    /** Standard stat stage multipliers for atk/def/spa/spd/spe: -6..+6 */
    private static function statStageMultiplier(int $stage): float {
        $stage = max(-6, min(6, $stage));
        if ($stage >= 0) return (2 + $stage) / 2.0;
        return 2.0 / (2 - $stage);
    }

    /** Accuracy/evasion stage multiplier: -6..+6 */
    private static function accEvaStageMultiplier(int $stage): float {
        $stage = max(-6, min(6, $stage));
        if ($stage >= 0) return (3 + $stage) / 3.0;
        return 3.0 / (3 - $stage);
    }

    /** @return array{state:array, logs:array, seq:int} */
    private static function useMove(array $state, string $atkSide, string $defSide, array $move, ?int $slot, Rng $rng, int $turn, int $seq): array {
        $attacker = $state[$atkSide];
        $defender = $state[$defSide];

        $moveName = (string)($move['name'] ?? 'Move');
        $logs = [];

        $logs[] = self::log($turn, $seq++, "{$atkSide} used {$moveName}.", ['side' => $atkSide, 'move' => $move]);

        // Spend PP
        if ($slot !== null) {
            foreach ($state[$atkSide]['moves'] as &$m) {
                if ((int)$m['slot'] === $slot) {
                    $m['pp_current'] = max(0, (int)$m['pp_current'] - 1);
                    break;
                }
            }
            unset($m);
        }

        // Status move MVP: slot 6 (Low Call) => target atk -1
        $cat = strtolower((string)($move['category'] ?? ''));
        if ($cat === 'status') {
            $moveId = (int)($move['move_id'] ?? 0);
            if ($moveId === 6) {
                $state[$defSide]['boosts']['atk'] = max(-6, (int)($state[$defSide]['boosts']['atk'] ?? 0) - 1);
                $logs[] = self::log($turn, $seq++, "{$defSide}'s Attack fell!", ['side' => $defSide, 'stat' => 'atk', 'stage' => (int)$state[$defSide]['boosts']['atk']]);
            } else {
                $logs[] = self::log($turn, $seq++, "But nothing happened.", ['move' => $moveName]);
            }
            return ['state' => $state, 'logs' => $logs, 'seq' => $seq];
        }

        $power = $move['power'] ?? null;
        if ($power === null || (int)$power <= 0) {
            $logs[] = self::log($turn, $seq++, "But it failed.", ['move' => $moveName]);
            return ['state' => $state, 'logs' => $logs, 'seq' => $seq];
        }
        $power = (int)$power;

        // Accuracy check
        $baseAcc = $move['accuracy'] ?? null;
        if ($baseAcc !== null) {
            $accStage = (int)($state[$atkSide]['boosts']['accuracy'] ?? 0);
            $evaStage = (int)($state[$defSide]['boosts']['evasion'] ?? 0);
            $acc = (float)$baseAcc;
            $acc *= self::accEvaStageMultiplier($accStage);
            $acc /= self::accEvaStageMultiplier($evaStage);
            $acc = max(1.0, min(100.0, $acc));

            $roll = $rng->rangeInt(1, 100);
            if ($roll > (int)floor($acc)) {
                $logs[] = self::log($turn, $seq++, "It missed!", ['roll' => $roll, 'acc' => $acc]);
                return ['state' => $state, 'logs' => $logs, 'seq' => $seq];
            }
        }

        // Critical
        $crit = ($rng->rangeInt(1, 24) === 1);
        $critMult = $crit ? 1.5 : 1.0;

        // Base attack/defense
        $level = (int)($attacker['level'] ?? 1);
        $moveType = (string)($move['type'] ?? 'normal');
        $atkStat = ($cat === 'physical') ? 'atk' : 'spa';
        $defStat = ($cat === 'physical') ? 'def' : 'spd';

        $atk = self::effectiveStat($state[$atkSide], $atkStat);
        $def = self::effectiveStat($state[$defSide], $defStat);
        $def = max(1, $def);

        // Damage base
        $step1 = intdiv(2 * $level, 5) + 2;
        $baseDmg = intdiv(intdiv($step1 * $power * $atk, $def), 50) + 2;

        // Modifiers
        $stab = 1.0;
        if (isset($attacker['types']) && is_array($attacker['types'])) {
            foreach ($attacker['types'] as $t) {
                if ($t && strtolower((string)$t) === strtolower($moveType)) { $stab = 1.5; break; }
            }
        }

        $eff = TypeChart::effectiveness($moveType, $defender['types'][0] ?? null, $defender['types'][1] ?? null);

        $rand = $rng->rangeInt(85, 100) / 100.0;

        $mod = $stab * $eff * $critMult * $rand;

        $dmg = (int)floor($baseDmg * $mod);
        if ($eff > 0.0) $dmg = max(1, $dmg);

        $state[$defSide]['hp'] = max(0, (int)$state[$defSide]['hp'] - $dmg);

        $logs[] = self::log($turn, $seq++, "{$defSide} took {$dmg} damage.", [
            'damage' => $dmg,
            'stab' => $stab,
            'effectiveness' => $eff,
            'crit' => $crit,
            'rand' => $rand,
        ]);

        if ($crit) {
            $logs[] = self::log($turn, $seq++, "A critical hit!", []);
        }

        if ($eff >= 2.0) $logs[] = self::log($turn, $seq++, "It's super effective!", []);
        elseif ($eff > 0.0 && $eff < 1.0) $logs[] = self::log($turn, $seq++, "It's not very effective...", []);
        elseif ($eff == 0.0) $logs[] = self::log($turn, $seq++, "It had no effect.", []);

        if ((int)$state[$defSide]['hp'] <= 0) {
            $logs[] = self::log($turn, $seq++, "{$defSide} fainted.", ['side' => $defSide]);
        }

        return ['state' => $state, 'logs' => $logs, 'seq' => $seq];
    }

    /** @return array{turn:int,seq:int,message:string,payload:array} */
    private static function log(int $turn, int $seq, string $message, array $payload): array {
        return ['turn' => $turn, 'seq' => $seq, 'message' => $message, 'payload' => $payload];
    }
}
