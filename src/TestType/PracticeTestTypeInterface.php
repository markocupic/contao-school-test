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

/**
 * A test type that can also be practised in the content element «Online-Test üben».
 *
 * In the frontend the student first picks the type, then one of its topics
 * and a difficulty (easy, medium, hard). Labels are needed in all frontend
 * languages: ['de' => …, 'fr' => …, 'it' => …, 'en' => …].
 */
interface PracticeTestTypeInterface extends TestTypeInterface
{
    public const DIFFICULTIES = ['easy', 'medium', 'hard'];

    /**
     * Name of the type in the practice picker, per language.
     *
     * @return array<string, string>
     */
    public function getPracticeLabels(): array;

    /**
     * Topics the student can choose (alias => labels per language), in display order.
     * Return an empty array if the type has no topics.
     *
     * @return array<string, array<string, string>>
     */
    public function getPracticeTopics(): array;

    /**
     * Tasks of one practice round, in the same format as generate().
     *
     * @param string $topic      one of the aliases from getPracticeTopics() (or '' without topics)
     * @param string $difficulty easy, medium or hard
     *
     * @return list<array{area: string, title: string, instruction: string, tasks: list<array>}>
     */
    public function generatePractice(string $topic, string $difficulty): array;

    /**
     * Whether the student chooses a difficulty (easy, medium, hard) for this type.
     */
    public function hasPracticeDifficulty(): bool;
}
