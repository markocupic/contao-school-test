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

use Markocupic\ContaoSchoolTest\Controller\Backend\PrintController;

/*
 * Backend modules
 */
$GLOBALS['BE_MOD']['school_test'] = [
    'st_classes' => [
        'tables' => ['tl_st_class', 'tl_st_student'],
    ],
    'st_tests' => [
        'tables' => ['tl_st_test', 'tl_st_participation'],
        'codesheet' => [PrintController::class, 'codeSheet'],
        'evaluation' => [PrintController::class, 'evaluation'],
    ],
];
