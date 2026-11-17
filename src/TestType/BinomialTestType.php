<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\TestType;

use Markocupic\ContaoSchoolTest\Task\Binomial\BinomialGenerator;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Produkte von Binomen: rectangle figures, calculating cleverly, the rectangle model,
 * multiplying out and factorising. Every field is worth one point.
 * Easy = basic requirements, hard = additional requirements, medium mixes both.
 */
final class BinomialTestType implements PracticeTestTypeInterface
{
    public const ALIAS = 'binomials';

    public const TITLES = [
        'rechteckfiguren' => 'Rechteckfiguren: Fläche und Umfang',
        'geschickt' => 'Geschickt rechnen',
        'rechteckmodell' => 'Terme am Rechteck',
        'ausmultiplizieren' => 'Ausmultiplizieren',
        'faktorisieren' => 'Produkt und Summe: faktorisieren',
        'gemischt' => 'Gemischt',
    ];

    /**
     * Instruction per section.
     */
    public const INSTRUCTIONS = [
        'rechteckfiguren' => 'Lies die Masse an der Figur ab. Hochzahlen schreibst du mit ^ oder mit der Taste x², zum Beispiel 4a^2 + 6ab.',
        'geschickt' => 'Rechne ohne Taschenrechner.',
        'rechteckmodell' => 'Hochzahlen schreibst du mit ^ oder mit der Taste x². Mal-Punkte darfst du weglassen, zum Beispiel (x + 3)(x + 5).',
        'ausmultiplizieren' => 'Hochzahlen schreibst du mit ^ oder mit der Taste x². Mal-Punkte darfst du weglassen, zum Beispiel 9x^2 − 12xy + 4y^2.',
        'faktorisieren' => 'Hochzahlen schreibst du mit ^ oder mit der Taste x². Mal-Punkte darfst du weglassen, zum Beispiel 2a(3a + b) oder (x + 3)(x − 5).',
        'gemischt' => 'Hochzahlen schreibst du mit ^ oder mit der Taste x². Mal-Punkte darfst du weglassen.',
    ];

    private readonly BinomialGenerator $generator;

    public function __construct()
    {
        $this->generator = new BinomialGenerator();
    }

    public function getAlias(): string
    {
        return self::ALIAS;
    }

    public function getLabel(): string
    {
        return 'Produkte von Binomen (Rechteckfiguren, geschickt rechnen, Rechteckmodell, ausmultiplizieren, faktorisieren)';
    }

    public function getPaletteFields(): string
    {
        return '{config_legend},difficulty,binomialTopics,tasksPerKind';
    }

    public function generate(array $test): array
    {
        $difficulty = $this->difficulty((string) ($test['difficulty'] ?? 'medium'));
        $count = max(1, min(10, (int) ($test['tasksPerKind'] ?? 2)));
        $sections = [];

        foreach ($this->kinds($test['binomialTopics'] ?? null) as $kind) {
            $sections[] = $this->section($kind, $this->many($kind, $difficulty, $count));
        }

        return $sections;
    }

    public function getPracticeLabels(): array
    {
        return [
            'de' => 'Produkte von Binomen',
            'fr' => 'Produits de binômes',
            'it' => 'Prodotti di binomi',
            'en' => 'Products of binomials',
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
     * Three tasks per round; "gemischt": three of different topics.
     */
    public function generatePractice(string $topic, string $difficulty): array
    {
        $difficulty = $this->difficulty($difficulty);

        if ('gemischt' === $topic) {
            $kinds = \array_slice(Util::shuffle(BinomialGenerator::TOPICS), 0, 3);

            return [$this->section('gemischt', array_map(fn (string $kind): array => $this->generator->build($kind, $difficulty), $kinds))];
        }

        $kind = \in_array($topic, BinomialGenerator::TOPICS, true) ? $topic : 'ausmultiplizieren';

        return [$this->section($kind, $this->many($kind, $difficulty, 3))];
    }

    public function getPracticeArea(): string
    {
        return 'algebra';
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

    private function section(string $kind, array $tasks): array
    {
        return ['area' => $kind, 'title' => self::TITLES[$kind], 'instruction' => self::INSTRUCTIONS[$kind], 'tasks' => $tasks];
    }

    private function difficulty(string $difficulty): string
    {
        return \in_array($difficulty, BinomialGenerator::LEVELS, true) ? $difficulty : 'medium';
    }

    /**
     * Selected topics from the backend (serialized array), all if nothing is selected.
     */
    private function kinds(mixed $value): array
    {
        if (\is_string($value) && '' !== $value) {
            $value = @unserialize($value, ['allowed_classes' => false]);
        }

        $kinds = \is_array($value) ? array_values(array_intersect(BinomialGenerator::TOPICS, $value)) : [];

        return $kinds ?: BinomialGenerator::TOPICS;
    }
}
