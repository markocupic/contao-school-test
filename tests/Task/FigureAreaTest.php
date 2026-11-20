<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Tests\Task;

use Markocupic\ContaoSchoolTest\Task\AnswerChecker;
use Markocupic\ContaoSchoolTest\Task\FigureArea\FigureAreaGenerator;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\TestType\FigureAreaTestType;
use PHPUnit\Framework\TestCase;

class FigureAreaTest extends TestCase
{
    /**
     * Every figure, level and word problem: plausible values, the solution is right,
     * a wrong value is wrong, and question and way are translated.
     */
    public function testAllTasksAreSolvableAndTranslated(): void
    {
        $generator = new FigureAreaGenerator();
        $checker = new AnswerChecker();
        $translator = new TaskTranslator();
        mt_srand(7);

        foreach (FigureAreaGenerator::KINDS as $kind) {
            foreach (FigureAreaGenerator::LEVELS as $level) {
                foreach ([false, true] as $word) {
                    for ($i = 0; $i < 60; ++$i) {
                        $task = $generator->build($kind, $level, $word);
                        $a = $task['a'];
                        $info = "$kind $level ".($word ? 'word' : 'figure').': '.$task['q'];

                        $this->assertSame('angles', $a['type']);
                        $this->assertSame(\count($a['fields']), $task['points']);
                        $this->assertStringStartsWith('<svg', $a['solSvg'], $info);
                        $this->assertSame($word, '' === $a['svg'], $info);
                        $this->assertStringNotContainsString('NAN', $a['solSvg'].$task['way'], $info);
                        $this->assertStringNotContainsString('INF', $a['solSvg'].$task['way'], $info);

                        foreach ($a['v'] as $v) {
                            $this->assertTrue(is_finite((float) $v) && $v > 0, $info.' value '.$v);
                            // At most three decimal places (or rounded to one with π)
                            $this->assertEqualsWithDelta(round((float) $v, 3), (float) $v, 1e-9, $info.' '.$v);
                        }

                        $right = array_map(static fn ($v): string => (string) $v, $a['v']);
                        $this->assertSame($task['points'], $checker->check($task, $right)['points'], $info);

                        // Comma as decimal separator
                        $comma = array_map(static fn ($v): string => str_replace('.', ',', (string) $v), $a['v']);
                        $this->assertSame($task['points'], $checker->check($task, $comma)['points'], $info);

                        $wrong = array_map(static fn ($v): string => (string) ($v + 1), $a['v']);
                        $this->assertSame(0, $checker->check($task, $wrong)['points'], $info);

                        // The solution value appears in the way
                        $last = end($a['v']);
                        $this->assertStringContainsString('<b>'.\Markocupic\ContaoSchoolTest\Task\Util::fmt($last), $task['way'], $info);

                        foreach (['fr', 'it', 'en'] as $lang) {
                            $q = $translator->question($task['q'], $lang);
                            $this->assertNotSame($task['q'], $q, "question $lang: $info");
                            $way = $translator->way($task['way'], $lang);

                            preg_match_all('#<span class="st-t">(.*?)</span>#su', $task['way'], $de);
                            preg_match_all('#<span class="st-t">(.*?)</span>#su', $way, $tr);

                            foreach ($de[1] as $k => $phrase) {
                                $this->assertNotSame($phrase, $tr[1][$k], "way $lang: $phrase");
                            }

                            foreach ($a['fields'] as $f) {
                                $this->assertTrue(mb_strlen($translator->text($f['label'], $lang)) <= 2 || $translator->text($f['label'], $lang) !== $f['label'], "label $lang: ".$f['label']);
                            }
                        }
                    }
                }
            }
        }
    }

    public function testRoundedValuesAcceptPiAsThreePointOneFour(): void
    {
        $checker = new AnswerChecker();
        $task = ['a' => ['type' => 'angles', 'fields' => [['label' => 'A', 'unit' => ' cm²']], 'v' => [452.4], 'tol' => [FigureAreaGenerator::ROUND]]];

        // r = 12: π · 144 = 452.39, 3.14 · 144 = 452.16
        foreach (['452.4', '452,39', '452.2', '452.16'] as $ok) {
            $this->assertSame(1, $checker->check($task, [$ok])['points'], $ok);
        }

        foreach (['452', '453', '45.2'] as $bad) {
            $this->assertSame(0, $checker->check($task, [$bad])['points'], $bad);
        }

        $exact = ['a' => ['type' => 'angles', 'fields' => [['label' => 'A', 'unit' => ' cm²']], 'v' => [24.5], 'tol' => [FigureAreaGenerator::EXACT]]];
        $this->assertSame(1, $checker->check($exact, ['24.5'])['points']);
        $this->assertSame(0, $checker->check($exact, ['24.6'])['points']);
        $this->assertSame(0, $checker->check($exact, ['24'])['points']);
    }

    public function testTestTypeAndPractice(): void
    {
        $type = new FigureAreaTestType();
        mt_srand(3);

        $this->assertCount(\count(FigureAreaTestType::KINDS), $type->generate([]));

        $sections = $type->generate(['areaKinds' => serialize(['kreis', 'text']), 'tasksPerKind' => 4, 'difficulty' => 'hard']);
        $this->assertCount(2, $sections);
        $this->assertSame('kreis', $sections[0]['area']);
        $this->assertCount(4, $sections[0]['tasks']);
        // Every fourth task is a word problem, the word problem section has no sketches
        $this->assertSame('', $sections[0]['tasks'][3]['a']['svg']);
        $this->assertNotSame('', $sections[0]['tasks'][0]['a']['svg']);

        foreach ($sections[1]['tasks'] as $t) {
            $this->assertSame('', $t['a']['svg']);
        }

        foreach (array_keys(FigureAreaTestType::TITLES) as $topic) {
            $practice = $type->generatePractice($topic, 'medium');
            $this->assertCount(4, $practice[0]['tasks'], $topic);
        }

        $topics = $type->getPracticeTopics();
        $this->assertSame('Composite shapes', $topics['zusammengesetzt']['en']);
        $this->assertSame('Trapèze', $topics['trapez']['fr']);

        // Public view: no solution, labels and units translated, decimal comma in the sketch
        $public = (new TaskPresenter())->toPublic($type->generatePractice('text', 'hard'), 'fr');
        $json = json_encode($public, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('solSvg', $json);
        $this->assertStringNotContainsString('"v"', $json);
        $this->assertStringNotContainsString('"tol"', $json);
        $this->assertSame('Résous les problèmes. Fais d’abord un croquis. Arrondis les résultats avec π à une décimale.', $public[0]['instruction']);
    }

    public function testUnitsAndLabelsInTheFrontend(): void
    {
        $translator = new TaskTranslator();
        $this->assertSame("\u{00A0}fr.", $translator->units("\u{00A0}Fr.", 'fr'));
        $this->assertSame("\u{00A0}CHF", $translator->units("\u{00A0}Fr.", 'en'));
        $this->assertSame("\u{00A0}litres", $translator->units("\u{00A0}Liter", 'fr'));
        $this->assertSame('Number of tiles', $translator->text('Anzahl Platten', 'en'));
        $this->assertSame('<text>4,5 cm</text>', $translator->decimals('<text>4.5 cm</text>', 'it'));
    }
}
