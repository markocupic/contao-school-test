<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\FigureArea;

use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * More composed figures: T and U shapes, stairs, arrow, star, polygon on squared paper,
 * hall wall with a gate, workpiece, and figures with circles (circle in a square, square in
 * a circle, lens, semicircle cut out, square with semicircles).
 */
trait FigureAreaComposedTasks
{
    private const MORE_COMPOSED = ['tshape', 'stairs', 'arrow', 'ushape', 'star', 'gridpoly', 'hall', 'workpiece', 'roundsquare', 'circleInSquare', 'squareInCircle', 'lens', 'semicut', 'coordPoly', 'coordArc'];

    private function composedMore(string $variant, string $unit): array
    {
        $q = 'Berechne den Flächeninhalt A der farbigen Fläche.';
        $u = static fn (float|int $x, string $sq = ''): string => self::u($x, $unit.$sq);

        if ('coordPoly' === $variant || 'coordArc' === $variant) {
            return $this->coordFigure('coordArc' === $variant);
        }

        switch ($variant) {
            case 'tshape':
                $W = mt_rand(6, 12);
                $w = mt_rand(2, $W - 3);

                if (0 !== ($W - $w) % 2) {
                    ++$W;
                }

                $t = mt_rand(2, 4);
                $h = mt_rand(3, 7);
                $x = ($W - $w) / 2;
                $A1 = $W * $t;
                $A2 = $w * $h;

                return [
                    'q' => $q,
                    'fields' => [['A', $unit.'²', $A1 + $A2, self::EXACT]],
                    'steps' => [
                        self::p('Zerlege die Figur in zwei Rechtecke.'),
                        'A₁ = '.$u($W).' · '.$u($t).' = '.$u($A1, '²'),
                        'A₂ = '.$u($w).' · '.$u($h).' = '.$u($A2, '²'),
                        'A = A₁ + A₂ = '.self::b($u($A1 + $A2, '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['poly', [[$x, 0], [$x + $w, 0], [$x + $w, $h], [$W, $h], [$W, $h + $t], [0, $h + $t], [0, $h], [$x, $h]]]], 'teal')
                        ->line([$x, $h], [$x + $w, $h], 'sol', 's')
                        ->label([$W / 2, $h + $t / 2], 'A₁', 'part', null, 0, 's')
                        ->label([$W / 2, $h / 2], 'A₂', 'part', null, 0, 's')
                        ->dim([0, $h + $t], [$W, $h + $t], $u($W), 1)
                        ->dim([$W, $h], [$W, $h + $t], $u($t), -1)
                        ->dim([$x, 0], [$x + $w, 0], $u($w), -1)
                        ->dim([$x, 0], [$x, $h], $u($h), 1),
                ];

            case 'stairs':
                $a = mt_rand(2, 5);
                $b = mt_rand(1, 4);
                $A = 6 * $a * $b;

                return [
                    'q' => 'Alle Stufen sind gleich breit und gleich hoch. Berechne den Flächeninhalt A der farbigen Fläche.',
                    'fields' => [['A', $unit.'²', $A, self::EXACT]],
                    'steps' => [
                        self::p('Zerlege die Treppe in drei Rechtecke (Spalten).'),
                        'A₁ = '.$u($a).' · '.$u(3 * $b).' = '.$u(3 * $a * $b, '²').', A₂ = '.$u($a).' · '.$u(2 * $b).' = '.$u(2 * $a * $b, '²').', A₃ = '.$u($a).' · '.$u($b).' = '.$u($a * $b, '²'),
                        'A = A₁ + A₂ + A₃ = '.self::b($u($A, '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['poly', [[0, 0], [3 * $a, 0], [3 * $a, $b], [2 * $a, $b], [2 * $a, 2 * $b], [$a, 2 * $b], [$a, 3 * $b], [0, 3 * $b]]]], 'orange')
                        ->line([$a, 0], [$a, 2 * $b], 'sol', 's')
                        ->line([2 * $a, 0], [2 * $a, $b], 'sol', 's')
                        ->label([$a / 2, 1.5 * $b], 'A₁', 'part', null, 0, 's')
                        ->label([1.5 * $a, $b], 'A₂', 'part', null, 0, 's')
                        ->label([2.5 * $a, $b / 2], 'A₃', 'part', null, 0, 's')
                        ->dim([0, 0], [3 * $a, 0], $u(3 * $a), -1)
                        ->dim([0, 0], [0, 3 * $b], $u(3 * $b), 1)
                        ->dim([$a, 3 * $b], [2 * $a, 3 * $b], $u($a), 1)
                        ->dim([2 * $a, $b], [2 * $a, 2 * $b], $u($b), -1),
                ];

            case 'arrow':
                $h = mt_rand(2, 4);
                $B = $h + 2 * mt_rand(1, 3);
                $L = mt_rand(5, 10);
                $t = mt_rand(2, 5);
                $y1 = ($B - $h) / 2;
                $y2 = ($B + $h) / 2;
                $A1 = $L * $h;
                $A2 = $B * $t / 2;

                return [
                    'q' => $q,
                    'fields' => [['A', $unit.'²', $A1 + $A2, self::EXACT]],
                    'steps' => [
                        self::p('Zerlege die Figur in ein Rechteck und ein Dreieck.'),
                        'A₁ = '.$u($L).' · '.$u($h).' = '.$u($A1, '²'),
                        'A₂ = '.$u($B).' · '.$u($t).' : 2 = '.$u($A2, '²'),
                        'A = A₁ + A₂ = '.self::b($u($A1 + $A2, '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['poly', [[0, $y1], [$L, $y1], [$L, 0], [$L + $t, $B / 2], [$L, $B], [$L, $y2], [0, $y2]]]], 'pink')
                        ->line([$L, $y1], [$L, $y2], 'sol', 's')
                        ->label([$L / 2, $B / 2], 'A₁', 'part', null, 0, 's')
                        ->label([$L + $t / 3, $B / 2], 'A₂', 'part', null, 0, 's')
                        ->dim([0, $y2], [$L, $y2], $u($L), 1)
                        ->dim([0, $y1], [0, $y2], $u($h), 1)
                        ->dim([$L, 0], [$L + $t, 0], $u($t), -1)
                        ->dim([$L + $t, 0], [$L + $t, $B], $u($B), -1),
                ];

            case 'ushape':
                $W = mt_rand(8, 14);
                $H = mt_rand(5, 9);
                $w = mt_rand(2, $W - 4);
                $d = mt_rand(2, $H - 2);
                $x = mt_rand(2, $W - $w - 2);
                $A = $W * $H - $w * $d;

                return [
                    'q' => $q,
                    'fields' => [['A', $unit.'²', $A, self::EXACT]],
                    'steps' => [
                        self::p('Grosses Rechteck minus den Ausschnitt.'),
                        'A = '.$u($W).' · '.$u($H).' − '.$u($w).' · '.$u($d).' = '.$u($W * $H, '²').' − '.$u($w * $d, '²').' = '.self::b($u($A, '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['poly', [[0, 0], [$W, 0], [$W, $H], [$x + $w, $H], [$x + $w, $H - $d], [$x, $H - $d], [$x, $H], [0, $H]]]], 'blue')
                        ->line([$x, $H], [$x + $w, $H], 'sol', 's')
                        ->dim([0, 0], [$W, 0], $u($W), -1)
                        ->dim([0, 0], [0, $H], $u($H), 1)
                        ->dim([$x, $H], [$x + $w, $H], $u($w), 1)
                        ->side([$x + $w, $H - $d], [$x + $w, $H], $u($d), [$x, $H]),
                ];

            case 'star':
                $a = 2 * mt_rand(2, 5);
                $t = mt_rand(2, 6);
                $h = $a / 2;
                $A = $a * $a + 2 * $a * $t;

                return [
                    'q' => 'Auf jeder Seite des Quadrats sitzt ein gleich grosses Dreieck. Berechne den Flächeninhalt A des Sterns.',
                    'fields' => [['A', $unit.'²', $A, self::EXACT]],
                    'steps' => [
                        self::p('Quadrat und vier gleiche Dreiecke.'),
                        'A = a · a + 4 · a · h : 2 = '.$u($a).' · '.$u($a).' + 4 · '.$u($a).' · '.$u($t).' : 2 = '.$u($a * $a, '²').' + '.$u(2 * $a * $t, '²').' = '.self::b($u($A, '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['poly', [[0, 0], [$h, -$t], [$a, 0], [$a + $t, $h], [$a, $a], [$h, $a + $t], [0, $a], [-$t, $h]]]], 'yellow')
                        ->line([0, 0], [$a, 0], 'thin')
                        ->line([$a, 0], [$a, $a], 'thin')
                        ->line([$a, $a], [0, $a], 'thin')
                        ->line([0, $a], [0, 0], 'thin')
                        ->line([$h, 0], [$h, -$t])
                        ->right([$h, 0], [$a, 0], [$h, -$t])
                        ->label([$h, $h], 'a = '.$u($a), 'given')
                        ->side([$h, 0], [$h, -$t], 'h = '.$u($t), [$a, 0]),
                ];

            case 'gridpoly':
                $W = mt_rand(4, 9);
                $H = mt_rand(2, 5);
                $t = mt_rand(2, 4);
                $px = mt_rand(0, $W);
                $A1 = $W * $H;
                $A2 = $W * $t / 2;

                return [
                    'q' => 'Jedes Häuschen ist 1 cm lang. Berechne den Flächeninhalt A der farbigen Fläche.',
                    'fields' => [['A', 'cm²', $A1 + $A2, self::EXACT]],
                    'steps' => [
                        self::p('Häuschen zählen: Rechteck {0}, Dreieck mit Grundseite {1} und Höhe {2}.', self::u($W, 'cm').' × '.self::u($H, 'cm'), self::u($W, 'cm'), self::u($t, 'cm')),
                        'A₁ = '.self::u($W, 'cm').' · '.self::u($H, 'cm').' = '.self::u($A1, 'cm²').', A₂ = '.self::u($W, 'cm').' · '.self::u($t, 'cm').' : 2 = '.self::u($A2, 'cm²'),
                        'A = A₁ + A₂ = '.self::b(self::u($A1 + $A2, 'cm²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->grid(1)
                        ->shape([['poly', [[0, 0], [$W, 0], [$W, $H], [$px, $H + $t], [0, $H]]]], 'violet')
                        ->line([0, $H], [$W, $H], 'sol', 's')
                        ->line([$px, $H], [$px, $H + $t], 'sol', 's')
                        ->label([$W / 2, $H / 2], 'A₁', 'part', null, 0, 's')
                        ->label([($W + $px) / 3, $H + $t / 3], 'A₂', 'part', null, 0, 's'),
                ];

            case 'hall':
                $W = 2 * mt_rand(5, 10);
                $H = mt_rand(4, 7);
                $t = mt_rand(2, 4);
                $gw = mt_rand(3, 5);
                $gh = mt_rand(3, $H - 1);
                $gx = ($W - $gw) / 2;
                $A1 = $W * $H;
                $A2 = $W * $t / 2;
                $A = $A1 + $A2 - $gw * $gh;

                return [
                    'q' => 'Die Seitenwand einer Halle hat ein Tor. Berechne den Flächeninhalt A der Wand ohne das Tor.',
                    'fields' => [['A', 'm²', $A, self::EXACT]],
                    'steps' => [
                        self::p('Rechteck plus Dreieck, minus das Tor.'),
                        'A = '.self::u($W, 'm').' · '.self::u($H, 'm').' + '.self::u($W, 'm').' · '.self::u($t, 'm').' : 2 − '.self::u($gw, 'm').' · '.self::u($gh, 'm'),
                        'A = '.self::u($A1, 'm²').' + '.self::u($A2, 'm²').' − '.self::u($gw * $gh, 'm²').' = '.self::b(self::u($A, 'm²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['poly', [[0, 0], [$W, 0], [$W, $H], [$W / 2, $H + $t], [0, $H]]], ['poly', [[$gx, 0.0001], [$gx + $gw, 0.0001], [$gx + $gw, $gh], [$gx, $gh]]]], 'orange')
                        ->line([0, $H], [$W, $H], 'sol', 's')
                        ->dim([0, 0], [$W, 0], self::u($W, 'm'), -1)
                        ->dim([$W, 0], [$W, $H], self::u($H, 'm'), -1)
                        ->dim([$W, $H], [$W, $H + $t], self::u($t, 'm'), -1)
                        ->side([$gx, $gh], [$gx + $gw, $gh], self::u($gw, 'm'), [$gx, 0])
                        ->side([$gx, 0], [$gx, $gh], self::u($gh, 'm'), [$gx + $gw, 0]),
                ];

            case 'workpiece':
                $W = 2 * mt_rand(3, 7);
                $c = 2 * mt_rand(1, (int) ($W / 2) - 1);
                $H = mt_rand(2, 6);
                $t = mt_rand(2, 5);
                $A1 = $W * $H;
                $A2 = ($W + $c) / 2 * $t;
                $x = ($W - $c) / 2;

                return [
                    'q' => $q,
                    'fields' => [['A', $unit.'²', $A1 + $A2, self::EXACT]],
                    'steps' => [
                        self::p('Zerlege die Figur in ein Rechteck und ein Trapez.'),
                        'A₁ = '.$u($W).' · '.$u($H).' = '.$u($A1, '²'),
                        'A₂ = ('.$u($W).' + '.$u($c).') : 2 · '.$u($t).' = '.$u($A2, '²'),
                        'A = A₁ + A₂ = '.self::b($u($A1 + $A2, '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['poly', [[0, 0], [$W, 0], [$W, $H], [$x + $c, $H + $t], [$x, $H + $t], [0, $H]]]], 'green')
                        ->line([0, $H], [$W, $H], 'sol', 's')
                        ->label([$W / 2, $H / 2], 'A₁', 'part', null, 0, 's')
                        ->label([$W / 2, $H + $t / 2], 'A₂', 'part', null, 0, 's')
                        ->dim([0, 0], [$W, 0], $u($W), -1)
                        ->dim([$W, 0], [$W, $H], $u($H), -1)
                        ->dim([$W, $H], [$W, $H + $t], $u($t), -1, 'given', 'b', 2)
                        ->dim([$x, $H + $t], [$x + $c, $H + $t], $u($c), 1),
                ];

            case 'roundsquare':
                $a = 2 * mt_rand(2, 6);
                $r = $a / 2;
                $A = $a * $a + M_PI * $r * $r;

                return [
                    'q' => 'An zwei Seiten des Quadrats sitzt je ein Halbkreis. Berechne den Flächeninhalt A der Figur.',
                    'fields' => [['A', $unit.'²', round($A, 1), self::ROUND]],
                    'steps' => [
                        self::p('Quadrat und zwei Halbkreise, die zusammen einen Kreis mit r = {0} ergeben.', $u($r)),
                        'A = '.$u($a).' · '.$u($a).' + π · ('.$u($r).')² ≈ '.$u($a * $a, '²').' + '.$u(round(M_PI * $r * $r, 2), '²').' ≈ '.self::b($u(round($A, 1), '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['path', [['M', [0, 0]], ['L', [$a, 0]], ['arc', [$a, $r], $r, -90, 90], ['L', [0, $a]], ['arc', [0, $r], $r, 90, 270]]]], 'pink')
                        ->line([0, 0], [0, $a], 'sol', 's')
                        ->line([$a, 0], [$a, $a], 'sol', 's')
                        ->dim([0, 0], [$a, 0], $u($a), -1),
                ];

            case 'circleInSquare':
                $a = 2 * mt_rand(2, 8);
                $r = $a / 2;
                $A = $a * $a - M_PI * $r * $r;

                return [
                    'q' => 'Der Kreis berührt alle vier Seiten des Quadrats. Berechne den Flächeninhalt A der farbigen Fläche.',
                    'fields' => [['A', $unit.'²', round($A, 1), self::ROUND]],
                    'steps' => [
                        self::p('Quadrat minus Kreis; der Radius ist die halbe Seite: r = {0}.', $u($r)),
                        'A = '.$u($a).' · '.$u($a).' − π · ('.$u($r).')² ≈ '.$u($a * $a, '²').' − '.$u(round(M_PI * $r * $r, 2), '²').' ≈ '.self::b($u(round($A, 1), '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['poly', [[0, 0], [$a, 0], [$a, $a], [0, $a]]], ['circle', [$r, $r], $r]], 'violet')
                        ->dot([$r, $r])
                        ->dim([0, 0], [$a, 0], $u($a), -1),
                ];

            case 'squareInCircle':
                $r = mt_rand(2, 9);
                $Aq = 2 * $r * $r;
                $A = M_PI * $r * $r - $Aq;

                return [
                    'q' => 'Die Ecken des Quadrats liegen auf dem Kreis. Berechne den Flächeninhalt A der farbigen Fläche.',
                    'fields' => [['A', $unit.'²', round($A, 1), self::ROUND]],
                    'steps' => [
                        self::p('Kreis minus Quadrat. Die Diagonalen des Quadrats sind Durchmesser: d = 2 · r = {0}.', $u(2 * $r)),
                        self::p('Das Quadrat ist eine Raute mit zwei gleichen Diagonalen: {0}', 'A = d · d : 2 = '.$u(2 * $r).' · '.$u(2 * $r).' : 2 = '.$u($Aq, '²')),
                        'A = π · ('.$u($r).')² − '.$u($Aq, '²').' ≈ '.$u(round(M_PI * $r * $r, 2), '²').' − '.$u($Aq, '²').' ≈ '.self::b($u(round($A, 1), '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['circle', [0, 0], $r], ['poly', [[$r, 0], [0, $r], [-$r, 0], [0, -$r]]]], 'teal')
                        ->dot([0, 0])
                        ->line([-$r, 0], [$r, 0], 'sol', 's')
                        ->line([0, -$r], [0, $r], 'sol', 's')
                        ->line([0, 0], [$r, 0])
                        ->side([0, 0], [$r, 0], 'r = '.$u($r), [0, $r]),
                ];

            case 'lens':
                $a = mt_rand(3, 10);
                $A = 2 * (M_PI * $a * $a / 4 - $a * $a / 2);

                return [
                    'q' => 'Die zwei Viertelkreise haben den Radius a. Berechne den Flächeninhalt A der farbigen Fläche.',
                    'fields' => [['A', $unit.'²', round($A, 1), self::ROUND]],
                    'steps' => [
                        self::p('Die Diagonale teilt die Fläche in zwei gleiche Teile: je Viertelkreis minus halbes Quadrat.'),
                        'A = 2 · (π · ('.$u($a).')² : 4 − '.$u($a).' · '.$u($a).' : 2) ≈ 2 · ('.$u(round(M_PI * $a * $a / 4, 2), '²').' − '.$u($a * $a / 2, '²').') ≈ '.self::b($u(round($A, 1), '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['poly', [[0, 0], [$a, 0], [$a, $a], [0, $a]]]], 'white')
                        ->shape([['path', [['arc', [0, 0], $a, 0, 90], ['arc', [$a, $a], $a, 180, 270]]]], 'green')
                        ->line([$a, 0], [0, $a], 'sol', 's')
                        ->dot([0, 0])
                        ->dot([$a, $a])
                        ->side([0, 0], [$a, 0], 'a = '.$u($a), [0, $a]),
                ];

            default:
                // Rectangle with a semicircle cut out at the bottom
                $d = 2 * mt_rand(1, 4);
                $W = $d + 2 * mt_rand(2, 5);
                $H = mt_rand((int) ($d / 2) + 2, (int) ($d / 2) + 7);
                $x1 = ($W - $d) / 2;
                $r = $d / 2;
                $A = $W * $H - M_PI * $r * $r / 2;

                return [
                    'q' => 'Unten ist ein Halbkreis ausgeschnitten. Berechne den Flächeninhalt A der farbigen Fläche.',
                    'fields' => [['A', $unit.'²', round($A, 1), self::ROUND]],
                    'steps' => [
                        self::p('Rechteck minus Halbkreis mit r = {0}.', $u($r)),
                        'A = '.$u($W).' · '.$u($H).' − π · ('.$u($r).')² : 2 ≈ '.$u($W * $H, '²').' − '.$u(round(M_PI * $r * $r / 2, 2), '²').' ≈ '.self::b($u(round($A, 1), '²')),
                    ],
                    'svg' => (new FigureAreaSvg())
                        ->shape([['path', [['M', [0, 0]], ['L', [$x1, 0]], ['arc', [$W / 2, 0], $r, 180, 0], ['L', [$W, 0]], ['L', [$W, $H]], ['L', [0, $H]]]]], 'blue')
                        ->line([$x1, 0], [$x1 + $d, 0], 'thin')
                        ->dim([0, 0], [$W, 0], $u($W), -1, 'given', 'b', 2)
                        ->dim([0, 0], [0, $H], $u($H), 1)
                        ->dim([$x1, 0], [$x1 + $d, 0], 'd = '.$u($d), -1),
                ];
        }
    }

    /**
     * Pairs of right triangles with the same leg h: h => list of [other leg, hypotenuse].
     */
    private static function triplesByLeg(): array
    {
        $byLeg = [];

        foreach (self::TRIPLES as [$a, $b, $c]) {
            for ($k = 1; $k * $c <= 45; ++$k) {
                $byLeg[$k * $a][] = [$k * $b, $k * $c];
                $byLeg[$k * $b][] = [$k * $a, $k * $c];
            }
        }

        return array_filter($byLeg, static fn (array $l, int $h): bool => $h >= 4 && $h <= 24, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Composed figure with Pythagoras (as in aufgabenfuchs, Aufgabe 52): the height h comes from
     * a right triangle. Medium: triangle and rectangle. Hard: triangle, rectangle with a
     * semicircle cut out, tower and a second triangle with the same height.
     */
    private function pyComposed(bool $hard, string $unit): array
    {
        $u = static fn (float|int $x, string $sq = ''): string => self::u($x, $unit.$sq);
        $byLeg = self::triplesByLeg();

        // Two different triangles with the same height, if there are
        for ($i = 0; $i < 200; ++$i) {
            $h = array_rand($byLeg);
            [$p, $s1] = Util::pick($byLeg[$h]);
            [$q, $s2] = Util::pick($byLeg[$h]);

            if ($p <= 40 && $q <= 30 && $p >= 4 && ($q !== $p || $i > 150)) {
                break;
            }
        }

        $steps = [
            self::p('Die Höhe h aus dem linken Dreieck (Satz des Pythagoras): {0}', 'h = √(('.$u($s1).')² − ('.$u($p).')²) = '.$u($h)),
        ];
        $A1 = $p * $h / 2;

        if (!$hard) {
            $w = mt_rand(max(4, (int) ($p / 3)), max(6, $p));
            $A2 = $w * $h;
            $A = $A1 + $A2;
            $steps[] = 'A₁ = '.$u($p).' · '.$u($h).' : 2 = '.$u($A1, '²').', A₂ = '.$u($w).' · '.$u($h).' = '.$u($A2, '²');
            $steps[] = 'A = A₁ + A₂ = '.self::b($u($A, '²'));

            return [
                'q' => 'Berechne zuerst die Höhe h mit dem Satz des Pythagoras, dann den Flächeninhalt A der Figur.',
                'fields' => [['h', $unit, $h, self::EXACT], ['A', $unit.'²', $A, self::EXACT]],
                'steps' => $steps,
                'svg' => (new FigureAreaSvg())
                    ->shape([['poly', [[0, 0], [$p + $w, 0], [$p + $w, $h], [$p, $h]]]], 'green')
                    ->line([$p, 0], [$p, $h], 'sol', 's')
                    ->right([$p, 0], [$p + $w, 0], [$p, $h], 's')
                    ->label([$p * .65, $h / 3], 'A₁', 'part', null, 0, 's')
                    ->label([$p + $w / 2, $h / 2], 'A₂', 'part', null, 0, 's')
                    ->side([0, 0], [$p, $h], $u($s1), [$p, 0])
                    ->dim([0, 0], [$p, 0], $u($p), -1)
                    ->dim([$p, 0], [$p + $w, 0], $u($w), -1)
                    ->dim([$p + $w, 0], [$p + $w, $h], 'h = ?', -1, 'ask', 'q')
                    ->dim([$p + $w, 0], [$p + $w, $h], 'h = '.$u($h), -1, 'sol', 's'),
            ];
        }

        $d = 2 * mt_rand(3, max(3, min(10, $h - 1)));
        $r = $d / 2;
        $w = 2 * mt_rand(3, 10);
        $H = mt_rand(6, 20);
        $x1 = $p + $d;
        $x2 = $x1 + $w;
        $A2 = $d * $h - M_PI * $r * $r / 2;
        $A3 = $w * ($h + $H);
        $A4 = $q * $h / 2;
        $A = $A1 + $A2 + $A3 + $A4;
        // Narrow parts get their dimension one row lower, so the labels do not overlap
        $steps[] = self::p('Kontrolle mit dem rechten Dreieck: {0}', '√(('.$u($s2).')² − ('.$u($q).')²) = '.$u($h));
        $steps[] = self::p('Zerlege in Dreieck, Rechteck minus Halbkreis, Turm und Dreieck.');
        $steps[] = 'A₁ = '.$u($p).' · '.$u($h).' : 2 = '.$u($A1, '²');
        $steps[] = 'A₂ = '.$u($d).' · '.$u($h).' − π · ('.$u($r).')² : 2 ≈ '.$u($d * $h, '²').' − '.$u(round(M_PI * $r * $r / 2, 2), '²').' ≈ '.$u(round($A2, 2), '²');
        $steps[] = 'A₃ = '.$u($w).' · ('.$u($h).' + '.$u($H).') = '.$u($A3, '²');
        $steps[] = 'A₄ = '.$u($q).' · '.$u($h).' : 2 = '.$u($A4, '²');
        $steps[] = 'A = A₁ + A₂ + A₃ + A₄ ≈ '.self::b($u(round($A, 1), '²'));

        return [
            'q' => 'Berechne zuerst die Höhe h mit dem Satz des Pythagoras, dann den Flächeninhalt A der Figur.',
            'fields' => [['h', $unit, $h, self::EXACT], ['A', $unit.'²', round($A, 1), self::ROUND]],
            'steps' => $steps,
            'svg' => (new FigureAreaSvg())
                ->shape([['path', [['M', [0, 0]], ['L', [$x2 + $q, 0]], ['L', [$x2, $h]], ['L', [$x2, $h + $H]], ['L', [$x1, $h + $H]], ['L', [$x1, $h]], ['arc', [$p + $r, $h], $r, 0, -180]]]], 'green')
                ->line([$p, 0], [$p, $h], 'sol', 's')
                ->line([$x1, 0], [$x1, $h], 'sol', 's')
                ->line([$x2, 0], [$x2, $h], 'sol', 's')
                ->label([$p * .65, $h / 3], 'A₁', 'part', null, 0, 's')
                ->label([$p + $r, max(.2 * $h, ($h - $r) / 2)], 'A₂', 'part', null, 0, 's')
                ->label([$x1 + $w / 2, ($h + $H) / 2], 'A₃', 'part', null, 0, 's')
                ->label([$x2 + $q / 3, $h / 3], 'A₄', 'part', null, 0, 's')
                ->side([0, 0], [$p, $h], $u($s1), [$p, 0])
                ->side([$x2 + $q, 0], [$x2, $h], $u($s2), [$x2, 0])
                // Bottom dimensions on two rows, so short parts do not overlap
                ->dim([0, 0], [$p, 0], $u($p), -1)
                ->dim([$p, 0], [$x1, 0], $u($d), -1, 'given', 'b', 2)
                ->dim([$x1, 0], [$x2, 0], $u($w), -1)
                ->dim([$x2, 0], [$x2 + $q, 0], $u($q), -1, 'given', 'b', 2)
                ->dim([$x1, $h], [$x1, $h + $H], $u($H), 1)
                ->dim([0, 0], [0, $h], 'h = ?', 1, 'ask', 'q')
                ->dim([0, 0], [0, $h], 'h = '.$u($h), 1, 'sol', 's'),
        ];
    }

    /**
     * Figure in a coordinate system (1 square = 1 cm), as in aufgabenfuchs Aufgabe 30: rectangle,
     * triangle on top, triangle on the right and, at the bottom right, a quarter circle (hard)
     * or a triangle (medium). The lengths are read off the grid.
     */
    private function coordFigure(bool $arc): array
    {
        $x0 = mt_rand(1, 3);
        $a = mt_rand(5, 9);
        $r = mt_rand(4, 8);
        $c = $x0 + $a;
        $yL = $r + mt_rand(2, 5);
        $xR = $c + $r + mt_rand(-1, 1);
        $yR = $r + mt_rand(2, 6);
        $A1 = $a * $r;
        $A2 = $a * ($yL - $r) / 2;
        $A3 = $arc ? M_PI * $r * $r / 4 : $r * $r / 2;
        $A4 = $r * ($yR - $r) / 2;
        $A = $A1 + $A2 + $A3 + $A4;
        $bottom = $arc ? ['arc', [$c, $r], $r, -90, 0] : ['L', [$c + $r, $r]];
        $u = static fn (float|int $x, string $sq = ''): string => self::u($x, 'cm'.$sq);
        $xMax = (int) (5 * ceil(($xR + 2) / 5));
        $yMax = (int) (5 * ceil(($yL > $yR ? $yL : $yR) / 5));

        $steps = [
            $arc
                ? self::p('Lies die Längen im Gitter ab. Zerlege in ein Rechteck, zwei Dreiecke und einen Viertelkreis mit r = {0}.', $u($r))
                : self::p('Lies die Längen im Gitter ab. Zerlege in ein Rechteck und drei Dreiecke.'),
            'A₁ = '.$u($a).' · '.$u($r).' = '.$u($A1, '²'),
            'A₂ = '.$u($a).' · '.$u($yL - $r).' : 2 = '.$u($A2, '²'),
            $arc
                ? 'A₃ = π · ('.$u($r).')² : 4 ≈ '.$u(round($A3, 2), '²')
                : 'A₃ = '.$u($r).' · '.$u($r).' : 2 = '.$u($A3, '²'),
            'A₄ = '.$u($r).' · '.$u($yR - $r).' : 2 = '.$u($A4, '²'),
            'A = A₁ + A₂ + A₃ + A₄ '.($arc ? '≈ ' : '= ').self::b($u($arc ? round($A, 1) : $A, '²')),
        ];

        return [
            'q' => 'Jedes Häuschen ist 1 cm lang. Lies die Masse im Koordinatensystem ab und berechne den Flächeninhalt A der farbigen Fläche.',
            'fields' => [['A', 'cm²', $arc ? round($A, 1) : $A, $arc ? self::ROUND : self::EXACT]],
            'steps' => $steps,
            'svg' => (new FigureAreaSvg())
                ->grid(1)
                ->axes($xMax, $yMax)
                ->shape([['path', [['M', [$x0, 0]], ['L', [$c, 0]], $bottom, ['L', [$xR, $yR]], ['L', [$c, $r]], ['L', [$x0, $yL]]]]], 'green')
                ->line([$c, 0], [$c, $r], 'sol', 's')
                ->line([$x0, $r], [$c + $r, $r], 'sol', 's')
                ->label([$x0 + $a / 2, $r / 2], 'A₁', 'part', null, 0, 's')
                ->label([$x0 + $a / 3, $r + ($yL - $r) / 3], 'A₂', 'part', null, 0, 's')
                ->label([$c + $r * .35, $r * .55], 'A₃', 'part', null, 0, 's')
                ->label([$c + $r * .6, $r + ($yR - $r) / 3], 'A₄', 'part', null, 0, 's'),
        ];
    }
}
