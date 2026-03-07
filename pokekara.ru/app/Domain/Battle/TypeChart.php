<?php
declare(strict_types=1);

namespace App\Domain\Battle;

final class TypeChart {
    /** @var array<string, array<string, float>> */
    private static array $chart = [
        'normal' => [
            // MVP: no immunities implemented yet
        ],
        'grass' => [
            'fire' => 0.5,
            'water' => 2.0,
            'grass' => 0.5,
        ],
        'fire' => [
            'grass' => 2.0,
            'water' => 0.5,
            'fire' => 0.5,
        ],
        'water' => [
            'fire' => 2.0,
            'grass' => 0.5,
            'water' => 0.5,
        ],
    ];

    public static function effectiveness(string $moveType, ?string $defType1, ?string $defType2): float {
        $moveType = strtolower($moveType);
        $t1 = $defType1 ? strtolower($defType1) : null;
        $t2 = $defType2 ? strtolower($defType2) : null;

        $m = 1.0;
        $m *= self::single($moveType, $t1);
        if ($t2 && $t2 !== $t1) $m *= self::single($moveType, $t2);
        return $m;
    }

    private static function single(string $moveType, ?string $defType): float {
        if (!$defType) return 1.0;
        if (!isset(self::$chart[$moveType])) return 1.0;
        return (float)(self::$chart[$moveType][$defType] ?? 1.0);
    }
}
