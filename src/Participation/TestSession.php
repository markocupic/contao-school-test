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
use Markocupic\ContaoSchoolTest\Grading\GradeCalculator;
use Markocupic\ContaoSchoolTest\Task\Evaluator;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\TestType\TestTypeRegistry;

/**
 * The state machine behind the frontend test.
 *
 * With "same tasks" (default) the tasks are generated once per test, otherwise per student.
 *
 * open --start--> running --lock--> locked --resume(+teacher code)--> running --submit--> finished
 *
 * Entering the code of a running test again (reload, second device, crash) also
 * needs the resume code. Only the device holding the current token may save or submit.
 *
 * Time limit: only the time in status "running" counts (usedSeconds + now - runningSince).
 * When it is up, the next request hands the test in with the saved answers.
 */
final class TestSession
{
    /**
     * Seconds of tolerance for network delay when the time is up.
     */
    private const GRACE = 20;

    public function __construct(
        private readonly Connection $connection,
        private readonly TestTypeRegistry $testTypes,
        private readonly TaskPresenter $presenter,
        private readonly Evaluator $evaluator,
        private readonly GradeCalculator $gradeCalculator,
    ) {
    }

    public function start(string $code, string $lang = TaskTranslator::DEFAULT): array
    {
        $p = $this->findByCode($code);

        if (!$p) {
            return $this->error('invalid_code', 'Diesen Code gibt es nicht. Prüfe deine Eingabe.');
        }

        return match ($p['status']) {
            ParticipationStatus::FINISHED => $this->finishedPayload($p),
            ParticipationStatus::RUNNING, ParticipationStatus::LOCKED => [
                'state' => 'resume_required',
                'student' => $p['studentName'],
                'test' => $p['testTitle'],
            ],
            default => $this->begin($p, $lang),
        };
    }

    public function resume(string $code, string $resumeCode, string $lang = TaskTranslator::DEFAULT): array
    {
        $p = $this->findByCode($code);

        if (!$p) {
            return $this->error('invalid_code', 'Diesen Code gibt es nicht. Prüfe deine Eingabe.');
        }

        if (ParticipationStatus::FINISHED === $p['status']) {
            return $this->finishedPayload($p);
        }

        if (!\in_array($p['status'], [ParticipationStatus::RUNNING, ParticipationStatus::LOCKED], true)) {
            return $this->start($code, $lang);
        }

        $expected = strtoupper(trim((string) $p['resumeCode']));

        if ('' === $expected || !hash_equals($expected, CodeGenerator::normalizeResumeCode($resumeCode) ?? '')) {
            return $this->error('invalid_resume_code', 'Der Wiederaufnahme-Code stimmt nicht.');
        }

        // Time used so far (a crashed device without lock keeps counting until now)
        $p['usedSeconds'] = $this->elapsed($p);

        if (null !== ($left = $this->remaining($p)) && $left <= 0) {
            return $this->finalize($p, $this->decode($p['answers']), 'time_up');
        }

        $token = $this->newToken();
        $p['runningSince'] = time();

        $this->connection->update('tl_st_participation', [
            'status' => ParticipationStatus::RUNNING,
            'token' => $token,
            'usedSeconds' => $p['usedSeconds'],
            'runningSince' => $p['runningSince'],
            'lang' => $lang,
            'tstamp' => time(),
            'log' => $this->appendLog($p['log'], 'resumed:'.$lang),
        ], ['id' => $p['id']]);
        $p['status'] = ParticipationStatus::RUNNING;

        return $this->runningPayload($p, $token, $this->decode($p['answers']), $lang);
    }

    public function save(string $code, string $token, mixed $answers): array
    {
        $p = $this->findRunning($code, $token);

        if (!$p) {
            return $this->notRunning($code);
        }

        $sections = $this->decode($p['tasks']);
        $clean = $this->evaluator->sanitizeAnswers($sections, $answers);

        if ($this->isTimeUp($p)) {
            return $this->finalize($p, $clean, 'time_up');
        }

        $this->connection->update('tl_st_participation', [
            'answers' => json_encode($clean, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'tstamp' => time(),
        ], ['id' => $p['id']]);

        return ['state' => 'saved', 'remaining' => $this->remaining($p)];
    }

    /**
     * Called when the student leaves the page (tab change, window blur, reload).
     */
    public function lock(string $code, string $token, string $reason, mixed $answers = null): array
    {
        $p = $this->findRunning($code, $token);

        if (!$p) {
            return $this->notRunning($code);
        }

        $reason = preg_replace('/[^a-z_]/', '', strtolower($reason)) ?: 'left';

        // The browser sends the latest answers together with the lock, so nothing typed is lost
        if (\is_array($answers)) {
            $p['answers'] = json_encode($this->evaluator->sanitizeAnswers($this->decode($p['tasks']), $answers), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $this->connection->update('tl_st_participation', ['answers' => $p['answers']], ['id' => $p['id']]);
        }

        if ($this->isTimeUp($p)) {
            return $this->finalize($p, $this->decode($p['answers']), 'time_up');
        }

        if (!$p['lockOnLeave']) {
            $this->connection->update('tl_st_participation', ['log' => $this->appendLog($p['log'], 'left:'.$reason)], ['id' => $p['id']]);

            return ['state' => 'running'];
        }

        $this->connection->update('tl_st_participation', [
            'status' => ParticipationStatus::LOCKED,
            'token' => '',
            'lockCount' => (int) $p['lockCount'] + 1,
            'usedSeconds' => $this->elapsed($p),
            'runningSince' => 0,
            'tstamp' => time(),
            'log' => $this->appendLog($p['log'], 'locked:'.$reason),
        ], ['id' => $p['id']]);

        return ['state' => 'locked'];
    }

    public function submit(string $code, string $token, mixed $answers): array
    {
        $p = $this->findRunning($code, $token);

        if (!$p) {
            return $this->notRunning($code);
        }

        return $this->finalize($p, $this->evaluator->sanitizeAnswers($this->decode($p['tasks']), $answers), $this->isTimeUp($p) ? 'time_up' : 'submitted');
    }

    /**
     * Grade and close the test.
     */
    private function finalize(array $p, array $answers, string $event): array
    {
        $sections = $this->decode($p['tasks']);
        $result = $this->evaluator->evaluate($sections, $answers);
        $grade = $this->gradeCalculator->calculate($result['points'], $result['max'], (int) $p['pointsForSix'], (float) $p['gradeStep']);
        $used = $this->elapsed($p);

        if ((int) $p['timeLimit'] > 0) {
            $used = min($used, (int) $p['timeLimit'] * 60);
        }

        $this->connection->update('tl_st_participation', [
            'status' => ParticipationStatus::FINISHED,
            'token' => '',
            'answers' => json_encode($answers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'points' => $result['points'],
            'maxPoints' => $result['max'],
            'grade' => (string) $grade,
            'usedSeconds' => $used,
            'runningSince' => 0,
            'finishedAt' => time(),
            'tstamp' => time(),
            'log' => $this->appendLog($p['log'], $event),
        ], ['id' => $p['id']]);

        return [
            'state' => 'finished',
            'student' => $p['studentName'],
            'test' => $p['testTitle'],
            'points' => $result['points'],
            'max' => $result['max'],
            'timeUp' => 'time_up' === $event,
        ];
    }

    /**
     * Seconds spent in status "running".
     */
    private function elapsed(array $p): int
    {
        $used = (int) $p['usedSeconds'];

        if (ParticipationStatus::RUNNING === $p['status'] && (int) $p['runningSince'] > 0) {
            $used += max(0, time() - (int) $p['runningSince']);
        }

        return $used;
    }

    /**
     * Seconds left, null without time limit.
     */
    private function remaining(array $p): int|null
    {
        if ((int) $p['timeLimit'] <= 0) {
            return null;
        }

        return (int) $p['timeLimit'] * 60 - $this->elapsed($p);
    }

    private function isTimeUp(array $p): bool
    {
        $left = $this->remaining($p);

        return null !== $left && $left < -self::GRACE;
    }

    private function begin(array $p, string $lang): array
    {
        $sections = $this->decode($p['tasks']);

        if (!$sections) {
            $test = $this->connection->fetchAssociative('SELECT * FROM tl_st_test WHERE id = ?', [$p['pid']]);

            if (!$this->testTypes->has((string) $test['type'])) {
                return $this->error('unknown_type', 'Dieser Test kann nicht gestartet werden. Melde dich bei deiner Lehrperson.');
            }

            $p['tasks'] = $test['sameTasks'] ? $this->sharedTasks($test) : $this->generateTasks($test);
        }

        $token = $this->newToken();
        $p['status'] = ParticipationStatus::RUNNING;
        $p['runningSince'] = time();
        $p['usedSeconds'] = 0;

        $this->connection->update('tl_st_participation', [
            'status' => ParticipationStatus::RUNNING,
            'token' => $token,
            'tasks' => $p['tasks'],
            'runningSince' => $p['runningSince'],
            'usedSeconds' => 0,
            'lang' => $lang,
            'startedAt' => time(),
            'tstamp' => time(),
            'log' => $this->appendLog($p['log'], 'started:'.$lang),
        ], ['id' => $p['id']]);

        return $this->runningPayload($p, $token, $this->decode($p['answers']), $lang);
    }

    private function generateTasks(array $test): string
    {
        return json_encode($this->testTypes->get((string) $test['type'])->generate($test), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * All students of the test get the same tasks: generated by the first start and stored
     * on the test. Each participation keeps its own copy, so the evaluation never changes.
     */
    private function sharedTasks(array $test): string
    {
        if (!empty($test['tasks'])) {
            return (string) $test['tasks'];
        }

        // Only the first of several simultaneous starts wins
        $this->connection->executeStatement(
            "UPDATE tl_st_test SET tasks = ? WHERE id = ? AND (tasks IS NULL OR tasks = '')",
            [$this->generateTasks($test), $test['id']],
        );

        return (string) $this->connection->fetchOne('SELECT tasks FROM tl_st_test WHERE id = ?', [$test['id']]);
    }

    private function runningPayload(array $p, string $token, array $answers, string $lang): array
    {
        return [
            'state' => 'running',
            'token' => $token,
            'student' => $p['studentName'],
            'test' => $p['testTitle'],
            'lockOnLeave' => (bool) $p['lockOnLeave'],
            'timeLimit' => (int) $p['timeLimit'],
            'remaining' => $this->remaining($p),
            'lang' => $lang,
            'sections' => $this->presenter->toPublic($this->decode($p['tasks']), $lang),
            'answers' => $answers ?: new \stdClass(),
        ];
    }

    private function finishedPayload(array $p): array
    {
        return [
            'state' => 'finished',
            'student' => $p['studentName'],
            'test' => $p['testTitle'],
            'points' => (int) $p['points'],
            'max' => (int) $p['maxPoints'],
            'already' => true,
        ];
    }

    private function notRunning(string $code): array
    {
        $p = $this->findByCode($code);

        if ($p && ParticipationStatus::FINISHED === $p['status']) {
            return $this->finishedPayload($p);
        }

        return ['state' => 'locked'];
    }

    private function error(string $code, string $message): array
    {
        return ['state' => 'error', 'error' => $code, 'message' => $message];
    }

    private function findByCode(string $code): array|null
    {
        if (!preg_match('/^\d{5}$/', $code)) {
            return null;
        }

        $row = $this->connection->fetchAssociative(
            'SELECT p.*, t.title AS testTitle, t.resumeCode, t.lockOnLeave, t.gradeStep, t.pointsForSix, t.timeLimit
               FROM tl_st_participation p
               JOIN tl_st_test t ON t.id = p.pid
              WHERE p.code = ? AND t.published = 1',
            [$code],
        );

        if (!$row) {
            return null;
        }

        // Contao stores input HTML-encoded ("(" as "&#40;"); the browser escapes on output itself
        $row['studentName'] = self::plain((string) $row['studentName']);
        $row['testTitle'] = self::plain((string) $row['testTitle']);

        return $row;
    }

    private static function plain(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function findRunning(string $code, string $token): array|null
    {
        $p = $this->findByCode($code);

        if (!$p || ParticipationStatus::RUNNING !== $p['status'] || '' === $token || !hash_equals((string) $p['token'], $token)) {
            return null;
        }

        return $p;
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    private function decode(string|null $json): array
    {
        if (!$json) {
            return [];
        }

        $data = json_decode($json, true);

        return \is_array($data) ? $data : [];
    }

    private function appendLog(string|null $log, string $event): string
    {
        $entries = $this->decode($log);
        $entries[] = ['t' => time(), 'e' => $event];

        return json_encode(\array_slice($entries, -200), JSON_THROW_ON_ERROR);
    }
}
