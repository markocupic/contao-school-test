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

namespace Markocupic\ContaoSchoolTest\Tests\Task;

use Markocupic\ContaoSchoolTest\Task\AnswerChecker;
use Markocupic\ContaoSchoolTest\Task\Evaluator;
use Markocupic\ContaoSchoolTest\Task\Geometry\BoardSvg;
use Markocupic\ContaoSchoolTest\Task\Geometry\TransformGenerator;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\TestType\GeometryTransformTestType;
use PHPUnit\Framework\TestCase;

class GeometryTransformTest extends TestCase
{
    public function testImagePointsAreOnTheGrid(): void
    {
        $generator = new TransformGenerator();
        mt_srand(3);

        foreach (TransformGenerator::KINDS as $kind) {
            foreach (TransformGenerator::LEVELS as $level) {
                for ($i = 0; $i < 150; ++$i) {
                    $task = $generator->build($kind, $level);
                    $a = $task['a'];
                    $this->assertSame('points', $a['type']);
                    $this->assertCount($a['draw']['n'], $a['img']);
                    // GeoGebra: one more point for joining the image figure
                    $this->assertSame($task['points'], $a['draw']['n'] + (\in_array($kind, TransformGenerator::GGB_KINDS, true) ? 1 : 0));

                    $free = isset(TransformGenerator::FREE_KINDS[$kind]);
                    $this->assertSame($free, !empty($a['draw']['free']));
                    // GeoGebra only for its own kinds
                    $this->assertSame(\in_array($kind, TransformGenerator::GGB_KINDS, true), !empty($a['draw']['ggb']));
                    // Geodreieck: not allowed on squared paper at the hard level
                    $this->assertSame(!$free && 'hard' === $level, !empty($a['draw']['noGeo']));

                    foreach (array_merge($a['img'], $a['draw']['pts']) as $p) {
                        if (!$free) {
                            $this->assertIsInt($p[0]);
                            $this->assertIsInt($p[1]);
                        }

                        $this->assertTrue($p[0] >= 0 && $p[0] <= TransformGenerator::W && $p[1] >= 0 && $p[1] <= TransformGenerator::H, "$kind/$level outside");
                    }

                    // The image differs from the original (otherwise there is nothing to do)
                    $this->assertNotSame($a['draw']['pts'], $a['img']);
                }
            }
        }
    }

    public function testCheckerCountsEveryPoint(): void
    {
        $checker = new AnswerChecker();
        $task = (new TransformGenerator())->build('verschiebung', 'medium');
        $img = $task['a']['img'];

        $this->assertSame($task['points'], $checker->check($task, $img)['points']);

        $wrong = $img;
        $wrong[0] = [$img[0][0] + 1, $img[0][1]];
        $wrong[1] = null;
        $this->assertSame($task['points'] - 2, $checker->check($task, $wrong)['points']);
        $this->assertSame(0, $checker->check($task, null)['points']);
        $this->assertSame(0, $checker->check($task, 'abc')['points']);
    }

    public function testConstructionToleranceIsThreeMillimetres(): void
    {
        $checker = new AnswerChecker();
        mt_srand(12);

        foreach (array_keys(TransformGenerator::FREE_KINDS) as $kind) {
            foreach (['easy' => 0.8, 'medium' => 0.6, 'hard' => 0.3] as $level => $tol) {
                $task = (new TransformGenerator())->build($kind, $level);
                $img = $task['a']['img'];
                $this->assertSame($tol, $task['a']['tol']);
                $this->assertSame($tol, $task['a']['draw']['tol']);
                $this->assertArrayNotHasKey('axType', $task['a']['draw']);

                // Only the image points here (GeoGebra: the point for the segments is missing)
                $max = \count($img);
                $near = $img;
                $near[0] = [$img[0][0] + $tol - 0.01, $img[0][1]];
                $this->assertSame($max, $checker->check($task, $near)['points']);

                $far = $img;
                $far[0] = [$img[0][0], $img[0][1] - $tol - 0.01];
                $this->assertSame($max - 1, $checker->check($task, $far)['points']);
            }
        }

        $this->assertSame('1.5', TransformGenerator::toleranceMm('hard'));
        $this->assertSame('4', TransformGenerator::toleranceMm('easy'));

        $this->assertSame([[1.235, 2.5], null], Evaluator::gridPoints([[1.23456, 2.5], [21, 3]], 2, [20, 14], true));
    }

    public function testGeoGebraKindsAreNotInTheDefaultSelection(): void
    {
        $type = new GeometryTransformTestType();
        $areas = array_column($type->generate(['difficulty' => 'medium', 'transformKinds' => '', 'tasksPerKind' => 1]), 'area');
        $this->assertSame(['achse', 'punkt', 'drehung', 'verschiebung'], $areas);

        // Earlier constructions on the own board are now the GeoGebra ones
        $legacy = $type->generate(['difficulty' => 'easy', 'transformKinds' => serialize(['achse_frei']), 'tasksPerKind' => 1]);
        $this->assertSame('achse_ggb', $legacy[0]['area']);

        $sections = $type->generate(['difficulty' => 'hard', 'transformKinds' => serialize(['punkt_ggb']), 'tasksPerKind' => 1]);
        $this->assertSame('Punktspiegelung mit GeoGebra', $sections[0]['title']);
        $this->assertStringContainsString('GeoGebra', $sections[0]['instruction']);
        $this->assertStringContainsString('1.5 mm', $sections[0]['instruction']);
        $this->assertStringContainsString('«Gerade»', $sections[0]['tasks'][0]['way']);
    }

    public function testGeoGebraNeedsTheImageFigureJoined(): void
    {
        $checker = new AnswerChecker();
        $evaluator = new Evaluator($checker);
        mt_srand(21);
        $task = (new TransformGenerator())->build('achse_ggb', 'medium');
        $img = $task['a']['img'];
        $n = \count($img);
        $ring = array_map(static fn (int $i): array => [$i, ($i + 1) % $n], range(0, $n - 1));

        $this->assertSame($n + 1, $task['points']);
        $this->assertSame($n + 1, $checker->check($task, [...$img, $ring])['points']);
        // Points right, not joined
        $this->assertSame($n, $checker->check($task, $img)['points']);
        // One segment missing, or a diagonal too much
        $this->assertSame($n, $checker->check($task, [...$img, \array_slice($ring, 1)])['points']);
        $this->assertSame($n, $checker->check($task, [...$img, [...$ring, [0, 2]]])['points']);
        // Order and direction of the pairs do not matter
        $this->assertSame($n + 1, $checker->check($task, [...$img, array_reverse(array_map('array_reverse', $ring))])['points']);

        // Sanitizing: pairs between placed points only, sorted, each once
        $given = $img;
        $given[1] = null;
        $clean = $evaluator->sanitizeAnswers([['tasks' => [$task]]], ['s0t0' => [...$given, [[1, 0], [0, 2], [2, 0], [0, 0], [0, 9], 'x', [3, 2]]]]);
        $this->assertSame([[0, 2], [2, 3]], $clean['s0t0'][$n]);
        $this->assertNull($clean['s0t0'][1]);

        // Print: the segments are drawn
        $svg = (new BoardSvg())->render($task['a'], $img, array_fill(0, $n + 1, true), $ring);
        $this->assertSame($n, substr_count($svg, 'stroke-width="1.4"'));
    }

    public function testSanitizingKeepsOnlyGridPoints(): void
    {
        $this->assertSame([[1, 2], null, null], Evaluator::gridPoints([[1, 2], [25, 3], 'x'], 3, [20, 14]));
        $this->assertSame([null, null], Evaluator::gridPoints('[[1,2]]', 2, [20, 14]));
    }

    public function testNoSolutionInPublicTask(): void
    {
        $type = new GeometryTransformTestType();
        mt_srand(8);
        $sections = $type->generatePractice('gemischt', 'hard');
        $json = json_encode((new TaskPresenter())->toPublic($sections, 'de'), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('"img"', $json);
        $this->assertStringNotContainsString('wrongDirection', $json);
        $this->assertStringNotContainsString('"way"', $json);
        $this->assertStringContainsString('"draw"', $json);
        $this->assertCount(4, $sections[0]['tasks']);
    }

    public function testBoardSvgMarksPoints(): void
    {
        $task = (new TransformGenerator())->build('punkt', 'easy');
        $given = $task['a']['img'];
        $given[0] = [0, 0] === $given[0] ? [1, 1] : [0, 0];
        $correct = (new AnswerChecker())->check($task, $given)['correct'];
        $svg = (new BoardSvg())->render($task['a'], $given, $correct);

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertSame(1, substr_count($svg, 'fill="#c62828"'));
        $this->assertSame(\count($given) - 1, substr_count($svg, 'r="4" fill="#1a7f43"'));
    }
}
