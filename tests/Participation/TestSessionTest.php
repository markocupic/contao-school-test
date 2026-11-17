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

namespace Markocupic\ContaoSchoolTest\Tests\Participation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Markocupic\ContaoSchoolTest\Grading\GradeCalculator;
use Markocupic\ContaoSchoolTest\Participation\CodeGenerator;
use Markocupic\ContaoSchoolTest\Participation\ParticipationSynchronizer;
use Markocupic\ContaoSchoolTest\Participation\TestSession;
use Markocupic\ContaoSchoolTest\Task\AnswerChecker;
use Markocupic\ContaoSchoolTest\Task\Evaluator;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\Task\Util;
use Markocupic\ContaoSchoolTest\TestType\MentalArithmeticTestType;
use Markocupic\ContaoSchoolTest\TestType\TestTypeRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Runs the whole student flow against an in-memory SQLite database (needs pdo_sqlite).
 */
class TestSessionTest extends TestCase
{
    public function testStudentFlow(): void
    {
        $c = $this->createDatabase();
        $sync = new ParticipationSynchronizer($c, new CodeGenerator($c));
        $session = new TestSession($c, new TestTypeRegistry([new MentalArithmeticTestType()]), new TaskPresenter(), new Evaluator(new AnswerChecker()), new GradeCalculator());

        $this->assertSame(4, $sync->sync(1));
        $this->assertSame(0, $sync->sync(1));

        $codes = $c->fetchFirstColumn('SELECT code FROM tl_st_participation ORDER BY studentName');
        $code = $codes[0];

        $this->assertSame('error', $session->start('99999')['state']);
        $this->assertNull($session->sebSettings('99999'));
        $this->assertSame(['only' => false, 'configKey' => ''], $session->sebSettings($code));
        $c->update('tl_st_test', ['acceptSebBrowserOnly' => 1, 'sebConfigKey' => str_repeat('a', 64)], ['id' => 1]);
        $this->assertSame(['only' => true, 'configKey' => str_repeat('a', 64)], $session->sebSettings($code));
        $c->update('tl_st_test', ['acceptSebBrowserOnly' => 0, 'sebConfigKey' => ''], ['id' => 1]);

        $r = $session->start($code);
        $this->assertSame('running', $r['state']);
        $this->assertSame(1800, $r['remaining']);
        $this->assertSame('Kopfrechnen (Brüche)', $r['test'], 'Contao input encoding is decoded for the browser');
        $fr = $session->start($codes[1], 'fr');
        $this->assertSame('Muster Léa', $fr['student']);
        $this->assertSame('Calcul mental jusqu’à 1000', $fr['sections'][0]['title'], 'the student sees the chosen language');
        $this->assertSame('fr', $c->fetchOne('SELECT lang FROM tl_st_participation WHERE code = ?', [$codes[1]]));
        $this->assertDoesNotMatchRegularExpression('/"(v|show|way|estimate|tol|rel)":/', json_encode($r), 'no solutions in the browser payload');

        $answers = $this->correctAnswers($c, $code);
        $answers['s0t0'] = '99999';

        $this->assertSame('saved', $session->save($code, $r['token'], $answers)['state']);
        $this->assertSame('locked', $session->save($code, 'wrong', $answers)['state']);
        $this->assertSame('locked', $session->lock($code, $r['token'], 'hidden', $answers)['state']);
        $this->assertSame('resume_required', $session->start($code)['state']);
        $this->assertSame('locked', $session->save($code, $r['token'], $answers)['state'], 'old token is invalid after a lock');
        $this->assertSame('error', $session->resume($code, 'XXXXXX')['state']);

        $r = $session->resume($code, 'abc234');
        $this->assertSame('running', $r['state']);
        $this->assertSame('99999', $r['answers']['s0t0']);

        // Reload: with the token of this tab the test goes on, without it the resume code is needed
        $this->assertSame('running', $session->continue($code, $r['token'], 'it')['state']);
        $this->assertSame('it', $c->fetchOne('SELECT lang FROM tl_st_participation WHERE code = ?', [$code]));
        $this->assertSame('99999', $session->continue($code, $r['token'])['answers']['s0t0']);
        $this->assertSame('resume_required', $session->continue($code, 'wrong')['state']);
        $this->assertSame('error', $session->continue('99999', $r['token'])['state']);

        // Without lock on leave a reload (pagehide) keeps the test running
        $c->update('tl_st_test', ['lockOnLeave' => 0], ['id' => 1]);
        $this->assertSame('running', $session->lock($code, $r['token'], 'pagehide', $answers)['state']);
        $this->assertSame('running', $session->continue($code, $r['token'])['state']);
        $c->update('tl_st_test', ['lockOnLeave' => 1], ['id' => 1]);
        // With lock on leave the reload locks it
        $this->assertSame('locked', $session->lock($code, $r['token'], 'pagehide', $answers)['state']);
        $this->assertSame('resume_required', $session->continue($code, $r['token'])['state']);
        $r = $session->resume($code, 'abc234');

        $f = $session->submit($code, $r['token'], $answers);
        $this->assertSame('finished', $f['state']);
        $this->assertSame($f['max'] - 1, $f['points']);
        $this->assertSame('finished', $session->start($code)['state'], 'a finished test cannot be repeated');

        // Time is up: the next request hands the test in
        $r2 = $session->start($codes[2]);
        $tasksOf = static fn (string $c2) => $c->fetchOne('SELECT tasks FROM tl_st_participation WHERE code = ?', [$c2]);
        $this->assertSame($tasksOf($code), $tasksOf($codes[2]), 'all students get the same tasks');
        $c->executeStatement('UPDATE tl_st_participation SET runningSince = runningSince - 1900 WHERE code = ?', [$codes[2]]);
        $x = $session->save($codes[2], $r2['token'], ['s0t0' => '1']);
        $this->assertSame('finished', $x['state']);
        $this->assertTrue($x['timeUp']);
        $this->assertSame(1800, (int) $c->fetchOne('SELECT usedSeconds FROM tl_st_participation WHERE code = ?', [$codes[2]]));

        // Unpublished tests cannot be started
        $c->update('tl_st_test', ['published' => 0], ['id' => 1]);
        $this->assertSame('error', $session->start($codes[3])['state']);
    }

    private function correctAnswers(Connection $c, string $code): array
    {
        $sections = json_decode((string) $c->fetchOne('SELECT tasks FROM tl_st_participation WHERE code = ?', [$code]), true);
        $answers = [];

        foreach ($sections as $si => $section) {
            foreach ($section['tasks'] as $ti => $t) {
                $a = $t['a'];
                $k = TaskPresenter::key($si, $ti);

                if ('num' === $a['type']) {
                    $answers[$k] = Util::fmt($a['v']);
                } elseif ('frac' === $a['type']) {
                    $answers[$k] = ['n' => (string) $a['n'], 'd' => (string) $a['d']];
                } else {
                    foreach ($a['cols'] as $j => $col) {
                        if (isset($col['ask'])) {
                            $answers[$k][$j] = Util::fmt($col['v']);
                        }
                    }
                }
            }
        }

        return $answers;
    }

    private function createDatabase(): Connection
    {
        $c = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $c->executeStatement('CREATE TABLE tl_st_student (id INTEGER PRIMARY KEY, pid INT, tstamp INT DEFAULT 0, firstname TEXT, lastname TEXT)');
        $c->executeStatement("CREATE TABLE tl_st_test (id INTEGER PRIMARY KEY, tstamp INT DEFAULT 0, title TEXT, type TEXT, class INT, difficulty TEXT, tasksPerArea INT, gradeStep TEXT, pointsForSix INT DEFAULT 0, timeLimit INT DEFAULT 0, lockOnLeave INT DEFAULT 1, acceptSebBrowserOnly INT DEFAULT 0, sebConfigKey TEXT DEFAULT '', resumeCode TEXT, codeSheetNote TEXT DEFAULT '', published INT DEFAULT 0, sameTasks INT DEFAULT 1, tasks TEXT)");
        $c->executeStatement("CREATE TABLE tl_st_participation (id INTEGER PRIMARY KEY, pid INT, tstamp INT DEFAULT 0, student INT, studentName TEXT DEFAULT '', code TEXT UNIQUE, status TEXT DEFAULT 'open', token TEXT DEFAULT '', tasks TEXT, answers TEXT, points INT DEFAULT 0, maxPoints INT DEFAULT 0, grade TEXT DEFAULT '', lockCount INT DEFAULT 0, runningSince INT DEFAULT 0, usedSeconds INT DEFAULT 0, lang TEXT DEFAULT 'de', startedAt INT DEFAULT 0, finishedAt INT DEFAULT 0, log TEXT)");

        foreach ([['L&eacute;a', 'Muster'], ['X', 'Y&#40;2&#41;'], ['Noah', 'Beispiel'], ['Mia', 'Test']] as [$first, $last]) {
            $c->insert('tl_st_student', ['pid' => 1, 'firstname' => $first, 'lastname' => $last]);
        }

        $c->insert('tl_st_test', ['id' => 1, 'title' => 'Kopfrechnen &#40;Br&uuml;che&#41;', 'type' => MentalArithmeticTestType::ALIAS, 'class' => 1, 'difficulty' => 'medium', 'tasksPerArea' => 6, 'gradeStep' => '0.5', 'timeLimit' => 30, 'lockOnLeave' => 1, 'resumeCode' => 'ABC234', 'published' => 1]);

        return $c;
    }
}
