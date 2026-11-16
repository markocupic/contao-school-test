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
use Markocupic\ContaoSchoolTest\Task\Evaluator;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\Task\Triangle\TriangleGenerator;
use Markocupic\ContaoSchoolTest\Task\Triangle\TriangleGeometry;
use Markocupic\ContaoSchoolTest\Task\Triangle\TriangleSvg;
use Markocupic\ContaoSchoolTest\TestType\TriangleTestType;
use PHPUnit\Framework\TestCase;

class TriangleTest extends TestCase
{
    private const SIDES = [[0, 1], [1, 2], [0, 2]];

    private const GERMAN = '/\b(Konstruiere|Dreieck|Zeichne|Strecke|Kreis|Radius|Trage|Winkel|Schenkel|schneiden|schneidet|Parallele|Abstand|Wähle|Verbinde|und|mit|Seite|Seiten|Höhe|drei|zwei|Gegenwinkel|grösseren|anliegenden|Zwischenwinkel|Gemischt|Winkelhalbierende|Mittelpunkt|Gerade|Verlängere|Länge|halb|Kreise)\b/u';

    public function testMeasures(): void
    {
        // Right triangle 3-4-5: γ = 90°, h_c = 2.4
        $m = TriangleGeometry::measures([0, 0], [5, 0], [3.2, 2.4]);

        $this->assertEqualsWithDelta(3.0, $m['a'], 1e-9);
        $this->assertEqualsWithDelta(4.0, $m['b'], 1e-9);
        $this->assertEqualsWithDelta(5.0, $m['c'], 1e-9);
        $this->assertEqualsWithDelta(2.4, $m['hc'], 1e-9);
        $this->assertEqualsWithDelta(180.0, $m['alpha'] + $m['beta'] + $m['gamma'], 1e-9);
        $this->assertEqualsWithDelta(hypot(3.2 - 2.5, 2.4), $m['sc'], 1e-9);
    }

    public function testEveryConstructionGivesFullPointsInAnyPosition(): void
    {
        $generator = new TriangleGenerator();
        $checker = new AnswerChecker();
        mt_srand(11);

        foreach (TriangleGenerator::KINDS as $kind) {
            foreach (TriangleGenerator::LEVELS as $level) {
                for ($i = 0; $i < 100; ++$i) {
                    $task = $generator->build($kind, $level);
                    $a = $task['a'];
                    $shape = $a['shape'];

                    $this->assertSame('triangle', $a['type']);
                    $this->assertCount(3, $a['given']);
                    $this->assertSame(4, $task['points']);

                    // The solution triangle has the given values
                    $m = TriangleGeometry::measures(...$shape);

                    foreach ($a['given'] as $g) {
                        // The shape is stored with 4 decimals
                        $this->assertEqualsWithDelta($g['v'], $m[$g['k']], \in_array($g['k'], TriangleGeometry::ANGLES, true) ? 0.01 : 0.001, "$kind/$level {$g['k']}");
                    }

                    $this->assertSame(4, $checker->check($task, [...$shape, self::SIDES])['points']);

                    // Moved, turned and mirrored: still right
                    $phi = mt_rand(0, 628) / 100;
                    $move = static fn (array $p): array => [4 + cos($phi) * $p[0] + sin($phi) * $p[1], 3 + sin($phi) * $p[0] - cos($phi) * $p[1]];
                    $this->assertSame(4, $checker->check($task, [...array_map($move, $shape), [[1, 0], [2, 1], [2, 0]]])['points']);

                    // Without the sides one point less, without points nothing
                    $this->assertSame(3, $checker->check($task, $shape)['points']);
                    $this->assertSame(0, $checker->check($task, null)['points']);
                }
            }
        }
    }

    public function testTolerance(): void
    {
        mt_srand(4);

        foreach (TriangleGenerator::TOLERANCE as $level => [$len]) {
            $task = (new TriangleGenerator())->build('sss', $level);
            [$A, $B, $C] = $task['a']['shape'];

            // Stretch AB by just under / just over the tolerance: c changes, a and b hardly
            $c = $B[0];
            $near = [$A, [$c + $len - 0.005, 0], $C, self::SIDES];
            $far = [$A, [$c + $len + 0.01, 0], $C, self::SIDES];
            $correct = (new AnswerChecker())->check($task, $near)['correct'];
            $keyC = array_search('c', array_column($task['a']['given'], 'k'), true);

            $this->assertTrue($correct[$keyC], $level);
            $this->assertFalse((new AnswerChecker())->check($task, $far)['correct'][$keyC], $level);
        }

        $this->assertSame(['1', '1'], TriangleGenerator::toleranceText('medium'));
        $this->assertSame(['0.5', '0.5'], TriangleGenerator::toleranceText('hard'));
    }

    public function testSanitizing(): void
    {
        $clean = Evaluator::triangleAnswer([[1.23456, 2], [500, 3], [4, 5], [[0, 1], [0, 2], [2, 0], [1, 2]]]);

        // B is far away: dropped, and with it the sides at B
        $this->assertSame([[1.2346, 2.0], null, [4.0, 5.0], [[0, 2]]], $clean);
        $this->assertNull(AnswerChecker::triangleMeasures([[0, 0], [1, 1], [2, 2]]));
    }

    public function testTestTypeAndTranslations(): void
    {
        $type = new TriangleTestType();
        $translator = new TaskTranslator();
        mt_srand(8);

        $sections = $type->generate(['difficulty' => 'medium', 'triangleKinds' => '', 'tasksPerKind' => 2]);
        $this->assertSame(TriangleGenerator::KINDS, array_column($sections, 'area'));
        $this->assertStringContainsString('Bis 1 mm und 1° Abweichung', $sections[0]['instruction']);

        foreach (['fr', 'it', 'en'] as $lang) {
            foreach (TriangleTestType::TITLES as $title) {
                $this->assertDoesNotMatchRegularExpression(self::GERMAN, $translator->text($title, $lang), "$lang: $title");
            }

            $this->assertDoesNotMatchRegularExpression(self::GERMAN, strip_tags($translator->text($sections[0]['instruction'], $lang)), $lang);

            foreach (TriangleGenerator::KINDS as $kind) {
                foreach (TriangleGenerator::LEVELS as $level) {
                    for ($i = 0; $i < 30; ++$i) {
                        $task = (new TriangleGenerator())->build($kind, $level);
                        $this->assertDoesNotMatchRegularExpression(self::GERMAN, strip_tags($translator->question($task['q'], $lang)), "$lang: ".$task['q']);
                        $this->assertDoesNotMatchRegularExpression(self::GERMAN, strip_tags($translator->way($task['way'], $lang)), "$lang: ".$task['way']);
                    }
                }
            }
        }

        // Decimal comma in French and Italian
        $task = (new TriangleGenerator())->build('sss', 'medium');
        $this->assertDoesNotMatchRegularExpression('/\d\.\d/', $translator->question($task['q'], 'fr'));
        $this->assertStringContainsString('0,5 mm', $translator->text(str_replace(['{0}', '{1}'], ['0.5', '0.5'], TriangleTestType::INSTRUCTION), 'it'));

        // Practice: two per round
        $this->assertCount(2, $type->generatePractice('hoehe', 'hard')[0]['tasks']);
    }

    public function testEvaluationSheetDrawing(): void
    {
        mt_srand(2);
        $task = (new TriangleGenerator())->build('wsw', 'easy');
        $shape = $task['a']['shape'];
        $svg = (new TriangleSvg())->render($task['a'], $shape, self::SIDES, true);

        $this->assertStringContainsString('stroke-dasharray', $svg);
        $this->assertSame(3, substr_count($svg, '<line'));
        $this->assertSame('', (new TriangleSvg())->render($task['a'], [null, null, null], [], false));
    }
}
