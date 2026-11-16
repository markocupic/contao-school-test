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

namespace Markocupic\ContaoSchoolTest\Participation;

use Doctrine\DBAL\Connection;

/**
 * Creates one participation (with a code) for every student of the test's class.
 * Participations of students who left the class are kept, results are never deleted.
 */
final class ParticipationSynchronizer
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CodeGenerator $codeGenerator,
    ) {
    }

    /**
     * @return int number of new participations
     */
    public function sync(int $testId): int
    {
        $classId = (int) $this->connection->fetchOne('SELECT class FROM tl_st_test WHERE id = ?', [$testId]);

        if (!$classId) {
            return 0;
        }

        $students = $this->connection->fetchAllAssociative('SELECT id, firstname, lastname FROM tl_st_student WHERE pid = ?', [$classId]);
        $existing = $this->connection->fetchAllKeyValue('SELECT student, id FROM tl_st_participation WHERE pid = ?', [$testId]);
        $created = 0;

        foreach ($students as $student) {
            $name = trim($student['lastname'].' '.$student['firstname']);

            if (isset($existing[$student['id']])) {
                $this->connection->update('tl_st_participation', ['studentName' => $name], ['id' => $existing[$student['id']]]);
                continue;
            }

            $this->connection->insert('tl_st_participation', [
                'pid' => $testId,
                'tstamp' => time(),
                'student' => $student['id'],
                'studentName' => $name,
                'code' => $this->codeGenerator->studentCode(),
                'status' => ParticipationStatus::OPEN,
            ]);

            ++$created;
        }

        return $created;
    }
}
