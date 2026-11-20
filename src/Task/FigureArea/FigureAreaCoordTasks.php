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
 * Areas in the coordinate system (1 unit = 1 cm): figures with labelled corners A(x|y), the
 * lengths come from the coordinates. Hard: the frame method (rectangle around the figure minus
 * right triangles) and a figure with a quarter circle read off the grid.
 */
trait FigureAreaCoordTasks
{
    private function coordTask(string $level): array
    {
        $variant = match ($level) {
            'easy' => $this->variant('koordinaten', ['cRect', 'cTri', 'cPara', 'cRightTri']),
            'medium' => $this->variant('koordinaten', ['cTrap', 'cLshape', 'coordPoly', 'cHouse']),
            default => $this->variant('koordinaten', ['cFrame', 'cTriFrame', 'coordArc', 'cOct']),
        };

        return match ($variant) {
            'coordPoly', 'coordArc' => $this->coordFigure('coordArc' === $variant),
            'cFrame', 'cTriFrame', 'cOct' => $this->coordFrame($variant),
            default => $this->coordSimple($variant),
        };
    }

    /**
     * Grid, axes, figure and the corners with their coordinates.
     *
     * @param array<string, array{0: int, 1: int}> $corners name => point
     */
    private static function coordSvg(array $corners, string $fill, array $extra = []): FigureAreaSvg
    {
        $pts = array_values($corners);
        $xs = array_column($pts, 0);
        $ys = array_column($pts, 1);
        $c = [array_sum($xs) / \count($xs), array_sum($ys) / \count($ys)];
        $svg = (new FigureAreaSvg())
            ->grid(1)
            ->axes((int) (5 * ceil((max($xs) + 1) / 5)), (int) (5 * ceil((max($ys) + 1) / 5)))
            ->shape([['poly', $pts], ...$extra], $fill);

        foreach ($corners as $name => $p) {
            $svg->dot($p)->label($p, $name.'('.$p[0].'|'.$p[1].')', 'vertex', $c, 16);
        }

        return $svg;
    }

    private function coordSimple(string $variant): array
    {
        $u = static fn (float|int $x, string $sq = ''): string => self::u($x, 'cm'.$sq);
        $x0 = mt_rand(1, 3);
        $y0 = mt_rand(1, 3);

        if ('cRect' === $variant) {
            $a = mt_rand(3, 9);
            $b = mt_rand(2, 6);
            $svg = self::coordSvg(['A' => [$x0, $y0], 'B' => [$x0 + $a, $y0], 'C' => [$x0 + $a, $y0 + $b], 'D' => [$x0, $y0 + $b]], 'blue');

            return [
                'q' => 'Die Eckpunkte sind im Koordinatensystem gegeben (1 Einheit = 1 cm). Berechne den Flächeninhalt A und den Umfang U des Rechtecks.',
                'fields' => [['A', 'cm²', $a * $b, self::EXACT], ['U', 'cm', 2 * ($a + $b), self::EXACT]],
                'steps' => [
                    self::p('Längen aus den Koordinaten: {0}', 'a = '.($x0 + $a).' − '.$x0.' = '.$u($a).', b = '.($y0 + $b).' − '.$y0.' = '.$u($b)),
                    'A = a · b = '.$u($a).' · '.$u($b).' = '.self::b($u($a * $b, '²')),
                    'U = 2 · (a + b) = '.self::b($u(2 * ($a + $b))),
                ],
                'svg' => $svg,
            ];
        }

        if ('cTri' === $variant) {
            $g = mt_rand(4, 9);
            $h = mt_rand(3, 7);
            $px = $x0 + mt_rand(-1, $g + 1);
            $px = max(0, $px);
            $A = $g * $h / 2;
            $svg = self::coordSvg(['A' => [$x0, $y0], 'B' => [$x0 + $g, $y0], 'C' => [$px, $y0 + $h]], 'yellow')
                ->line([$px, $y0 + $h], [$px, $y0], 'sol', 's');

            return [
                'q' => 'Die Eckpunkte sind im Koordinatensystem gegeben (1 Einheit = 1 cm). Berechne den Flächeninhalt A des Dreiecks.',
                'fields' => [['A', 'cm²', $A, self::EXACT]],
                'steps' => [
                    self::p('Längen aus den Koordinaten: {0}', 'g = '.($x0 + $g).' − '.$x0.' = '.$u($g).', h = '.($y0 + $h).' − '.$y0.' = '.$u($h)),
                    'A = g · h : 2 = '.$u($g).' · '.$u($h).' : 2 = '.self::b($u($A, '²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('cPara' === $variant) {
            $g = mt_rand(4, 8);
            $h = mt_rand(2, 6);
            $s = mt_rand(1, 3);
            $A = $g * $h;
            $svg = self::coordSvg(['A' => [$x0, $y0], 'B' => [$x0 + $g, $y0], 'C' => [$x0 + $g + $s, $y0 + $h], 'D' => [$x0 + $s, $y0 + $h]], 'violet')
                ->line([$x0 + $s, $y0 + $h], [$x0 + $s, $y0], 'sol', 's');

            return [
                'q' => 'Die Eckpunkte sind im Koordinatensystem gegeben (1 Einheit = 1 cm). Berechne den Flächeninhalt A des Parallelogramms.',
                'fields' => [['A', 'cm²', $A, self::EXACT]],
                'steps' => [
                    self::p('Längen aus den Koordinaten: {0}', 'g = '.($x0 + $g).' − '.$x0.' = '.$u($g).', h = '.($y0 + $h).' − '.$y0.' = '.$u($h)),
                    'A = g · h = '.$u($g).' · '.$u($h).' = '.self::b($u($A, '²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('cRightTri' === $variant) {
            $a = mt_rand(3, 9);
            $b = mt_rand(2, 7);
            $A = $a * $b / 2;
            $svg = self::coordSvg(['A' => [$x0, $y0], 'B' => [$x0 + $a, $y0], 'C' => [$x0, $y0 + $b]], 'yellow')
                ->right([$x0, $y0], [$x0 + 1, $y0], [$x0, $y0 + 1]);

            return [
                'q' => 'Die Eckpunkte sind im Koordinatensystem gegeben (1 Einheit = 1 cm). Berechne den Flächeninhalt A des Dreiecks.',
                'fields' => [['A', 'cm²', $A, self::EXACT]],
                'steps' => [
                    self::p('Längen aus den Koordinaten: {0}', 'AB = '.($x0 + $a).' − '.$x0.' = '.$u($a).', AC = '.($y0 + $b).' − '.$y0.' = '.$u($b)),
                    self::p('Die Katheten stehen senkrecht aufeinander: Die eine ist die Grundseite, die andere die Höhe.'),
                    'A = '.$u($a).' · '.$u($b).' : 2 = '.self::b($u($A, '²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('cHouse' === $variant) {
            $W = 2 * mt_rand(2, 5);
            $H = mt_rand(2, 5);
            $t = mt_rand(2, 4);
            $A1 = $W * $H;
            $A2 = $W * $t / 2;
            $svg = self::coordSvg(['A' => [$x0, $y0], 'B' => [$x0 + $W, $y0], 'C' => [$x0 + $W, $y0 + $H], 'D' => [$x0 + $W / 2, $y0 + $H + $t], 'E' => [$x0, $y0 + $H]], 'orange')
                ->line([$x0, $y0 + $H], [$x0 + $W, $y0 + $H], 'sol', 's')
                ->label([$x0 + $W / 2, $y0 + $H / 2], 'A₁', 'part', null, 0, 's')
                ->label([$x0 + $W / 2, $y0 + $H + $t / 3], 'A₂', 'part', null, 0, 's');

            return [
                'q' => 'Die Eckpunkte sind im Koordinatensystem gegeben (1 Einheit = 1 cm). Berechne den Flächeninhalt A der Figur.',
                'fields' => [['A', 'cm²', $A1 + $A2, self::EXACT]],
                'steps' => [
                    self::p('Zerlege die Figur in ein Rechteck und ein Dreieck.'),
                    'A₁ = '.$u($W).' · '.$u($H).' = '.$u($A1, '²').', A₂ = '.$u($W).' · '.$u($t).' : 2 = '.$u($A2, '²'),
                    'A = A₁ + A₂ = '.self::b($u($A1 + $A2, '²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('cTrap' === $variant) {
            $a = mt_rand(6, 11);
            $c = mt_rand(2, $a - 2);
            $x1 = $x0 + mt_rand(0, $a - $c);
            $h = mt_rand(3, 7);
            $A = ($a + $c) * $h / 2;
            $svg = self::coordSvg(['A' => [$x0, $y0], 'B' => [$x0 + $a, $y0], 'C' => [$x1 + $c, $y0 + $h], 'D' => [$x1, $y0 + $h]], 'green')
                ->line([$x1, $y0 + $h], [$x1, $y0], 'sol', 's');

            return [
                'q' => 'Die Eckpunkte sind im Koordinatensystem gegeben (1 Einheit = 1 cm). Berechne den Flächeninhalt A des Trapezes.',
                'fields' => [['A', 'cm²', $A, self::EXACT]],
                'steps' => [
                    self::p('Längen aus den Koordinaten: {0}', 'a = '.$u($a).', c = '.$u($c).', h = '.($y0 + $h).' − '.$y0.' = '.$u($h)),
                    'A = (a + c) : 2 · h = ('.$u($a).' + '.$u($c).') : 2 · '.$u($h).' = '.self::b($u($A, '²')),
                ],
                'svg' => $svg,
            ];
        }

        // L shape from six corners
        $W = mt_rand(6, 10);
        $H = mt_rand(5, 8);
        $w1 = mt_rand(2, $W - 2);
        $h2 = mt_rand(1, $H - 2);
        $A1 = $w1 * $H;
        $A2 = ($W - $w1) * $h2;
        $svg = self::coordSvg(['A' => [$x0, $y0], 'B' => [$x0 + $W, $y0], 'C' => [$x0 + $W, $y0 + $h2], 'D' => [$x0 + $w1, $y0 + $h2], 'E' => [$x0 + $w1, $y0 + $H], 'F' => [$x0, $y0 + $H]], 'teal')
            ->line([$x0 + $w1, $y0], [$x0 + $w1, $y0 + $h2], 'sol', 's')
            ->label([$x0 + $w1 / 2, $y0 + $H / 2], 'A₁', 'part', null, 0, 's')
            ->label([$x0 + ($W + $w1) / 2, $y0 + $h2 / 2], 'A₂', 'part', null, 0, 's');

        return [
            'q' => 'Die Eckpunkte sind im Koordinatensystem gegeben (1 Einheit = 1 cm). Berechne den Flächeninhalt A der Figur.',
            'fields' => [['A', 'cm²', $A1 + $A2, self::EXACT]],
            'steps' => [
                self::p('Zerlege die Figur in zwei Rechtecke.'),
                'A₁ = '.$u($w1).' · '.$u($H).' = '.$u($A1, '²').', A₂ = '.$u($W - $w1).' · '.$u($h2).' = '.$u($A2, '²'),
                'A = A₁ + A₂ = '.self::b($u($A1 + $A2, '²')),
            ],
            'svg' => $svg,
        ];
    }

    /**
     * Frame method: a quadrilateral or triangle with its corners on the sides of a rectangle.
     */
    private function coordFrame(string $variant): array
    {
        $u = static fn (float|int $x, string $sq = ''): string => self::u($x, 'cm'.$sq);
        $x0 = mt_rand(1, 3);
        $y0 = mt_rand(1, 3);
        $w = mt_rand(5, 10);
        $h = mt_rand(4, 8);
        $x1 = $x0 + $w;
        $y1 = $y0 + $h;
        $R = $w * $h;

        if ('cOct' === $variant) {
            // Rectangle with the four corners cut off
            $cut = static fn (int $max): array => [mt_rand(1, max(1, intdiv($max, 2) - 1)), mt_rand(1, max(1, intdiv($max, 2) - 1))];
            [$a1, $a2] = $cut($w);
            [$a3, $a4] = $cut($w);
            [$b1, $b2] = $cut($h);
            [$b3, $b4] = $cut($h);
            $corners = ['A' => [$x0 + $a1, $y0], 'B' => [$x1 - $a2, $y0], 'C' => [$x1, $y0 + $b2], 'D' => [$x1, $y1 - $b3], 'E' => [$x1 - $a3, $y1], 'F' => [$x0 + $a4, $y1], 'G' => [$x0, $y1 - $b4], 'H' => [$x0, $y0 + $b1]];
            $tris = [[$a1, $b1], [$a2, $b2], [$a3, $b3], [$a4, $b4]];
            $q = 'Die Eckpunkte sind im Koordinatensystem gegeben (1 Einheit = 1 cm). Berechne den Flächeninhalt A der Figur.';
        } elseif ('cFrame' === $variant) {
            $a = mt_rand(1, $w - 1);
            $b = mt_rand(1, $h - 1);
            $c = mt_rand(1, $w - 1);
            $d = mt_rand(1, $h - 1);
            $corners = ['A' => [$x0 + $a, $y0], 'B' => [$x1, $y0 + $b], 'C' => [$x1 - $c, $y1], 'D' => [$x0, $y1 - $d]];
            $tris = [[$w - $a, $b], [$c, $h - $b], [$w - $c, $d], [$a, $h - $d]];
            $q = 'Die Eckpunkte sind im Koordinatensystem gegeben (1 Einheit = 1 cm). Berechne den Flächeninhalt A des Vierecks.';
        } else {
            $e = mt_rand(1, $h - 1);
            $f = mt_rand(1, $w - 1);
            $corners = ['A' => [$x0, $y0], 'B' => [$x1, $y0 + $e], 'C' => [$x0 + $f, $y1]];
            $tris = [[$w, $e], [$w - $f, $h - $e], [$f, $h]];
            $q = 'Die Eckpunkte sind im Koordinatensystem gegeben (1 Einheit = 1 cm). Berechne den Flächeninhalt A des Dreiecks.';
        }

        $sum = 0;
        $parts = [];

        foreach ($tris as [$p, $r]) {
            $sum += $p * $r / 2;
            $parts[] = $p.' · '.$r.' : 2';
        }

        $A = $R - $sum;
        $svg = self::coordSvg($corners, 'orange')
            ->line([$x0, $y0], [$x1, $y0], 'sol', 's')
            ->line([$x1, $y0], [$x1, $y1], 'sol', 's')
            ->line([$x1, $y1], [$x0, $y1], 'sol', 's')
            ->line([$x0, $y1], [$x0, $y0], 'sol', 's');

        return [
            'q' => $q,
            'fields' => [['A', 'cm²', $A, self::EXACT]],
            'steps' => [
                self::p('Rahmen-Methode: Rechteck um die Figur minus die rechtwinkligen Dreiecke an den Ecken.'),
                self::p('Rechteck: {0}', $u($w).' · '.$u($h).' = '.$u($R, '²')),
                self::p('Dreiecke: {0}', implode(' + ', $parts).' = '.$u($sum, '²')),
                'A = '.$u($R, '²').' − '.$u($sum, '²').' = '.self::b($u($A, '²')),
            ],
            'svg' => $svg,
        ];
    }
}
