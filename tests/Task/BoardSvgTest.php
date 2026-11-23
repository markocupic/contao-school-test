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
use Markocupic\ContaoSchoolTest\TestType\GeometryTransformTestType;
use Markocupic\ContaoSchoolTest\TestType\TaskCounts;
use PHPUnit\Framework\TestCase;

class BoardSvgTest extends TestCase
{
    public function testAxisNameAwayFromTheFigure(): void
    {
        // Vertical axis x = 5 from y = 0 to 14, figure at the top across the axis (the screenshot)
        $pts = [[4, 13], [4, 10], [6, 11], [7, 13]];
        $t = BoardSvg::axisLabelT([5, 0], [5, 14], $pts);
        $this->assertLessThan(0.5, $t);

        // Figure at the bottom: the name stays at the upper end as before
        $this->assertSame(0.92, BoardSvg::axisLabelT([5, 0], [5, 14], [[4, 1], [4, 3], [6, 2]]));
    }

    public function testGeneratedAxesHaveAFreeName(): void
    {
        $type = new GeometryTransformTestType();
        mt_srand(4);

        for ($i = 0; $i < 300; ++$i) {
            $task = $type->generate(['taskCounts' => TaskCounts::toRows(['achse' => 1], ['easy', 'medium', 'hard'][$i % 3])])[0]['tasks'][0];
            [$p, $q] = $task['a']['draw']['seg'];
            $pts = $task['a']['draw']['pts'];
            $t = BoardSvg::axisLabelT($p, $q, $pts);
            $c = [$p[0] + ($q[0] - $p[0]) * $t, $p[1] + ($q[1] - $p[1]) * $t];
            $min = min(array_map(static fn (array $v): float => hypot($v[0] - $c[0], $v[1] - $c[1]), $pts));
            $this->assertGreaterThanOrEqual(1.0, $min, json_encode($task['a']['draw']));
        }
    }
}
