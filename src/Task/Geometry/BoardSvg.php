<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Geometry;

/**
 * Draws a reflection/rotation/translation task as SVG for the printed evaluation sheet:
 * the figure, axis, centre or arrow, the solution as a dashed outline and the
 * student's points (green right, red wrong).
 */
final class BoardSvg
{
    private const S = 16;

    private const P = 12;

    private const LETTERS = 'ABCDEFGH';

    /**
     * @param array<int, array{0: int, 1: int}|null> $given
     * @param array<int, bool>                        $correct
     */
    public function render(array $a, array $given, array $correct): string
    {
        $d = $a['draw'];
        [$w, $h] = $d['grid'];
        $x = static fn (float $v): float => self::P + $v * self::S;
        $y = static fn (float $v): float => self::P + ($h - $v) * self::S;
        $fmt = static fn (float $v): string => rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
        $vw = $w * self::S + 2 * self::P;
        $vh = $h * self::S + 2 * self::P;
        $violet = '#6b2fa8';

        $svg = '<svg class="geo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$vw.' '.$vh.'" width="'.$vw.'" height="'.$vh.'">';
        $svg .= '<defs><marker id="ah" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="'.$violet.'"/></marker></defs>';

        $free = !empty($d['free']);

        if ($free) {
            // Construction without squares: blank paper
            $svg .= '<rect x="'.$x(0).'" y="'.$y($h).'" width="'.($w * self::S).'" height="'.($h * self::S).'" fill="none" stroke="#d6d6d6" stroke-width=".7"/>';
        } else {
            for ($i = 0; $i <= $w; ++$i) {
                $svg .= '<line x1="'.$x($i).'" y1="'.$y(0).'" x2="'.$x($i).'" y2="'.$y($h).'" stroke="#d6d6d6" stroke-width=".7"/>';
            }

            for ($i = 0; $i <= $h; ++$i) {
                $svg .= '<line x1="'.$x(0).'" y1="'.$y($i).'" x2="'.$x($w).'" y2="'.$y($i).'" stroke="#d6d6d6" stroke-width=".7"/>';
            }
        }

        if (isset($d['seg'])) {
            [$p, $q] = $d['seg'];
            $svg .= '<line x1="'.$fmt($x($p[0])).'" y1="'.$fmt($y($p[1])).'" x2="'.$fmt($x($q[0])).'" y2="'.$fmt($y($q[1])).'" stroke="'.$violet.'" stroke-width="2"/>';
            $svg .= $this->text($x($p[0] + ($q[0] - $p[0]) * .86) + 7, $y($p[1] + ($q[1] - $p[1]) * .86) - 4, 's', $violet, true);
        }

        if (isset($d['vec'], $d['vecStart'])) {
            [$sx, $sy] = $d['vecStart'];
            $ex = $sx + $d['vec'][0];
            $ey = $sy + $d['vec'][1];
            $svg .= '<line x1="'.$x($sx).'" y1="'.$y($sy).'" x2="'.$x($ex).'" y2="'.$y($ey).'" stroke="'.$violet.'" stroke-width="2" marker-end="url(#ah)"/>';
            $svg .= $this->text(($x($sx) + $x($ex)) / 2 + 6, ($y($sy) + $y($ey)) / 2 - 5, 'v', $violet, true);
        }

        if (isset($d['center'])) {
            [$zx, $zy] = $d['center'];
            $svg .= '<circle cx="'.$x($zx).'" cy="'.$y($zy).'" r="3" fill="'.$violet.'"/>'.$this->text($x($zx) + 5, $y($zy) + 12, 'Z', $violet, true);
        }

        // Figure
        $svg .= $this->poly($d['pts'], $x, $y, 'fill="#dfe6f5" stroke="#1d3f8f" stroke-width="1.5"');
        $svg .= $this->labels($d['pts'], '', '#1d3f8f', $x, $y);

        // Solution, for constructions with the tolerance circle around every image point
        $svg .= $this->poly($a['img'], $x, $y, 'fill="none" stroke="#1a7f43" stroke-width="1.8" stroke-dasharray="5 3"');

        if ($free) {
            foreach ($a['img'] as $p) {
                $svg .= '<circle cx="'.round($x($p[0]), 2).'" cy="'.round($y($p[1]), 2).'" r="'.round((float) ($a['tol'] ?? 0.6) * self::S, 2).'" fill="none" stroke="#1a7f43" stroke-width=".8" stroke-dasharray="2 2"/>';
            }
        }

        // Student's points
        $placed = array_values(array_filter($given));

        if (\count($placed) === \count($a['img'])) {
            $svg .= $this->poly($placed, $x, $y, 'fill="none" stroke="#888" stroke-width="1"');
        }

        foreach ($given as $i => $p) {
            if (!$p) {
                continue;
            }

            $color = !empty($correct[$i]) ? '#1a7f43' : '#c62828';
            $svg .= '<circle cx="'.$x($p[0]).'" cy="'.$y($p[1]).'" r="4" fill="'.$color.'" stroke="#fff" stroke-width="1.2"/>';
        }

        $svg .= $this->labels($given, "\u{2032}", '#444', $x, $y);

        return $svg.'</svg>';
    }

    private function poly(array $pts, \Closure $x, \Closure $y, string $attr): string
    {
        $xy = implode(' ', array_map(static fn (array $p): string => $x($p[0]).','.$y($p[1]), $pts));

        return \count($pts) > 2 ? '<polygon points="'.$xy.'" '.$attr.'/>' : '<polyline points="'.$xy.'" '.$attr.'/>';
    }

    /**
     * @param array<int, array{0: int, 1: int}|null> $pts
     */
    private function labels(array $pts, string $suffix, string $color, \Closure $x, \Closure $y): string
    {
        $set = array_filter($pts);

        if (!$set) {
            return '';
        }

        $cx = array_sum(array_column($set, 0)) / \count($set);
        $cy = array_sum(array_column($set, 1)) / \count($set);
        $out = '';

        foreach ($pts as $i => $p) {
            if (!$p) {
                continue;
            }

            $dx = $p[0] - $cx;
            $dy = $p[1] - $cy;
            $len = hypot($dx, $dy) ?: 1;
            $out .= $this->text($x($p[0]) + $dx / $len * 9 - 4, $y($p[1]) - $dy / $len * 9 + 4, self::LETTERS[$i].$suffix, $color);
        }

        return $out;
    }

    private function text(float $px, float $py, string $text, string $color, bool $italic = false): string
    {
        return '<text x="'.round($px, 1).'" y="'.round($py, 1).'" fill="'.$color.'" font-size="10" font-weight="700" font-family="Arial, sans-serif"'.($italic ? ' font-style="italic"' : '').'>'.htmlspecialchars($text, ENT_QUOTES | ENT_XML1).'</text>';
    }
}
