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
use Markocupic\ContaoSchoolTest\Task\Binomial\BinomialGenerator;
use Markocupic\ContaoSchoolTest\Task\Evaluator;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\Task\Term\TermParser;
use Markocupic\ContaoSchoolTest\TestType\BinomialTestType;
use PHPUnit\Framework\TestCase;

class BinomialTest extends TestCase
{
    private const GERMAN = '/\b(Flächeninhalt|Umfang|Figur|Berechne|Bestimme|Rechne|Ergänze|Feldern|Schreibe|Klammere|Faktorisiere|Summenterm|Produktterm|denn|und|ist|Klammern|Benutze|Hochzahlen|Taste|Rechtecks|beträgt|Gemischt|Ausmultiplizieren)\b/u';

    public function testTermParser(): void
    {
        $p = new TermParser();
        $cases = [
            // answer, solution, form, bracket factors, ok, equivalent
            ['3a^2+2ab', 'a(3a+2b)', 'any', 1, true, true],
            ['3a² + 2ab', 'a(3a+2b)', 'sum', 1, true, true],
            ['a·(3a + 2b)', '3a^2+2ab', 'complete', 1, true, true],
            ['(3a^2+2ab)', '3a^2+2ab', 'complete', 1, false, true],
            ['9x²−12xy+4y²', '(3x-2y)^2', 'sum', 1, true, true],
            ['9x^2-6xy-6xy+4y^2', '(3x-2y)^2', 'sum', 1, false, true],
            ['(3x-2y)^2', '9x^2-12xy+4y^2', 'sum', 1, false, true],
            ['(x+5)*(3+x)', 'x^2+8x+15', 'complete', 2, true, true],
            ['(x+4)(x+4)', 'x^2+8x+15', 'complete', 2, false, false],
            ['2(x+3)^2', '2x^2+12x+18', 'complete', 2, true, true],
            ['2(x^2+6x+9)', '2x^2+12x+18', 'complete', 2, false, true],
            ['(2x+6)(x+3)', '2x^2+12x+18', 'complete', 2, false, true],
            ['6(2x-6y+14z)', '12x-36y+84z', 'complete', 1, false, true],
            ['0,25x^2+3x+9', '(0.5x+3)^2', 'sum', 1, true, true],
            ['a^2/25-2ab+25b^2', '(a/5-5b)^2', 'sum', 1, true, true],
            ['A = 4a^2+6ab', '2a(2a+3b)', 'any', 1, true, true],
            ['400 - 1', '399', 'any', 1, true, true],
            ['-(x-3)(x+3)', '9-x^2', 'complete', 2, true, true],
            ['x^2+', 'x^2', 'any', 1, false, false],
            ['(x+1', 'x+1', 'any', 1, false, false],
            ['x/(x+1)', 'x', 'any', 1, false, false],
            ['x^12', 'x^12', 'any', 1, false, false],
            ['', '0', 'any', 1, false, false],
        ];

        foreach ($cases as [$answer, $solution, $form, $factors, $ok, $eq]) {
            $r = $p->check($answer, $solution, $form, $factors);
            $this->assertSame($ok, $r['ok'], "$answer ($form)");
            $this->assertSame($eq, $r['equivalent'], "$answer equivalent");
        }

        $this->assertSame('9x<sup>2</sup> − 12xy + 4y<sup>2</sup>', $p->polynomial('(3x-2y)^2')->html());
        $this->assertSame('9x^2-12xy+4y^2', $p->polynomial('(3x-2y)^2')->plain());
    }

    public function testEveryTaskAcceptsItsSolution(): void
    {
        $generator = new BinomialGenerator();
        $checker = new AnswerChecker();
        mt_srand(17);

        foreach (BinomialGenerator::TOPICS as $topic) {
            foreach (BinomialGenerator::LEVELS as $level) {
                for ($i = 0; $i < 150; ++$i) {
                    $task = $generator->build($topic, $level);
                    $a = $task['a'];

                    $this->assertSame('terms', $a['type']);
                    $this->assertSame(\count($a['fields']), $task['points']);
                    $this->assertCount(\count($a['fields']), $a['v']);

                    $right = [];
                    $empty = [];

                    foreach ($a['v'] as $k => $v) {
                        $right[$k] = isset($v['choice']) ? (string) $v['choice'] : $v['ans'];
                        $empty[$k] = '';

                        // The public part never contains the solution
                        $this->assertArrayNotHasKey('ans', $a['fields'][$k]);
                    }

                    $this->assertSame($task['points'], $checker->check($task, $right)['points'], "$topic/$level ".json_encode($right));
                    $this->assertSame(0, $checker->check($task, $empty)['points']);
                }
            }
        }
    }

    public function testFormNotes(): void
    {
        mt_srand(3);
        $task = (new BinomialGenerator())->build('ausmultiplizieren', 'easy');

        // The product itself is equivalent, but not multiplied out
        $prompt = strip_tags(str_replace(['<sup>2</sup>', ' · ', '−'], ['^2', '*', '-'], $task['a']['fields'][0]['prompt']));
        $r = (new AnswerChecker())->check($task, [0 => rtrim($prompt, ' =')]);

        $this->assertFalse($r['correct'][0]);
        $this->assertSame('sum', $r['notes'][0]);
    }

    public function testSanitizingAndPresenter(): void
    {
        mt_srand(5);
        $task = (new BinomialGenerator())->build('faktorisieren', 'hard');
        $clean = (new Evaluator(new AnswerChecker()))->sanitizeAnswers([['tasks' => [$task]]], ['s0t0' => [0 => ' 2(x+1) ', 99 => 'x', 1 => ['a']]]);

        $this->assertSame('2(x+1)', $clean['s0t0'][0]);
        $this->assertArrayNotHasKey(99, $clean['s0t0']);
        $this->assertSame('', $clean['s0t0'][1]);

        // Public: no solutions, decimal comma in French
        $task = ['q' => 'Schreibe ohne Klammern und fasse zusammen.', 'a' => ['type' => 'terms', 'fields' => [['kind' => 'term', 'prompt' => '(0.5x + 3)<sup>2</sup> =']], 'v' => [['ans' => '0.25x^2+3x+9', 'form' => 'sum', 'factors' => 1, 'show' => '']]], 'points' => 1];
        $public = (new TaskPresenter(new TaskTranslator()))->toPublic([['title' => 'Ausmultiplizieren', 'instruction' => '', 'tasks' => [$task]]], 'fr');
        $a = $public[0]['tasks'][0]['a'];

        $this->assertArrayNotHasKey('v', $a);
        $this->assertSame('(0,5x + 3)<sup>2</sup> =', $a['fields'][0]['prompt']);
    }

    public function testTestTypeAndTranslations(): void
    {
        $type = new BinomialTestType();
        $translator = new TaskTranslator();
        mt_srand(9);

        $sections = $type->generate(['difficulty' => 'medium']);
        $this->assertSame(BinomialGenerator::TOPICS, array_column($sections, 'area'));
        $this->assertSame('algebra', $type->getPracticeArea());
        $this->assertCount(3, $type->generatePractice('geschickt', 'easy')[0]['tasks']);
        $this->assertCount(3, $type->generatePractice('gemischt', 'hard')[0]['tasks']);

        foreach (['fr', 'it', 'en'] as $lang) {
            foreach ([...BinomialTestType::TITLES, ...BinomialTestType::INSTRUCTIONS, 'Summenterm', 'Produktterm'] as $text) {
                $this->assertDoesNotMatchRegularExpression(self::GERMAN, strip_tags($translator->text($text, $lang)), "$lang: $text");
            }

            foreach (BinomialGenerator::TOPICS as $topic) {
                foreach (BinomialGenerator::LEVELS as $level) {
                    for ($i = 0; $i < 30; ++$i) {
                        $task = (new BinomialGenerator())->build($topic, $level);
                        $this->assertDoesNotMatchRegularExpression(self::GERMAN, strip_tags($translator->question($task['q'], $lang)), "$lang: ".$task['q']);
                        $this->assertDoesNotMatchRegularExpression(self::GERMAN, strip_tags($translator->way($task['way'], $lang)), "$lang: ".$task['way']);
                    }
                }
            }
        }
    }
}
