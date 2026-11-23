<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\TestType;

use Markocupic\ContaoSchoolTest\Task\FigureArea\FigureAreaGenerator;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Flächenberechnung: triangle, rectangle and square, parallelogram and rhombus, trapezoid,
 * circle, composed figures, areas with Pythagoras and in the coordinate system, in three
 * levels, with sketches.
 * Every fourth task of a figure is a word problem; "Textaufgaben" has word problems only.
 */
final class FigureAreaTestType implements PracticeTestTypeInterface, TaskKindsInterface
{
    public const ALIAS = 'figure_areas';

    public const TITLES = [
        'dreieck' => 'Dreieck',
        'rechteck' => 'Rechteck und Quadrat',
        'parallelogramm' => 'Parallelogramm und Raute',
        'trapez' => 'Trapez',
        'kreis' => 'Kreis',
        'zusammengesetzt' => 'Zusammengesetzte Flächen',
        'pythagoras' => 'Flächen mit fehlenden Längen',
        'koordinaten' => 'Im Koordinatensystem',
        'text' => 'Textaufgaben',
        'gemischt' => 'Alle Bereiche gemischt',
    ];

    /**
     * Kinds selectable in the backend: the figures and the word problems.
     */
    public const KINDS = [...FigureAreaGenerator::KINDS, 'text'];

    public const INSTRUCTION = 'Berechne die gesuchten Grössen. Die Skizzen sind nicht massstabsgetreu. Runde Ergebnisse mit π oder mit Wurzeln auf eine Dezimale.';

    public const INSTRUCTION_WORD = 'Löse die Textaufgaben. Mache dir zuerst eine Skizze. Runde Ergebnisse mit π auf eine Dezimale.';

    private readonly FigureAreaGenerator $generator;

    public function __construct()
    {
        $this->generator = new FigureAreaGenerator();
    }

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function getLabel(): string
    {
        return 'Flächenberechnung (Dreieck, Rechteck, Parallelogramm, Trapez, Kreis, zusammengesetzte Flächen, Pythagoras, Koordinatensystem, Textaufgaben)';
    }

    public function getPaletteFields(): string
    {
        return '{config_legend},difficulty,taskCounts';
    }

    public function generate(array $test): array
    {
        $level = $this->level((string) ($test['difficulty'] ?? 'medium'));
        $sections = [];

        foreach (TaskCounts::rows($test, $this) as $kind => $count) {
            $sections[] = $this->section((string) $kind, $this->many((string) $kind, $level, $count));
        }

        return $sections;
    }

    public function getTaskKinds(): array
    {
        return TaskCounts::labels('areaKindsOptions', array_combine(self::KINDS, array_map(static fn (string $k): string => self::TITLES[$k] ?? $k, self::KINDS)));
    }

    public function getDefaultTaskCounts(): array
    {
        return array_fill_keys(array_keys($this->getTaskKinds()), 2);
    }

    public function getMaxTaskCount(string|int $kind): int
    {
        return 10;
    }

    public function getPracticeLabels(): array
    {
        return ['de' => 'Flächenberechnung', 'fr' => 'Calcul d’aires', 'it' => 'Calcolo delle aree', 'en' => 'Area calculation'];
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
     * Four tasks: three with a sketch and one word problem; "gemischt": four different figures.
     */
    public function generatePractice(string $topic, string $difficulty): array
    {
        $level = $this->level($difficulty);

        if ('gemischt' === $topic) {
            $kinds = \array_slice(Util::shuffle(FigureAreaGenerator::KINDS), 0, 4);

            return [$this->section('gemischt', array_map(fn (string $kind): array => $this->generator->build($kind, $level), $kinds))];
        }

        $kind = \in_array($topic, self::KINDS, true) ? $topic : 'rechteck';

        return [$this->section($kind, $this->many($kind, $level, 4))];
    }

    public function getPracticeArea(): string
    {
        return 'geometrie';
    }

    public function hasPracticeDifficulty(): bool
    {
        return true;
    }

    /**
     * Tasks of one kind without duplicates; every fourth one a word problem.
     * "text": word problems of different figures.
     */
    private function many(string $kind, string $level, int $count): array
    {
        $tasks = [];
        $seen = [];
        $figures = Util::shuffle(FigureAreaGenerator::WORD_KINDS);
        $this->generator->resetVariants();

        for ($i = 0; \count($tasks) < $count && $i < $count * 30; ++$i) {
            $n = \count($tasks);
            $task = 'text' === $kind
                ? $this->generator->build($figures[$n % \count($figures)], $level, true)
                : $this->generator->build($kind, $level, 3 === $n % 4 && \in_array($kind, FigureAreaGenerator::WORD_KINDS, true));

            if (!isset($seen[$task['key']])) {
                $seen[$task['key']] = true;
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    private function section(string $kind, array $tasks): array
    {
        return [
            'area' => $kind,
            'title' => self::TITLES[$kind],
            'instruction' => 'text' === $kind ? self::INSTRUCTION_WORD : self::INSTRUCTION,
            'tasks' => $tasks,
        ];
    }

    private function level(string $difficulty): string
    {
        return \in_array($difficulty, FigureAreaGenerator::LEVELS, true) ? $difficulty : 'medium';
    }
}
