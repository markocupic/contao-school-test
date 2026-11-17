<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Term;

use Markocupic\ContaoSchoolTest\Task\Equation\Fraction;
use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Polynomial in several variables with rational coefficients, always in normal form:
 * one entry per monomial, no zero coefficients. Two terms are equivalent if their
 * polynomials are equal.
 */
final class Polynomial
{
    /**
     * @param array<string, array{c: Fraction, v: array<string, int>}> $terms monomial key => coefficient and exponents
     */
    private function __construct(public readonly array $terms)
    {
    }

    public static function constant(Fraction $c): self
    {
        return self::make([['c' => $c, 'v' => []]]);
    }

    public static function variable(string $name): self
    {
        return self::make([['c' => new Fraction(1), 'v' => [$name => 1]]]);
    }

    /**
     * @param list<array{c: Fraction, v: array<string, int>}> $list
     */
    public static function make(array $list): self
    {
        $terms = [];

        foreach ($list as $t) {
            $v = array_filter($t['v']);
            ksort($v);
            $key = self::key($v);

            if (isset($terms[$key])) {
                $terms[$key]['c'] = $terms[$key]['c']->add($t['c']);
            } else {
                $terms[$key] = ['c' => $t['c'], 'v' => $v];
            }
        }

        return new self(array_filter($terms, static fn (array $t): bool => !$t['c']->isZero()));
    }

    public function add(self $o): self
    {
        return self::make([...array_values($this->terms), ...array_values($o->terms)]);
    }

    public function neg(): self
    {
        return self::make(array_map(static fn (array $t): array => ['c' => $t['c']->mul(new Fraction(-1)), 'v' => $t['v']], array_values($this->terms)));
    }

    public function sub(self $o): self
    {
        return $this->add($o->neg());
    }

    public function mul(self $o): self
    {
        $list = [];

        foreach ($this->terms as $a) {
            foreach ($o->terms as $b) {
                $v = $a['v'];

                foreach ($b['v'] as $name => $e) {
                    $v[$name] = ($v[$name] ?? 0) + $e;
                }

                $list[] = ['c' => $a['c']->mul($b['c']), 'v' => $v];
            }
        }

        return self::make($list);
    }

    public function pow(int $n): self
    {
        $out = self::constant(new Fraction(1));

        for ($i = 0; $i < $n; ++$i) {
            $out = $out->mul($this);
        }

        return $out;
    }

    public function equals(self $o): bool
    {
        if (\count($this->terms) !== \count($o->terms)) {
            return false;
        }

        foreach ($this->terms as $key => $t) {
            if (!isset($o->terms[$key]) || !$t['c']->eq($o->terms[$key]['c'])) {
                return false;
            }
        }

        return true;
    }

    public function isZero(): bool
    {
        return [] === $this->terms;
    }

    /**
     * Constant value, null if a variable occurs.
     */
    public function constantValue(): Fraction|null
    {
        if ($this->isZero()) {
            return new Fraction(0);
        }

        if (1 === \count($this->terms) && isset($this->terms[''])) {
            return $this->terms['']['c'];
        }

        return null;
    }

    public function degree(): int
    {
        return $this->terms ? max(array_map(static fn (array $t): int => (int) array_sum($t['v']), $this->terms)) : 0;
    }

    /**
     * Not a multiple of a whole number > 1 or of a variable: integer coefficients with
     * greatest common divisor 1 and no variable in every monomial.
     */
    public function isPrimitive(): bool
    {
        $g = 0;
        $common = null;

        foreach ($this->terms as $t) {
            if (!$t['c']->isInt()) {
                return false;
            }

            $g = Util::gcd($g, abs($t['c']->n));
            $vars = array_keys($t['v']);
            $common = null === $common ? $vars : array_values(array_intersect($common, $vars));
        }

        return 1 === $g && !$common;
    }

    /**
     * HTML in the usual order (highest degree first, then alphabetically): 9x² − 12xy + 4y².
     */
    public function html(): string
    {
        if ($this->isZero()) {
            return '0';
        }

        $terms = array_values($this->terms);
        usort($terms, self::compare(...));
        $out = '';

        foreach ($terms as $i => $t) {
            $neg = $t['c']->n < 0;
            $abs = $t['c']->abs();
            $coef = self::coefHtml($abs, [] === $t['v']);
            $vars = implode('', array_map(static fn (string $n, int $e): string => $n.($e > 1 ? '<sup>'.$e.'</sup>' : ''), array_keys($t['v']), $t['v']));
            $out .= (0 === $i ? ($neg ? '−' : '') : ($neg ? ' − ' : ' + ')).$coef.$vars;
        }

        return $out;
    }

    /**
     * Plain text the parser reads again: "9x^2-12xy+4y^2", fractions as "1/25a^2".
     */
    public function plain(): string
    {
        if ($this->isZero()) {
            return '0';
        }

        $terms = array_values($this->terms);
        usort($terms, self::compare(...));
        $out = '';

        foreach ($terms as $i => $t) {
            $neg = $t['c']->n < 0;
            $abs = $t['c']->abs();
            $alone = [] === $t['v'];
            $coef = !$alone && $abs->isInt() && 1 === $abs->n ? '' : ($abs->isInt() ? (string) $abs->n : $abs->n.'/'.$abs->d);
            $vars = implode('', array_map(static fn (string $n, int $e): string => $n.($e > 1 ? '^'.$e : ''), array_keys($t['v']), $t['v']));
            $out .= ($neg ? '-' : (0 === $i ? '' : '+')).$coef.$vars;
        }

        return $out;
    }

    /**
     * Coefficient as text: decimal for halves, quarters, tenths …, otherwise a stacked fraction;
     * 1 is left out before variables.
     */
    public static function coefHtml(Fraction $c, bool $alone): string
    {
        if (!$alone && 1 === $c->n && 1 === $c->d) {
            return '';
        }

        if ($c->isInt()) {
            return (string) $c->n;
        }

        if (\in_array($c->d, [2, 4, 5, 8, 10, 20, 40, 50, 100, 1000], true)) {
            return Util::fmt($c->n / $c->d);
        }

        return Util::f($c->n, $c->d);
    }

    /**
     * Highest degree first, then the higher power of the earlier letter: x², xy, y².
     */
    private static function compare(array $a, array $b): int
    {
        $da = array_sum($a['v']);
        $db = array_sum($b['v']);

        if ($da !== $db) {
            return $db <=> $da;
        }

        $letters = array_unique([...array_keys($a['v']), ...array_keys($b['v'])]);
        sort($letters);

        foreach ($letters as $l) {
            $ea = $a['v'][$l] ?? 0;
            $eb = $b['v'][$l] ?? 0;

            if ($ea !== $eb) {
                return $eb <=> $ea;
            }
        }

        return 0;
    }

    /**
     * @param array<string, int> $v
     */
    private static function key(array $v): string
    {
        return implode('', array_map(static fn (string $n, int $e): string => $n.$e, array_keys($v), $v));
    }
}
