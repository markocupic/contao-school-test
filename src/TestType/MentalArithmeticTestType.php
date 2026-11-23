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

namespace Markocupic\ContaoSchoolTest\TestType;

use Markocupic\ContaoSchoolTest\Task\Area\AbstractArea;
use Markocupic\ContaoSchoolTest\Task\Area\ConversionArea;
use Markocupic\ContaoSchoolTest\Task\Area\EstimationArea;
use Markocupic\ContaoSchoolTest\Task\Area\FractionsArea;
use Markocupic\ContaoSchoolTest\Task\Area\MentalArithmeticArea;
use Markocupic\ContaoSchoolTest\Task\Area\PercentTableArea;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Kopfrechnungstest: all five areas (A–E), difficulty easy/medium/hard and a number of tasks per area.
 */
final class MentalArithmeticTestType implements PracticeTestTypeInterface, TaskKindsInterface
{
    public const ALIAS = 'mental_arithmetic';

    /**
     * Difficulty => the two levels (of six) used in every area.
     */
    public const DIFFICULTY_LEVELS = [
        'easy' => [1, 2],
        'medium' => [3, 4],
        'hard' => [5, 6],
    ];

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function getLabel(): string
    {
        return 'Kopfrechnungstest (Kopfrechnen, Brüche, Umwandeln, Prozent-Tabellen, Schätzen)';
    }

    public function getPaletteFields(): string
    {
        return '{config_legend},taskCounts';
    }

    /**
     * @return list<AbstractArea>
     */
    public function getAreas(): array
    {
        return [new MentalArithmeticArea(), new FractionsArea(), new ConversionArea(), new PercentTableArea(), new EstimationArea()];
    }

    public function generate(array $test): array
    {
        $areas = [];

        foreach ($this->getAreas() as $area) {
            $areas[$area->getAlias()] = $area;
        }

        $sections = [];

        // Percent tables: the number is the number of tables (6–7 fields each)
        foreach (TaskCounts::rows($test, $this) as $alias => ['count' => $count, 'difficulty' => $difficulty]) {
            $area = $areas[$alias];
            $levels = self::DIFFICULTY_LEVELS[$difficulty] ?? self::DIFFICULTY_LEVELS['medium'];

            if (1 === $count) {
                $tasks = $area->generateMany((int) Util::pick($levels), 1);
            } else {
                $half = (int) ceil($count / 2);
                $tasks = [...$area->generateMany($levels[0], $half), ...$area->generateMany($levels[1], $count - $half)];
            }

            $sections[] = [
                'area' => $area->getAlias(),
                'title' => $area->getTitle(),
                'instruction' => $area->getInstruction(),
                'tasks' => $tasks,
            ];
        }

        return $sections;
    }

    public function getTaskKinds(): array
    {
        $labels = [];

        foreach ($this->getAreas() as $area) {
            $labels[$area->getAlias()] = $area->getTitle().('pt' === $area->getAlias() ? ' (Anzahl Tabellen, höchstens 3)' : '');
        }

        return $labels;
    }

    /**
     * All five areas, 6 tasks each, one percent table.
     */
    public function getDefaultTaskCounts(): array
    {
        return ['kr' => 6, 'br' => 6, 'uw' => 6, 'pt' => 1, 'sz' => 6];
    }

    public function getMaxTaskCount(string|int $kind): int
    {
        return 'pt' === $kind ? 3 : 20;
    }

    public function hasTaskDifficulty(): bool
    {
        return true;
    }

    public function getPracticeLabels(): array
    {
        return [
            'de' => 'Kopfrechnen, Brüche und Prozente',
            'fr' => 'Calcul mental, fractions et pourcentages',
            'it' => 'Calcolo mentale, frazioni e percentuali',
            'en' => 'Mental arithmetic, fractions and percentages',
        ];
    }

    public function getPracticeTopics(): array
    {
        $translator = new TaskTranslator();
        $topics = [];

        foreach ($this->getAreas() as $area) {
            foreach (TaskTranslator::LANGUAGES as $lang) {
                $topics[$area->getAlias()][$lang] = $translator->text($area->getTitle(), $lang);
            }
        }

        $topics['all'] = ['de' => 'Alle Bereiche', 'fr' => 'Tous les domaines', 'it' => 'Tutti i campi', 'en' => 'All areas'];

        return $topics;
    }

    /**
     * One area: 10 tasks (percent tables: 2), half from each level of the difficulty.
     * All areas: 4 tasks per area, like a test.
     */
    public function generatePractice(string $topic, string $difficulty): array
    {
        $difficulty = \array_key_exists($difficulty, self::DIFFICULTY_LEVELS) ? $difficulty : 'medium';

        if ('all' === $topic) {
            return $this->generate([TaskCounts::FIELD => TaskCounts::toRows(['kr' => 4, 'br' => 4, 'uw' => 4, 'pt' => 1, 'sz' => 4], $difficulty)]);
        }

        [$low, $high] = self::DIFFICULTY_LEVELS[$difficulty];

        foreach ($this->getAreas() as $area) {
            if ($area->getAlias() !== $topic) {
                continue;
            }

            $count = 'pt' === $topic ? 2 : 10;
            $half = intdiv($count, 2);

            return [[
                'area' => $area->getAlias(),
                'title' => $area->getTitle(),
                'instruction' => $area->getInstruction(),
                'tasks' => [...$area->generateMany($low, $count - $half), ...$area->generateMany($high, $half)],
            ]];
        }

        return $this->generatePractice('kr', $difficulty);
    }

    public function getPracticeArea(): string
    {
        return 'arithmetik';
    }

    public function hasPracticeDifficulty(): bool
    {
        return true;
    }
}
