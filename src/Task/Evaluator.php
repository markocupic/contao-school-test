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
 * Grades all tasks of a participation.
 */
final class Evaluator
{
    public function __construct(
        private readonly AnswerChecker $checker,
    ) {
    }

    /**
     * @return array{points: int, max: int, sections: list<array{points: int, max: int, tasks: list<array{points: int, max: int, correct: array}>}>}
     */
    public function evaluate(array $sections, array $answers): array
    {
        $total = 0;
        $max = 0;
        $result = [];

        foreach ($sections as $si => $section) {
            $sp = 0;
            $sm = 0;
            $tasks = [];

            foreach ($section['tasks'] as $ti => $task) {
                $r = $this->checker->check($task, $answers[TaskPresenter::key($si, $ti)] ?? null);
                $sp += $r['points'];
                $sm += $r['max'];
                $tasks[] = $r;
            }

            $total += $sp;
            $max += $sm;
            $result[] = ['points' => $sp, 'max' => $sm, 'tasks' => $tasks];
        }

        return ['points' => $total, 'max' => $max, 'sections' => $result];
    }

    /**
     * List of [x, y] grid points (or null) for each image point.
     */
    public static function gridPoints(mixed $v, int $n, array $grid, bool $free = false): array
    {
        $out = [];

        for ($i = 0; $i < $n; ++$i) {
            $p = \is_array($v) ? ($v[$i] ?? null) : null;
            $ok = \is_array($p) && isset($p[0], $p[1]) && is_numeric($p[0]) && is_numeric($p[1])
                && (float) $p[0] >= 0 && (float) $p[0] <= $grid[0] && (float) $p[1] >= 0 && (float) $p[1] <= $grid[1];

            // Constructions: any position, rounded to 1/1000 square; grid tasks: grid points
            $out[$i] = $ok ? ($free ? [round((float) $p[0], 3), round((float) $p[1], 3)] : [(int) $p[0], (int) $p[1]]) : null;
        }

        return $out;
    }

    /**
     * Triangle construction: A, B, C (cm, anywhere near the sheet) and the segments between them.
     */
    public static function triangleAnswer(mixed $v): array
    {
        $points = [];

        for ($i = 0; $i < 3; ++$i) {
            $p = \is_array($v) ? ($v[$i] ?? null) : null;
            $ok = \is_array($p) && isset($p[0], $p[1]) && is_numeric($p[0]) && is_numeric($p[1]) && abs((float) $p[0]) <= 100 && abs((float) $p[1]) <= 100;
            $points[$i] = $ok ? [round((float) $p[0], 4), round((float) $p[1], 4)] : null;
        }

        $points[] = self::edges($v, $points);

        return $points;
    }

    /**
     * Image points; with GeoGebra followed by the segments between them (one more list element).
     */
    public static function pointsAnswer(array $a, mixed $v): array
    {
        $points = self::gridPoints($v, \count($a['img']), $a['draw']['grid'] ?? [20, 14], !empty($a['draw']['free']));

        if (!empty($a['draw']['ggb'])) {
            $points[] = self::edges($v, $points);
        }

        return $points;
    }

    /**
     * Segments between two placed image points as sorted index pairs [i, j] with i < j, each once.
     *
     * @param array<int, array{0: float, 1: float}|null> $points
     *
     * @return list<array{0: int, 1: int}>
     */
    public static function edges(mixed $v, array $points): array
    {
        $n = \count($points);
        $raw = \is_array($v) ? ($v[$n] ?? null) : null;
        $out = [];

        if (!\is_array($raw)) {
            return [];
        }

        foreach (\array_slice($raw, 0, 64) as $e) {
            if (!\is_array($e) || !isset($e[0], $e[1]) || !is_numeric($e[0]) || !is_numeric($e[1])) {
                continue;
            }

            $i = (int) min($e[0], $e[1]);
            $j = (int) max($e[0], $e[1]);

            if ($i >= 0 && $j < $n && $i !== $j && null !== $points[$i] && null !== $points[$j]) {
                $out[$i.'-'.$j] = [$i, $j];
            }
        }

        ksort($out);

        return array_values($out);
    }

    /**
     * Keep only answers that belong to a task and have a sane shape and size.
     */
    public function sanitizeAnswers(array $sections, mixed $answers): array
    {
        if (!\is_array($answers)) {
            return [];
        }

        $clean = [];
        $str = static fn (mixed $v): string => mb_substr(\is_scalar($v) ? trim((string) $v) : '', 0, 20);

        foreach ($sections as $si => $section) {
            foreach ($section['tasks'] as $ti => $task) {
                $key = TaskPresenter::key($si, $ti);

                if (!\array_key_exists($key, $answers)) {
                    continue;
                }

                $v = $answers[$key];

                $clean[$key] = match ($task['a']['type']) {
                    'num' => $str($v),
                    'frac' => \is_array($v) ? ['w' => $str($v['w'] ?? ''), 'n' => $str($v['n'] ?? ''), 'd' => $str($v['d'] ?? '')] : [],
                    'table' => \is_array($v) ? array_map($str, array_intersect_key($v, array_filter($task['a']['cols'], static fn ($c) => isset($c['ask'])))) : [],
                    'rat' => mb_substr($str($v), 0, 20),
                    'angles' => \is_array($v) ? array_map($str, array_intersect_key($v, $task['a']['v'])) : [],
                    'points' => self::pointsAnswer($task['a'], $v),
                    'triangle' => self::triangleAnswer($v),
                    'terms' => \is_array($v) ? array_map(static fn (mixed $x): string => mb_substr(\is_scalar($x) ? trim((string) $x) : '', 0, 120), array_intersect_key($v, $task['a']['v'])) : [],
                    default => '',
                };
            }
        }

        return $clean;
    }
}
