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

namespace Markocupic\ContaoSchoolTest\Task\Area;

use Markocupic\ContaoSchoolTest\Task\Util as U;

/**
 * Umwandeln zwischen Bruch, Prozentzahl und Dezimalbruch.
 */
final class ConversionArea extends AbstractArea
{
    private const DIRS = ['BP', 'PB', 'BD', 'DB', 'PD', 'DP'];

    private const REPEATING = [[1, 3], [2, 3], [1, 6], [5, 6]];

    private const BIG = [[5, 4], [3, 2], [7, 4], [6, 5], [11, 10], [9, 4], [5, 2], [13, 10], [7, 5], [11, 4], [9, 5], [21, 20]];

    public function getAlias(): string
    {
        return 'uw';
    }

    public function getTitle(): string
    {
        return 'Bruch · Prozent · Dezimal';
    }

    public function getInstruction(): string
    {
        return 'Gib Brüche vollständig gekürzt an.';
    }

    protected function generators(int $level): array
    {
        return match ($level) {
            1 => $this->byDirection(static fn () => self::p1()),
            2 => $this->byDirection(static fn () => self::p2()),
            3 => $this->byDirection(static fn () => self::p3()),
            4 => $this->level4(),
            5 => $this->level5(),
            6 => [...$this->byDirection(static fn () => self::p3()), ...$this->level4(), ...$this->level5()],
        };
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function p1(): array
    {
        return U::pick([[1, 2], [1, 4], [3, 4], [1, 10], [3, 10], [7, 10], [9, 10]]);
    }

    private static function p2(): array
    {
        return match (U::ri(0, 2)) {
            0 => self::p1(),
            1 => [U::ri(1, 4), 5],
            2 => U::red(U::ri(1, 99), 100),
        };
    }

    private static function p3(): array
    {
        return match (U::ri(0, 4)) {
            0 => self::p2(),
            1 => [U::pick([1, 3, 5, 7]), 8],
            2 => U::red(U::ri(1, 19), 20),
            3 => U::red(U::ri(1, 24), 25),
            4 => U::red(U::ri(1, 49), 50),
        };
    }

    private static function pct(int $n, int $d): float
    {
        return $n * 100 / $d;
    }

    private static function chain(int $n, int $d, int $k = 0): string
    {
        $pre = $k ? U::f($n * $k, $d * $k).' = ' : '';
        $mid = 0 === 100 % $d ? U::m($n, $d).' = '.U::f(intdiv($n * 100, $d), 100) : U::m($n, $d)." = $n : $d";

        return $pre.$mid.' = '.U::fmt($n / $d).' = '.U::fmt(self::pct($n, $d)).' %';
    }

    public static function task(array $v, string $dir, int $k = 0): array
    {
        [$n, $d] = $v;
        $fr = $k ? U::bf($n * $k, $d * $k) : '<b>'.U::m($n, $d).'</b>';
        $pc = '<b>'.U::fmt(self::pct($n, $d)).'&nbsp;%</b>';
        $de = '<b>'.U::fmt($n / $d).'</b>';
        $way = self::chain($n, $d, $k);

        return match ($dir) {
            'BP' => U::num("Schreibe $fr als Prozentzahl.", self::pct($n, $d), $way, ['suffix' => '%']),
            'BD' => U::num("Schreibe $fr als Dezimalbruch.", $n / $d, $way),
            'PB' => U::frac("Schreibe $pc als Bruch.", $n, $d, 'reduced', $way),
            'DB' => U::frac("Schreibe $de als Bruch.", $n, $d, 'reduced', $way),
            'PD' => U::num("Schreibe $pc als Dezimalbruch.", $n / $d, $way),
            'DP' => U::num("Schreibe $de als Prozentzahl.", self::pct($n, $d), $way, ['suffix' => '%']),
        };
    }

    private function byDirection(\Closure $pool): array
    {
        return array_map(static fn (string $dir) => static fn (): array => self::task($pool(), $dir), self::DIRS);
    }

    /**
     * @return array{0: array{0: int, 1: int}, 1: int}
     */
    private static function unreduced(): array
    {
        do {
            $v = self::p3();
        } while ($v[1] > 25);

        return [$v, U::ri(2, max(2, intdiv(60, $v[1])))];
    }

    private function level4(): array
    {
        return [
            static fn (): array => self::task(U::pick(self::BIG), 'BP'),
            static fn (): array => self::task(U::pick(self::BIG), 'PB'),
            static fn (): array => self::task(U::pick(self::BIG), 'DB'),
            static fn (): array => self::task(U::pick(self::BIG), 'DP'),
            static function (): array {
                [$v, $k] = self::unreduced();

                return self::task($v, 'BP', $k);
            },
            static function (): array {
                [$v, $k] = self::unreduced();

                return self::task($v, 'BD', $k);
            },
        ];
    }

    private function level5(): array
    {
        return [
            static function (): array {
                [$n, $d] = U::pick(self::REPEATING);
                $p = self::pct($n, $d);
                $r = round($p * 10) / 10;

                return U::num('Schreibe '.U::bf($n, $d).' als Prozentzahl. Runde auf eine Stelle nach dem Punkt.', $p, U::f($n, $d)." = $n : $d = ".substr(U::fmt($n / $d), 0, 7).'… ≈ '.U::fmt($r).' %', ['suffix' => '%', 'tol' => 0.05, 'show' => U::fmt($r)]);
            },
            static function (): array {
                [$n, $d] = U::pick(self::REPEATING);
                $r = round($n / $d * 100) / 100;

                return U::num('Schreibe '.U::bf($n, $d).' als Dezimalbruch. Runde auf zwei Stellen nach dem Punkt.', $n / $d, U::f($n, $d)." = $n : $d = ".substr(U::fmt($n / $d), 0, 7).'… ≈ '.U::fmt($r), ['tol' => 0.005, 'show' => U::fmt($r)]);
            },
            static function (): array {
                [$n, $d] = U::pick(self::REPEATING);
                $mp = U::m($n * 100, $d);

                return U::frac("Schreibe <b>$mp&nbsp;%</b> als Bruch.", $n, $d, 'reduced', "$mp % = $mp : 100 = ".U::f($n, $d));
            },
            static fn (): array => self::task([U::pick([1, 3, 5, 7]), 8], 'PB'),
            static fn (): array => self::task([U::pick([1, 3, 5, 7]), 8], 'BP'),
            static fn (): array => self::task([U::pick([1, 3, 5, 7]), 8], 'PD'),
        ];
    }
}
