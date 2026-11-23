<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Tests\TestType;

use Markocupic\ContaoSchoolTest\TestType\AngleTestType;
use Markocupic\ContaoSchoolTest\TestType\BinomialTestType;
use Markocupic\ContaoSchoolTest\TestType\EquationTestType;
use Markocupic\ContaoSchoolTest\TestType\FigureAreaTestType;
use Markocupic\ContaoSchoolTest\TestType\GeometryTransformTestType;
use Markocupic\ContaoSchoolTest\TestType\MentalArithmeticTestType;
use Markocupic\ContaoSchoolTest\TestType\TaskCounts;
use Markocupic\ContaoSchoolTest\TestType\TaskKindsInterface;
use Markocupic\ContaoSchoolTest\TestType\TriangleTestType;
use PHPUnit\Framework\TestCase;

class TaskCountsTest extends TestCase
{
    public function testRowsKeepOrderMergeSameDifficultyAndClamp(): void
    {
        $type = new MentalArithmeticTestType();
        $rows = [
            ['kind' => 'sz', 'count' => '3', 'difficulty' => 'hard'],
            ['kind' => 'pt', 'count' => '9', 'difficulty' => 'easy'],
            ['kind' => 'nope', 'count' => '4'],
            ['kind' => 'sz', 'count' => '2', 'difficulty' => 'easy'],
            ['kind' => 'kr', 'count' => ''],
            ['kind' => 'sz', 'count' => '1', 'difficulty' => 'hard'],
            ['kind' => 'br', 'count' => '50', 'difficulty' => 'extreme'],
        ];

        $this->assertSame([
            ['kind' => 'sz', 'count' => 4, 'difficulty' => 'hard'],
            ['kind' => 'pt', 'count' => 3, 'difficulty' => 'easy'],
            ['kind' => 'sz', 'count' => 2, 'difficulty' => 'easy'],
            ['kind' => 'br', 'count' => 20, 'difficulty' => 'medium'],
        ], TaskCounts::rows(['taskCounts' => serialize($rows)], $type));

        $defaults = [];

        foreach ($type->getDefaultTaskCounts() as $kind => $count) {
            $defaults[] = ['kind' => $kind, 'count' => $count, 'difficulty' => 'medium'];
        }

        $this->assertSame($defaults, TaskCounts::rows(['taskCounts' => ''], $type));
        $this->assertSame($defaults, TaskCounts::rows([], $type));
        $this->assertSame($defaults, TaskCounts::rows(['taskCounts' => 'a:1:{i:0;s:3:"bad"'], $type));
    }

    public function testAKindSeveralTimesWithDifferentDifficulties(): void
    {
        $rows = [
            ['kind' => 'achse_ggb', 'count' => '2', 'difficulty' => 'medium'],
            ['kind' => 'achse_ggb', 'count' => '1', 'difficulty' => 'hard'],
        ];
        $sections = (new GeometryTransformTestType())->generate(['taskCounts' => serialize($rows)]);

        $this->assertSame(['achse_ggb', 'achse_ggb'], array_column($sections, 'area'));
        $this->assertSame([2, 1], array_map(static fn (array $s): int => \count($s['tasks']), $sections));
        $this->assertStringContainsString('3 mm', $sections[0]['instruction']);
        $this->assertStringContainsString('1.5 mm', $sections[1]['instruction']);
    }

    public function testZeroLeavesTheKindOut(): void
    {
        $type = new FigureAreaTestType();
        $rows = [
            ['kind' => 'dreieck', 'count' => '0', 'difficulty' => 'easy'],
            ['kind' => 'kreis', 'count' => '3', 'difficulty' => 'hard'],
            ['kind' => 'trapez', 'count' => '0', 'difficulty' => 'medium'],
        ];

        $this->assertSame([['kind' => 'kreis', 'count' => 3, 'difficulty' => 'hard']], TaskCounts::rows(['taskCounts' => serialize($rows)], $type));
        $this->assertSame(['kreis'], array_column($type->generate(['taskCounts' => serialize($rows)]), 'area'));
    }

    public function testNumericKindsOfEquations(): void
    {
        $type = new EquationTestType();

        $this->assertSame([['kind' => 9, 'count' => 3, 'difficulty' => 'easy'], ['kind' => 2, 'count' => 1, 'difficulty' => 'easy']], TaskCounts::rows(['taskCounts' => TaskCounts::toRows(['9' => 3, '2' => 1], 'easy')], $type));
        $this->assertSame([['kind' => '9', 'count' => '3', 'difficulty' => 'medium']], TaskCounts::toRows([9 => 3]));
        $this->assertSame('Stufe 9: Binome (x² fällt weg)', $type->getTaskKinds()[9]);

        $sections = $type->generate(['taskCounts' => TaskCounts::toRows([9 => 3, 2 => 1], 'easy')]);
        $this->assertSame(['level9', 'level2'], array_column($sections, 'area'));
        $this->assertSame([3, 1], array_map(static fn (array $s): int => \count($s['tasks']), $sections));
    }

    public function testMentalArithmeticCountsPerArea(): void
    {
        $sections = (new MentalArithmeticTestType())->generate(['taskCounts' => TaskCounts::toRows(['br' => 3, 'pt' => 2, 'kr' => 11], 'easy')]);

        $this->assertSame(['br', 'pt', 'kr'], array_column($sections, 'area'));
        $this->assertSame([3, 2, 11], array_map(static fn (array $s): int => \count($s['tasks']), $sections));
    }

    public function testDifficultyPerRow(): void
    {
        $rows = [
            ['kind' => 'achse_ggb', 'count' => '1', 'difficulty' => 'easy'],
            ['kind' => 'punkt_ggb', 'count' => '1', 'difficulty' => 'hard'],
        ];
        $sections = (new GeometryTransformTestType())->generate(['taskCounts' => serialize($rows)]);
        $this->assertStringContainsString('4 mm', $sections[0]['instruction']);
        $this->assertStringContainsString('1.5 mm', $sections[1]['instruction']);

        $sections = (new TriangleTestType())->generate(['taskCounts' => serialize([['kind' => 'sss', 'count' => '1', 'difficulty' => 'easy'], ['kind' => 'sws', 'count' => '1', 'difficulty' => 'hard']])]);
        $this->assertNotSame($sections[0]['instruction'], $sections[1]['instruction']);

        // Mental arithmetic: easy = levels 1 and 2, hard = levels 5 and 6
        $sections = (new MentalArithmeticTestType())->generate(['taskCounts' => serialize([['kind' => 'kr', 'count' => '2', 'difficulty' => 'easy'], ['kind' => 'br', 'count' => '2', 'difficulty' => 'hard']])]);
        $this->assertSame(['kr', 'br'], array_column($sections, 'area'));

        $this->assertFalse((new AngleTestType())->hasTaskDifficulty());
        $this->assertTrue((new FigureAreaTestType())->hasTaskDifficulty());
    }

    public function testEveryTypeHasConsistentKinds(): void
    {
        foreach ([new AngleTestType(), new BinomialTestType(), new EquationTestType(), new FigureAreaTestType(), new GeometryTransformTestType(), new MentalArithmeticTestType(), new TriangleTestType()] as $type) {
            $this->assertTrue($type instanceof TaskKindsInterface);
            $this->assertStringContainsString('taskCounts', $type->getPaletteFields());
            $kinds = $type->getTaskKinds();

            foreach ($type->getDefaultTaskCounts() as $kind => $count) {
                $this->assertTrue(isset($kinds[$kind]), $type->getAlias().': '.$kind);
                $this->assertLessThanOrEqual($type->getMaxTaskCount($kind), $count);
            }

            // Each kind on its own, as many tasks as allowed (up to 10)
            foreach (array_keys($kinds) as $kind) {
                $max = min(10, $type->getMaxTaskCount($kind));
                $sections = $type->generate(['taskCounts' => TaskCounts::toRows([$kind => $max], 'hard')]);
                $this->assertCount(1, $sections, $type->getAlias().': '.$kind);
                $this->assertCount($max, $sections[0]['tasks'], $type->getAlias().': '.$kind);
            }
        }
    }
}
