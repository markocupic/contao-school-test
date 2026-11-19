<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Tests\Task;

use Markocupic\ContaoSchoolTest\Task\AnswerChecker;
use Markocupic\ContaoSchoolTest\Task\Evaluator;
use Markocupic\ContaoSchoolTest\Task\Geometry\SymmetryGenerator;
use PHPUnit\Framework\TestCase;

class SymmetryTest extends TestCase
{
    public function testCardsHaveTheSymmetryOfTheSolution(): void
    {
        $generator = new SymmetryGenerator();
        mt_srand(41);
        $kinds = [];

        foreach (['easy', 'medium', 'hard'] as $level) {
            for ($i = 0; $i < 200; ++$i) {
                $task = $generator->build($level);
                $a = $task['a'];
                $shapes = $a['draw']['shapes'];
                $this->assertSame('symmetry', $a['type']);
                $this->assertSame(2, $task['points']);

                foreach ($shapes as $s) {
                    foreach ($s['p'] as $q) {
                        $this->assertTrue($q[0] > 0 && $q[0] < 10 && $q[1] > 0 && $q[1] < 10);
                    }
                }

                // Reflected in every axis and turned by 180° about the centre, the picture stays the same
                foreach ($a['axes'] as [$p, $d]) {
                    $mirror = static function (array $q) use ($p, $d): array {
                        $t = ($q[0] - $p[0]) * $d[0] + ($q[1] - $p[1]) * $d[1];
                        $f = [$p[0] + $t * $d[0], $p[1] + $t * $d[1]];

                        return [2 * $f[0] - $q[0], 2 * $f[1] - $q[1]];
                    };
                    $this->assertTrue(self::same($shapes, $mirror), "axis $level");
                }

                if (null !== $a['center']) {
                    $c = $a['center'];
                    $this->assertTrue(self::same($shapes, static fn (array $q): array => [2 * $c[0] - $q[0], 2 * $c[1] - $q[1]]), "centre $level");
                }

                $kinds[($a['axes'] ? 'A' : '').(null !== $a['center'] ? 'P' : '')] = true;

                // The right answer gives both points, nothing gives none
                $checker = new AnswerChecker();
                $answer = Evaluator::symmetryAnswer([$a['center'], [], array_map(static fn (array $l): array => [$l[0][0] + 3 * $l[1][0], $l[0][1] + 3 * $l[1][1], -$l[1][0], -$l[1][1]], $a['axes']), !$a['axes'] && null === $a['center']]);
                $this->assertSame(2, $checker->check($task, $answer)['points']);
                $this->assertSame(0, $checker->check($task, Evaluator::symmetryAnswer([]))['points']);
                // Stored answers are read again the same way (evaluation sheet, resume)
                $this->assertSame($answer, Evaluator::symmetryAnswer($answer));

                // A missing or extra axis, a wrong centre
                $wrong = $answer;
                $wrong['axes'][] = [5, 5, 0.6, 0.8];
                $this->assertFalse($checker->check($task, $wrong)['correct'][0]);

                if (null !== $a['center']) {
                    $wrong = $answer;
                    $wrong['z'] = [$a['center'][0] + 1, $a['center'][1]];
                    $this->assertFalse($checker->check($task, $wrong)['correct'][1]);
                } else {
                    $this->assertFalse($checker->check($task, Evaluator::symmetryAnswer([[5, 5]]))['correct'][1]);
                }
            }
        }

        $this->assertCount(4, $kinds, 'all four kinds of cards');
    }

    private static function same(array $shapes, \Closure $f): bool
    {
        foreach ($shapes as $s) {
            $img = array_map($f, $s['p']);
            $hit = false;

            foreach ($shapes as $t) {
                if ($t['f'] !== $s['f'] || \count($t['p']) !== \count($img)) {
                    continue;
                }

                $all = true;

                foreach ($img as $q) {
                    $all = $all && (bool) array_filter($t['p'], static fn (array $r): bool => hypot($r[0] - $q[0], $r[1] - $q[1]) < 0.02);
                }

                $hit = $hit || $all;
            }

            if (!$hit) {
                return false;
            }
        }

        return true;
    }
}
