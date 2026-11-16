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
use Markocupic\ContaoSchoolTest\Participation\ParticipationStatus;

/*
 * One record per student and test. Created by the ParticipationSynchronizer,
 * never by hand. The teacher may only change the status (e.g. reset a test).
 */
$GLOBALS['TL_DCA']['tl_st_participation'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_st_test',
        'closed' => true,
        'notCopyable' => true,
        'notSortable' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'code' => 'unique',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['studentName'],
            'headerFields' => ['title', 'class', 'resumeCode', 'published'],
            'panelLayout' => 'filter;search,limit',
            'disableGrouping' => true,
        ],
        'label' => [
            'fields' => ['studentName', 'code', 'status'],
            'format' => '%s · Code %s · %s',
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
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
        'default' => '{student_legend},studentName,code;{status_legend},status;{result_legend},points,maxPoints,grade,lang,lockCount,usedSeconds,startedAt,finishedAt',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey' => 'tl_st_test.title',
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'student' => [
            'foreignKey' => 'tl_st_student.lastname',
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'studentName' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(160) NOT NULL default ''",
        ],
        'code' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(5) NOT NULL default ''",
        ],
        'status' => [
            'inputType' => 'select',
            'filter' => true,
            'options' => ParticipationStatus::ALL,
            'reference' => &$GLOBALS['TL_LANG']['tl_st_participation']['statusOptions'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'open'",
        ],
        'token' => [
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'tasks' => [
            'sql' => 'mediumtext NULL',
        ],
        'answers' => [
            'sql' => 'mediumtext NULL',
        ],
        'points' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => 'smallint(5) unsigned NOT NULL default 0',
        ],
        'maxPoints' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => 'smallint(5) unsigned NOT NULL default 0',
        ],
        'grade' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(8) NOT NULL default ''",
        ],
        'lang' => [
            'inputType' => 'select',
            'filter' => true,
            'options' => ['de', 'fr', 'it', 'en'],
            'reference' => &$GLOBALS['TL_LANG']['tl_st_participation']['langOptions'],
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(2) NOT NULL default 'de'",
        ],
        'lockCount' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => 'smallint(5) unsigned NOT NULL default 0',
        ],
        'runningSince' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'usedSeconds' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'startedAt' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'rgxp' => 'datim', 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'finishedAt' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'rgxp' => 'datim', 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'log' => [
            'sql' => 'text NULL',
        ],
    ],
];
