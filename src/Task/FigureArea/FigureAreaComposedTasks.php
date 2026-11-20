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
    private const MORE_COMPOSED = ['tshape', 'stairs', 'arrow', 'ushape', 'star', 'gridpoly', 'hall', 'workpiece', 'roundsquare', 'circleInSquare', 'squareInCircle', 'lens', 'semicut'];

    private function composedMore(string $variant, string $unit): array
    {
        $q = 'Berechne den Flächeninhalt A der farbigen Fläche.';
        $u = static fn (float|int $x, string $sq = ''): string => self::u($x, $unit.$sq);

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
}
