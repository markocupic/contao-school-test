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
#[Route('/_school_test_practice/{action}', name: 'markocupic_school_test_practice_api', requirements: ['action' => 'new|check'], defaults: ['_scope' => 'frontend', '_token_check' => false], methods: ['POST'])]
final class PracticeApiController
{
    private const SESSION_KEY = 'markocupic_school_test_practice_';

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
            $session->set($key, $sections);

            return $this->json([
                'state' => 'running',
                'remaining' => null,
                'lang' => $lang,
                'sections' => $this->presenter->toPublic($sections, $lang),
            ]);
        }

        $sections = $session->get($key);

        if (!\is_array($sections) || !$sections) {
            return $this->json(['state' => 'error', 'error' => 'expired', 'message' => 'Die Übung ist abgelaufen. Starte eine neue Übung.'], 409);
        }

        $answers = $this->evaluator->sanitizeAnswers($sections, $data['answers'] ?? []);
        $result = $this->evaluator->evaluate($sections, $answers);
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
                    'cells' => array_map(fn (string $c): string => $this->translator->units($c, $lang), $this->solutions->tableSolutions($task['a'])),
                    'way' => $this->translator->way((string) $task['way'], $lang),
                ];

                // Angles: the sketch with the solution
                if ('angles' === ($task['a']['type'] ?? '')) {
                    $tasks[$id]['svg'] = $task['a']['solSvg'];
                }

                // Triangle constructions: the solution triangle and the measures of the student's triangle
                if ('triangle' === ($task['a']['type'] ?? '')) {
                    $measured = AnswerChecker::triangleMeasures(\is_array($answers[$id] ?? null) ? $answers[$id] : []);
                    $tasks[$id]['shape'] = $task['a']['shape'];
                    $tasks[$id]['given'] = array_map(static function (array $g) use ($measured): array {
                        $angle = \in_array($g['k'], TriangleGeometry::ANGLES, true);

                        return [
                            'label' => TriangleGenerator::LABELS[$g['k']],
                            'v' => $g['v'],
                            'unit' => $angle ? '°' : ' cm',
                            'yours' => null === $measured ? null : round($measured[$g['k']], $angle ? 1 : 2),
                        ];
                    }, $task['a']['given']);
                }

                // Drawing tasks: the solution points for the board, and a hint for rotations in the wrong direction
                if ('points' === ($task['a']['type'] ?? '')) {
                    $tasks[$id]['img'] = $task['a']['img'];
                    $tasks[$id]['wrongDir'] = isset($task['a']['wrongDirection']) && ($answers[$id] ?? null) === $task['a']['wrongDirection'];
                }
            }
        }

        // One evaluation per round
        $session->remove($key);

        return $this->json(['state' => 'checked', 'points' => $result['points'], 'max' => $result['max'], 'tasks' => $tasks]);
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
