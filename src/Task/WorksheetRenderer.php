<?php

declare(strict_types=1);

/*HEADER*/

namespace Markocupic\ContaoSchoolTest\Task;

use Contao\StringUtil;
use Markocupic\ContaoSchoolTest\Task\Geometry\BoardSvg;
use Markocupic\ContaoSchoolTest\Task\Geometry\SymmetryGenerator;

/**
 * Paper version of a test: every task with empty answer boxes to write in, and a solution
 * sheet. Works on the stored task format (with solutions), German like the evaluation sheet.
 */
final class WorksheetRenderer
{
    /**
     * Millimetres per grid unit of a drawing board (squared paper).
     */
    public const GRID_MM = 5;

    /**
     * Section instructions for paper instead of the online ones (clicks, GeoGebra).
     */
    private const PAPER_INSTRUCTIONS = [
        'points' => 'Zeichne die Bildfigur ein und beschrifte die Bildpunkte A′, B′, …',
        'free' => 'Konstruiere die Bildfigur mit Geodreieck und Zirkel und beschrifte die Bildpunkte A′, B′, …',
        'triangle' => 'Konstruiere das Dreieck mit Geodreieck und Zirkel im Feld. Beschrifte die Ecken A, B und C und zeichne die drei Seiten.',
        'symmetry' => 'Zeichne alle Symmetrieachsen ein und markiere das Symmetriezentrum Z. Hat die Figur keine Symmetrie, kreuze «keine Symmetrie» an.',
        'rat' => 'Löse nach x auf. Schreibe den Lösungsweg auf.',
    ];

    public function __construct(
        private readonly SolutionRenderer $solutions,
        private readonly BoardSvg $board,
    ) {
    }

    /**
     * Instruction of a section on paper.
     */
    public function instruction(array $section): string
    {
        $a = $section['tasks'][0]['a'] ?? [];
        $key = 'points' === ($a['type'] ?? '') && !empty($a['draw']['free']) ? 'free' : ($a['type'] ?? '');

        if (isset(self::PAPER_INSTRUCTIONS[$key])) {
            return self::PAPER_INSTRUCTIONS[$key];
        }

        // Drop the hints for typing and clicking (x² key, ^, GeoGebra)
        $sentences = preg_split('/(?<=[.!])\s+/u', trim((string) $section['instruction'])) ?: [];
        $paper = array_filter($sentences, static fn (string $s): bool => !preg_match('/\^|Taste|[Kk]lick|GeoGebra|tippe|eingeben|Knopf/u', $s));

        return implode(' ', $paper);
    }

    /**
     * Whether a task needs the whole width (figure, table, board).
     */
    public static function isWide(array $task): bool
    {
        return \in_array($task['a']['type'], ['angles', 'terms', 'table', 'points', 'triangle', 'symmetry'], true);
    }

    /**
     * The task as it is printed for the student: figure and empty boxes.
     */
    public function blank(array $task): string
    {
        $a = $task['a'];

        return match ($a['type']) {
            'num' => '<span class="ans">'.(!empty($a['est']) ? '≈&nbsp;' : '').'<span class="box"></span>'.$this->solutions->suffix($a).'</span>',
            'frac' => '<span class="ans">'.(empty($a['noWhole']) ? '<span class="box sm"></span>' : '').'<span class="stack"><span class="box sm"></span><span class="bar"></span><span class="box sm"></span></span></span>',
            'rat' => '<span class="ans"><i>x</i>&nbsp;= <span class="box wide"></span></span>',
            'angles' => '<div class="fig">'.($a['svg'] ?? '').'</div>'.$this->fieldsLine($a['fields']),
            'terms' => $this->terms($a),
            'table' => $this->table($a, null),
            'points' => '<div class="fig">'.$this->board->render($a, [], [], null, false, self::GRID_MM).'</div>',
            'symmetry' => '<div class="fig sym">'.SymmetryGenerator::svg($a, null, [], false).'</div><div class="choice"><span class="tick"></span> keine Symmetrie</div>',
            // Construction with compass and ruler: the sheet of the online board in real size
            'triangle' => '<div class="construct" style="width:'.(10 * ($a['draw']['grid'][0] ?? 16)).'mm;height:'.(10 * ($a['draw']['grid'][1] ?? 11)).'mm"></div>',
            default => '<span class="ans"><span class="box"></span></span>',
        };
    }

    /**
     * The solution of a task for the solution sheet.
     */
    public function solution(array $task): string
    {
        $a = $task['a'];

        return match ($a['type']) {
            'angles' => '<div class="fig sol">'.($a['solSvg'] ?? '').'</div><div>'.$this->solutions->solution($a).'</div>',
            'table' => $this->table($a, $this->solutions->tableSolutions($a)),
            'points' => '<div class="fig sol">'.$this->board->render($a, [], [], null, true, 3).'</div><div>'.$this->solutions->solution($a).'</div>',
            'symmetry' => '<div class="fig sym sol">'.SymmetryGenerator::svg($a).'</div><div>'.$this->solutions->solution($a).'</div>',
            'terms' => ($a['svg'] ?? '' ? '<div class="fig sol">'.$a['svg'].'</div>' : '').'<div>'.$this->solutions->solution($a).'</div>',
            'triangle' => '<div>Gegeben: '.$this->solutions->solution($a).'</div>',
            default => $this->solutions->solution($a),
        };
    }

    /**
     * Angles and areas: "ω₁ = ____ °" for every field.
     */
    private function fieldsLine(array $fields): string
    {
        $out = '';

        foreach ($fields as $f) {
            $out .= '<span class="ans">'.StringUtil::specialchars((string) $f['label']).'&nbsp;= <span class="box"></span>'.StringUtil::specialchars(trim((string) $f['unit'])).'</span>';
        }

        return '<div class="fields">'.$out.'</div>';
    }

    private function terms(array $a): string
    {
        $row = static function (array $f): string {
            $tag = isset($f['tag']) ? ' <span class="tag">'.('sum' === $f['tag'] ? 'als Summe' : 'als Produkt').'</span>' : '';

            if ('choice' === ($f['kind'] ?? '')) {
                return '<div class="trow"><span>'.($f['prompt'] ?? '').'</span>'.$tag.' <span class="choice"><span class="tick"></span> Summenterm <span class="tick"></span> Produktterm</span></div>';
            }

            return '<div class="trow"><span>'.($f['prompt'] ?? '').'</span>'.$tag.' <span class="box '.('num' === ($f['kind'] ?? '') ? '' : 'xwide').'"></span>'.StringUtil::specialchars((string) ($f['unit'] ?? '')).'</div>';
        };

        $html = isset($a['svg']) && '' !== $a['svg'] ? '<div class="fig">'.$a['svg'].'</div>' : '';

        if (isset($a['kreuz'])) {
            // Multiplication cross: given numbers, empty cells for the rest
            $rows = '';

            for ($r = 0; $r < 3; ++$r) {
                $rows .= '<tr>';

                foreach ($a['kreuz'][$r] as $c) {
                    $rows .= isset($c['t']) ? '<td>'.StringUtil::specialchars((string) $c['t']).'</td>' : '<td class="in"></td>';
                }

                $rows .= '</tr>';
            }

            return $html.'<table class="kreuz">'.$rows.'</table>'.$row($a['fields'][$a['kreuz'][3]['total']]);
        }

        return $html.implode('', array_map($row, $a['fields']));
    }

    /**
     * Percent table: empty cells to fill in, or the solutions.
     *
     * @param array<int, string>|null $solutions
     */
    private function table(array $a, array|null $solutions): string
    {
        $cell = static function (array $c, int $i, string $row) use ($solutions): string {
            if (!empty($c['ref'])) {
                return '<td class="ref">'.($c[$row] ?? '').'</td>';
            }

            if (($c['ask'] ?? '') !== $row) {
                return '<td>'.($c[$row] ?? '').'</td>';
            }

            if (null !== $solutions) {
                return '<td class="solcell"><b>'.($solutions[$i] ?? '').'</b></td>';
            }

            return '<td class="in"><span class="unit">'.StringUtil::specialchars((string) $c['unit']).'</span></td>';
        };

        $top = '';
        $bot = '';

        foreach ($a['cols'] as $i => $c) {
            $top .= $cell($c, $i, 'top');
            $bot .= $cell($c, $i, 'bot');
        }

        return '<table class="pt"><tr><th>'.StringUtil::specialchars((string) $a['name']).'</th>'.$top.'</tr><tr><th>Anteil in %</th>'.$bot.'</tr></table>';
    }
}
