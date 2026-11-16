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
 * A test type ("Testtyp") builds the tasks of one participation.
 *
 * To add a new test type, implement this interface in any bundle. Services
 * implementing it are tagged automatically and show up in the backend.
 */
interface TestTypeInterface
{
    /**
     * Unique alias, stored in tl_st_test.type and used as palette name.
     */
    public function getAlias(): string;

    /**
     * Label shown in the backend select menu.
     */
    public function getLabel(): string;

    /**
     * The type specific part of the tl_st_test palette, e.g. "{config_legend},difficulty,tasksPerArea".
     * The fields must exist in the tl_st_test DCA.
     */
    public function getPaletteFields(): string;

    /**
     * Generate the tasks for one student.
     *
     * @param array $test the tl_st_test record
     *
     * @return list<array{area: string, title: string, instruction: string, tasks: list<array>}>
     */
    public function generate(array $test): array;
}
