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

use Markocupic\ContaoSchoolTest\Task\Area\AbstractArea;
use Markocupic\ContaoSchoolTest\Task\Area\ConversionArea;
use Markocupic\ContaoSchoolTest\Task\Area\EstimationArea;
use Markocupic\ContaoSchoolTest\Task\Area\FractionsArea;
use Markocupic\ContaoSchoolTest\Task\Area\MentalArithmeticArea;
use Markocupic\ContaoSchoolTest\Task\Area\PercentTableArea;
use Markocupic\ContaoSchoolTest\Task\Angle\AngleGenerator;
use Markocupic\ContaoSchoolTest\Task\Equation\EquationGenerator;
use Markocupic\ContaoSchoolTest\Task\Geometry\TransformGenerator;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\TestType\GeometryTransformTestType;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use PHPUnit\Framework\TestCase;

/**
 * Every generated question, solution path, title and unit must be translated:
 * no German word may be left in French, Italian or English.
 */
class TaskTranslatorTest extends TestCase
{
    /**
     * German words that must not appear in a translation.
     */
    private const GERMAN = '/\b(und|von|ist|sind|Berechne|Schreibe|Schätze|Wie|Zahl|Zahlen|Summe|Differenz|Produkt|Quotienten|Halbiere|Verdopple|Verdreifache|Drittle|Addiere|Subtrahiere|Multipliziere|Vermindere|Kürze|Erweitere|Gramm|Minuten|Stunden|Franken|Rappen|dann|Dann|also|Nenner|Zähler|Kehrwert|Punkt|Genau|Wenn|Runde|Ganze|Umwandeln|Gesuchte|Achte|Einheiten|Bruch|Prozentzahl|Dezimalbruch|kleiner|grösser|Rest|ggT|zusammen|davon|Gewicht|Länge|Preis|Liter|Fr\.|Rp\.|Spiegle|Drehe|Verschiebe|Figur|Achse|Häuschen|Zähle|Gehe|nach|rechts|links|oben|unten|Gegenuhrzeigersinn|Uhrzeigersinn|Pfeil|Setze|Bildpunkte|Achsenspiegelung|Punktspiegelung|Drehung|Verschiebung|Gemischt|Achtung)\b/u';

    public function testAllTasksAreTranslated(): void
    {
        $translator = new TaskTranslator();
        $areas = [new MentalArithmeticArea(), new FractionsArea(), new ConversionArea(), new PercentTableArea(), new EstimationArea()];

        foreach (['fr', 'it', 'en'] as $lang) {
            mt_srand(42);

            foreach ($areas as $area) {
                $this->assertDoesNotMatchRegularExpression(self::GERMAN, $translator->text($area->getTitle(), $lang), $area->getTitle());
                $this->assertDoesNotMatchRegularExpression(self::GERMAN, $translator->text($area->getInstruction(), $lang), $area->getInstruction());

                for ($level = 1; $level <= AbstractArea::LEVELS; ++$level) {
                    $runs = 'pt' === $area->getAlias() ? 40 : 200;

                    for ($i = 0; $i < $runs; ++$i) {
                        $task = $area->generate($level);
                        $q = $translator->question($task['q'], $lang);
                        $way = $translator->way($task['way'], $lang);

                        $this->assertDoesNotMatchRegularExpression(self::GERMAN, strip_tags($q), "$lang: ".strip_tags($task['q']));
                        $this->assertDoesNotMatchRegularExpression(self::GERMAN, strip_tags($way), "$lang: ".strip_tags($task['way']));
                        $this->assertSame(substr_count($task['q'], '<b>'), substr_count($q, '<b>'), "$lang: bold parts kept in ".strip_tags($task['q']));

                        if (isset($task['a']['suffix'])) {
                            $this->assertDoesNotMatchRegularExpression(self::GERMAN, $translator->units($task['a']['suffix'], $lang));
                        }
                    }
                }
            }
        }
    }

    public function testGeometryIsTranslated(): void
    {
        $translator = new TaskTranslator();
        $generator = new TransformGenerator();

        foreach (['fr', 'it', 'en'] as $lang) {
            mt_srand(5);

            foreach (GeometryTransformTestType::TITLES as $title) {
                $this->assertDoesNotMatchRegularExpression(self::GERMAN, $translator->text($title, $lang), $title);
            }

            $this->assertDoesNotMatchRegularExpression(self::GERMAN, $translator->text(GeometryTransformTestType::INSTRUCTION, $lang));

            foreach (TransformGenerator::KINDS as $kind) {
                foreach (TransformGenerator::LEVELS as $level) {
                    for ($i = 0; $i < 60; ++$i) {
                        $task = $generator->build($kind, $level);
                        $this->assertDoesNotMatchRegularExpression(self::GERMAN, strip_tags($translator->question($task['q'], $lang)), "$lang: ".strip_tags($task['q']));
                        $this->assertDoesNotMatchRegularExpression(self::GERMAN, strip_tags($translator->way($task['way'], $lang)), "$lang: ".$task['way']);
                    }
                }
            }
        }
    }

    public function testAnglesAndEquationsAreTranslated(): void
    {
        $translator = new TaskTranslator();
        $angles = new AngleGenerator();
        $equations = new EquationGenerator();
        $german = '/\\b(Berechne|Winkel|Dreieck|Viereck|Geraden?|sind|liegt|liegen|und|gleich|gross|Nebenwinkel|Scheitelwinkel|Stufenwinkel|Wechselwinkel|Winkelsumme|verlängert|Ecken|TU)\\b/u';

        foreach (['fr', 'it', 'en'] as $lang) {
            mt_srand(9);

            foreach (AngleGenerator::KINDS as $kind) {
                for ($i = 0; $i < 80; ++$i) {
                    $task = $angles->build($kind);
                    $this->assertDoesNotMatchRegularExpression($german, strip_tags($translator->question($task['q'], $lang)), "$lang: ".$task['q']);
                    $this->assertDoesNotMatchRegularExpression($german, strip_tags($translator->way($task['way'], $lang)), "$lang: ".strip_tags($task['way']));
                }
            }

            foreach (array_keys(EquationGenerator::LEVELS) as $level) {
                $task = $equations->build($level, 'hard');
                $this->assertDoesNotMatchRegularExpression($german, strip_tags($translator->way($task['way'], $lang)), "$lang: equation $level");
            }
        }
    }

    public function testPresenterTranslatesTables(): void
    {
        mt_srand(7);
        $task = (new PercentTableArea())->generate(5);
        $public = (new TaskPresenter())->toPublic([['title' => 'Prozent-Tabellen', 'instruction' => 'x', 'tasks' => [$task]]], 'en');
        $json = json_encode($public, JSON_UNESCAPED_UNICODE);

        $this->assertSame('Percentage tables', $public[0]['title']);
        $this->assertDoesNotMatchRegularExpression('/Gewicht|Länge|Preis|Volumen"|Fr\.|Rp\./u', $json);
    }

    public function testGermanStaysUnchanged(): void
    {
        $t = new TaskTranslator();
        $q = 'Die Summe von <b>12</b> und <b>30</b> ist:';

        $this->assertSame($q, $t->question($q, 'de'));
        $this->assertSame($q, $t->question($q, 'xx'), 'unknown language falls back to German');
        $this->assertSame('La somme de <b>12</b> et <b>30</b> est&nbsp;:', $t->question($q, 'fr'));
        $this->assertSame('The sum of <b>12</b> and <b>30</b> is:', $t->question($q, 'en'));
    }
}
