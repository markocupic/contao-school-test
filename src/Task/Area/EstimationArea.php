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
 * Schätzaufgaben mit Prozenten: Prozentwert, Prozentsatz und Grundwert.
 * An estimate counts as correct if it is within 10 % of the exact result.
 */
final class EstimationArea extends AbstractArea
{
    private const UNITS = ['Fr.', 'kg', 'm', 'Liter', 'km', 'g'];

    private const BASE_S = [60, 120, 150, 200, 240, 300, 360, 400, 450, 500, 600, 750, 800, 900, 1200, 1500, 1800, 2400];

    private const BASE_B = [1200, 1500, 2000, 2400, 3000, 4500, 6000, 9000, 12000, 15000, 18000, 24000, 30000, 36000];

    private const RAT = [[1, 2, '50 %'], [1, 3, '33 %'], [2, 3, '67 %'], [1, 4, '25 %'], [3, 4, '75 %'], [1, 5, '20 %'], [2, 5, '40 %'], [3, 5, '60 %'], [4, 5, '80 %'], [1, 10, '10 %'], [3, 10, '30 %'], [1, 20, '5 %']];

    private const WHO = ['Schülerinnen und Schülern', 'Personen', 'Ländern', 'Kindern', 'Velos', 'Bäumen', 'Fragen', 'Tagen'];

    private const WHO_B = ['Personen', 'Besucherinnen und Besuchern', 'Stimmen', 'Losen', 'Einwohnerinnen und Einwohnern'];

    public function getAlias(): string
    {
        return 'sz';
    }

    public function getTitle(): string
    {
        return 'Schätzaufgaben';
    }

    public function getInstruction(): string
    {
        return 'Schätze. Runde die Zahlen geschickt und rechne im Kopf. Höchstens 10 % Abweichung sind erlaubt.';
    }

    protected function generators(int $level): array
    {
        return match ($level) {
            1 => [static fn (): array => self::fair(static fn () => self::percentValue('easy'))],
            2 => [static fn (): array => self::fair(static fn () => self::percentValue('hard'))],
            3 => [static fn (): array => self::fair(static fn () => self::percentRate(false))],
            4 => [static fn (): array => self::fair(static fn () => self::baseValue(false))],
            5 => [
                static fn (): array => self::fair(static fn () => self::percentValue('hard')),
                static fn (): array => self::fair(static fn () => self::percentRate(false)),
                static fn (): array => self::fair(static fn () => self::baseValue(false)),
            ],
            6 => [
                static fn (): array => self::fair(static fn () => self::percentValue('big')),
                static fn (): array => self::fair(static fn () => self::percentRate(true)),
                static fn (): array => self::fair(static fn () => self::baseValue(true)),
            ],
        };
    }

    /**
     * Keep only tasks where the suggested rounding is clearly within the tolerance (8 %).
     */
    private static function fair(\Closure $gen): array
    {
        for ($i = 0; $i < 200; ++$i) {
            $t = $gen();
            $e = $t['a']['estimate'];
            $limit = max(isset($t['a']['min']) ? $t['a']['min'] * 0.8 : 0, 0.08 * $t['a']['v']);

            if (abs($e - $t['a']['v']) <= $limit) {
                return $t;
            }
        }

        return $t;
    }

    /**
     * @return list<array{p: float, l: string, t?: bool}>
     */
    private static function pn(): array
    {
        return [
            ['p' => 10, 'l' => '10 %'], ['p' => 20, 'l' => '20 %'], ['p' => 25, 'l' => '25 %'], ['p' => 50, 'l' => '50 %'],
            ['p' => 5, 'l' => '5 %'], ['p' => 1, 'l' => '1 %'], ['p' => 30, 'l' => '30 %'], ['p' => 40, 'l' => '40 %'],
            ['p' => 60, 'l' => '60 %'], ['p' => 75, 'l' => '75 %'], ['p' => 100 / 3, 'l' => U::f(1, 3), 't' => true], ['p' => 200 / 3, 'l' => U::f(2, 3), 't' => true],
        ];
    }

    /**
     * A "crooked" number near a round percentage.
     */
    private static function wig(float $np): float
    {
        if (abs($np - 100 / 3) < 1e-9) {
            return U::pick([32, 33, 34]);
        }

        if (abs($np - 200 / 3) < 1e-9) {
            return U::pick([65, 66, 67]);
        }

        do {
            $x = $np * (1 + U::pick([-1, 1]) * U::ri(1, 5) / 100);
            $x = $np >= 20 ? round($x) : ($np >= 5 ? round($x * 10) / 10 : round($x * 100) / 100);
        } while (abs($x - $np) < 1e-9);

        return $x;
    }

    private static function wigG(int|float $g): int
    {
        do {
            $x = (int) round($g * (1 + U::pick([-1, 1]) * U::ri(4, 25) / 1000));
        } while ($x == $g);

        return $x;
    }

    private static function dot(string $u): string
    {
        return str_ends_with($u, '.') ? '' : '.';
    }

    private static function percentValue(string $mode): array
    {
        $pn = self::pn();
        $p = 'easy' === $mode ? U::pick(\array_slice($pn, 0, 8)) : U::pick($pn);

        do {
            $nG = U::pick('big' === $mode ? self::BASE_B : self::BASE_S);
        } while (!empty($p['t']) && $nG % 3);

        $g = self::wigG($nG);
        $pp = 'easy' === $mode ? $p['p'] : self::wig($p['p']);
        $u = U::pick(self::UNITS);
        $exact = $pp * $g / 100;
        $est = $p['p'] * $nG / 100;
        $way = 'Runde: '.('easy' === $mode ? '' : U::fmt($pp).' % ≈ '.$p['l'].', ').U::sep($g).' ≈ '.U::sep($nG).'. '.$p['l'].' von '.U::sep($nG).' = '.U::sep(U::r1($est)).' '.$u.self::dot($u).' Genau wären es '.U::sep(U::r1($exact)).' '.$u.self::dot($u);

        return U::num('Schätze: <b>'.U::fmt($pp).'&nbsp;%</b> von <b>'.U::sep($g).'&nbsp;'.$u.'</b>', $exact, $way, ['suffix' => $u, 'rel' => 0.1, 'show' => '≈ '.U::sep(U::r1($est)), 'estimate' => U::r1($est), 'est' => true]);
    }

    private static function percentRate(bool $big): array
    {
        $who = U::pick($big ? self::WHO_B : self::WHO);

        if ($big && U::chance()) {
            $base = U::pick([100, 1000]);
            $g = $base + U::pick([-1, 1]) * U::ri(1, 100 === $base ? 3 : 12);
            $a = U::ri(100 === $base ? 3 : 15, 100 === $base ? 90 : 900);
            $est = $a * 100 / $base;
            $way = "$g ≈ $base, also $a von $base = ".U::fmt($est).' %.';
        } else {
            [$n, $d, $l] = U::pick(self::RAT);
            $lo = $big ? 200 : 12;
            $hi = $big ? 2000 : 120;
            $mMin = max((int) ceil($lo / $d), (int) ceil(10 / $n));
            $m = U::ri($mMin, max($mMin, intdiv($hi, $d)));
            $nG = $d * $m;
            $na = $n * $m;

            do {
                $a = $na;
                $g = $nG;
                $dv = static fn (int $x): int => U::pick([-1, 1]) * max(1, (int) round($x * U::ri(2, 5) / 100));

                if (U::chance()) {
                    $a = $na + $dv($na);
                } else {
                    $g = $nG + $dv($nG);
                }
            } while ($a <= 0 || $a >= $g);

            $est = $n * 100 / $d;
            $way = "$a von $g ≈ $na von $nG = ".U::f($n, $d)." ≈ $l.";
        }

        $exact = $a / $g * 100;

        return U::num('Schätze den Anteil in Prozent: <b>'.U::sep($a).' von '.U::sep($g)."</b> $who", $exact, $way.' Genau wären es '.U::fmt(U::r1($exact)).' %.', ['suffix' => '%', 'rel' => 0.1, 'min' => 2, 'show' => '≈ '.round($est), 'estimate' => round($est), 'est' => true]);
    }

    private static function baseValue(bool $big): array
    {
        $pn = array_values(array_filter(self::pn(), static fn ($x) => $x['p'] >= 5));
        $p = U::pick($pn);

        do {
            $nG = U::pick($big ? self::BASE_B : [16, 20, 40, 60, 80, 120, 200, 240, 300, 400, 600, 800, 1200]);
            $nW = $nG * $p['p'] / 100;
        } while ((!empty($p['t']) && $nG % 3) || abs($nW - round($nW)) > 1e-9);

        $nW = (int) round($nW);
        $w = $nW >= 20 && U::chance() ? self::wigG($nW) : $nW;
        $pp = self::wig($p['p']);
        $u = U::pick(['m', 's', 'Fr.', 'kg', 'km', 'min', 'Liter']);
        $exact = $w / $pp * 100;
        $way = U::fmt($pp).' % ≈ '.$p['l'].($w !== $nW ? ', '.U::sep($w).' ≈ '.U::sep($nW) : '').'. Wenn '.$p['l'].' = '.U::sep($nW).' '.$u.' sind, dann sind 100 % = '.U::sep($nG).' '.$u.self::dot($u).' Genau wären es '.U::sep(U::r1($exact)).' '.$u.self::dot($u);

        return U::num('Wie viel etwa sind 100 %? <b>'.U::sep($w).'&nbsp;'.$u.'</b> sind <b>'.U::fmt($pp).'&nbsp;%</b>.', $exact, $way, ['suffix' => $u, 'rel' => 0.1, 'show' => '≈ '.U::sep($nG), 'estimate' => $nG, 'est' => true]);
    }
}
