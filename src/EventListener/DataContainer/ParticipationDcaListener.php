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

namespace Markocupic\ContaoSchoolTest\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Markocupic\ContaoSchoolTest\Grading\GradeCalculator;
use Markocupic\ContaoSchoolTest\Participation\ParticipationStatus;

class ParticipationDcaListener
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    #[AsCallback(table: 'tl_st_participation', target: 'list.sorting.child_record')]
    public function childRecord(array $row): string
    {
        $status = $GLOBALS['TL_LANG']['tl_st_participation']['statusOptions'][$row['status']] ?? $row['status'];
        $colors = [
            ParticipationStatus::OPEN => '#777',
            ParticipationStatus::RUNNING => '#0a7cba',
            ParticipationStatus::LOCKED => '#c33',
            ParticipationStatus::FINISHED => '#2b8a3e',
        ];
        $result = ParticipationStatus::FINISHED === $row['status']
            ? \sprintf(' · %d/%d Punkte · Note %s', $row['points'], $row['maxPoints'], GradeCalculator::format($row['grade']))
            : '';
        $lang = !empty($row['lang']) && 'de' !== $row['lang'] ? ' · '.strtoupper((string) $row['lang']) : '';
        $locks = (int) $row['lockCount'] > 0 ? \sprintf(' · <span style="color:#c33">%d× gesperrt</span>', $row['lockCount']) : '';

        return \sprintf(
            '<div><strong>%s</strong> · Code <code>%s</code> · <span style="color:%s">%s</span>%s%s%s</div>',
            StringUtil::specialchars($row['studentName']),
            StringUtil::specialchars($row['code']),
            $colors[$row['status']] ?? '#777',
            StringUtil::specialchars($status),
            $result,
            $lang,
            $locks,
        );
    }

    /**
     * Setting the status back to "open" resets the test completely (new tasks on next start).
     */
    #[AsCallback(table: 'tl_st_participation', target: 'config.onsubmit')]
    public function onSubmit(DataContainer $dc): void
    {
        $status = $dc->activeRecord?->status;

        if (ParticipationStatus::OPEN === $status) {
            $this->connection->update('tl_st_participation', [
                'token' => '',
                'tasks' => null,
                'answers' => null,
                'points' => 0,
                'maxPoints' => 0,
                'grade' => '',
                'startedAt' => 0,
                'finishedAt' => 0,
                'runningSince' => 0,
                'usedSeconds' => 0,
            ], ['id' => $dc->id]);
        } elseif (ParticipationStatus::LOCKED === $status) {
            $this->connection->update('tl_st_participation', ['token' => ''], ['id' => $dc->id]);
        }
    }
}
