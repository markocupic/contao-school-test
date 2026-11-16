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
 * Prozent-Tabellen (Proportionalität): one reference column and 6–7 empty fields.
 */
final class PercentTableArea extends AbstractArea
{
    private const QTY = [
        ['n' => 'Gewicht', 'b' => 'kg', 's' => 'g', 'f' => 1000, 'B' => [2, 3, 4, 5, 6, 8, 12, 16, 20, 24, 40, 60, 80]],
        ['n' => 'Länge', 'b' => 'm', 's' => 'cm', 'f' => 100, 'B' => [2, 4, 5, 8, 12, 16, 20, 24, 32, 40, 60, 80, 120]],
        ['n' => 'Preis', 'b' => 'Fr.', 's' => 'Rp.', 'f' => 100, 'B' => [2, 4, 5, 8, 10, 12, 20, 24, 40, 50, 60, 80, 120, 200]],
        ['n' => 'Volumen', 'b' => 'l', 's' => 'dl', 'f' => 10, 'B' => [2, 4, 5, 6, 8, 10, 12, 20, 30, 40]],
    ];

    private const P_EASY = [10, 20, 25, 50, 75, 200, 1, 5, 30, 40, 60, 150];

    private const P_HARD = [325, 250, 175, 120, 1.5, 5.5, 2.5, 0.5, 12.5, 7.5, 2000, 1000, 400];

    private const P_KNOWN = [75, 25, 50, 20, 40, 60, 80, 150, 200, 10, 5];

    public function getAlias(): string
    {
        return 'pt';
    }

    public function getTitle(): string
    {
        return 'Prozent-Tabellen';
    }

    public function getInstruction(): string
    {
        return 'Vervollständige die Tabelle. Die grau hinterlegte Spalte ist der Bezug.';
    }

    protected function generators(int $level): array
    {
        $all = [...self::P_EASY, ...self::P_HARD];

        return match ($level) {
            1 => [static fn (): array => self::table(true, self::P_EASY, 'val', 6)],
            2 => [static fn (): array => self::table(true, self::P_EASY, 'pct', 6)],
            3 => [static fn (): array => self::table(false, self::P_EASY, 'mix', 6)],
            4 => [static fn (): array => self::table(null, [...self::P_HARD, 10, 20, 25, 50], 'mix', 6)],
            5 => [static fn (): array => self::table(null, $all, 'mix', 6, true)],
            6 => [static fn (): array => self::table(false, $all, 'mix', 7, true)],
        };
    }

    private static function dec(float $x, int $k): bool
    {
        $s = $x * 10 ** $k;

        return abs(round($s) - $s) < 1e-6;
    }

    private static function nat(float $v): string
    {
        return $v < 1 ? 's' : 'b';
    }

    private static function inU(array $q, float $v, string $u): float
    {
        return 's' === $u ? round($v * $q['f'], 6) : round($v, 6);
    }

    private static function vu(array $q, float $v, string $u): string
    {
        return U::sep(U::fmt(self::inU($q, $v, $u))).'&nbsp;'.$q[$u];
    }

    /**
     * @param bool|null $given100 true: reference is 100 %, false: another percentage, null: either
     */
    public static function table(bool|null $given100, array $set, string $mode, int $n, bool $units = false): array
    {
        for ($tries = 0; $tries < 2000; ++$tries) {
            $q = U::pick(self::QTY);
            $base = U::pick($q['B']);
            $p1 = true === $given100 ? 100 : (false === $given100 ? U::pick(self::P_KNOWN) : U::pick([100, ...self::P_KNOWN]));
            $v1 = $base * $p1 / 100;

            if (!self::dec($v1, 2) || !self::dec($v1 / $p1, 4)) {
                continue;
            }

            $u1 = $units && U::chance(0.4) ? ('b' === self::nat($v1) ? 's' : 'b') : self::nat($v1);
            $a1 = self::inU($q, $v1, $u1);

            if (!self::dec($a1, 2) || $a1 >= 100000 || ('s' === $u1 && $a1 > 5000)) {
                continue;
            }

            $cols = [['ref' => true, 'top' => self::vu($q, $v1, $u1), 'bot' => U::fmt($p1).'&nbsp;%']];
            $rest = array_values(array_filter($set, static fn ($p) => $p != $p1 && 100 != $p));
            $cand = 100 === $p1 ? U::shuffle($rest) : [100, ...U::shuffle($rest)];

            foreach ($cand as $p2) {
                if (\count($cols) > $n) {
                    break;
                }

                $v2 = $base * $p2 / 100;
                $u2 = self::nat($v2);

                if ($units && U::chance()) {
                    $u2 = 'b' === $u2 ? 's' : 'b';
                }

                $a2 = self::inU($q, $v2, $u2);

                if (!self::dec($a2, 2) || $a2 >= 100000 || $a2 < 0.01 || ('s' === $u2 && $a2 > 5000)) {
                    continue;
                }

                $colMode = 'mix' === $mode ? (\count($cols) % 2 ? 'val' : 'pct') : $mode;

                if ('val' === $colMode) {
                    $cols[] = ['ask' => 'top', 'v' => $a2, 'unit' => $q[$u2], 'bot' => U::fmt($p2).'&nbsp;%', 'show' => self::vu($q, $v2, $u2)];
                } else {
                    $cols[] = ['ask' => 'bot', 'v' => (float) $p2, 'unit' => '%', 'top' => self::vu($q, $v2, $u2), 'show' => U::fmt($p2).'&nbsp;%'];
                }
            }

            if (\count($cols) < $n + 1) {
                continue;
            }

            $ref = array_shift($cols);
            $cols = U::shuffle($cols);
            array_splice($cols, U::ri(0, \count($cols)), 0, [$ref]);

            $way = 100 === $p1
                ? '100 % = '.self::vu($q, $base, 'b').' → 1 % = '.U::sep(U::fmt($base / 100)).'&nbsp;'.$q['b']
                : U::fmt($p1).' % = '.self::vu($q, $v1, 'b').' → 1 % = '.U::sep(U::fmt($v1 / $p1)).'&nbsp;'.$q['b'].' → 100 % = '.self::vu($q, $base, 'b');

            if ('val' !== $mode) {
                $way .= '. Gesuchte Prozente: Wert : '.self::vu($q, $base, 'b').' · 100';
            }

            if ($units) {
                $way .= '. Achte auf die Einheiten: 1 kg = 1000 g, 1 m = 100 cm, 1 Fr. = 100 Rp., 1 l = 10 dl';
            }

            return [
                'q' => 'Vervollständige die Tabelle.',
                'key' => md5(json_encode($cols, JSON_THROW_ON_ERROR)),
                'a' => ['type' => 'table', 'name' => $q['n'], 'cols' => $cols],
                'way' => $way,
                'points' => \count($cols) - 1,
            ];
        }

        throw new \RuntimeException('Could not generate a percent table.');
    }
}
