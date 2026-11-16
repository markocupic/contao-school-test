<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-school-test
 */

namespace Markocupic\ContaoSchoolTest\Task;

/**
 * Helpers shared by all task generators: random numbers, fractions and the
 * small HTML snippets used to display numbers and fractions in a task.
 */
final class Util
{
    public static function ri(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    public static function pick(array $items): mixed
    {
        $items = array_values($items);

        return $items[mt_rand(0, \count($items) - 1)];
    }

    public static function chance(float $p = 0.5): bool
    {
        return mt_rand() / mt_getrandmax() < $p;
    }

    public static function shuffle(array $items): array
    {
        $items = array_values($items);

        for ($i = \count($items) - 1; $i > 0; --$i) {
            $j = mt_rand(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return $items;
    }

    public static function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);

        while (0 !== $b) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a;
    }

    public static function lcm(int $a, int $b): int
    {
        return intdiv($a, self::gcd($a, $b)) * $b;
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function red(int $n, int $d): array
    {
        $g = self::gcd($n, $d) ?: 1;

        return [intdiv($n, $g), intdiv($d, $g)];
    }

    public static function properNum(int $d): int
    {
        do {
            $n = mt_rand(1, $d - 1);
        } while (1 !== self::gcd($n, $d));

        return $n;
    }

    /**
     * Random proper, reduced fraction with a denominator from the list.
     *
     * @return array{0: int, 1: int}
     */
    public static function prop(array $denominators): array
    {
        $d = (int) self::pick($denominators);

        return [self::properNum($d), $d];
    }

    /**
     * Number to string, rounded to 6 decimals, without trailing zeros.
     */
    public static function fmt(float|int $x): string
    {
        $x = round((float) $x, 6);

        if (0.0 === $x) {
            return '0';
        }

        $s = rtrim(rtrim(\sprintf('%.6F', $x), '0'), '.');

        return '-0' === $s ? '0' : $s;
    }

    /**
     * Swiss thousands separator (apostrophe) for numbers >= 10'000.
     */
    public static function sep(float|int|string $x): string
    {
        $s = \is_string($x) ? $x : self::fmt($x);

        if (abs((float) $s) < 10000) {
            return $s;
        }

        $parts = explode('.', $s, 2);
        $int = preg_replace('/\B(?=(\d{3})+(?!\d))/', "'", $parts[0]);

        return $int.(isset($parts[1]) ? '.'.$parts[1] : '');
    }

    public static function r1(float $x): float
    {
        return abs($x) >= 100 ? round($x) : round($x * 10) / 10;
    }

    public static function b(int|float|string $x): string
    {
        return '<b>'.(\is_string($x) ? $x : self::fmt($x)).'</b>';
    }

    public static function f(int|string $n, int|string $d): string
    {
        return '<span class="fr"><span class="nu">'.$n.'</span><span class="de">'.$d.'</span></span>';
    }

    public static function bf(int|string $n, int|string $d): string
    {
        return '<b>'.self::f($n, $d).'</b>';
    }

    /**
     * Fraction, mixed number or integer (reduced).
     */
    public static function m(int $n, int $d): string
    {
        [$n, $d] = self::red($n, $d);

        if (1 === $d) {
            return (string) $n;
        }

        $w = intdiv($n, $d);
        $r = $n % $d;

        if (0 === $w) {
            return self::f($r, $d);
        }

        return '<span class="mx">'.$w.self::f($r, $d).'</span>';
    }

    /**
     * Task with a numeric answer.
     *
     * Options: suffix, tol (absolute tolerance), rel (relative tolerance),
     * min (minimum absolute tolerance with rel), show (displayed solution),
     * est (estimation task), unitHint.
     */
    public static function num(string $q, float|int $v, string $way, array $options = []): array
    {
        return ['q' => $q, 'a' => ['type' => 'num', 'v' => (float) $v, ...$options], 'way' => $way, 'points' => 1];
    }

    /**
     * Task with a fraction answer. Mode "reduced": any equal value in lowest
     * terms; mode "exact": exactly this numerator and denominator.
     */
    public static function frac(string $q, int $n, int $d, string $mode, string $way, array $options = []): array
    {
        if ('reduced' === $mode) {
            [$n, $d] = self::red($n, $d);
        }

        return ['q' => $q, 'a' => ['type' => 'frac', 'n' => $n, 'd' => $d, 'mode' => $mode, ...$options], 'way' => $way, 'points' => 1];
    }
}
