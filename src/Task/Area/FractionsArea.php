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
 * Bruchrechnen: kürzen und erweitern, gemischte Zahlen, addieren und subtrahieren
 * (auch mit Übertrag), Bruchteile, multiplizieren, dividieren, Punkt vor Strich.
 *
 * Fractions are handled as [numerator, denominator] pairs (improper, reduced).
 */
final class FractionsArea extends AbstractArea
{
    /**
     * Denominators for unlike fractions.
     */
    private const D = [2, 3, 4, 5, 6, 8, 9, 10, 12, 15];

    public function getAlias(): string
    {
        return 'br';
    }

    public function getTitle(): string
    {
        return 'Bruchrechnen';
    }

    public function getInstruction(): string
    {
        return 'Gib Brüche vollständig gekürzt an. Gemischte Zahlen sind erlaubt.';
    }

    protected function generators(int $level): array
    {
        return match ($level) {
            1 => $this->level1(),
            2 => $this->level2(),
            3 => $this->level3(),
            4 => $this->level4(),
            5 => $this->level5(),
            6 => [...$this->level6(), ...\array_slice($this->level4(), 1, 2), ...\array_slice($this->level5(), 0, 2)],
        };
    }

    /* ---------- Rational helpers ---------- */

    private static function add(array $a, array $b): array
    {
        return U::red($a[0] * $b[1] + $b[0] * $a[1], $a[1] * $b[1]);
    }

    private static function sub(array $a, array $b): array
    {
        return U::red($a[0] * $b[1] - $b[0] * $a[1], $a[1] * $b[1]);
    }

    private static function mul(array $a, array $b): array
    {
        return U::red($a[0] * $b[0], $a[1] * $b[1]);
    }

    private static function div(array $a, array $b): array
    {
        return U::red($a[0] * $b[1], $a[1] * $b[0]);
    }

    /**
     * Mixed number w n/d as improper fraction.
     */
    private static function mixed(int $w, int $n, int $d): array
    {
        return U::red($w * $d + $n, $d);
    }

    /**
     * Bold display: fraction, mixed number or integer.
     */
    private static function show(array $r): string
    {
        return '<b>'.U::m($r[0], $r[1]).'</b>';
    }

    /**
     * Bold display of a fraction exactly as given (not reduced).
     */
    private static function showRaw(int $n, int $d): string
    {
        return U::bf($n, $d);
    }

    private static function improper(array $r): string
    {
        return U::f($r[0], $r[1]);
    }

    /**
     * "2 1/4 = 9/4" for every mixed number in the list.
     */
    private static function convertWay(array ...$values): string
    {
        $parts = [];

        foreach ($values as $r) {
            if ($r[0] > $r[1] && 0 !== $r[0] % $r[1]) {
                $parts[] = U::m($r[0], $r[1]).' = '.U::f($r[0], $r[1]);
            }
        }

        return $parts ? 'Umwandeln: '.implode(', ', $parts).'. ' : '';
    }

    private static function resultFrac(string $q, array $r, string $way): array
    {
        return U::frac($q, $r[0], $r[1], 'reduced', $way);
    }

    /**
     * Two proper fractions with different denominators and a common denominator up to $maxLcm.
     */
    private static function pair(int $maxLcm = 60): array
    {
        do {
            $d1 = U::pick(self::D);
            $d2 = U::pick(self::D);
        } while ($d1 === $d2 || U::lcm($d1, $d2) > $maxLcm || 0 === $d1 % $d2 || 0 === $d2 % $d1 && U::chance(0.5));

        return [[U::properNum($d1), $d1], [U::properNum($d2), $d2]];
    }

    private static function lcdWay(array $a, array $b, string $op): string
    {
        $l = U::lcm($a[1], $b[1]);
        $x = $a[0] * intdiv($l, $a[1]);
        $y = $b[0] * intdiv($l, $b[1]);
        $n = '+' === $op ? $x + $y : $x - $y;

        return 'Gemeinsamer Nenner '.$l.': '.U::f($x, $l).' '.$op.' '.U::f($y, $l).' = '.U::f($n, $l);
    }

    /* ---------- Level 1: Kürzen, Erweitern, gemischte Zahlen ---------- */

    private function level1(): array
    {
        return [
            static function (): array {
                do {
                    [$p, $q] = U::prop([3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 15]);
                    $k = U::ri(3, 12);
                } while ($k * $q > 180);

                return U::frac('Kürze '.self::showRaw($k * $p, $k * $q).' vollständig.', $p, $q, 'reduced', 'ggT von '.($k * $p).' und '.($k * $q)." ist $k: ".U::f($k * $p, $k * $q).' = '.U::f($p, $q), ['noWhole' => true]);
            },
            static function (): array {
                do {
                    [$p, $q] = U::prop([3, 4, 5, 6, 7, 8, 9, 12, 15]);
                    $k = U::ri(3, 12);
                } while ($k * $q > 120);

                return U::frac('Erweitere '.self::showRaw($p, $q).' auf den Nenner '.U::b($k * $q).'.', $k * $p, $k * $q, 'exact', ($k * $q)." : $q = $k, also mit $k erweitern: ".U::f($k * $p, $k * $q), ['noWhole' => true]);
            },
            static function (): array {
                $d = U::ri(3, 12);
                $w = U::ri(2, 9);
                $n = U::properNum($d);

                return U::frac('Schreibe '.self::showRaw($w * $d + $n, $d).' als gemischte Zahl.', $w * $d + $n, $d, 'mixed', ($w * $d + $n)." : $d = $w Rest $n, also ".U::m($w * $d + $n, $d));
            },
            static function (): array {
                $d = U::ri(3, 12);
                $w = U::ri(2, 9);
                $n = U::properNum($d);

                return U::frac('Schreibe <b>'.U::m($w * $d + $n, $d).'</b> als unechten Bruch.', $w * $d + $n, $d, 'improper', "$w · $d + $n = ".($w * $d + $n).', also '.U::f($w * $d + $n, $d), ['noWhole' => true]);
            },
        ];
    }

    /* ---------- Level 2: Gleichnamige Brüche und gemischte Zahlen ---------- */

    private function level2(): array
    {
        return [
            // Subtraction with borrowing: 3 1/5 − 1 4/5
            static function (): array {
                $d = U::ri(4, 12);
                $n1 = U::ri(1, $d - 2);
                $n2 = U::ri($n1 + 1, $d - 1);
                $w1 = U::ri(2, 7);
                $w2 = U::ri(1, $w1 - 1);
                $a = self::mixed($w1, $n1, $d);
                $b = self::mixed($w2, $n2, $d);
                $r = self::sub($a, $b);

                return self::resultFrac('<b>'.U::m($w1 * $d + $n1, $d).'</b> − <b>'.U::m($w2 * $d + $n2, $d).'</b> =', $r, 'Ein Ganzes in '.$d.'tel umwandeln: '.U::m($w1 * $d + $n1, $d).' = '.($w1 - 1).U::f($d + $n1, $d).'. Dann '.($w1 - 1 - $w2).' Ganze und '.U::f($d + $n1 - $n2, $d).' = '.U::m($r[0], $r[1]));
            },
            // Addition with carry: 2 3/8 + 1 7/8
            static function (): array {
                $d = U::ri(4, 12);
                $n1 = U::ri(2, $d - 1);
                $n2 = U::ri($d - $n1 + 1, $d - 1);
                $w1 = U::ri(1, 6);
                $w2 = U::ri(1, 5);
                $r = self::add(self::mixed($w1, $n1, $d), self::mixed($w2, $n2, $d));

                return self::resultFrac('<b>'.U::m($w1 * $d + $n1, $d).'</b> + <b>'.U::m($w2 * $d + $n2, $d).'</b> =', $r, ($w1 + $w2).' Ganze + '.U::f($n1 + $n2, $d).' = '.U::m($r[0], $r[1]));
            },
            // Three like fractions
            static function (): array {
                $d = U::ri(6, 15);
                $a = U::ri(1, $d - 1);
                $b = U::ri(1, $d - 1);
                $c = U::ri(1, $a + $b - 1);
                $r = U::red($a + $b - $c, $d);

                return self::resultFrac(self::showRaw($a, $d).' + '.self::showRaw($b, $d).' − '.self::showRaw($c, $d).' =', $r, "Zähler: $a + $b − $c = ".($a + $b - $c).', also '.U::f($a + $b - $c, $d).(($r[1] !== $d || $r[0] >= $r[1]) ? ' = '.U::m($r[0], $r[1]) : ''));
            },
            // Whole number minus mixed number: 4 − 2 3/7
            static function (): array {
                $d = U::ri(3, 12);
                $n = U::properNum($d);
                $w = U::ri(1, 5);
                $z = U::ri($w + 1, 9);
                $r = self::sub([$z, 1], self::mixed($w, $n, $d));

                return self::resultFrac(U::b($z).' − <b>'.U::m($w * $d + $n, $d).'</b> =', $r, "$z = ".($z - 1).U::f($d, $d).', '.($z - 1).U::f($d, $d).' − '.U::m($w * $d + $n, $d).' = '.U::m($r[0], $r[1]));
            },
        ];
    }

    /* ---------- Level 3: Bruchteile ---------- */

    private function level3(): array
    {
        return [
            static function (): array {
                [$p, $q] = U::prop([3, 4, 5, 6, 7, 8, 9, 12, 15]);
                $m = U::ri(4, intdiv(300, $q));
                $t = $q * $m;

                return U::num('Wie viel sind '.self::showRaw($p, $q).' von '.U::b($t).'?', $p * $m, "$t : $q = $m, dann $m · $p = ".($p * $m));
            },
            static function (): array {
                [$p, $q] = U::prop([3, 4, 5, 6, 7, 8, 9, 12]);
                $m = U::ri(6, 40);

                return U::num(self::showRaw($p, $q).' einer Zahl sind '.U::b($p * $m).'. Wie heisst die Zahl?', $q * $m, ($p * $m)." : $p = $m (das ist ".U::f(1, $q)."), dann $m · $q = ".($q * $m));
            },
            static function (): array {
                [$p, $q] = U::prop([4, 5, 8, 10, 20, 25]);
                $kg = U::ri(2, 5);
                $g = intdiv(1000 * $kg, $q) * $p;

                return U::num('Wie viele Gramm sind '.self::showRaw($p, $q).' von '.U::b($kg.' kg').'?', $g, "$kg kg = ".(1000 * $kg).' g, '.(1000 * $kg)." : $q = ".intdiv(1000 * $kg, $q).', dann · '.$p.' = '.$g.' g', ['suffix' => 'g']);
            },
            static function (): array {
                [$p, $q] = U::prop([3, 4, 5, 6, 10, 12, 15, 20]);
                $h = U::ri(1, 3);
                $min = $h * 60 + intdiv(60, $q) * $p;

                return U::num('Wie viele Minuten sind <b>'.U::m($h * $q + $p, $q).'</b> Stunden?', $min, "$h h = ".($h * 60).' min, '.U::f($p, $q).' h = '.(intdiv(60, $q) * $p).' min, zusammen '.$min.' min', ['suffix' => 'min']);
            },
            static function (): array {
                [$p, $q] = U::prop([3, 4, 5, 6, 8]);
                [$r, $s] = U::prop([2, 3, 4, 5]);
                $m = U::ri(2, 10);
                $t = $q * $s * $m;

                return U::num('Wie viel sind '.self::showRaw($r, $s).' von '.self::showRaw($p, $q).' von '.U::b($t).'?', $t / $q * $p / $s * $r, U::f($p, $q)." von $t = ".($t / $q * $p).', davon '.U::f($r, $s).' = '.($t / $q * $p / $s * $r));
            },
        ];
    }

    /* ---------- Level 4: Ungleichnamige Brüche ---------- */

    private function level4(): array
    {
        return [
            static function (): array {
                [$a, $b] = self::pair();
                $r = self::add($a, $b);

                return self::resultFrac(self::showRaw(...$a).' + '.self::showRaw(...$b).' =', $r, self::lcdWay($a, $b, '+').(($r[0] >= $r[1] || U::lcm($a[1], $b[1]) !== $r[1]) ? ' = '.U::m($r[0], $r[1]) : ''));
            },
            // Mixed numbers: 2 1/4 + 1 5/6
            static function (): array {
                [$a, $b] = self::pair(36);
                $ma = self::add([U::ri(1, 4), 1], $a);
                $mb = self::add([U::ri(1, 3), 1], $b);
                $r = self::add($ma, $mb);

                return self::resultFrac(self::show($ma).' + '.self::show($mb).' =', $r, self::convertWay($ma, $mb).self::lcdWay($ma, $mb, '+').' = '.U::m($r[0], $r[1]));
            },
            // Mixed numbers with borrowing: 4 1/3 − 1 3/4
            static function (): array {
                do {
                    [$a, $b] = self::pair(36);
                } while ($a[0] * $b[1] >= $b[0] * $a[1]);

                $w = U::ri(2, 6);
                $ma = self::add([$w, 1], $a);
                $mb = self::add([U::ri(1, $w - 1), 1], $b);
                $r = self::sub($ma, $mb);

                return self::resultFrac(self::show($ma).' − '.self::show($mb).' =', $r, self::convertWay($ma, $mb).self::lcdWay($ma, $mb, '−').' = '.U::m($r[0], $r[1]));
            },
            // Three fractions
            static function (): array {
                do {
                    $ds = [U::pick([2, 3, 4, 5, 6]), U::pick([3, 4, 6, 8, 9, 10, 12]), U::pick([2, 4, 5, 6, 12])];
                    $l = U::lcm(U::lcm($ds[0], $ds[1]), $ds[2]);
                } while (\count(array_unique($ds)) < 3 || $l > 60);

                $f = array_map(static fn (int $d): array => [U::properNum($d), $d], $ds);
                $r = self::sub(self::add($f[0], $f[1]), $f[2]);

                if ($r[0] <= 0) {
                    $r = self::add(self::add($f[0], $f[1]), $f[2]);
                    $op = '+';
                } else {
                    $op = '−';
                }

                $nums = array_map(static fn (array $x): int => $x[0] * intdiv($l, $x[1]), $f);

                return self::resultFrac(self::showRaw(...$f[0]).' + '.self::showRaw(...$f[1]).' '.$op.' '.self::showRaw(...$f[2]).' =', $r, "Gemeinsamer Nenner $l: ".U::f($nums[0], $l).' + '.U::f($nums[1], $l).' '.$op.' '.U::f($nums[2], $l).' = '.U::m($r[0], $r[1]));
            },
        ];
    }

    /* ---------- Level 5: Multiplizieren ---------- */

    private function level5(): array
    {
        return [
            // Cancel before multiplying: 8/15 · 25/32
            static function (): array {
                do {
                    $x = U::ri(2, 6);
                    $y = U::ri(2, 6);
                    // p and s share the factor x, r and q share y: cross cancelling
                    [$p, $s] = [U::ri(1, 5) * $x, U::ri(2, 9) * $x];
                    [$r, $q] = [U::ri(1, 5) * $y, U::ri(2, 9) * $y];
                    $res = self::mul([$p, $q], [$r, $s]);
                } while ($p >= $q || $r >= $s || $q > 40 || $s > 40 || U::gcd($p, $q) > 1 || U::gcd($r, $s) > 1 || $res[1] > 60);

                return self::resultFrac(self::showRaw($p, $q).' · '.self::showRaw($r, $s).' =', $res, 'Vor dem Multiplizieren kürzen: '.U::f("$p · $r", "$q · $s").' = '.U::m($res[0], $res[1]));
            },
            // Mixed numbers: 1 1/2 · 2 2/3
            static function (): array {
                $a = self::add([U::ri(1, 3), 1], U::prop([2, 3, 4, 5]));
                $b = self::add([U::ri(1, 3), 1], U::prop([2, 3, 4, 5, 6]));
                $r = self::mul($a, $b);

                return self::resultFrac(self::show($a).' · '.self::show($b).' =', $r, self::convertWay($a, $b).self::improper($a).' · '.self::improper($b).' = '.U::f($a[0] * $b[0], $a[1] * $b[1]).' = '.U::m($r[0], $r[1]));
            },
            // Fraction times whole number with cancelling: 5/6 · 14
            static function (): array {
                [$p, $q] = U::prop([4, 6, 8, 9, 10, 12, 15]);
                $k = U::ri(2, 5) * U::pick(array_filter([2, 3, 4, 5, 6], static fn ($f) => 0 === $q % $f) ?: [1]) + U::ri(0, 1);
                $r = self::mul([$p, $q], [$k, 1]);

                return self::resultFrac(self::showRaw($p, $q).' · '.U::b($k).' =', $r, U::f("$p · $k", $q).' = '.U::f($p * $k, $q).' = '.U::m($r[0], $r[1]));
            },
            // "von" with a mixed number
            static function (): array {
                [$r, $s] = U::prop([2, 3, 4, 5]);
                $m = self::add([U::ri(1, 4), 1], U::prop([2, 3, 4, 6]));
                $res = self::mul([$r, $s], $m);

                return self::resultFrac('Wie viel ist '.self::showRaw($r, $s).' von '.self::show($m).'?', $res, '«von» heisst mal: '.U::f($r, $s).' · '.self::improper($m).' = '.U::m($res[0], $res[1]));
            },
        ];
    }

    /* ---------- Level 6: Dividieren, Punkt vor Strich ---------- */

    private function level6(): array
    {
        return [
            // Mixed : mixed
            static function (): array {
                do {
                    $a = self::add([U::ri(1, 4), 1], U::prop([2, 3, 4, 5, 6]));
                    $b = self::add([U::ri(1, 2), 1], U::prop([2, 3, 4, 5, 9]));
                    $r = self::div($a, $b);
                } while ($r[1] > 30);

                return self::resultFrac(self::show($a).' : '.self::show($b).' =', $r, self::convertWay($a, $b).'Mit dem Kehrwert multiplizieren: '.self::improper($a).' · '.U::f($b[1], $b[0]).' = '.U::m($r[0], $r[1]));
            },
            // Whole number : mixed number
            static function (): array {
                $b = self::add([U::ri(1, 3), 1], U::prop([2, 3, 4, 5]));
                $z = U::ri(2, 12);
                $r = self::div([$z, 1], $b);

                return self::resultFrac(U::b($z).' : '.self::show($b).' =', $r, self::convertWay($b)."$z · ".U::f($b[1], $b[0]).' = '.U::m($r[0], $r[1]));
            },
            // Fraction : fraction
            static function (): array {
                [$p, $q] = U::prop([3, 4, 5, 6, 8, 9, 10, 12, 15]);
                [$r, $s] = U::prop([3, 4, 5, 6, 8, 9]);
                $res = self::div([$p, $q], [$r, $s]);

                return self::resultFrac(self::showRaw($p, $q).' : '.self::showRaw($r, $s).' =', $res, 'Mit dem Kehrwert multiplizieren: '.U::f($p, $q).' · '.U::f($s, $r).' = '.U::m($res[0], $res[1]));
            },
            // Punkt vor Strich: a + b · c
            static function (): array {
                $a = U::prop([2, 3, 4, 5, 6]);
                $b = U::prop([2, 3, 4, 5, 6, 8]);
                $c = U::prop([3, 4, 5, 6, 9]);
                $prod = self::mul($b, $c);
                $r = self::add($a, $prod);

                return self::resultFrac(self::showRaw(...$a).' + '.self::showRaw(...$b).' · '.self::showRaw(...$c).' =', $r, 'Punkt vor Strich: '.U::f($b[0], $b[1]).' · '.U::f($c[0], $c[1]).' = '.U::m($prod[0], $prod[1]).', dann '.U::f($a[0], $a[1]).' + '.U::m($prod[0], $prod[1]).' = '.U::m($r[0], $r[1]));
            },
            // Punkt vor Strich: a − b : c
            static function (): array {
                do {
                    $a = self::add([U::ri(1, 3), 1], U::prop([2, 3, 4, 6]));
                    $b = U::prop([2, 3, 4, 5, 6]);
                    $c = U::prop([3, 4, 5, 6]);
                    $quot = self::div($b, $c);
                    $r = self::sub($a, $quot);
                } while ($r[0] <= 0 || $quot[1] > 30);

                return self::resultFrac(self::show($a).' − '.self::showRaw(...$b).' : '.self::showRaw(...$c).' =', $r, 'Punkt vor Strich: '.U::f($b[0], $b[1]).' : '.U::f($c[0], $c[1]).' = '.U::m($quot[0], $quot[1]).', dann '.U::m($a[0], $a[1]).' − '.U::m($quot[0], $quot[1]).' = '.U::m($r[0], $r[1]));
            },
        ];
    }
}
