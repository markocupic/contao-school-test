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

use Markocupic\ContaoSchoolTest\Controller\ContentElement\SchoolTestController;
use Markocupic\ContaoSchoolTest\Controller\ContentElement\SchoolTestPracticeController;

// Test: no own settings, the test comes from the student's code. Practice: the choice is made in the frontend.
// Headline, template, CSS ID/classes, protection and visibility as with every content element.
$GLOBALS['TL_DCA']['tl_content']['palettes'][SchoolTestController::TYPE] = '{type_legend},type,headline;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
// Practice: optionally the space background over the whole page (own practice page)
$GLOBALS['TL_DCA']['tl_content']['palettes'][SchoolTestPracticeController::TYPE] = '{type_legend},type,headline;{config_legend},schoolTestPageStyle;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['schoolTestPageStyle'] = [
    'inputType' => 'checkbox',
    'exclude' => true,
    'eval' => ['tl_class' => 'clr'],
    'sql' => ['type' => 'boolean', 'default' => false],
];
