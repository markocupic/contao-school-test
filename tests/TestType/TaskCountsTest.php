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
    public function testRowsKeepOrderSumDuplicatesAndClamp(): void
    {
        $type = new MentalArithmeticTestType();
        $rows = [
            ['kind' => 'sz', 'count' => '3'],
            ['kind' => 'pt', 'count' => '9'],
            ['kind' => 'nope', 'count' => '4'],
            ['kind' => 'sz', 'count' => '2'],
            ['kind' => 'kr', 'count' => ''],
            ['kind' => 'br', 'count' => '50'],
        ];

        $this->assertSame(['sz' => 5, 'pt' => 3, 'br' => 20], TaskCounts::rows(['taskCounts' => serialize($rows)], $type));
        $this->assertSame($type->getDefaultTaskCounts(), TaskCounts::rows(['taskCounts' => ''], $type));
        $this->assertSame($type->getDefaultTaskCounts(), TaskCounts::rows([], $type));
        $this->assertSame($type->getDefaultTaskCounts(), TaskCounts::rows(['taskCounts' => 'a:1:{i:0;s:3:"bad"'], $type));
    }

    public function testNumericKindsOfEquations(): void
    {
        $type = new EquationTestType();

        $this->assertSame([9 => 3, 2 => 1], TaskCounts::rows(['taskCounts' => TaskCounts::toRows(['9' => 3, '2' => 1])], $type));
        $this->assertSame([['kind' => '9', 'count' => '3']], TaskCounts::toRows([9 => 3]));
        $this->assertSame('Stufe 9: Binome (x² fällt weg)', $type->getTaskKinds()[9]);

        $sections = $type->generate(['taskCounts' => TaskCounts::toRows([9 => 3, 2 => 1]), 'difficulty' => 'easy']);
        $this->assertSame(['level9', 'level2'], array_column($sections, 'area'));
        $this->assertSame([3, 1], array_map(static fn (array $s): int => \count($s['tasks']), $sections));
    }

    public function testMentalArithmeticCountsPerArea(): void
    {
        $sections = (new MentalArithmeticTestType())->generate(['difficulty' => 'easy', 'taskCounts' => TaskCounts::toRows(['br' => 3, 'pt' => 2, 'kr' => 11])]);

        $this->assertSame(['br', 'pt', 'kr'], array_column($sections, 'area'));
        $this->assertSame([3, 2, 11], array_map(static fn (array $s): int => \count($s['tasks']), $sections));
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
                $sections = $type->generate(['difficulty' => 'hard', 'taskCounts' => TaskCounts::toRows([$kind => $max])]);
                $this->assertCount(1, $sections, $type->getAlias().': '.$kind);
                $this->assertCount($max, $sections[0]['tasks'], $type->getAlias().': '.$kind);
            }
        }
    }
}
