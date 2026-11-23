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
use Markocupic\ContaoSchoolTest\Task\AnswerChecker;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\TestType\AngleTestType;
use PHPUnit\Framework\TestCase;

class AngleCircleTest extends TestCase
{
    /**
     * The sketch is true to scale: every marked angle (given or unknown) has the stated size.
     */
    public function testDrawnAnglesMatchTheValues(): void
    {
        $generator = new AngleGenerator();
        $gen = new \ReflectionMethod($generator, 'genCircle');
        mt_srand(11);

        for ($i = 0; $i < 400; ++$i) {
            $t = $gen->invoke($generator);
            $count = 0;

            foreach ($t['S']['marks'] as $m) {
                $u = [$m['a'][0] - $m['v'][0], $m['a'][1] - $m['v'][1]];
                $w = [$m['b'][0] - $m['v'][0], $m['b'][1] - $m['v'][1]];
                $drawn = rad2deg(acos(($u[0] * $w[0] + $u[1] * $w[1]) / (hypot(...$u) * hypot(...$w))));
                $value = 'unknown' === $m['kind'] ? $m['ans'] : (int) $m['text'];
                $this->assertEqualsWithDelta($value, $drawn, 0.01, $t['prompt'].' '.$m['text']);
                $this->assertGreaterThanOrEqual(20, $value);

                if ('unknown' === $m['kind']) {
                    ++$count;
                }
            }

            $this->assertGreaterThanOrEqual(3, $count, 'several unknown angles per task');
        }
    }

    public function testTasksAreSolvableTranslatedAndVaried(): void
    {
        $generator = new AngleGenerator();
        $checker = new AnswerChecker();
        $translator = new TaskTranslator();
        mt_srand(4);

        for ($i = 0; $i < 100; ++$i) {
            $task = $generator->build('kreis');
            $right = array_map('strval', $task['a']['v']);
            $this->assertSame($task['points'], $checker->check($task, $right)['points']);
            $this->assertStringContainsString('<circle', $task['a']['svg']);

            foreach (['fr', 'it', 'en'] as $lang) {
                $this->assertNotSame($task['q'], $translator->question($task['q'], $lang), $task['q']);
                preg_match_all('#<span class="st-t">(.*?)</span>#su', $task['way'], $de);
                preg_match_all('#<span class="st-t">(.*?)</span>#su', $translator->way($task['way'], $lang), $tr);

                foreach ($de[1] as $k => $phrase) {
                    $this->assertNotSame($phrase, $tr[1][$k], "$lang: $phrase");
                }
            }
        }

        // Four tasks in a practice round: four different figures
        $type = new AngleTestType();

        for ($i = 0; $i < 20; ++$i) {
            $tasks = $type->generatePractice('kreis', '')[0]['tasks'];
            $this->assertCount(4, $tasks);
            $this->assertCount(4, array_unique(array_map(static fn (array $t): string => $t['q'].\count($t['a']['v']).substr_count($t['a']['svg'], '<line'), $tasks)));
        }

        $this->assertSame('Angles in a circle (inscribed and central angles)', $type->getPracticeTopics()['kreis']['en']);
    }
}
