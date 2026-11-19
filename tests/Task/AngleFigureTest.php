<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Tests\Task;

use Markocupic\ContaoSchoolTest\Task\Angle\AngleGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Parallels with two intersecting lines and figures with diagonals and heights: every given
 * and every asked angle must be the angle that is really drawn in the sketch.
 */
class AngleFigureTest extends TestCase
{
    public function testValuesMatchTheDrawing(): void
    {
        $generator = new AngleGenerator();
        mt_srand(31);

        foreach (['genTransversal', 'fTrapez', 'fPentagon', 'fParallelogram', 'fIsoHeight'] as $method) {
            $m = new \ReflectionMethod(AngleGenerator::class, $method);

            for ($i = 0; $i < 300; ++$i) {
                $t = $m->invoke($generator);
                $unknowns = 0;

                foreach (array_merge($t['S']['marks'], $t['S']['solMarks']) as $mark) {
                    if ('right' === $mark['kind']) {
                        $this->assertEqualsWithDelta(90, self::angle($mark), 0.01, $method);

                        continue;
                    }

                    if (isset($mark['ans'])) {
                        ++$unknowns;
                        $value = $mark['ans'];
                    } elseif (preg_match('/(\d+)°$/', (string) $mark['text'], $hit)) {
                        $value = (int) $hit[1];
                    } else {
                        continue;
                    }

                    $this->assertEqualsWithDelta($value, self::angle($mark), 0.01, "$method: {$mark['text']} in ".$t['prompt']);
                }

                $this->assertGreaterThanOrEqual(3, $unknowns, $method);
                $this->assertGreaterThanOrEqual(3, \count($t['steps']), $method);
            }
        }
    }

    public function testBuildGivesOneFieldPerUnknown(): void
    {
        $generator = new AngleGenerator();
        mt_srand(32);

        foreach (['transversalen', 'figuren'] as $kind) {
            for ($i = 0; $i < 100; ++$i) {
                $task = $generator->build($kind);
                $this->assertSame(\count($task['a']['v']), \count($task['a']['fields']));
                $this->assertGreaterThanOrEqual(3, $task['points']);
                $this->assertStringContainsString('<svg', $task['a']['solSvg']);
            }
        }
    }

    /**
     * The smaller angle between the two legs of a mark, in degrees.
     */
    private static function angle(array $mark): float
    {
        $u = [$mark['a'][0] - $mark['v'][0], $mark['a'][1] - $mark['v'][1]];
        $w = [$mark['b'][0] - $mark['v'][0], $mark['b'][1] - $mark['v'][1]];
        $cos = ($u[0] * $w[0] + $u[1] * $w[1]) / (hypot($u[0], $u[1]) * hypot($w[0], $w[1]));

        return rad2deg(acos(max(-1, min(1, $cos))));
    }
}
