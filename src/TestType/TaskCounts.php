<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\TestType;

/**
 * Reads tl_st_test.taskCounts: a list of rows [kind, count, difficulty] from the row wizard (Contao 5.7+).
 */
final class TaskCounts
{
    public const FIELD = 'taskCounts';

    public const DIFFICULTIES = ['easy', 'medium', 'hard'];

    /**
     * Kind => [count, difficulty], in the order of the rows. Unknown kinds and empty rows are
     * skipped, a kind listed twice gets the sum (and the first difficulty), counts are limited to
     * 1 … getMaxTaskCount(). Difficulty of a row: its own, else medium.
     * Without a valid row: the defaults of the type.
     *
     * @return array<string|int, array{count: int, difficulty: string}>
     */
    public static function rows(array $test, TaskKindsInterface $type): array
    {
        $kinds = self::keys($type->getTaskKinds());
        $fallback = 'medium';
        $rows = [];

        foreach (self::decode($test[self::FIELD] ?? null) as $row) {
            $kind = $row['kind'] ?? null;
            $count = (int) ($row['count'] ?? 0);

            if (!\is_scalar($kind) || !\array_key_exists((string) $kind, $kinds) || $count < 1) {
                continue;
            }

            $kind = $kinds[(string) $kind];
            $rows[$kind] = [
                'count' => min($type->getMaxTaskCount($kind), ($rows[$kind]['count'] ?? 0) + $count),
                'difficulty' => $rows[$kind]['difficulty'] ?? self::difficulty($row['difficulty'] ?? null, $fallback),
            ];
        }

        if (!$rows) {
            foreach ($type->getDefaultTaskCounts() as $kind => $count) {
                $rows[$kind] = ['count' => max(1, min($type->getMaxTaskCount($kind), $count)), 'difficulty' => $fallback];
            }
        }

        return $rows;
    }

    /**
     * Rows for the row wizard.
     *
     * @param array<string|int, int> $counts
     *
     * @return list<array{kind: string, count: string, difficulty: string}>
     */
    public static function toRows(array $counts, string $difficulty = 'medium'): array
    {
        $rows = [];

        foreach ($counts as $kind => $count) {
            $rows[] = ['kind' => (string) $kind, 'count' => (string) $count, 'difficulty' => $difficulty];
        }

        return $rows;
    }

    /**
     * @return list<array>
     */
    public static function decode(mixed $value): array
    {
        if (\is_string($value) && '' !== $value) {
            $value = @unserialize($value, ['allowed_classes' => false]);
        }

        return \is_array($value) ? array_values(array_filter($value, \is_array(...))) : [];
    }

    /**
     * Backend labels from tl_st_test.<$langKey> (e.g. areaKindsOptions), otherwise $fallback.
     *
     * @param array<string|int, string> $fallback kind => label
     *
     * @return array<string|int, string>
     */
    public static function labels(string $langKey, array $fallback): array
    {
        $labels = [];

        foreach ($fallback as $kind => $label) {
            $labels[$kind] = (string) ($GLOBALS['TL_LANG']['tl_st_test'][$langKey][$kind] ?? $label);
        }

        return $labels;
    }

    private static function difficulty(mixed $value, string $fallback): string
    {
        return \is_string($value) && \in_array($value, self::DIFFICULTIES, true) ? $value : $fallback;
    }

    /**
     * "1" and 1 are the same kind.
     *
     * @return array<string, string|int>
     */
    private static function keys(array $kinds): array
    {
        $keys = [];

        foreach (array_keys($kinds) as $kind) {
            $keys[(string) $kind] = $kind;
        }

        return $keys;
    }
}
