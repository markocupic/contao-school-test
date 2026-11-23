<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Tests\Task;

use Markocupic\ContaoSchoolTest\Task\Geometry\BoardSvg;
use Markocupic\ContaoSchoolTest\Task\SolutionRenderer;
use Markocupic\ContaoSchoolTest\Task\WorksheetRenderer;
use Markocupic\ContaoSchoolTest\TestType\AngleTestType;
use Markocupic\ContaoSchoolTest\TestType\BinomialTestType;
use Markocupic\ContaoSchoolTest\TestType\EquationTestType;
use Markocupic\ContaoSchoolTest\TestType\FigureAreaTestType;
use Markocupic\ContaoSchoolTest\TestType\GeometryTransformTestType;
use Markocupic\ContaoSchoolTest\TestType\MentalArithmeticTestType;
use Markocupic\ContaoSchoolTest\TestType\TaskCounts;
use Markocupic\ContaoSchoolTest\TestType\TriangleTestType;
use PHPUnit\Framework\TestCase;

class WorksheetRendererTest extends TestCase
{
    public function testBlankTasksShowNoSolution(): void
    {
        $renderer = new WorksheetRenderer(new SolutionRenderer(), new BoardSvg());
        mt_srand(5);
        $types = [new AngleTestType(), new BinomialTestType(), new EquationTestType(), new FigureAreaTestType(), new GeometryTransformTestType(), new MentalArithmeticTestType(), new TriangleTestType()];

        foreach ($types as $type) {
            $counts = array_fill_keys(array_keys($type->getTaskKinds()), 2);
            $sections = $type->generate(['taskCounts' => TaskCounts::toRows($counts, 'hard')]);

            foreach ($sections as $section) {
                $instruction = $renderer->instruction($section);
                $this->assertDoesNotMatchRegularExpression('/GeoGebra|Klick|klick|Taste|\^/', $instruction, $type->getAlias().': '.$instruction);

                foreach ($section['tasks'] as $task) {
                    $blank = $renderer->blank($task);
                    $label = $type->getAlias().' '.$task['a']['type'].': '.strip_tags((string) $task['q']);
                    $this->assertNotSame('', $blank, $label);
                    $this->assertNotSame('', $renderer->solution($task), $label);

                    // Solution colours of the boards and symmetry cards
                    $this->assertStringNotContainsString('#1a7f43', $blank, $label);
                    $this->assertStringNotContainsString('#16a34a', $blank, $label);

                    // Tables: the asked cells are empty
                    if ('table' === $task['a']['type']) {
                        foreach ($task['a']['cols'] as $c) {
                            if (isset($c['ask'])) {
                                $this->assertStringNotContainsString('>'.$c['show'].'<', $blank, $label);
                            }
                        }
                    }

                    // Angles and areas: the sketch without solution, never the solution sketch
                    if ('angles' === $task['a']['type'] && !empty($task['a']['solSvg']) && $task['a']['solSvg'] !== ($task['a']['svg'] ?? '')) {
                        $this->assertStringNotContainsString($task['a']['solSvg'], $blank, $label);
                    }
                }
            }
        }
    }

    public function testBoardsAndConstructionsInRealSize(): void
    {
        $renderer = new WorksheetRenderer(new SolutionRenderer(), new BoardSvg());
        mt_srand(2);
        $board = (new GeometryTransformTestType())->generate(['taskCounts' => TaskCounts::toRows(['achse' => 1])])[0]['tasks'][0];
        [$w] = $board['a']['draw']['grid'];
        $this->assertMatchesRegularExpression('/style="width:'.preg_quote((string) round(($w * 16 + 24) / 16 * WorksheetRenderer::GRID_MM, 1), '/').'mm/', $renderer->blank($board));

        $triangle = (new TriangleTestType())->generate(['taskCounts' => TaskCounts::toRows(['sss' => 1])])[0]['tasks'][0];
        $this->assertStringContainsString('width:160mm;height:110mm', $renderer->blank($triangle));
    }
}
