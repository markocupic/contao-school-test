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
 * Reads tl_st_test.taskCounts: a list of rows [kind, count] from the row wizard (Contao 5.7+).
 */
final class TaskCounts
{
    public const FIELD = 'taskCounts';

    /**
     * Kind => number of tasks, in the order of the rows. Unknown kinds and empty rows are skipped,
     * a kind listed twice gets the sum, counts are limited to 1 … getMaxTaskCount(). Without a
     * valid row: the defaults of the type.
     *
     * @return array<string|int, int>
     */
    public static function rows(array $test, TaskKindsInterface $type): array
    {
        $kinds = $type->getTaskKinds();
        $counts = [];

        foreach (self::decode($test[self::FIELD] ?? null) as $row) {
            $kind = $row['kind'] ?? null;
            $count = (int) ($row['count'] ?? 0);

            if (!\is_scalar($kind) || !\array_key_exists((string) $kind, self::keys($kinds)) || $count < 1) {
                continue;
            }

            $kind = self::keys($kinds)[(string) $kind];
            $counts[$kind] = min($type->getMaxTaskCount($kind), ($counts[$kind] ?? 0) + $count);
        }

        if (!$counts) {
            foreach ($type->getDefaultTaskCounts() as $kind => $count) {
                $counts[$kind] = max(1, min($type->getMaxTaskCount($kind), $count));
            }
        }

        return $counts;
    }

    /**
     * Rows for the row wizard.
     *
     * @param array<string|int, int> $counts
     *
     * @return list<array{kind: string, count: string}>
     */
    public static function toRows(array $counts): array
    {
        $rows = [];

        foreach ($counts as $kind => $count) {
            $rows[] = ['kind' => (string) $kind, 'count' => (string) $count];
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
