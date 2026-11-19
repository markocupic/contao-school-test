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
            'triangle' => $this->checkTriangle($a, \is_array($answer) ? $answer : []),
            'symmetry' => $this->checkSymmetry($a, \is_array($answer) ? $answer : []),
            'terms' => $this->checkTerms($a, \is_array($answer) ? $answer : []),
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

        // GeoGebra: one more point if the image points are joined to the image figure, without extra segments
        if (!empty($a['draw']['ggb'])) {
            $n = \count($a['img']);
            $expected = [];

            for ($i = 0; $i < $n; ++$i) {
                $j = ($i + 1) % $n;
                $expected[min($i, $j).'-'.max($i, $j)] = true;
            }

            $given = [];

            foreach (\is_array($raw[$n] ?? null) ? $raw[$n] : [] as $e) {
                if (\is_array($e) && isset($e[0], $e[1]) && is_numeric($e[0]) && is_numeric($e[1])) {
                    $given[(int) min($e[0], $e[1]).'-'.(int) max($e[0], $e[1])] = true;
                }
            }

            ksort($expected);
            ksort($given);
            $correct[$n] = $given === $expected;
        }

        return $this->result($correct);
    }

    /**
     * Terms and numbers: equivalent to the solution and in the required form; choices.
     * Notes: field => form, if the answer is equivalent but not in the required form.
     */
    private function checkTerms(array $a, array $raw): array
    {
        $parser = new Term\TermParser();
        $correct = [];
        $notes = [];

        foreach ($a['v'] as $i => $v) {
            $given = $raw[$i] ?? null;

            if (isset($v['choice'])) {
                $correct[$i] = \is_scalar($given) && '' !== (string) $given && (int) $given === $v['choice'];
                continue;
            }

            $r = $parser->check(\is_scalar($given) ? (string) $given : null, $v['ans'], $v['form'], $v['factors']);
            $correct[$i] = $r['ok'];

            if ($r['equivalent'] && !$r['ok']) {
                $notes[$i] = 'sum' === $v['form'] ? 'sum' : 'product';
            }
        }

        return [...$this->result($correct), 'notes' => $notes];
    }

    /**
     * Triangle construction: one entry per given measure (does the triangle ABC have it within
     * the tolerance?), the last entry for the three sides drawn as segments.
     * Position, orientation and direction of the labels do not matter.
     */
    private function checkTriangle(array $a, array $raw): array
    {
        $correct = [];
        $measured = self::triangleMeasures($raw);
        [$tolLen, $tolAng] = $a['tol'];

        foreach ($a['given'] as $g) {
            $tol = \in_array($g['k'], Triangle\TriangleGeometry::ANGLES, true) ? $tolAng : $tolLen;
            $correct[] = null !== $measured && abs($measured[$g['k']] - (float) $g['v']) <= $tol + 1e-9;
        }

        $sides = [];

        foreach (\is_array($raw[3] ?? null) ? $raw[3] : [] as $e) {
            if (\is_array($e) && isset($e[0], $e[1]) && is_numeric($e[0]) && is_numeric($e[1])) {
                $sides[(int) min($e[0], $e[1]).'-'.(int) max($e[0], $e[1])] = true;
            }
        }

        $correct[] = null !== $measured && isset($sides['0-1'], $sides['1-2'], $sides['0-2']);

        return $this->result($correct);
    }

    /**
     * Measures of the student's triangle, null without three points or for a degenerate triangle.
     *
     * @return array<string, float>|null
     */
    public static function triangleMeasures(array $raw): array|null
    {
        $pts = [];

        for ($i = 0; $i < 3; ++$i) {
            $p = $raw[$i] ?? null;

            if (!\is_array($p) || !isset($p[0], $p[1]) || !is_numeric($p[0]) || !is_numeric($p[1])) {
                return null;
            }

            $pts[] = [(float) $p[0], (float) $p[1]];
        }

        $m = Triangle\TriangleGeometry::measures(...$pts);

        return $m['hc'] < 0.05 ? null : $m;
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

    /**
     * Symmetry card: [axes right, centre right]. Every axis must be drawn once, no other line
     * counts as an axis; the centre must be set exactly when the picture is point symmetric.
     * Nothing drawn and "keine Symmetrie" not chosen: no answer.
     */
    private function checkSymmetry(array $a, array $answer): array
    {
        $z = $answer['z'] ?? null;
        $axes = $answer['axes'] ?? [];

        if (null === $z && !$axes && empty($answer['none'])) {
            return ['points' => 0, 'max' => 2, 'correct' => [false, false]];
        }

        $tol = (float) ($a['tol'] ?? 0.3);
        $maxAngle = (float) ($a['angle'] ?? 3);
        $left = $axes;
        $axesOk = \count($axes) === \count($a['axes']);

        foreach ($a['axes'] as [$p, $d]) {
            $hit = null;

            foreach ($left as $k => $l) {
                $cos = abs($d[0] * $l[2] + $d[1] * $l[3]);
                $angle = rad2deg(acos(min(1, $cos)));
                // Distance of the expected axis point from the drawn line
                $dist = abs(($p[0] - $l[0]) * $l[3] - ($p[1] - $l[1]) * $l[2]);

                if ($angle <= $maxAngle && $dist <= $tol) {
                    $hit = $k;

                    break;
                }
            }

            if (null === $hit) {
                $axesOk = false;

                break;
            }

            unset($left[$hit]);
        }

        $c = $a['center'];
        $centerOk = null === $c ? null === $z : (null !== $z && hypot($z[0] - $c[0], $z[1] - $c[1]) <= $tol);
        $correct = [$axesOk, $centerOk];

        return ['points' => \count(array_filter($correct)), 'max' => 2, 'correct' => $correct];
    }
}
