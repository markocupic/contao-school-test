<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\TestType;

use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\Task\Triangle\TriangleGenerator;
use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Dreieckskonstruktionen in GeoGebra: SSS, SWS, WSW, SsW and constructions with a height,
 * an angle bisector or a median.
 * Every given measure the constructed triangle has is worth one point, one more for the sides.
 */
final class TriangleTestType implements PracticeTestTypeInterface, TaskKindsInterface
{
    public const ALIAS = 'triangles';

    public const TITLES = [
        'sss' => 'SSS: drei Seiten',
        'sws' => 'SWS: zwei Seiten und der Zwischenwinkel',
        'wsw' => 'WSW: eine Seite und die anliegenden Winkel',
        'ssw' => 'SsW: zwei Seiten und der Gegenwinkel der grösseren Seite',
        'hoehe' => 'Mit Höhe',
        'winkelhalbierende' => 'Mit Winkelhalbierender',
        'schwerelinie' => 'Mit Schwerelinie',
        'gemischt' => 'Alle Bereiche gemischt',
    ];

    /**
     * {0}: tolerance in mm, {1}: in degrees.
     */
    public const INSTRUCTION = 'Konstruiere das Dreieck mit den Werkzeugen von GeoGebra. Die Ecken heissen A, B und C; mit den Knöpfen A, B, C gibst du einem anderen Punkt diesen Namen. Zeichne zum Schluss die drei Seiten als Strecken, das gibt einen Punkt mehr. Bis {0} mm und {1}° Abweichung gilt als richtig.';

    private readonly TriangleGenerator $generator;

    public function __construct()
    {
        $this->generator = new TriangleGenerator();
    }

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function getLabel(): string
    {
        return 'Dreieckskonstruktionen mit GeoGebra (SSS, SWS, WSW, SsW, mit Höhe, Winkelhalbierender oder Schwerelinie)';
    }

    public function getPaletteFields(): string
    {
        return '{config_legend},taskCounts';
    }

    public function generate(array $test): array
    {
        $sections = [];

        foreach (TaskCounts::rows($test, $this) as $kind => ['count' => $count, 'difficulty' => $difficulty]) {
            $difficulty = $this->difficulty($difficulty);
            $sections[] = $this->section((string) $kind, $this->many((string) $kind, $difficulty, $count), $difficulty);
        }

        return $sections;
    }

    public function getTaskKinds(): array
    {
        return TaskCounts::labels('triangleKindsOptions', array_combine(TriangleGenerator::KINDS, array_map(static fn (string $k): string => self::TITLES[$k] ?? $k, TriangleGenerator::KINDS)));
    }

    public function getDefaultTaskCounts(): array
    {
        return array_fill_keys(array_keys($this->getTaskKinds()), 2);
    }

    public function getMaxTaskCount(string|int $kind): int
    {
        return 10;
    }

    public function hasTaskDifficulty(): bool
    {
        return true;
    }

    public function getPracticeLabels(): array
    {
        return [
            'de' => 'Dreieckskonstruktionen',
            'fr' => 'Constructions de triangles',
            'it' => 'Costruzioni di triangoli',
            'en' => 'Triangle constructions',
        ];
    }

    public function getPracticeTopics(): array
    {
        $translator = new TaskTranslator();
        $topics = [];

        foreach (self::TITLES as $alias => $title) {
            foreach (TaskTranslator::LANGUAGES as $lang) {
                $topics[$alias][$lang] = $translator->text($title, $lang);
            }
        }

        return $topics;
    }

    /**
     * Two constructions per round; "gemischt": two of different kinds.
     */
    public function generatePractice(string $topic, string $difficulty): array
    {
        $difficulty = $this->difficulty($difficulty);

        if ('gemischt' === $topic) {
            $kinds = \array_slice(Util::shuffle(TriangleGenerator::KINDS), 0, 2);

            return [$this->section('gemischt', array_map(fn (string $kind): array => $this->generator->build($kind, $difficulty), $kinds), $difficulty)];
        }

        $kind = \in_array($topic, TriangleGenerator::KINDS, true) ? $topic : 'sss';

        return [$this->section($kind, $this->many($kind, $difficulty, 2), $difficulty)];
    }

    public function getPracticeArea(): string
    {
        return 'geometrie';
    }

    public function hasPracticeDifficulty(): bool
    {
        return true;
    }

    private function many(string $kind, string $difficulty, int $count): array
    {
        $tasks = [];
        $seen = [];

        for ($i = 0; \count($tasks) < $count && $i < $count * 20; ++$i) {
            $task = $this->generator->build($kind, $difficulty);

            if (!isset($seen[$task['key']])) {
                $seen[$task['key']] = true;
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    private function section(string $kind, array $tasks, string $difficulty): array
    {
        [$mm, $deg] = TriangleGenerator::toleranceText($difficulty);

        return ['area' => $kind, 'title' => self::TITLES[$kind], 'instruction' => str_replace(['{0}', '{1}'], [$mm, $deg], self::INSTRUCTION), 'tasks' => $tasks];
    }

    private function difficulty(string $difficulty): string
    {
        return \in_array($difficulty, TriangleGenerator::LEVELS, true) ? $difficulty : 'medium';
    }
}
