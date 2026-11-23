<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\TestType;

use Markocupic\ContaoSchoolTest\Task\Angle\AngleGenerator;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Winkel: angles at intersecting lines, at parallels, in triangles, quadrilaterals and
 * polygons. Every unknown angle is worth one point.
 */
final class AngleTestType implements PracticeTestTypeInterface, TaskKindsInterface
{
    public const ALIAS = 'angles';

    public const TITLES = [
        'scheitel' => 'Scheitel- und Nebenwinkel',
        'parallel' => 'Stufenwinkel an Parallelen',
        'transversalen' => 'Parallelen und zwei Geraden',
        'dreieck' => 'Dreieck',
        'viereck' => 'Viereck',
        'vieleck' => 'Vieleck',
        'figuren' => 'Figuren mit Diagonalen und Höhen',
        'kreis' => 'Winkel im Kreis (Peripherie- und Zentriwinkel)',
        'kombi' => 'Mehrere Winkel pro Aufgabe',
        'profi' => 'Profi: gleichschenklige und gleichseitige Dreiecke',
        'gemischt' => 'Alle Bereiche gemischt',
    ];

    public const INSTRUCTION = 'Berechne die gesuchten Winkel. Die Skizzen sind nicht massstabsgetreu: rechnen, nicht messen!';

    private readonly AngleGenerator $generator;

    public function __construct()
    {
        $this->generator = new AngleGenerator();
    }

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function getLabel(): string
    {
        return 'Winkel (Scheitel-, Neben- und Stufenwinkel, Dreieck, Viereck, Vieleck, Winkel im Kreis)';
    }

    public function getPaletteFields(): string
    {
        return '{config_legend},taskCounts';
    }

    public function generate(array $test): array
    {
        $sections = [];

        foreach (TaskCounts::rows($test, $this) as $kind => ['count' => $count]) {
            $sections[] = $this->section((string) $kind, $this->many((string) $kind, $count));
        }

        return $sections;
    }

    public function getTaskKinds(): array
    {
        return TaskCounts::labels('angleKindsOptions', array_combine(AngleGenerator::KINDS, array_map(static fn (string $k): string => self::TITLES[$k] ?? $k, AngleGenerator::KINDS)));
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
        return false;
    }

    public function getPracticeLabels(): array
    {
        return ['de' => 'Winkel', 'fr' => 'Angles', 'it' => 'Angoli', 'en' => 'Angles'];
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
     * Four tasks of one kind, or four of different kinds ("gemischt").
     */
    public function generatePractice(string $topic, string $difficulty): array
    {
        if ('gemischt' === $topic) {
            $kinds = \array_slice(Util::shuffle(AngleGenerator::KINDS), 0, 4);

            return [$this->section('gemischt', array_map(fn (string $kind): array => $this->generator->build($kind), $kinds))];
        }

        $kind = \in_array($topic, AngleGenerator::KINDS, true) ? $topic : 'scheitel';

        return [$this->section($kind, $this->many($kind, 4))];
    }

    public function getPracticeArea(): string
    {
        return 'geometrie';
    }

    public function hasPracticeDifficulty(): bool
    {
        return false;
    }

    private function many(string $kind, int $count): array
    {
        $tasks = [];
        $seen = [];

        for ($i = 0; \count($tasks) < $count && $i < $count * 20; ++$i) {
            $task = $this->generator->build($kind);

            if (!isset($seen[$task['key']])) {
                $seen[$task['key']] = true;
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    private function section(string $kind, array $tasks): array
    {
        return ['area' => $kind, 'title' => self::TITLES[$kind], 'instruction' => self::INSTRUCTION, 'tasks' => $tasks];
    }
}
