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

namespace Markocupic\ContaoSchoolTest\Controller\Api;

use Doctrine\DBAL\Connection;
use Markocupic\ContaoSchoolTest\Controller\ContentElement\SchoolTestPracticeController;
use Markocupic\ContaoSchoolTest\Task\AnswerChecker;
use Markocupic\ContaoSchoolTest\Task\Evaluator;
use Markocupic\ContaoSchoolTest\Task\SolutionRenderer;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Markocupic\ContaoSchoolTest\Task\Triangle\TriangleGenerator;
use Markocupic\ContaoSchoolTest\Task\Triangle\TriangleGeometry;
use Markocupic\ContaoSchoolTest\TestType\PracticeTestTypeInterface;
use Markocupic\ContaoSchoolTest\TestType\TestTypeRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Practice without login and without a test. The student picks a test type (any type that
 * implements PracticeTestTypeInterface), one of its topics and a difficulty (easy, medium, hard). The tasks of the current round live in
 * the PHP session (one round per content element), the solutions stay on the server until
 * the student asks for the evaluation.
 */
#[AsController]
#[Route('/_school_test_practice/{action}', name: 'markocupic_school_test_practice_api', requirements: ['action' => 'new|try|check'], defaults: ['_scope' => 'frontend', '_token_check' => false], methods: ['POST'])]
final class PracticeApiController
{
    private const SESSION_KEY = 'markocupic_school_test_practice_';

    /**
     * Open practice rounds per element and session (one per browser tab).
     */
    private const MAX_ROUNDS = 8;

    public function __construct(
        private readonly Connection $connection,
        private readonly TestTypeRegistry $testTypes,
        private readonly TaskPresenter $presenter,
        private readonly Evaluator $evaluator,
        private readonly SolutionRenderer $solutions,
        private readonly TaskTranslator $translator,
    ) {
    }

    public function __invoke(string $action, Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        $data = \is_array($data) ? $data : [];
        $elementId = (int) ($data['element'] ?? 0);
        $lang = TaskTranslator::normalize($data['lang'] ?? null);

        // Only visible practice elements may be used
        $exists = $this->connection->fetchOne(
            "SELECT id FROM tl_content WHERE id = ? AND type = ? AND invisible != '1'",
            [$elementId, SchoolTestPracticeController::TYPE],
        );

        if (!$exists || !$request->hasSession()) {
            return $this->json(['state' => 'error', 'error' => 'unavailable', 'message' => 'Diese Übung ist nicht verfügbar.'], 404);
        }

        $session = $request->getSession();
        $key = self::SESSION_KEY.$elementId;

        if ('new' === $action) {
            $alias = (string) ($data['type'] ?? '');
            $type = $this->testTypes->has($alias) ? $this->testTypes->get($alias) : null;

            if (!$type instanceof PracticeTestTypeInterface) {
                return $this->json(['state' => 'error', 'error' => 'unavailable', 'message' => 'Diese Übung ist nicht verfügbar.'], 404);
            }

            $difficulty = \in_array($data['difficulty'] ?? '', PracticeTestTypeInterface::DIFFICULTIES, true) ? (string) $data['difficulty'] : 'medium';
            $sections = $type->generatePractice((string) ($data['topic'] ?? ''), $difficulty);

            // Several rounds side by side (one per browser tab), the oldest go first
            $rounds = self::rounds($session->get($key));
            $round = bin2hex(random_bytes(8));
            $rounds[$round] = ['t' => time(), 's' => $sections];
            uasort($rounds, static fn (array $a, array $b): int => $b['t'] <=> $a['t']);
            $session->set($key, \array_slice($rounds, 0, self::MAX_ROUNDS, true));

            return $this->json([
                'state' => 'running',
                'round' => $round,
                'remaining' => null,
                'lang' => $lang,
                'sections' => $this->presenter->toPublic($sections, $lang),
            ]);
        }

        $rounds = self::rounds($session->get($key));
        $round = (string) ($data['round'] ?? '');
        $sections = $rounds[$round]['s'] ?? null;

        if (!\is_array($sections) || !$sections) {
            return $this->json(['state' => 'error', 'error' => 'expired', 'message' => 'Die Übung ist abgelaufen. Starte eine neue Übung.'], 409);
        }

        $answers = $this->evaluator->sanitizeAnswers($sections, $data['answers'] ?? []);
        $result = $this->evaluator->evaluate($sections, $answers);

        // Check one task without revealing the solution; the round stays open for corrections
        if ('try' === $action) {
            return $this->json($this->tryTask($sections, $result, $answers, (string) ($data['task'] ?? '')));
        }

        $tasks = [];

        foreach ($sections as $si => $section) {
            foreach ($section['tasks'] as $ti => $task) {
                $r = $result['sections'][$si]['tasks'][$ti];
                $id = TaskPresenter::key($si, $ti);
                $tasks[$id] = [
                    'points' => $r['points'],
                    'max' => $r['max'],
                    'correct' => $r['correct'],
                    'solution' => $this->translator->units($this->solutions->solution($task['a']), $lang),
                    'cells' => array_map(fn (string $c): string => $this->translator->units($this->translator->text($c, $lang), $lang), $this->solutions->tableSolutions($task['a'])),
                    'way' => $this->translator->way((string) $task['way'], $lang),
                    // Terms: equivalent, but not in the required form
                    'notes' => $r['notes'] ?? [],
                ];

                // Terms: decimal comma in French and Italian
                if ('terms' === ($task['a']['type'] ?? '')) {
                    $tasks[$id]['cells'] = array_map(fn (string $c): string => $this->translator->decimals($c, $lang), $tasks[$id]['cells']);
                    $tasks[$id]['way'] = $this->translator->decimals($tasks[$id]['way'], $lang);
                }

                // Angles and areas: the sketch with the solution, decimal comma in French and Italian
                if ('angles' === ($task['a']['type'] ?? '')) {
                    $tasks[$id]['svg'] = $this->translator->decimals((string) $task['a']['solSvg'], $lang);
                    $tasks[$id]['way'] = $this->translator->decimals($tasks[$id]['way'], $lang);
                    $tasks[$id]['cells'] = array_map(fn (string $c): string => $this->translator->decimals($c, $lang), $tasks[$id]['cells']);
                }

                // Triangle constructions: the solution triangle and the measures of the student's triangle
                if ('triangle' === ($task['a']['type'] ?? '')) {
                    $tasks[$id]['shape'] = $task['a']['shape'];
                    $tasks[$id]['given'] = self::given($task, $answers[$id] ?? null);
                }

                // Symmetry cards: the axes and the centre for the board
                if ('symmetry' === ($task['a']['type'] ?? '')) {
                    $tasks[$id]['sym'] = ['axes' => $task['a']['axes'], 'center' => $task['a']['center']];
                }

                // Drawing tasks: the solution points for the board, and a hint for rotations in the wrong direction
                if ('points' === ($task['a']['type'] ?? '')) {
                    $tasks[$id]['img'] = $task['a']['img'];
                    $tasks[$id]['wrongDir'] = isset($task['a']['wrongDirection']) && ($answers[$id] ?? null) === $task['a']['wrongDirection'];
                }
            }
        }

        // One evaluation per round; the other tabs keep theirs
        unset($rounds[$round]);
        $session->set($key, $rounds);

        return $this->json(['state' => 'checked', 'points' => $result['points'], 'max' => $result['max'], 'tasks' => $tasks]);
    }

    /**
     * Open rounds of an element: round id => ['t' => created, 's' => sections].
     */
    private static function rounds(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        return array_filter($value, static fn ($r, $k): bool => \is_string($k) && preg_match('/^[0-9a-f]{16}$/', $k) && \is_array($r) && isset($r['t'], $r['s']) && \is_array($r['s']), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Result of a single task: which fields are right, but no solution, no way and no solution figure.
     */
    private function tryTask(array $sections, array $result, array $answers, string $id): array
    {
        if (!preg_match('/^s(\d+)t(\d+)$/', $id, $m) || !isset($sections[(int) $m[1]]['tasks'][(int) $m[2]])) {
            return ['state' => 'error', 'error' => 'unavailable', 'message' => 'Diese Übung ist nicht verfügbar.'];
        }

        $task = $sections[(int) $m[1]]['tasks'][(int) $m[2]];
        $r = $result['sections'][(int) $m[1]]['tasks'][(int) $m[2]];
        $out = [
            'state' => 'tried',
            'task' => $id,
            'points' => $r['points'],
            'max' => $r['max'],
            'correct' => $r['correct'],
            'notes' => $r['notes'] ?? [],
        ];

        // Triangles: the given measures (already in the question) with the student's values
        if ('triangle' === ($task['a']['type'] ?? '')) {
            $out['given'] = self::given($task, $answers[$id] ?? null);
        }

        if ('points' === ($task['a']['type'] ?? '')) {
            $out['wrongDir'] = isset($task['a']['wrongDirection']) && ($answers[$id] ?? null) === $task['a']['wrongDirection'];
        }

        return $out;
    }

    /**
     * Given measures of a triangle construction and what the student's triangle measures.
     */
    private static function given(array $task, mixed $answer): array
    {
        $measured = AnswerChecker::triangleMeasures(\is_array($answer) ? $answer : []);

        return array_map(static function (array $g) use ($measured): array {
            $angle = \in_array($g['k'], TriangleGeometry::ANGLES, true);

            return [
                'label' => TriangleGenerator::LABELS[$g['k']],
                'v' => $g['v'],
                'unit' => $angle ? '°' : ' cm',
                'yours' => null === $measured ? null : round($measured[$g['k']], $angle ? 1 : 2),
            ];
        }, $task['a']['given']);
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
