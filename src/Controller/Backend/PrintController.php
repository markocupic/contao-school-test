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

namespace Markocupic\ContaoSchoolTest\Controller\Backend;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Markocupic\ContaoSchoolTest\Grading\GradeCalculator;
use Markocupic\ContaoSchoolTest\Participation\ParticipationStatus;
use Markocupic\ContaoSchoolTest\Participation\ParticipationSynchronizer;
use Markocupic\ContaoSchoolTest\Task\Evaluator;
use Markocupic\ContaoSchoolTest\Task\Geometry\BoardSvg;
use Markocupic\ContaoSchoolTest\Task\SolutionRenderer;
use Markocupic\ContaoSchoolTest\Task\TaskPresenter;
use Markocupic\ContaoSchoolTest\Task\Util;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Printable pages, opened from the test list in the backend
 * (key=codesheet / key=evaluation, see contao/config/config.php).
 * Contao checks the module permissions before calling these methods.
 */
class PrintController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly Environment $twig,
        private readonly ParticipationSynchronizer $synchronizer,
        private readonly Evaluator $evaluator,
        private readonly SolutionRenderer $solutions,
        private readonly BoardSvg $board,
    ) {
    }

    public function codeSheet(DataContainer|null $dc = null): string
    {
        $test = $this->getTest();
        $this->synchronizer->sync((int) $test['id']);

        $this->send($this->twig->render('@MarkocupicContaoSchoolTest/backend/codesheet.html.twig', [
            'test' => $test,
            'class' => $this->getClassTitle($test),
            'participations' => $this->getParticipations((int) $test['id']),
        ]));
    }

    public function evaluation(DataContainer|null $dc = null): string
    {
        $test = $this->getTest();
        $this->synchronizer->sync((int) $test['id']);
        $rows = $this->getParticipations((int) $test['id']);
        $sheets = [];

        foreach ($rows as $row) {
            $sections = json_decode((string) $row['tasks'], true) ?: [];
            $answers = json_decode((string) $row['answers'], true) ?: [];
            $result = $sections ? $this->evaluator->evaluate($sections, $answers) : null;
            $outSections = [];

            foreach ($sections as $si => $section) {
                $tasks = [];

                foreach ($section['tasks'] as $ti => $task) {
                    $r = $result['sections'][$si]['tasks'][$ti];
                    $given = $answers[TaskPresenter::key($si, $ti)] ?? null;
                    $tasks[] = [
                        'q' => $task['q'],
                        'isTable' => 'table' === $task['a']['type'],
                        'figure' => match ($task['a']['type']) {
                            'points' => $this->board->render($task['a'], Evaluator::gridPoints($given, \count($task['a']['img']), $task['a']['draw']['grid'], !empty($task['a']['draw']['free'])), $r['correct']),
                            'angles' => $task['a']['solSvg'].$this->renderAngles($task['a'], \is_array($given) ? $given : [], $r['correct']),
                            default => '',
                        },
                        'legend' => 'points' === $task['a']['type'] ? 'Lösung grün gestrichelt'.(empty($task['a']['draw']['free']) ? '' : ', Kreise: '.(string) round((float) ($task['a']['tol'] ?? 0.6) * 5, 1).' mm Toleranz').' · gesetzte Punkte: grün richtig, rot falsch' : '',
                        'table' => 'table' === $task['a']['type'] ? $this->renderTable($task['a'], \is_array($given) ? $given : [], $r['correct']) : '',
                        'given' => $this->renderGiven($task['a'], $given),
                        'solution' => $this->solutions->solution($task['a']),
                        'points' => $r['points'],
                        'max' => $r['max'],
                    ];
                }

                $outSections[] = [
                    'letter' => \chr(65 + $si),
                    'title' => $section['title'],
                    'points' => $result['sections'][$si]['points'],
                    'max' => $result['sections'][$si]['max'],
                    'tasks' => $tasks,
                ];
            }

            $sheets[] = [
                'row' => $row,
                'finished' => ParticipationStatus::FINISHED === $row['status'],
                'statusLabel' => $GLOBALS['TL_LANG']['tl_st_participation']['statusOptions'][$row['status']] ?? $row['status'],
                'grade' => GradeCalculator::format($row['grade']),
                'sections' => $outSections,
            ];
        }

        $this->send($this->twig->render('@MarkocupicContaoSchoolTest/backend/evaluation.html.twig', [
            'test' => $test,
            'class' => $this->getClassTitle($test),
            'sheets' => $sheets,
            'gradeStep' => $GLOBALS['TL_LANG']['tl_st_test']['gradeStepOptions'][$test['gradeStep']] ?? $test['gradeStep'],
        ]));
    }

    private function send(string $html): never
    {
        $response = new Response($html);
        $response->headers->set('Cache-Control', 'no-store, private');

        throw new ResponseException($response);
    }

    private function getTest(): array
    {
        $id = (int) $this->requestStack->getCurrentRequest()?->query->get('id');
        $test = $this->connection->fetchAssociative('SELECT * FROM tl_st_test WHERE id = ?', [$id]);

        if (!$test) {
            throw new \RuntimeException(\sprintf('Test ID %d not found.', $id));
        }

        // Contao stores input HTML-encoded, Twig escapes again: decode first to avoid "&amp;#40;"
        foreach (['title', 'subject', 'codeSheetNote'] as $field) {
            $test[$field] = StringUtil::decodeEntities((string) $test[$field]);
        }

        return $test;
    }

    private function getClassTitle(array $test): string
    {
        return StringUtil::decodeEntities((string) $this->connection->fetchOne('SELECT title FROM tl_st_class WHERE id = ?', [$test['class']]));
    }

    private function getParticipations(int $testId): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT * FROM tl_st_participation WHERE pid = ? ORDER BY studentName', [$testId]);

        foreach ($rows as &$row) {
            $row['studentName'] = StringUtil::decodeEntities((string) $row['studentName']);
        }

        return $rows;
    }

    /**
     * Answers of an angle task: right ones plain, wrong ones struck through with the solution.
     */
    private function renderAngles(array $a, array $given, array $correct): string
    {
        $out = [];

        foreach ($a['fields'] as $i => $f) {
            $v = trim((string) ($given[$i] ?? ''));
            $shown = '' === $v ? '–' : StringUtil::specialchars($v).$f['unit'];
            $out[] = $f['label'].' = '.(!empty($correct[$i]) ? '<span class="okv">'.$shown.'</span>' : '<s>'.$shown.'</s> <b>'.Util::fmt($a['v'][$i]).$f['unit'].'</b>');
        }

        return '<div class="angans">'.implode(' · ', $out).'</div>';
    }

    private function renderGiven(array $a, mixed $given): string
    {
        if ('rat' === $a['type']) {
            $v = \is_scalar($given) ? trim((string) $given) : '';

            return '' === $v ? '–' : '<i>x</i> = '.StringUtil::specialchars($v);
        }

        if ('num' === $a['type']) {
            $v = \is_scalar($given) ? trim((string) $given) : '';

            return '' === $v ? '–' : StringUtil::specialchars($v).$this->solutions->suffix($a);
        }

        if ('frac' === $a['type']) {
            $g = \is_array($given) ? $given : [];
            $w = trim((string) ($g['w'] ?? ''));
            $n = trim((string) ($g['n'] ?? ''));
            $d = trim((string) ($g['d'] ?? ''));
            $html = StringUtil::specialchars($w);

            if ('' !== $n || '' !== $d) {
                $html .= Util::f(StringUtil::specialchars($n ?: '?'), StringUtil::specialchars($d ?: '?'));
            }

            return '' === $html ? '–' : $html;
        }

        return '';
    }

    private function renderTable(array $a, array $given, array $correct): string
    {
        $cell = static function (array $c, int $i, string $row) use ($given, $correct): string {
            $txt = $c[$row] ?? '';

            if (!empty($c['ref'])) {
                return '<td class="ref">'.$txt.'</td>';
            }

            if (($c['ask'] ?? '') !== $row) {
                return '<td>'.$txt.'</td>';
            }

            $g = trim((string) ($given[$i] ?? ''));

            if (!empty($correct[$i])) {
                return '<td class="ok">'.StringUtil::specialchars($g).'&nbsp;'.StringUtil::specialchars($c['unit']).'</td>';
            }

            return '<td class="bad"><s>'.('' === $g ? '–' : StringUtil::specialchars($g)).'</s><br><b>'.$c['show'].'</b></td>';
        };

        $top = '';
        $bot = '';

        foreach ($a['cols'] as $i => $c) {
            $top .= $cell($c, $i, 'top');
            $bot .= $cell($c, $i, 'bot');
        }

        return '<table class="pt"><tr><th>'.StringUtil::specialchars($a['name']).'</th>'.$top.'</tr><tr><th>Anteil in %</th>'.$bot.'</tr></table>';
    }
}
