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

use Markocupic\ContaoSchoolTest\Task\Angle\AngleGenerator;
use Markocupic\ContaoSchoolTest\Task\AnswerChecker;
use Markocupic\ContaoSchoolTest\Task\Equation\EquationGenerator;
use Markocupic\ContaoSchoolTest\Task\Equation\Fraction;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\TestType\AngleTestType;
use Markocupic\ContaoSchoolTest\TestType\EquationTestType;
use PHPUnit\Framework\TestCase;

class AngleEquationTest extends TestCase
{
    public function testAngleTasksAreConsistent(): void
    {
        $generator = new AngleGenerator();
        $checker = new AnswerChecker();
        mt_srand(21);

        foreach (AngleGenerator::KINDS as $kind) {
            for ($i = 0; $i < 200; ++$i) {
                $task = $generator->build($kind);
                $a = $task['a'];
                $this->assertSame('angles', $a['type']);
                $this->assertCount(\count($a['v']), $a['fields']);
                $this->assertSame(\count($a['v']), $task['points']);
                $this->assertStringStartsWith('<svg', $a['svg']);

                foreach ($a['v'] as $v) {
                    $this->assertIsInt($v);
                    $this->assertGreaterThan(0, $v, $task['q']);
                }

                // Right answers give all points, the solution sketch shows the values
                $answer = array_map(static fn (int $v): string => (string) $v, $a['v']);
                $this->assertSame($task['points'], $checker->check($task, $answer)['points']);
                $answer[0] = (string) ($a['v'][0] + 1);
                $this->assertSame($task['points'] - 1, $checker->check($task, $answer)['points']);
            }
        }
    }

    public function testEquationSolutionSolvesTheEquation(): void
    {
        $generator = new EquationGenerator();
        $checker = new AnswerChecker();
        mt_srand(22);

        foreach (array_keys(EquationGenerator::LEVELS) as $level) {
            foreach (['easy', 'medium', 'hard'] as $difficulty) {
                for ($i = 0; $i < 40; ++$i) {
                    $task = $generator->build($level, $difficulty);
                    $a = $task['a'];
                    $this->assertSame('rat', $a['type']);
                    $this->assertSame(EquationGenerator::points($level), $task['points']);

                    if ('easy' === $difficulty) {
                        $this->assertSame(1, $a['d'], "level $level easy must have a whole-number solution");
                    }

                    $x = new Fraction($a['n'], $a['d']);
                    $this->assertSame($task['points'], $checker->check($task, $x->plain())['points']);
                    // Unreduced fraction is right as well
                    $this->assertSame($task['points'], $checker->check($task, (2 * $a['n']).'/'.(2 * $a['d']))['points']);
                    $this->assertSame(0, $checker->check($task, ($a['n'] + 1).'/'.$a['d'])['points']);
                    $this->assertStringContainsString('st-eqsteps', $task['way']);
                }
            }
        }
    }

    public function testRationalParser(): void
    {
        $this->assertSame([-3, 4], AnswerChecker::parseRational('-3/4'));
        $this->assertSame([-3, 4], AnswerChecker::parseRational(' x = −3 / 4 '));
        $this->assertSame([5, 1], AnswerChecker::parseRational('5'));
        $this->assertSame([75, 100], AnswerChecker::parseRational('0,75'));
        $this->assertSame([-5, 10], AnswerChecker::parseRational('-.5'));
        $this->assertNull(AnswerChecker::parseRational('3/0'));
        $this->assertNull(AnswerChecker::parseRational('abc'));
        $this->assertNull(AnswerChecker::parseRational(['3']));
    }

    public function testNoSolutionsInPublicTasks(): void
    {
        $presenter = new TaskPresenter();
        mt_srand(23);
        $json = json_encode($presenter->toPublic((new AngleTestType())->generatePractice('kombi', 'medium'), 'fr'), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('solSvg', $json);
        $this->assertStringNotContainsString('"v"', $json);
        $this->assertStringNotContainsString('"way"', $json);

        $json = json_encode($presenter->toPublic((new EquationTestType())->generatePractice('gemischt', 'hard'), 'en'), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('"n"', $json);
        $this->assertStringNotContainsString('"d"', $json);
        $this->assertStringNotContainsString('"way"', $json);
    }

    public function testTestTypesUseTheBackendSelection(): void
    {
        $sections = (new AngleTestType())->generate(['angleKinds' => serialize(['dreieck', 'profi']), 'tasksPerKind' => 3]);
        $this->assertCount(2, $sections);
        $this->assertCount(3, $sections[1]['tasks']);
        $this->assertSame('profi', $sections[1]['area']);

        $sections = (new EquationTestType())->generate(['equationLevels' => serialize(['2', '9']), 'tasksPerKind' => 2, 'difficulty' => 'easy']);
        $this->assertCount(2, $sections);
        $this->assertSame('level9', $sections[1]['area']);
        $this->assertSame(3, $sections[1]['tasks'][0]['points']);

        $this->assertCount(5, (new EquationTestType())->generate([]));
        $this->assertCount(\count(AngleGenerator::KINDS), (new AngleTestType())->generate([]));
    }

    public function testTitlesAreTranslated(): void
    {
        $translator = new TaskTranslator();

        foreach ([AngleTestType::TITLES, array_column(EquationTestType::TOPICS, 0), EquationGenerator::LEVELS, [AngleTestType::INSTRUCTION, EquationTestType::INSTRUCTION]] as $titles) {
            foreach ($titles as $title) {
                if (str_contains($title, ' = ')) {
                    continue;
                }

                $this->assertNotSame($title, $translator->text($title, 'en'), $title);
            }
        }
    }
}
