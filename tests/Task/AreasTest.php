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
use Markocupic\ContaoSchoolTest\Task\Area\AbstractArea;
use Markocupic\ContaoSchoolTest\Task\Area\ConversionArea;
use Markocupic\ContaoSchoolTest\Task\Area\EstimationArea;
use Markocupic\ContaoSchoolTest\Task\Area\FractionsArea;
use Markocupic\ContaoSchoolTest\Task\Area\MentalArithmeticArea;
use Markocupic\ContaoSchoolTest\Task\Area\PercentTableArea;
use Markocupic\ContaoSchoolTest\Task\Util;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Generates many tasks per area and level and checks them against invariants:
 * valid solutions, no broken text and the checker accepts the own solution.
 */
class AreasTest extends TestCase
{
    public static function areaLevels(): iterable
    {
        foreach ([new MentalArithmeticArea(), new FractionsArea(), new ConversionArea(), new PercentTableArea(), new EstimationArea()] as $area) {
            for ($level = 1; $level <= AbstractArea::LEVELS; ++$level) {
                yield $area->getAlias().'-'.$level => [$area, $level];
            }
        }
    }

    #[DataProvider('areaLevels')]
    public function testGeneratedTasksAreValid(AbstractArea $area, int $level): void
    {
        mt_srand(1000 + $level);
        $checker = new AnswerChecker();
        $runs = 'pt' === $area->getAlias() ? 150 : 600;

        for ($i = 0; $i < $runs; ++$i) {
            $task = $area->generate($level);
            $a = $task['a'];
            $text = $task['q'].$task['way'];

            $this->assertDoesNotMatchRegularExpression('/NAN|INF|undefined|\{\$/', $text, $task['q']);
            $this->assertNotSame('', trim(strip_tags($task['q'])));

            switch ($a['type']) {
                case 'num':
                    $this->assertIsFloat($a['v']);
                    $this->assertGreaterThanOrEqual(0, $a['v'], strip_tags($task['q']));

                    if ('kr' === $area->getAlias()) {
                        $this->assertSame((float) (int) $a['v'], $a['v'], strip_tags($task['q']));
                        $this->assertLessThanOrEqual(1000, $a['v'], strip_tags($task['q']));
                    }

                    if (!empty($a['est'])) {
                        // The suggested estimate must be accepted by the checker
                        $this->assertTrue($checker->checkNumber($a, (string) $a['estimate']), strip_tags($task['q']).' est '.$a['estimate'].' exact '.$a['v']);
                    }

                    $this->assertTrue($checker->checkNumber($a, Util::fmt($a['v'])), strip_tags($task['q']));
                    break;

                case 'frac':
                    $this->assertGreaterThan(0, $a['n']);
                    $this->assertGreaterThan(0, $a['d']);

                    if ('reduced' === $a['mode']) {
                        $this->assertSame(1, Util::gcd($a['n'], $a['d']));
                    }

                    $own = 'mixed' === $a['mode'] && $a['n'] > $a['d']
                        ? ['w' => (string) intdiv($a['n'], $a['d']), 'n' => (string) ($a['n'] % $a['d']), 'd' => (string) $a['d']]
                        : ['n' => (string) $a['n'], 'd' => (string) $a['d']];
                    $this->assertTrue($checker->checkFraction($a, $own), strip_tags($task['q']));
                    break;

                case 'table':
                    $asks = array_filter($a['cols'], static fn ($c) => isset($c['ask']));
                    $this->assertGreaterThanOrEqual(6, \count($asks));
                    $this->assertSame(\count($asks), $task['points']);
                    $answers = [];

                    foreach ($a['cols'] as $j => $col) {
                        if (isset($col['ask'])) {
                            $this->assertGreaterThan(0, $col['v']);
                            $this->assertTrue(abs(round($col['v'] * 100) - $col['v'] * 100) < 1e-6, 'max. 2 decimals');
                            $answers[$j] = Util::fmt($col['v']);
                        }
                    }

                    $result = $checker->check($task, $answers);
                    $this->assertSame($result['max'], $result['points']);
                    break;

                default:
                    $this->fail('Unknown type '.$a['type']);
            }
        }
    }

    public function testGenerateManyReturnsDistinctTasks(): void
    {
        $tasks = (new MentalArithmeticArea())->generateMany(3, 10);
        $this->assertCount(10, $tasks);
        $this->assertCount(10, array_unique(array_column($tasks, 'q')));
    }
}
