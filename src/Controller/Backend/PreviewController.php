<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Controller\Backend;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Doctrine\DBAL\Connection;
use Markocupic\ContaoSchoolTest\Participation\PreviewSession;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * A teacher tries a test from the backend ("Ausprobieren"): the student view without a code,
 * a participation, the lock on leaving or the Safe Exam Browser. The attempt lives in the
 * backend session (PreviewSession), so nothing shows up in the class evaluation.
 */
#[AsController]
#[Route('/contao/school-test/preview/{id}', requirements: ['id' => '\d+'], defaults: ['_scope' => 'backend'])]
final class PreviewController
{
    private const ACTIONS = ['start', 'continue', 'save', 'lock', 'submit', 'restart'];

    public function __construct(
        private readonly Connection $connection,
        private readonly Security $security,
        private readonly Environment $twig,
        private readonly PreviewSession $preview,
        private readonly PrintController $print,
    ) {
    }

    #[Route('', name: 'markocupic_school_test_preview', methods: ['GET'])]
    public function page(int $id, Request $request): Response
    {
        $test = $this->getTest($id);
        $config = ['preview' => true, 'title' => $test['title'], 'evaluation' => $request->getBasePath().'/contao/school-test/preview/'.$id.'/evaluation'];

        foreach (self::ACTIONS as $action) {
            $config[$action] = $request->getBasePath().'/contao/school-test/preview/'.$id.'/'.$action;
        }

        $response = new Response($this->twig->render('@MarkocupicContaoSchoolTest/backend/preview.html.twig', [
            'test' => $test,
            'config' => $config,
        ]));
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    #[Route('/evaluation', name: 'markocupic_school_test_preview_evaluation', methods: ['GET'])]
    public function evaluation(int $id, Request $request): Response
    {
        $test = $this->getTest($id);
        $row = $this->preview->row($request->getSession(), $test);

        if (!$row) {
            return new Response('<!DOCTYPE html><meta charset="utf-8"><p style="font:16px sans-serif;padding:2em">Noch kein Vorschau-Versuch vorhanden. Löse den Test zuerst in der Vorschau.</p>', 404);
        }

        $response = new Response($this->print->renderEvaluation($test, [$this->print->sheet($row)], true));
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    #[Route('/{action}', name: 'markocupic_school_test_preview_api', requirements: ['action' => 'start|continue|save|lock|submit|restart'], defaults: ['_token_check' => false], methods: ['POST'])]
    public function api(int $id, string $action, Request $request): JsonResponse
    {
        // JSON only: a plain form post from another site cannot reach these endpoints
        if ('lock' !== $action && !str_starts_with((string) $request->headers->get('Content-Type'), 'application/json')) {
            throw new BadRequestHttpException('JSON expected.');
        }

        $test = $this->getTest($id);
        $session = $request->getSession();
        $data = json_decode((string) $request->getContent(), true);
        $data = \is_array($data) ? $data : [];
        $lang = TaskTranslator::normalize($data['lang'] ?? null);

        $payload = match ($action) {
            'start' => $this->preview->start($session, $test, $lang),
            'continue' => $this->preview->continue($session, $test, $lang),
            'save' => $this->preview->save($session, $test, $data['answers'] ?? []),
            'submit' => $this->preview->submit($session, $test, $data['answers'] ?? []),
            'restart' => $this->preview->restart($session, $test, $lang),
            // Leaving the page does not lock a preview, it only keeps the answers
            'lock' => \is_array($data['answers'] ?? null) ? $this->preview->save($session, $test, $data['answers']) : ['state' => 'ok'],
        };

        $response = new JsonResponse($payload);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    private function getTest(int $id): array
    {
        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'st_tests')) {
            throw new AccessDeniedHttpException('No access to the school tests module.');
        }

        $test = $this->connection->fetchAssociative('SELECT * FROM tl_st_test WHERE id = ?', [$id]);

        if (!$test) {
            throw new NotFoundHttpException(\sprintf('Test ID %d not found.', $id));
        }

        return $this->print->decodeTest($test);
    }
}
