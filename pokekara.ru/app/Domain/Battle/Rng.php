<?php
declare(strict_types=1);

namespace App\Domain\Battle;

/**
 * Deterministic RNG (LCG) with 32-bit state, suitable for replay.
 * state_{n+1} = (a*state_n + c) mod 2^32
 */
final class Rng {
    private int $state;

    public function __construct(int $seed) {
        $this->state = $seed & 0xFFFFFFFF;
        if ($this->state === 0) $this->state = 1;
    }

    public function getState(): int {
        return $this->state & 0xFFFFFFFF;
    }

    public function setState(int $state): void {
        $this->state = $state & 0xFFFFFFFF;
        if ($this->state === 0) $this->state = 1;
    }

    /** Returns unsigned 32-bit int as PHP int. */
    public function nextU32(): int {
        // Numerical Recipes constants
        $a = 1664525;
        $c = 1013904223;

        $this->state = (int)(($a * ($this->state & 0xFFFFFFFF) + $c) & 0xFFFFFFFF);
        return $this->state;
    }

    /** int in [0, $maxExclusive) */
    public function nextInt(int $maxExclusive): int {
        if ($maxExclusive <= 0) return 0;
        $u = $this->nextU32();
        return (int)($u % $maxExclusive);
    }

    /** float in [0,1) */
    public function nextFloat(): float {
        $u = $this->nextU32();
        return ($u & 0xFFFFFFFF) / 4294967296.0; // 2^32
    }

    /** int in [min, max] */
    public function rangeInt(int $min, int $max): int {
        if ($max < $min) [$min, $max] = [$max, $min];
        $span = $max - $min + 1;
        return $min + $this->nextInt($span);
    }
}
