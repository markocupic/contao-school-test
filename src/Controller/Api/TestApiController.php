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

use Markocupic\ContaoSchoolTest\Participation\SebVerifier;
use Markocupic\ContaoSchoolTest\Participation\TestSession;
use Markocupic\ContaoSchoolTest\Task\TaskTranslator;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * JSON endpoints for the frontend test. Authentication is the student code plus
 * a random token that only the device which started (or resumed) the test knows.
 */
#[AsController]
#[Route('/_school_test/{action}', name: 'markocupic_school_test_api', requirements: ['action' => 'start|resume|continue|save|lock|submit'], defaults: ['_scope' => 'frontend', '_token_check' => false], methods: ['POST'])]
final class TestApiController
{
    /**
     * Failed code or resume code attempts per IP within the time window.
     */
    private const MAX_FAILURES = 15;

    private const WINDOW = 900;

    public function __construct(
        private readonly TestSession $session,
        private readonly CacheItemPoolInterface $cache,
        private readonly SebVerifier $seb,
    ) {
    }

    public function __invoke(string $action, Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);

        if (!\is_array($data)) {
            $data = $request->request->all();
        }

        $code = preg_replace('/\D/', '', (string) ($data['code'] ?? ''));
        $token = (string) ($data['token'] ?? '');
        $lang = TaskTranslator::normalize($data['lang'] ?? null);

        if (\in_array($action, ['start', 'resume', 'continue'], true) && $this->isBlocked($request)) {
            return $this->json(['state' => 'error', 'error' => 'too_many_attempts', 'message' => 'Zu viele falsche Versuche. Warte 15 Minuten oder melde dich bei deiner Lehrperson.'], 429);
        }

        // Tests for the Safe Exam Browser only: no other browser can start, save or submit
        $seb = $this->session->sebSettings($code);

        if ($seb && $seb['only'] && !$this->seb->isSeb($request, $seb['configKey'], \is_array($data['seb'] ?? null) ? $data['seb'] : null)) {
            return $this->json(['state' => 'error', 'error' => 'seb_required', 'message' => 'Dieser Test kann nur mit dem Safe Exam Browser gelöst werden.'], 403);
        }

        $result = match ($action) {
            'start' => $this->session->start($code, $lang),
            'resume' => $this->session->resume($code, (string) ($data['resumeCode'] ?? ''), $lang),
            'continue' => $this->session->continue($code, $token, $lang),
            'save' => $this->session->save($code, $token, $data['answers'] ?? []),
            'lock' => $this->session->lock($code, $token, (string) ($data['reason'] ?? 'left'), $data['answers'] ?? null),
            'submit' => $this->session->submit($code, $token, $data['answers'] ?? []),
        };

        if ('error' === ($result['state'] ?? null) && \in_array($result['error'] ?? '', ['invalid_code', 'invalid_resume_code'], true)) {
            $this->registerFailure($request);
        }

        return $this->json($result);
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    private function cacheKey(Request $request): string
    {
        return 'school_test_fail_'.hash('sha256', (string) $request->getClientIp());
    }

    private function isBlocked(Request $request): bool
    {
        $item = $this->cache->getItem($this->cacheKey($request));

        return $item->isHit() && (int) $item->get() >= self::MAX_FAILURES;
    }

    private function registerFailure(Request $request): void
    {
        $item = $this->cache->getItem($this->cacheKey($request));
        $item->set(($item->isHit() ? (int) $item->get() : 0) + 1);
        $item->expiresAfter(self::WINDOW);
        $this->cache->save($item);
    }
}
