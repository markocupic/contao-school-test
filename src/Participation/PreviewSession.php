<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Participation;

use Markocupic\ContaoSchoolTest\Grading\GradeCalculator;
use Markocupic\ContaoSchoolTest\Task\Evaluator;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\TestType\TestTypeRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * A teacher tries a test from the backend: the same answers in and the same payloads out as
 * TestSession, but without a code and a participation. The attempt lives in the backend
 * session only; nothing is written to the database, there is no lock and no SEB check.
 *
 * Tasks: the stored tasks of the test (what the students get), otherwise newly generated ones.
 */
final class PreviewSession
{
    public const STUDENT = 'Vorschau';

    private const GRACE = 20;

    public function __construct(
        private readonly TestTypeRegistry $testTypes,
        private readonly TaskPresenter $presenter,
        private readonly Evaluator $evaluator,
        private readonly GradeCalculator $gradeCalculator,
    ) {
    }

    /**
     * Start a new attempt, or go on with the running one.
     */
    public function start(SessionInterface $session, array $test, string $lang = TaskTranslator::DEFAULT): array
    {
        $state = $this->get($session, $test);

        if ($state) {
            return $this->payload($session, $test, $state, $lang);
        }

        if (!$this->testTypes->has((string) $test['type'])) {
            return ['state' => 'error', 'error' => 'unknown_type', 'message' => 'Dieser Testtyp ist nicht installiert.'];
        }

        $state = [
            'tasks' => !empty($test['tasks']) ? (string) $test['tasks'] : json_encode($this->testTypes->get((string) $test['type'])->generate($test), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'answers' => [],
            'startedAt' => time(),
            'finished' => false,
            'lang' => $lang,
        ];
        $this->set($session, $test, $state);

        return $this->running($test, $state, $lang);
    }

    /**
     * Page reloaded: the running or finished attempt, "none" without one.
     */
    public function continue(SessionInterface $session, array $test, string $lang = TaskTranslator::DEFAULT): array
    {
        $state = $this->get($session, $test);

        return $state ? $this->payload($session, $test, $state, $lang) : ['state' => 'none'];
    }

    public function save(SessionInterface $session, array $test, mixed $answers): array
    {
        $state = $this->get($session, $test);

        if (!$state || $state['finished']) {
            return $state ? $this->finished($test, $state) : ['state' => 'none'];
        }

        $state['answers'] = $this->evaluator->sanitizeAnswers($this->decode($state['tasks']), $answers);

        if ($this->isTimeUp($test, $state)) {
            return $this->finalize($session, $test, $state, true);
        }

        $this->set($session, $test, $state);

        return ['state' => 'saved', 'remaining' => $this->remaining($test, $state)];
    }

    public function submit(SessionInterface $session, array $test, mixed $answers): array
    {
        $state = $this->get($session, $test);

        if (!$state) {
            return ['state' => 'none'];
        }

        if ($state['finished']) {
            return $this->finished($test, $state);
        }

        $state['answers'] = $this->evaluator->sanitizeAnswers($this->decode($state['tasks']), $answers);

        return $this->finalize($session, $test, $state, $this->isTimeUp($test, $state));
    }

    /**
     * Throw the attempt away and start again (new random tasks unless the test has stored ones).
     */
    public function restart(SessionInterface $session, array $test, string $lang = TaskTranslator::DEFAULT): array
    {
        $session->remove($this->key($test));

        return $this->start($session, $test, $lang);
    }

    /**
     * The finished attempt as a participation row for the evaluation sheet, null without one.
     */
    public function row(SessionInterface $session, array $test): array|null
    {
        $state = $this->get($session, $test);

        if (!$state) {
            return null;
        }

        return [
            'studentName' => self::STUDENT,
            'status' => $state['finished'] ? ParticipationStatus::FINISHED : ParticipationStatus::RUNNING,
            'tasks' => $state['tasks'],
            'answers' => json_encode($state['answers'] ?: new \stdClass(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'points' => $state['points'] ?? 0,
            'maxPoints' => $state['max'] ?? 0,
            'grade' => $state['grade'] ?? '',
            'usedSeconds' => $state['usedSeconds'] ?? 0,
            'finishedAt' => $state['finishedAt'] ?? 0,
            'lockCount' => 0,
            'lang' => $state['lang'] ?? TaskTranslator::DEFAULT,
        ];
    }

    private function payload(SessionInterface $session, array $test, array $state, string $lang): array
    {
        if ($state['finished']) {
            return $this->finished($test, $state);
        }

        if ($this->isTimeUp($test, $state)) {
            return $this->finalize($session, $test, $state, true);
        }

        $state['lang'] = $lang;
        $this->set($session, $test, $state);

        return $this->running($test, $state, $lang);
    }

    private function running(array $test, array $state, string $lang): array
    {
        return [
            'state' => 'running',
            'token' => 'preview',
            'student' => self::STUDENT,
            'test' => (string) $test['title'],
            'lockOnLeave' => false,
            'timeLimit' => (int) $test['timeLimit'],
            'remaining' => $this->remaining($test, $state),
            'lang' => $lang,
            'sections' => $this->presenter->toPublic($this->decode($state['tasks']), $lang),
            'answers' => $state['answers'] ?: new \stdClass(),
        ];
    }

    private function finalize(SessionInterface $session, array $test, array $state, bool $timeUp): array
    {
        $result = $this->evaluator->evaluate($this->decode($state['tasks']), $state['answers']);
        $used = time() - (int) $state['startedAt'];

        if ((int) $test['timeLimit'] > 0) {
            $used = min($used, (int) $test['timeLimit'] * 60);
        }

        $state = [
            ...$state,
            'finished' => true,
            'timeUp' => $timeUp,
            'points' => $result['points'],
            'max' => $result['max'],
            'grade' => (string) $this->gradeCalculator->calculate($result['points'], $result['max'], (int) $test['pointsForSix'], (float) $test['gradeStep']),
            'usedSeconds' => $used,
            'finishedAt' => time(),
        ];
        $this->set($session, $test, $state);

        return $this->finished($test, $state);
    }

    private function finished(array $test, array $state): array
    {
        return [
            'state' => 'finished',
            'student' => self::STUDENT,
            'test' => (string) $test['title'],
            'points' => (int) $state['points'],
            'max' => (int) $state['max'],
            'grade' => GradeCalculator::format($state['grade']),
            'timeUp' => (bool) ($state['timeUp'] ?? false),
        ];
    }

    private function remaining(array $test, array $state): int|null
    {
        if ((int) $test['timeLimit'] <= 0) {
            return null;
        }

        return (int) $test['timeLimit'] * 60 - (time() - (int) $state['startedAt']);
    }

    private function isTimeUp(array $test, array $state): bool
    {
        $left = $this->remaining($test, $state);

        return null !== $left && $left < -self::GRACE;
    }

    private function key(array $test): string
    {
        return 'markocupic_school_test_preview_'.(int) $test['id'];
    }

    private function get(SessionInterface $session, array $test): array|null
    {
        $state = $session->get($this->key($test));

        return \is_array($state) && isset($state['tasks']) ? $state : null;
    }

    private function set(SessionInterface $session, array $test, array $state): void
    {
        $session->set($this->key($test), $state);
    }

    private function decode(string|null $json): array
    {
        $data = $json ? json_decode($json, true) : null;

        return \is_array($data) ? $data : [];
    }
}
