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

use Markocupic\ContaoSchoolTest\Grading\GradeCalculator;
use Markocupic\ContaoSchoolTest\Task\AnswerChecker;
use PHPUnit\Framework\TestCase;

class AnswerCheckerTest extends TestCase
{
    public function testNumbers(): void
    {
        $c = new AnswerChecker();
        $a = ['type' => 'num', 'v' => 1250.0];

        $this->assertTrue($c->checkNumber($a, "1'250"));
        $this->assertTrue($c->checkNumber($a, ' 1250 '));
        $this->assertFalse($c->checkNumber($a, '1251'));
        $this->assertFalse($c->checkNumber($a, 'abc'));
        $this->assertFalse($c->checkNumber($a, ''));
        $this->assertTrue($c->checkNumber(['type' => 'num', 'v' => 0.75], '0,75'));
        $this->assertTrue($c->checkNumber(['type' => 'num', 'v' => 0.75], '.75'));
        $this->assertTrue($c->checkNumber(['type' => 'num', 'v' => 75.0, 'suffix' => '%'], '75 %'));
    }

    public function testEstimationTolerance(): void
    {
        $c = new AnswerChecker();
        $a = ['type' => 'num', 'v' => 181.82, 'rel' => 0.1];

        $this->assertTrue($c->checkNumber($a, '195'));
        $this->assertFalse($c->checkNumber($a, '205'));

        $rate = ['type' => 'num', 'v' => 7.49, 'rel' => 0.1, 'min' => 2];
        $this->assertTrue($c->checkNumber($rate, '9'));
        $this->assertFalse($c->checkNumber($rate, '10'));
    }

    public function testFractions(): void
    {
        $c = new AnswerChecker();
        $a = ['type' => 'frac', 'n' => 7, 'd' => 4, 'mode' => 'reduced'];

        $this->assertTrue($c->checkFraction($a, ['n' => '7', 'd' => '4']));
        $this->assertTrue($c->checkFraction($a, ['w' => '1', 'n' => '3', 'd' => '4']));
        $this->assertFalse($c->checkFraction($a, ['n' => '14', 'd' => '8']), 'not reduced');
        $this->assertFalse($c->checkFraction($a, ['w' => '1', 'n' => '6', 'd' => '8']), 'not reduced');
        $this->assertFalse($c->checkFraction($a, ['w' => '1', 'n' => '7', 'd' => '4']), 'wrong value');
        $this->assertFalse($c->checkFraction($a, ['n' => '7']), 'missing denominator');
        $this->assertFalse($c->checkFraction($a, ['n' => '7', 'd' => '0']));

        $whole = ['type' => 'frac', 'n' => 2, 'd' => 1, 'mode' => 'reduced'];
        $this->assertTrue($c->checkFraction($whole, ['w' => '2']));
        $this->assertTrue($c->checkFraction($whole, ['n' => '2']), 'lone numerator counts as whole number');

        $improper = ['type' => 'frac', 'n' => 23, 'd' => 7, 'mode' => 'improper', 'noWhole' => true];
        $this->assertTrue($c->checkFraction($improper, ['n' => '23', 'd' => '7']));
        $this->assertFalse($c->checkFraction($improper, ['w' => '3', 'n' => '2', 'd' => '7']), 'mixed number is not an improper fraction');

        $mixed = ['type' => 'frac', 'n' => 23, 'd' => 7, 'mode' => 'mixed'];
        $this->assertTrue($c->checkFraction($mixed, ['w' => '3', 'n' => '2', 'd' => '7']));
        $this->assertFalse($c->checkFraction($mixed, ['n' => '23', 'd' => '7']), 'improper fraction is not a mixed number');

        $exact = ['type' => 'frac', 'n' => 8, 'd' => 12, 'mode' => 'exact', 'noWhole' => true];
        $this->assertTrue($c->checkFraction($exact, ['n' => '8', 'd' => '12']));
        $this->assertFalse($c->checkFraction($exact, ['n' => '2', 'd' => '3']));
    }

    public function testGrades(): void
    {
        $g = new GradeCalculator();

        $this->assertSame(6.0, $g->calculate(40, 40));
        $this->assertSame(1.0, $g->calculate(0, 40));
        $this->assertSame(3.5, $g->calculate(20, 40));
        $this->assertSame(4.5, $g->calculate(28, 40));
        $this->assertSame(4.75, $g->calculate(30, 40, 0, 0.25));
        $this->assertSame(6.0, $g->calculate(36, 40, 36));
        $this->assertSame(4.3, $g->calculate(26, 40, 0, 0.1));
        $this->assertSame('4.5', GradeCalculator::format(4.5));
        $this->assertSame('5', GradeCalculator::format('5.00'));
    }
}
