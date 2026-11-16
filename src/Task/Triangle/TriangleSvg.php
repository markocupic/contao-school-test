<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Triangle;

/**
 * Evaluation sheet: the student's triangle ABC (scaled to fit) with the solution laid onto
 * the side AB, green dashed. Sides the student drew: green if all three are there, else red.
 */
final class TriangleSvg
{
    private const W = 300;

    private const H = 210;

    private const PAD = 18;

    /**
     * @param array<int, array{0: float, 1: float}|null> $given A, B, C
     * @param list<array{0: int, 1: int}>                $edges
     */
    public function render(array $a, array $given, array $edges, bool $sidesOk): string
    {
        $pts = array_slice($given, 0, 3);
        $placed = array_values(array_filter($pts));

        if (!$placed) {
            return '';
        }

        // Solution on the student's side AB, on the side of C
        $solution = [];

        if (isset($pts[0], $pts[1])) {
            [$A, $B] = [$pts[0], $pts[1]];
            $len = hypot($B[0] - $A[0], $B[1] - $A[1]) ?: 1;
            $u = [($B[0] - $A[0]) / $len, ($B[1] - $A[1]) / $len];
            $side = 1;

            if (isset($pts[2])) {
                $side = $u[0] * ($pts[2][1] - $A[1]) - $u[1] * ($pts[2][0] - $A[0]) < 0 ? -1 : 1;
            }

            foreach ($a['shape'] as $p) {
                $solution[] = [$A[0] + $p[0] * $u[0] - $side * $p[1] * $u[1], $A[1] + $p[0] * $u[1] + $side * $p[1] * $u[0]];
            }
        }

        // Fit everything into the drawing
        $all = array_merge($placed, $solution);
        $xs = array_column($all, 0);
        $ys = array_column($all, 1);
        $minX = min($xs);
        $minY = min($ys);
        $scale = min((self::W - 2 * self::PAD) / max(0.5, max($xs) - $minX), (self::H - 2 * self::PAD) / max(0.5, max($ys) - $minY), 30);
        $x = static fn (float $v): string => (string) round(self::PAD + ($v - $minX) * $scale, 1);
        $y = static fn (float $v): string => (string) round(self::H - self::PAD - ($v - $minY) * $scale, 1);

        $svg = '<svg class="geo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.self::W.' '.self::H.'" width="'.self::W.'" height="'.self::H.'">';

        if ($solution) {
            $svg .= '<polygon points="'.implode(' ', array_map(static fn (array $p): string => $x($p[0]).','.$y($p[1]), $solution)).'" fill="none" stroke="#1a7f43" stroke-width="1.6" stroke-dasharray="5 3"/>';
        }

        $color = $sidesOk ? '#1a7f43' : '#c62828';

        foreach ($edges as [$i, $j]) {
            if (isset($pts[$i], $pts[$j])) {
                $svg .= '<line x1="'.$x($pts[$i][0]).'" y1="'.$y($pts[$i][1]).'" x2="'.$x($pts[$j][0]).'" y2="'.$y($pts[$j][1]).'" stroke="'.$color.'" stroke-width="1.4"/>';
            }
        }

        $cx = array_sum(array_column($placed, 0)) / \count($placed);
        $cy = array_sum(array_column($placed, 1)) / \count($placed);

        foreach ($pts as $i => $p) {
            if (!$p) {
                continue;
            }

            $dx = $p[0] - $cx;
            $dy = $p[1] - $cy;
            $l = hypot($dx, $dy) ?: 1;
            $svg .= '<circle cx="'.$x($p[0]).'" cy="'.$y($p[1]).'" r="3" fill="#444"/>';
            $svg .= '<text x="'.round((float) $x($p[0]) + $dx / $l * 11, 1).'" y="'.round((float) $y($p[1]) - $dy / $l * 11 + 4, 1).'" font-size="11" font-weight="700" font-family="Arial, sans-serif" text-anchor="middle" fill="#444">'.\chr(65 + $i).'</text>';
        }

        return $svg.'</svg>';
    }
}
