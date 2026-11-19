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

use Markocupic\ContaoSchoolTest\Task\Geometry\SymmetryTexts;
use Markocupic\ContaoSchoolTest\Task\Geometry\TransformGenerator;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Spiegeln, Drehen, Verschieben: the student places the image points on squared paper.
 * Every correctly placed image point is worth one point.
 */
final class GeometryTransformTestType implements PracticeTestTypeInterface
{
    public const ALIAS = 'geometry_transform';

    public const TITLES = [
        'achse' => 'Achsenspiegelung',
        'punkt' => 'Punktspiegelung',
        'drehung' => 'Drehung',
        'verschiebung' => 'Verschiebung',
        'achse_ggb' => 'Achsenspiegelung mit GeoGebra',
        'punkt_ggb' => 'Punktspiegelung mit GeoGebra',
        'symmetrie' => SymmetryTexts::TITLE,
        'gemischt' => 'Alle Bereiche gemischt',
    ];

    /**
     * %s: allowed deviation in mm, depends on the difficulty.
     */
    public const INSTRUCTION_GGB = 'Konstruiere die Bildpunkte mit den Werkzeugen von GeoGebra. Wähle zuerst den Bildpunkt (A′, B′, …) und klicke dann in der Zeichnung auf die Stelle, am besten auf einen Schnittpunkt. Bis %s mm Abweichung gilt als richtig. Verbinde zum Schluss die Bildpunkte mit Strecken zur Bildfigur, das gibt einen Punkt mehr.';

    public const INSTRUCTION = 'Setze die Bildpunkte auf die Gitterpunkte. Ein Klick auf einen gesetzten Punkt entfernt ihn.';

    public const TASKS_PER_KIND = [1, 2, 3, 4, 5];

    private readonly TransformGenerator $generator;

    public function __construct()
    {
        $this->generator = new TransformGenerator();
    }

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function getLabel(): string
    {
        return 'Spiegeln, Drehen, Verschieben (Achsen- und Punktspiegelung, Drehung, Verschiebung, Symmetrie)';
    }

    public function getPaletteFields(): string
    {
        return '{config_legend},difficulty,transformKinds,tasksPerKind';
    }

    public function generate(array $test): array
    {
        $difficulty = $this->difficulty((string) ($test['difficulty'] ?? 'medium'));
        $kinds = $this->kinds($test['transformKinds'] ?? null);
        $count = max(1, min(10, (int) ($test['tasksPerKind'] ?? 2)));
        $sections = [];

        foreach ($kinds as $kind) {
            $sections[] = $this->section($kind, $this->many($kind, $difficulty, $count), $difficulty);
        }

        return $sections;
    }

    public function getPracticeLabels(): array
    {
        return [
            'de' => 'Spiegeln, Drehen, Verschieben',
            'fr' => 'Symétries, rotations, translations',
            'it' => 'Simmetrie, rotazioni, traslazioni',
            'en' => 'Reflections, rotations, translations',
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
     * Four tasks of one kind, or one of each kind ("gemischt").
     */
    public function generatePractice(string $topic, string $difficulty): array
    {
        $difficulty = $this->difficulty($difficulty);

        if ('gemischt' === $topic) {
            $tasks = array_map(fn (string $kind): array => $this->generator->build($kind, $difficulty), Util::shuffle(['achse', 'punkt', 'drehung', 'verschiebung']));

            return [$this->section('gemischt', $tasks)];
        }

        $topic = TransformGenerator::LEGACY_KINDS[$topic] ?? $topic;
        $kind = \in_array($topic, TransformGenerator::KINDS, true) ? $topic : 'achse';

        // Constructions take longer: two per round, symmetry cards three
        $count = 'symmetrie' === $kind ? 3 : (isset(TransformGenerator::FREE_KINDS[$kind]) ? 2 : 4);

        return [$this->section($kind, $this->many($kind, $difficulty, $count), $difficulty)];
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

    private function section(string $kind, array $tasks, string $difficulty = 'medium'): array
    {
        $instruction = match (true) {
            'symmetrie' === $kind => SymmetryTexts::INSTRUCTION,
            isset(TransformGenerator::FREE_KINDS[$kind]) => sprintf(self::INSTRUCTION_GGB, TransformGenerator::toleranceMm($difficulty)),
            default => self::INSTRUCTION,
        };

        return ['area' => $kind, 'title' => self::TITLES[$kind], 'instruction' => $instruction, 'tasks' => $tasks];
    }

    private function difficulty(string $difficulty): string
    {
        return \in_array($difficulty, TransformGenerator::LEVELS, true) ? $difficulty : 'medium';
    }

    /**
     * Selected kinds from the backend (serialized array), all if nothing is selected.
     */
    private function kinds(mixed $value): array
    {
        if (\is_string($value) && '' !== $value) {
            $value = @unserialize($value, ['allowed_classes' => false]);
        }

        // Earlier constructions without squares are now the GeoGebra ones
        $value = \is_array($value) ? array_map(static fn (mixed $k): mixed => TransformGenerator::LEGACY_KINDS[$k] ?? $k, $value) : $value;
        $kinds = \is_array($value) ? array_values(array_intersect(TransformGenerator::KINDS, $value)) : [];

        // Nothing selected: all kinds except the GeoGebra variants (they load GeoGebra from outside)
        return $kinds ?: array_values(array_diff(TransformGenerator::KINDS, TransformGenerator::GGB_KINDS));
    }

    public function getPracticeArea(): string
    {
        return 'geometrie';
    }

    public function hasPracticeDifficulty(): bool
    {
        return true;
    }
}
