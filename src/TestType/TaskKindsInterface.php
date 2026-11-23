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
 * A test type with several kinds of tasks (Bereiche, Abbildungen, Stufen …). In the backend the
 * teacher lists the kinds, the number of tasks and the difficulty of each in tl_st_test.taskCounts (row wizard);
 * every row becomes a section of the test. TaskCounts::rows() reads the field.
 */
interface TaskKindsInterface
{
    /**
     * All kinds of the type: alias => label in the backend, in the usual order.
     *
     * @return array<string|int, string>
     */
    public function getTaskKinds(): array;

    /**
     * The rows a new test starts with (and the fallback for an empty field): alias => number of tasks.
     *
     * @return array<string|int, int>
     */
    public function getDefaultTaskCounts(): array;

    /**
     * Most tasks of one kind in a test.
     */
    public function getMaxTaskCount(string|int $kind): int;

    /**
     * Whether the rows have a difficulty (easy, medium, hard) of their own.
     */
    public function hasTaskDifficulty(): bool;
}
