<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Equation;

use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Exact rational number, always reduced, denominator positive.
 */
final class Fraction
{
    public readonly int $n;

    public readonly int $d;

    public function __construct(int $n, int $d = 1)
    {
        if (0 === $d) {
            throw new \DivisionByZeroError('Denominator 0');
        }

        if ($d < 0) {
            $n = -$n;
            $d = -$d;
        }

        $g = Util::gcd(abs($n), $d) ?: 1;
        $this->n = intdiv($n, $g);
        $this->d = intdiv($d, $g);
    }

    public static function of(self|int $v): self
    {
        return $v instanceof self ? $v : new self($v);
    }

    public function add(self $o): self
    {
        return new self($this->n * $o->d + $o->n * $this->d, $this->d * $o->d);
    }

    public function sub(self $o): self
    {
        return new self($this->n * $o->d - $o->n * $this->d, $this->d * $o->d);
    }

    public function mul(self $o): self
    {
        return new self($this->n * $o->n, $this->d * $o->d);
    }

    public function div(self $o): self
    {
        return new self($this->n * $o->d, $this->d * $o->n);
    }

    public function abs(): self
    {
        return new self(abs($this->n), $this->d);
    }

    public function isZero(): bool
    {
        return 0 === $this->n;
    }

    public function isInt(): bool
    {
        return 1 === $this->d;
    }

    public function eq(self $o): bool
    {
        return $this->n === $o->n && $this->d === $o->d;
    }

    /**
     * HTML: integer or stacked fraction, minus sign in front.
     */
    public function html(): string
    {
        $sign = $this->n < 0 ? '−' : '';

        return $sign.($this->isInt() ? (string) abs($this->n) : Util::f(abs($this->n), $this->d));
    }

    /**
     * Plain text as the student types it: "-3/4".
     */
    public function plain(): string
    {
        return $this->isInt() ? (string) $this->n : $this->n.'/'.$this->d;
    }
}
