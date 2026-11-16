<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Triangle;

/**
 * Measures of a triangle ABC (lengths in cm, angles in degrees) and a small solver
 * that finds the triangle for three given measures.
 *
 * Labels as in school: side a opposite A (a = BC), angle α at A, h_c is the height on c,
 * w_α the angle bisector from A to side a, s_c the median from C to the middle of c.
 */
final class TriangleGeometry
{
    public const LENGTHS = ['a', 'b', 'c', 'ha', 'hb', 'hc', 'wa', 'wb', 'wc', 'sa', 'sb', 'sc'];

    public const ANGLES = ['alpha', 'beta', 'gamma'];

    /**
     * @param array{0: float, 1: float} $A
     * @param array{0: float, 1: float} $B
     * @param array{0: float, 1: float} $C
     *
     * @return array<string, float>
     */
    public static function measures(array $A, array $B, array $C): array
    {
        $a = self::dist($B, $C);
        $b = self::dist($C, $A);
        $c = self::dist($A, $B);
        $alpha = self::angle($A, $B, $C);
        $beta = self::angle($B, $C, $A);
        $gamma = self::angle($C, $A, $B);
        $area2 = abs(($B[0] - $A[0]) * ($C[1] - $A[1]) - ($C[0] - $A[0]) * ($B[1] - $A[1]));

        // Angle bisector from a vertex: length to the opposite side
        $w = static function (float $s1, float $s2, float $angle): float {
            return 2 * $s1 * $s2 * cos(deg2rad($angle / 2)) / max(1e-9, $s1 + $s2);
        };

        // Median from a vertex to the middle of the opposite side
        $s = static fn (array $P, array $Q, array $R): float => self::dist($P, [($Q[0] + $R[0]) / 2, ($Q[1] + $R[1]) / 2]);

        return [
            'a' => $a,
            'b' => $b,
            'c' => $c,
            'alpha' => $alpha,
            'beta' => $beta,
            'gamma' => $gamma,
            'ha' => $a > 1e-9 ? $area2 / $a : 0.0,
            'hb' => $b > 1e-9 ? $area2 / $b : 0.0,
            'hc' => $c > 1e-9 ? $area2 / $c : 0.0,
            'wa' => $w($b, $c, $alpha),
            'wb' => $w($a, $c, $beta),
            'wc' => $w($a, $b, $gamma),
            'sa' => $s($A, $B, $C),
            'sb' => $s($B, $C, $A),
            'sc' => $s($C, $A, $B),
        ];
    }

    /**
     * Triangle A = (0, 0), B = (c, 0), C above AB that has the given measures. Newton's method,
     * starting from a triangle close to the solution (the one the values were taken from).
     *
     * @param array<string, float>                                                              $given three measures
     * @param array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}, 2: array{0: float, 1: float}} $start
     *
     * @return array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}, 2: array{0: float, 1: float}}|null
     */
    public static function solve(array $given, array $start): array|null
    {
        // Start in the standard position
        $m = self::measures(...$start);
        $p = [$m['c'], $m['b'] * cos(deg2rad($m['alpha'])), $m['b'] * sin(deg2rad($m['alpha']))];
        $keys = array_keys($given);

        // Angles count less than lengths: 1° is about 1 mm here
        $residual = static function (array $p) use ($given, $keys): array {
            $m = self::measures([0, 0], [$p[0], 0], [$p[1], $p[2]]);

            return array_map(static fn (string $k): float => ($m[$k] - $given[$k]) * (\in_array($k, self::ANGLES, true) ? 0.1 : 1), $keys);
        };

        for ($it = 0; $it < 60; ++$it) {
            $r = $residual($p);

            if (max(array_map('abs', $r)) < 1e-10) {
                break;
            }

            // Numerical Jacobian
            $J = [];

            for ($j = 0; $j < 3; ++$j) {
                $q = $p;
                $q[$j] += 1e-7;
                $rq = $residual($q);

                foreach ($rq as $i => $v) {
                    $J[$i][$j] = ($v - $r[$i]) / 1e-7;
                }
            }

            $step = self::solve3($J, array_map(static fn (float $v): float => -$v, $r));

            if (null === $step) {
                return null;
            }

            foreach ($step as $j => $v) {
                $p[$j] += max(-1.0, min(1.0, $v));
            }
        }

        $r = $residual($p);

        if (max(array_map('abs', $r)) > 1e-6 || $p[0] <= 0 || $p[2] <= 0) {
            return null;
        }

        return [[0.0, 0.0], [$p[0], 0.0], [$p[1], $p[2]]];
    }

    public static function dist(array $P, array $Q): float
    {
        return hypot($P[0] - $Q[0], $P[1] - $Q[1]);
    }

    /**
     * Angle at P between the rays to Q and R, in degrees.
     */
    public static function angle(array $P, array $Q, array $R): float
    {
        $u = [$Q[0] - $P[0], $Q[1] - $P[1]];
        $v = [$R[0] - $P[0], $R[1] - $P[1]];
        $len = hypot(...$u) * hypot(...$v);

        if ($len < 1e-12) {
            return 0.0;
        }

        return rad2deg(acos(max(-1.0, min(1.0, ($u[0] * $v[0] + $u[1] * $v[1]) / $len))));
    }

    /**
     * Gaussian elimination for 3 × 3.
     */
    private static function solve3(array $A, array $b): array|null
    {
        $n = 3;

        for ($i = 0; $i < $n; ++$i) {
            $A[$i][] = $b[$i];
        }

        for ($c = 0; $c < $n; ++$c) {
            $pivot = $c;

            for ($r = $c + 1; $r < $n; ++$r) {
                if (abs($A[$r][$c]) > abs($A[$pivot][$c])) {
                    $pivot = $r;
                }
            }

            if (abs($A[$pivot][$c]) < 1e-12) {
                return null;
            }

            [$A[$c], $A[$pivot]] = [$A[$pivot], $A[$c]];

            for ($r = 0; $r < $n; ++$r) {
                if ($r !== $c) {
                    $f = $A[$r][$c] / $A[$c][$c];

                    for ($k = $c; $k <= $n; ++$k) {
                        $A[$r][$k] -= $f * $A[$c][$k];
                    }
                }
            }
        }

        return array_map(static fn (int $i): float => $A[$i][$n] / $A[$i][$i], range(0, $n - 1));
    }
}
