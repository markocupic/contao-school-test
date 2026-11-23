<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Tests\Participation;

use Markocupic\ContaoSchoolTest\Grading\GradeCalculator;
use Markocupic\ContaoSchoolTest\Participation\ParticipationStatus;
use Markocupic\ContaoSchoolTest\Participation\PreviewSession;
use Markocupic\ContaoSchoolTest\Task\AnswerChecker;
use Markocupic\ContaoSchoolTest\Task\Evaluator;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\TestType\FigureAreaTestType;
use Markocupic\ContaoSchoolTest\TestType\TestTypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class PreviewSessionTest extends TestCase
{
    public function testAttemptLivesInTheSessionOnly(): void
    {
        $preview = $this->preview();
        $session = new Session(new MockArraySessionStorage());
        $test = $this->record();

        $this->assertSame(['state' => 'none'], $preview->continue($session, $test));
        $this->assertNull($preview->row($session, $test));

        $run = $preview->start($session, $test, 'fr');
        $this->assertSame('running', $run['state']);
        $this->assertSame(PreviewSession::STUDENT, $run['student']);
        $this->assertFalse($run['lockOnLeave']);
        $this->assertSame('fr', $run['lang']);
        $this->assertSame(20 * 60, $run['remaining']);
        $this->assertCount(2, $run['sections']);

        // The solutions never reach the browser
        $json = json_encode($run, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('"v"', $json);

        // Start again or reload: the same tasks
        $again = $preview->start($session, $test, 'de');
        $this->assertSame($run['sections'][0]['tasks'][0]['a']['svg'], $again['sections'][0]['tasks'][0]['a']['svg']);
        $this->assertSame('de', $again['lang']);
        $this->assertSame('running', $preview->continue($session, $test)['state']);
        $this->assertSame('saved', $preview->save($session, $test, [])['state']);

        $done = $preview->submit($session, $test, []);
        $this->assertSame('finished', $done['state']);
        $this->assertSame(0, $done['points']);
        $this->assertGreaterThan(0, $done['max']);
        $this->assertSame('1', $done['grade']);

        // Finished stays finished until restart
        $this->assertSame('finished', $preview->start($session, $test)['state']);
        $row = $preview->row($session, $test);
        $this->assertSame(ParticipationStatus::FINISHED, $row['status']);
        $this->assertSame(PreviewSession::STUDENT, $row['studentName']);
        $this->assertSame($done['max'], $row['maxPoints']);

        $this->assertSame('running', $preview->restart($session, $test)['state']);
        $this->assertSame(ParticipationStatus::RUNNING, $preview->row($session, $test)['status']);
    }

    public function testStoredTasksOfTheTestAreUsed(): void
    {
        $preview = $this->preview();
        $session = new Session(new MockArraySessionStorage());
        $tasks = (new FigureAreaTestType())->generate($this->record());
        $test = [...$this->record(), 'tasks' => json_encode($tasks, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)];

        $preview->start($session, $test);
        $this->assertSame($test['tasks'], $preview->row($session, $test)['tasks']);
    }

    public function testTimeUpHandsIn(): void
    {
        $preview = $this->preview();
        $session = new Session(new MockArraySessionStorage());
        $test = $this->record();
        $preview->start($session, $test);

        $key = 'markocupic_school_test_preview_7';
        $state = $session->get($key);
        $state['startedAt'] -= 21 * 60;
        $session->set($key, $state);

        $res = $preview->continue($session, $test);
        $this->assertSame('finished', $res['state']);
        $this->assertTrue($res['timeUp']);
        $this->assertSame(20 * 60, $preview->row($session, $test)['usedSeconds']);
    }

    public function testDifferentTestsDoNotMix(): void
    {
        $preview = $this->preview();
        $session = new Session(new MockArraySessionStorage());
        $preview->start($session, $this->record());

        $this->assertSame(['state' => 'none'], $preview->continue($session, [...$this->record(), 'id' => 8]));
    }

    private function preview(): PreviewSession
    {
        return new PreviewSession(new TestTypeRegistry([new FigureAreaTestType()]), new TaskPresenter(), new Evaluator(new AnswerChecker()), new GradeCalculator());
    }

    private function record(): array
    {
        return [
            'id' => 7,
            'title' => 'Flächen 2a',
            'type' => FigureAreaTestType::ALIAS,
            'tasks' => '',
            'timeLimit' => 20,
            'pointsForSix' => 0,
            'gradeStep' => '0.5',
            'difficulty' => 'easy',
            'areaKinds' => serialize(['rechteck', 'dreieck']),
            'tasksPerKind' => 2,
        ];
    }
}
