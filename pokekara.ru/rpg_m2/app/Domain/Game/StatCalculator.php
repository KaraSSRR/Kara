<?php
declare(strict_types=1);

namespace App\Domain\Game;

/**
 * Gen9-like stat math (Showdown-style).
 * - IV: 0..31
 * - EV: 0..252 per stat (<=510 total recommended; not enforced yet)
 *
 * HP: floor(((2*B + IV + floor(EV/4)) * L)/100) + L + 10
 * Other: floor((floor(((2*B + IV + floor(EV/4)) * L)/100) + 5) * Nature)
 */
final class StatCalculator {
    /**
     * Nature effects: +10% to one stat, -10% to another.
     * Keys are standard Pokémon natures (lowercase).
     *
     * @var array<string, array{inc:string|null, dec:string|null}>
     */
    private static array $natures = [
        'hardy'   => ['inc' => null,   'dec' => null],
        'lonely'  => ['inc' => 'atk',  'dec' => 'def'],
        'brave'   => ['inc' => 'atk',  'dec' => 'spe'],
        'adamant' => ['inc' => 'atk',  'dec' => 'spa'],
        'naughty' => ['inc' => 'atk',  'dec' => 'spd'],

        'bold'    => ['inc' => 'def',  'dec' => 'atk'],
        'docile'  => ['inc' => null,   'dec' => null],
        'relaxed' => ['inc' => 'def',  'dec' => 'spe'],
        'impish'  => ['inc' => 'def',  'dec' => 'spa'],
        'lax'     => ['inc' => 'def',  'dec' => 'spd'],

        'timid'   => ['inc' => 'spe',  'dec' => 'atk'],
        'hasty'   => ['inc' => 'spe',  'dec' => 'def'],
        'serious' => ['inc' => null,   'dec' => null],
        'jolly'   => ['inc' => 'spe',  'dec' => 'spa'],
        'naive'   => ['inc' => 'spe',  'dec' => 'spd'],

        'modest'  => ['inc' => 'spa',  'dec' => 'atk'],
        'mild'    => ['inc' => 'spa',  'dec' => 'def'],
        'quiet'   => ['inc' => 'spa',  'dec' => 'spe'],
        'bashful' => ['inc' => null,   'dec' => null],
        'rash'    => ['inc' => 'spa',  'dec' => 'spd'],

        'calm'    => ['inc' => 'spd',  'dec' => 'atk'],
        'gentle'  => ['inc' => 'spd',  'dec' => 'def'],
        'sassy'   => ['inc' => 'spd',  'dec' => 'spe'],
        'careful' => ['inc' => 'spd',  'dec' => 'spa'],
        'quirky'  => ['inc' => null,   'dec' => null],
    ];

    public static function normalizeNature(?string $nature): string {
        $n = strtolower(trim((string)$nature));
        return isset(self::$natures[$n]) ? $n : 'hardy';
    }

    /** @return float 0.9|1.0|1.1 */
    public static function natureModifier(string $nature, string $stat): float {
        $nature = self::normalizeNature($nature);
        $stat = strtolower($stat);
        $eff = self::$natures[$nature];
        if ($eff['inc'] === $stat) return 1.1;
        if ($eff['dec'] === $stat) return 0.9;
        return 1.0;
    }


    /**
     * Convenience: returns nature modifiers for all battle stats.
     *
     * @return array{hp:float,atk:float,def:float,spa:float,spd:float,spe:float}
     */
    public static function natureModifiers(string $nature): array {
        $nature = self::normalizeNature($nature);
        return [
            'hp'  => 1.0,
            'atk' => self::natureModifier($nature, 'atk'),
            'def' => self::natureModifier($nature, 'def'),
            'spa' => self::natureModifier($nature, 'spa'),
            'spd' => self::natureModifier($nature, 'spd'),
            'spe' => self::natureModifier($nature, 'spe'),
        ];
    }

    /**
     * @param array{hp:int,atk:int,def:int,spa:int,spd:int,spe:int} $base
     * @param array{hp:int,atk:int,def:int,spa:int,spd:int,spe:int} $iv
     * @param array{hp:int,atk:int,def:int,spa:int,spd:int,spe:int} $ev
     * @return array{hp:int,atk:int,def:int,spa:int,spd:int,spe:int}
     */
    public static function calcAll(array $base, array $iv, array $ev, int $level, string $nature): array {
        $level = max(1, min(100, $level));
        $nature = self::normalizeNature($nature);

        $hp = self::calcHp($base['hp'], $iv['hp'], $ev['hp'], $level);

        $atk = self::calcOther($base['atk'], $iv['atk'], $ev['atk'], $level, self::natureModifier($nature, 'atk'));
        $def = self::calcOther($base['def'], $iv['def'], $ev['def'], $level, self::natureModifier($nature, 'def'));
        $spa = self::calcOther($base['spa'], $iv['spa'], $ev['spa'], $level, self::natureModifier($nature, 'spa'));
        $spd = self::calcOther($base['spd'], $iv['spd'], $ev['spd'], $level, self::natureModifier($nature, 'spd'));
        $spe = self::calcOther($base['spe'], $iv['spe'], $ev['spe'], $level, self::natureModifier($nature, 'spe'));

        return ['hp' => $hp, 'atk' => $atk, 'def' => $def, 'spa' => $spa, 'spd' => $spd, 'spe' => $spe];
    }

    public static function calcHp(int $base, int $iv, int $ev, int $level): int {
        $base = max(1, $base);
        $iv = self::clamp($iv, 0, 31);
        $ev = self::clamp($ev, 0, 252);
        $v = (2 * $base + $iv + intdiv($ev, 4));
        $v = intdiv($v * $level, 100);
        return $v + $level + 10;
    }

    public static function calcOther(int $base, int $iv, int $ev, int $level, float $nature): int {
        $base = max(1, $base);
        $iv = self::clamp($iv, 0, 31);
        $ev = self::clamp($ev, 0, 252);
        $v = (2 * $base + $iv + intdiv($ev, 4));
        $v = intdiv($v * $level, 100) + 5;
        return (int)floor($v * $nature);
    }

    private static function clamp(int $v, int $min, int $max): int {
        return max($min, min($max, $v));
    }
}
