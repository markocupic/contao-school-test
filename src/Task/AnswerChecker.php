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
 * Grades one answer against one task. Runs on the server only, the solutions never leave it.
 */
final class AnswerChecker
{
    /**
     * @return array{points: int, max: int, correct: list<bool>}
     */
    public function check(array $task, mixed $answer): array
    {
        $a = $task['a'];

        return match ($a['type']) {
            'num' => $this->result([$this->checkNumber($a, $answer)]),
            'frac' => $this->result([$this->checkFraction($a, \is_array($answer) ? $answer : [])]),
            'table' => $this->checkTable($a, \is_array($answer) ? $answer : []),
            'points' => $this->checkPoints($a, \is_array($answer) ? $answer : []),
            'angles' => $this->checkAngles($a, \is_array($answer) ? $answer : []),
            'rat' => $this->checkRational($a, $answer, (int) ($task['points'] ?? 1)),
            default => throw new \InvalidArgumentException(\sprintf('Unknown answer type "%s".', $a['type'])),
        };
    }

    /**
     * Parses "1'250", "0,75", ".5", "75 %". Returns null for anything else.
     */
    public static function parseNumber(mixed $raw): float|null
    {
        if (!\is_string($raw) && !\is_int($raw) && !\is_float($raw)) {
            return null;
        }

        $s = preg_replace("/['’\\s\\x{00A0}]/u", '', (string) $raw);
        $s = preg_replace('/%$/', '', $s);
        $s = str_replace(',', '.', $s);

        if (!preg_match('/^-?(\d+\.?\d*|\.\d+)$/', $s)) {
            return null;
        }

        return (float) $s;
    }

    public function checkNumber(array $a, mixed $raw): bool
    {
        $x = self::parseNumber($raw);

        if (null === $x) {
            return false;
        }

        if (isset($a['tol'])) {
            $tol = (float) $a['tol'];
        } elseif (isset($a['rel'])) {
            $tol = max((float) ($a['min'] ?? 0), (float) $a['rel'] * abs((float) $a['v']));
        } else {
            $tol = 0.0;
        }

        return abs($x - (float) $a['v']) <= $tol + 1e-6;
    }

    public function checkFraction(array $a, array $raw): bool
    {
        $int = static function (mixed $v): int|false|null {
            $v = trim((string) ($v ?? ''));

            if ('' === $v) {
                return null;
            }

            return preg_match('/^\d+$/', $v) ? (int) $v : false;
        };

        $w = $int($raw['w'] ?? null);
        $n = $int($raw['n'] ?? null);
        $d = $int($raw['d'] ?? null);

        if (false === $w || false === $n || false === $d) {
            return false;
        }

        // A lone number in the numerator box counts as a whole number
        if (null !== $n && null === $d && null === $w && empty($a['noWhole'])) {
            $w = $n;
            $n = null;
        }

        if (null === $w && null === $n) {
            return false;
        }

        if ((null === $n) !== (null === $d) || 0 === $d) {
            return false;
        }

        if ('exact' === $a['mode']) {
            return null === $w && $n === (int) $a['n'] && $d === (int) $a['d'];
        }

        $num = null === $n ? $w : ($w ?? 0) * $d + $n;
        $den = null === $n ? 1 : $d;

        if ($num * (int) $a['d'] !== (int) $a['n'] * $den) {
            return false;
        }

        // "Schreibe als unechten Bruch": numerator and denominator only
        if ('improper' === $a['mode'] && (null !== $w || null === $n)) {
            return false;
        }

        // "Schreibe als gemischte Zahl": whole part required when the value is greater than 1
        if ('mixed' === $a['mode'] && (int) $a['n'] > (int) $a['d'] && (null === $w || 0 === $w || null === $n)) {
            return false;
        }

        // Must be fully reduced and a mixed number must have a proper fraction part
        if (null !== $n && Util::gcd($n, $d) > 1) {
            return false;
        }

        return !(null !== $w && null !== $n && $n >= $d);
    }

    /**
     * @return array{points: int, max: int, correct: list<bool>}
     */
    private function checkTable(array $a, array $raw): array
    {
        $correct = [];

        foreach ($a['cols'] as $i => $col) {
            if (!isset($col['ask'])) {
                continue;
            }

            $x = self::parseNumber($raw[$i] ?? null);
            $correct[$i] = null !== $x && abs($x - (float) $col['v']) <= 1e-6;
        }

        return ['points' => \count(array_filter($correct)), 'max' => \count($correct), 'correct' => $correct];
    }

    /**
     * Grid points: one point per image point at the right place with the right letter.
     *
     * @return array{points: int, max: int, correct: list<bool>}
     */
    private function checkPoints(array $a, array $raw): array
    {
        $correct = [];

        // Grid tasks: exactly on the grid point; constructions: within the tolerance (3 mm)
        $tol = (float) ($a['tol'] ?? 0) + 1e-9;

        foreach ($a['img'] as $i => $q) {
            $p = $raw[$i] ?? null;
            $correct[$i] = \is_array($p) && isset($p[0], $p[1]) && is_numeric($p[0]) && is_numeric($p[1])
                && hypot((float) $p[0] - $q[0], (float) $p[1] - $q[1]) <= $tol;
        }

        return $this->result($correct);
    }

    /**
     * @param list<bool> $correct
     *
     * @return array{points: int, max: int, correct: list<bool>}
     */
    /**
     * One field per unknown angle (or the number of corners).
     */
    private function checkAngles(array $a, array $raw): array
    {
        $correct = [];

        foreach ($a['v'] as $i => $v) {
            $x = self::parseNumber(\is_string($raw[$i] ?? null) ? str_replace('°', '', $raw[$i]) : ($raw[$i] ?? null));
            $correct[$i] = null !== $x && abs($x - (float) $v) < 0.01;
        }

        return $this->result($correct);
    }

    /**
     * Parses "5", "-2", "7/3", "-3/4", "x = 3/4", "0,75" into [numerator, denominator]. Null for anything else.
     *
     * @return array{0: int, 1: int}|null
     */
    public static function parseRational(mixed $raw): array|null
    {
        if (!\is_string($raw) && !\is_int($raw)) {
            return null;
        }

        $s = preg_replace('/[\s\x{00A0}]/u', '', (string) $raw);
        $s = str_replace(['−', '–', ','], ['-', '-', '.'], $s);
        $s = preg_replace('/^x=/i', '', $s);

        if (preg_match('/^([+-]?\d{1,9})(?:\/([+-]?\d{1,9}))?$/', $s, $m)) {
            $n = (int) $m[1];
            $d = isset($m[2]) ? (int) $m[2] : 1;

            return 0 === $d ? null : [$n, $d];
        }

        // Decimal number, exact: 0.75 = 75/100
        if (preg_match('/^([+-]?)(\d{0,6})\.(\d{1,6})$/', $s, $m) && '' !== $m[2].$m[3]) {
            $d = 10 ** \strlen($m[3]);

            return [('-' === $m[1] ? -1 : 1) * ((int) $m[2] * $d + (int) $m[3]), $d];
        }

        return null;
    }

    /**
     * Equation: the value counts, an unreduced fraction is right as well.
     */
    private function checkRational(array $a, mixed $raw, int $weight): array
    {
        $p = self::parseRational($raw);
        $ok = null !== $p && $p[0] * (int) $a['d'] === (int) $a['n'] * $p[1];

        return ['points' => $ok ? $weight : 0, 'max' => $weight, 'correct' => [$ok]];
    }

    private function result(array $correct): array
    {
        return ['points' => \count(array_filter($correct)), 'max' => \count($correct), 'correct' => $correct];
    }
}
