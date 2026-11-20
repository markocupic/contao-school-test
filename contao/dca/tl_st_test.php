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

use Contao\DataContainer;
use Contao\DC_Table;
use Markocupic\ContaoSchoolTest\Grading\GradeCalculator;
use Markocupic\ContaoSchoolTest\TestType\MentalArithmeticTestType;

/*
 * The palette of each test type is added by TestDcaListener (onload) from
 * TestTypeInterface::getPaletteFields().
 */
$GLOBALS['TL_DCA']['tl_st_test'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_st_participation'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['tstamp DESC'],
            'flag' => DataContainer::SORT_DAY_DESC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
            // label_callback in TestDcaListener adds class, status counts and resume code
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'children' => [
                'href' => 'table=tl_st_participation',
                'icon' => 'children.svg',
            ],
            'codesheet' => [
                'href' => 'key=codesheet',
                'icon' => 'bundles/markocupiccontaoschooltest/icons/codesheet.svg',
                'attributes' => 'target="_blank"',
            ],
            'evaluation' => [
                'href' => 'key=evaluation',
                'icon' => 'bundles/markocupiccontaoschooltest/icons/evaluation.svg',
                'attributes' => 'target="_blank"',
            ],
            'toggle' => [
                'href' => 'act=toggle&amp;field=published',
                'icon' => 'visible.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'data-action="contao--scroll-offset#store" onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        '__selector__' => ['type'],
        'default' => '{title_legend},title,subject,type',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'title' => [
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'subject' => [
            'inputType' => 'select',
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'default' => 'Mathe',
            'options' => ['Mathe', 'Deutsch', 'Französisch', 'Englisch', 'RZG', 'NT', 'WAH', 'LK', 'MINT', 'Medien und Informatik', 'Sport', 'Musik', 'Chor', 'TTG', 'BG'],
            'eval' => ['mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default 'Mathe'",
        ],
        'type' => [
            'inputType' => 'select',
            'exclude' => true,
            'filter' => true,
            'default' => MentalArithmeticTestType::ALIAS,
            // options from TestDcaListener
            'eval' => ['mandatory' => true, 'submitOnChange' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default '".MentalArithmeticTestType::ALIAS."'",
        ],
        'class' => [
            'inputType' => 'select',
            'exclude' => true,
            'filter' => true,
            'foreignKey' => 'tl_st_class.title',
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'difficulty' => [
            'inputType' => 'select',
            'exclude' => true,
            'filter' => true,
            'default' => 'medium',
            'options' => array_keys(MentalArithmeticTestType::DIFFICULTY_LEVELS),
            'reference' => &$GLOBALS['TL_LANG']['tl_st_test']['difficultyOptions'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'medium'",
        ],
        'tasksPerArea' => [
            'inputType' => 'select',
            'exclude' => true,
            'default' => 6,
            'options' => MentalArithmeticTestType::TASKS_PER_AREA,
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => 'smallint(5) unsigned NOT NULL default 6',
        ],
        'sameTasks' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'default' => true,
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => true],
        ],
        'tasks' => [
            // Shared tasks of all students, generated on the first start (see TestSession)
            'eval' => ['doNotCopy' => true],
            'sql' => 'mediumtext NULL',
        ],
        'transformKinds' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'options' => ['achse', 'punkt', 'drehung', 'verschiebung', 'achse_ggb', 'punkt_ggb', 'symmetrie'],
            'reference' => &$GLOBALS['TL_LANG']['tl_st_test']['transformKindsOptions'],
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
        ],
        'angleKinds' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'options' => ['scheitel', 'parallel', 'transversalen', 'dreieck', 'viereck', 'vieleck', 'figuren', 'kombi', 'profi'],
            'reference' => &$GLOBALS['TL_LANG']['tl_st_test']['angleKindsOptions'],
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
        ],
        'triangleKinds' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'options' => ['sss', 'sws', 'wsw', 'ssw', 'hoehe', 'winkelhalbierende', 'schwerelinie'],
            'reference' => &$GLOBALS['TL_LANG']['tl_st_test']['triangleKindsOptions'],
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
        ],
        'areaKinds' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'options' => ['dreieck', 'rechteck', 'parallelogramm', 'trapez', 'kreis', 'zusammengesetzt', 'pythagoras', 'koordinaten', 'text'],
            'reference' => &$GLOBALS['TL_LANG']['tl_st_test']['areaKindsOptions'],
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
        ],
        'binomialTopics' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'options' => ['rechteckfiguren', 'geschickt', 'rechteckmodell', 'ausmultiplizieren', 'faktorisieren'],
            'reference' => &$GLOBALS['TL_LANG']['tl_st_test']['binomialTopicsOptions'],
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
        ],
        'equationLevels' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'options' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'reference' => &$GLOBALS['TL_LANG']['tl_st_test']['equationLevelsOptions'],
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
        ],
        'tasksPerKind' => [
            'inputType' => 'select',
            'exclude' => true,
            'default' => 2,
            'options' => [1, 2, 3, 4, 5],
            'eval' => ['tl_class' => 'w50'],
            'sql' => 'smallint(5) unsigned NOT NULL default 2',
        ],
        'gradeStep' => [
            'inputType' => 'select',
            'exclude' => true,
            'default' => '0.5',
            'options' => GradeCalculator::STEPS,
            'reference' => &$GLOBALS['TL_LANG']['tl_st_test']['gradeStepOptions'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(8) NOT NULL default '0.5'",
        ],
        'pointsForSix' => [
            'inputType' => 'text',
            'exclude' => true,
            'default' => 0,
            'eval' => ['rgxp' => 'natural', 'maxlength' => 5, 'tl_class' => 'w50'],
            'sql' => 'smallint(5) unsigned NOT NULL default 0',
        ],
        'timeLimit' => [
            'inputType' => 'text',
            'exclude' => true,
            'default' => 0,
            'eval' => ['rgxp' => 'natural', 'maxlength' => 4, 'tl_class' => 'w50'],
            'sql' => 'smallint(5) unsigned NOT NULL default 0',
        ],
        'lockOnLeave' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'default' => true,
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => true],
        ],
        'acceptSebBrowserOnly' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            // New tests: only with the Safe Exam Browser; existing tests stay as they are
            'default' => true,
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'sebConfigKey' => [
            'inputType' => 'text',
            'exclude' => true,
            'eval' => ['maxlength' => 64, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'resumeCode' => [
            'inputType' => 'text',
            'exclude' => true,
            // Exactly 6 characters, generated by TestDcaListener if empty
            'eval' => ['minlength' => 6, 'maxlength' => 6, 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(6) NOT NULL default ''",
        ],
        'codeSheetNote' => [
            'inputType' => 'text',
            'exclude' => true,
            'eval' => ['maxlength' => 255, 'tl_class' => 'clr long'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'published' => [
            'inputType' => 'checkbox',
            'exclude' => true,
            'filter' => true,
            'toggle' => true,
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];
