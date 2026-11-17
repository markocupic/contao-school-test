<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Binomial;

/**
 * Drawings for "Produkte von Binomen": figures made of rectangles (blue, with the
 * measures at the edges) and the rectangle model (parts labelled with their areas).
 */
final class BinomialSvg
{
    private const FILL = '#cfdcf3';

    private const LINE = '#3d5f9e';

    /**
     * Blue figure: grid of columns and rows, only the filled cells are drawn.
     * Column measures below the figure, row measures on the left.
     *
     * @param list<string>     $cols   labels from left to right ("a", "b", "10", "x")
     * @param list<string>     $rows   labels from top to bottom
     * @param list<list<bool>> $filled [row][col]
     */
    public static function figure(array $cols, array $rows, array $filled): string
    {
        $left = 34;
        $top = 8;
        $bottom = 26;
        $ws = array_map(self::size(...), $cols);
        $hs = array_map(self::size(...), $rows);
        $w = $left + array_sum($ws) + 10;
        $h = $top + array_sum($hs) + $bottom;
        $xs = self::offsets($ws, $left);
        $ys = self::offsets($hs, $top);

        $svg = '<svg class="st-binfig" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'" width="'.$w.'" height="'.$h.'" font-family="Arial, sans-serif" font-size="13">';

        foreach ($filled as $r => $row) {
            foreach ($row as $c => $on) {
                if ($on) {
                    $svg .= '<rect x="'.$xs[$c].'" y="'.$ys[$r].'" width="'.$ws[$c].'" height="'.$hs[$r].'" fill="'.self::FILL.'" stroke="'.self::LINE.'" stroke-width="1"/>';
                }
            }
        }

        foreach ($cols as $c => $label) {
            $svg .= '<text x="'.($xs[$c] + $ws[$c] / 2).'" y="'.($top + array_sum($hs) + 17).'" text-anchor="middle"'.self::style($label).'>'.self::esc($label).'</text>';
        }

        foreach ($rows as $r => $label) {
            $svg .= '<text x="'.($left - 7).'" y="'.($ys[$r] + $hs[$r] / 2 + 4).'" text-anchor="end"'.self::style($label).'>'.self::esc($label).'</text>';
        }

        return $svg.'</svg>';
    }

    /**
     * Rectangle model: columns and rows with the area of every part written into it.
     * The side lengths are not shown.
     *
     * @param list<string>       $cols  monomials of the width (plain, for the size)
     * @param list<string>       $rows  monomials of the height
     * @param list<list<string>> $cells [row][col] area as HTML (with <sup>)
     */
    public static function model(array $cols, array $rows, array $cells): string
    {
        $ws = array_map(self::monomialSize(...), $cols);
        $hs = array_map(self::monomialSize(...), $rows);
        $pad = 4;
        $w = array_sum($ws) + 2 * $pad;
        $h = array_sum($hs) + 2 * $pad;
        $xs = self::offsets($ws, $pad);
        $ys = self::offsets($hs, $pad);

        $svg = '<svg class="st-binfig" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'" width="'.$w.'" height="'.$h.'" font-family="Arial, sans-serif" font-size="14">';

        foreach ($cells as $r => $row) {
            foreach ($row as $c => $text) {
                $svg .= '<rect x="'.$xs[$c].'" y="'.$ys[$r].'" width="'.$ws[$c].'" height="'.$hs[$r].'" fill="'.self::FILL.'" stroke="'.self::LINE.'" stroke-width="1"/>';
                $svg .= '<text x="'.($xs[$c] + $ws[$c] / 2).'" y="'.($ys[$r] + $hs[$r] / 2 + 5).'" text-anchor="middle">'.self::svgText($text).'</text>';
            }
        }

        return $svg.'</svg>';
    }

    /**
     * <sup> as tspan, everything else as text.
     */
    public static function svgText(string $html): string
    {
        $parts = preg_split('/(<sup>.*?<\/sup>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $out = '';

        foreach ($parts as $p) {
            if (preg_match('/^<sup>(.*)<\/sup>$/', $p, $m)) {
                $out .= '<tspan baseline-shift="super" font-size="10">'.self::esc(strip_tags($m[1])).'</tspan>';
            } else {
                $out .= '<tspan>'.self::esc(html_entity_decode(strip_tags($p))).'</tspan>';
            }
        }

        return $out;
    }

    /**
     * Pixels for a measure: variables a little wider than numbers.
     */
    private static function size(string $label): int
    {
        return match (true) {
            'a' === $label => 48,
            'b' === $label => 28,
            'x' === $label => 62,
            is_numeric($label) => (float) $label >= 10 ? 40 : 30,
            default => 40,
        };
    }

    private static function monomialSize(string $m): int
    {
        if (preg_match('/[a-z]/', $m)) {
            return 64 + 8 * max(0, \strlen($m) - 1);
        }

        return max(26, min(48, 18 + 3 * (int) $m));
    }

    private static function esc(string $s): string
    {
        return str_replace(['&', '<', '>', '"'], ['&amp;', '&lt;', '&gt;', '&quot;'], $s);
    }

    private static function style(string $label): string
    {
        return preg_match('/^[a-z]$/', $label) ? ' font-style="italic"' : '';
    }

    /**
     * @param list<int> $sizes
     *
     * @return list<int>
     */
    private static function offsets(array $sizes, int $start): array
    {
        $out = [];

        foreach ($sizes as $s) {
            $out[] = $start;
            $start += $s;
        }

        return $out;
    }
}
