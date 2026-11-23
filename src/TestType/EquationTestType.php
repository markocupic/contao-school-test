<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\TestType;

use Markocupic\ContaoSchoolTest\Task\Equation\EquationGenerator;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Lineare Gleichungen in zehn Stufen. The student types the solution for x
 * (whole number or fraction like -3/4). Points per equation: 1 (levels 1–3),
 * 2 (levels 4–7) or 3 (levels 8–10).
 */
final class EquationTestType implements PracticeTestTypeInterface, TaskKindsInterface
{
    public const ALIAS = 'equations';

    public const INSTRUCTION = 'Löse nach x auf. Schreibe Brüche als 3/4 und negative Zahlen mit Minus, zum Beispiel -3/4.';

    /**
     * Practice topics: alias => [title, levels].
     */
    public const TOPICS = [
        'grundform' => ['Grundform', [1, 2]],
        'beidseitig' => ['x auf beiden Seiten', [3, 4]],
        'klammern' => ['Klammern auflösen', [5]],
        'brueche' => ['Gleichungen mit Brüchen', [6, 7, 8]],
        'binome' => ['Binome', [9, 10]],
        'gemischt' => ['Alle Bereiche gemischt', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]],
    ];

    private readonly EquationGenerator $generator;

    public function __construct()
    {
        $this->generator = new EquationGenerator();
    }

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function getLabel(): string
    {
        return 'Lineare Gleichungen (Stufe 1 bis 10)';
    }

    public function getPaletteFields(): string
    {
        return '{config_legend},difficulty,taskCounts';
    }

    public function generate(array $test): array
    {
        $difficulty = $this->difficulty((string) ($test['difficulty'] ?? 'medium'));
        $sections = [];

        foreach (TaskCounts::rows($test, $this) as $level => $count) {
            $level = (int) $level;
            $tasks = [];
            $seen = [];

            for ($i = 0; \count($tasks) < $count && $i < $count * 30; ++$i) {
                $task = $this->generator->build($level, $difficulty);

                if (!isset($seen[$task['key']])) {
                    $seen[$task['key']] = true;
                    $tasks[] = $task;
                }
            }

            $sections[] = ['area' => 'level'.$level, 'title' => EquationGenerator::LEVELS[$level], 'instruction' => self::INSTRUCTION, 'tasks' => $tasks];
        }

        return $sections;
    }

    public function getTaskKinds(): array
    {
        $labels = [];

        foreach (EquationGenerator::LEVELS as $level => $title) {
            $labels[$level] = 'Stufe '.$level.': '.$title;
        }

        return TaskCounts::labels('equationLevelsOptions', $labels);
    }

    /**
     * Levels 1 to 5.
     */
    public function getDefaultTaskCounts(): array
    {
        return array_fill_keys([1, 2, 3, 4, 5], 2);
    }

    public function getMaxTaskCount(string|int $kind): int
    {
        return 10;
    }

    public function getPracticeLabels(): array
    {
        return ['de' => 'Lineare Gleichungen', 'fr' => 'Équations linéaires', 'it' => 'Equazioni lineari', 'en' => 'Linear equations'];
    }

    public function getPracticeTopics(): array
    {
        $translator = new TaskTranslator();
        $topics = [];

        foreach (self::TOPICS as $alias => [$title]) {
            foreach (TaskTranslator::LANGUAGES as $lang) {
                $topics[$alias][$lang] = $translator->text($title, $lang);
            }
        }

        return $topics;
    }

    /**
     * Six equations, spread over the levels of the topic.
     */
    public function generatePractice(string $topic, string $difficulty): array
    {
        $topic = isset(self::TOPICS[$topic]) ? $topic : 'grundform';
        [$title, $levels] = self::TOPICS[$topic];
        $difficulty = $this->difficulty($difficulty);
        $levels = 'gemischt' === $topic ? \array_slice(Util::shuffle($levels), 0, 6) : $levels;
        sort($levels);
        $tasks = [];
        $seen = [];

        for ($i = 0; \count($tasks) < 6 && $i < 200; ++$i) {
            $level = $levels[intdiv(\count($tasks) * \count($levels), 6)] ?? end($levels);
            $task = $this->generator->build($level, $difficulty);

            if (!isset($seen[$task['key']])) {
                $seen[$task['key']] = true;
                $tasks[] = $task;
            }
        }

        return [['area' => $topic, 'title' => $title, 'instruction' => self::INSTRUCTION, 'tasks' => $tasks]];
    }

    public function getPracticeArea(): string
    {
        return 'algebra';
    }

    public function hasPracticeDifficulty(): bool
    {
        return true;
    }

    private function difficulty(string $difficulty): string
    {
        return \in_array($difficulty, self::DIFFICULTIES, true) ? $difficulty : 'medium';
    }
}
