<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\FigureArea;

/**
 * Sketch of an area task: coloured figures with labelled sides, heights, radii and
 * dimension lines. Built in model coordinates (y up) and fitted into the picture.
 *
 * Every element is drawn in the question ('q'), in the solution ('s') or in both ('b').
 */
final class FigureAreaSvg
{
    public const W = 560;

    public const H = 330;

    public const FILLS = [
        'yellow' => '#fde68a',
        'blue' => '#bfdbfe',
        'violet' => '#ddd6fe',
        'green' => '#bbf7d0',
        'pink' => '#fbcfe8',
        'orange' => '#fed7aa',
        'teal' => '#99f6e4',
        'white' => '#ffffff',
    ];

    private const INK = '#1e293b';

    private const ASK = '#c2410c';

    private const SOL = '#15803d';

    private array $shapes = [];

    private array $lines = [];

    private array $labels = [];

    private array $rights = [];

    private array $dims = [];

    private array $dots = [];

    private float|null $grid = null;

    /**
     * A filled figure. Each part is ['poly', [[x, y], …]], ['circle', [x, y], r] or
     * ['path', [cmd, …]] with the commands ['M', [x, y]], ['L', [x, y]] and
     * ['arc', [cx, cy], r, from°, to°] (counterclockwise). Parts are combined even-odd, so a
     * part inside another one is a hole.
     */
    public function shape(array $parts, string $fill, string $when = 'b', bool $dashed = false): self
    {
        $this->shapes[] = ['parts' => $parts, 'fill' => self::FILLS[$fill] ?? $fill, 'when' => $when, 'dashed' => $dashed];

        return $this;
    }

    /**
     * Inner line: 'aux' dashed grey (height, radius, diagonal), 'sol' dashed green (solution only), 'edge' solid.
     */
    public function line(array $a, array $b, string $style = 'aux', string $when = 'b'): self
    {
        $this->lines[] = ['a' => $a, 'b' => $b, 'style' => $style, 'when' => $when];

        return $this;
    }

    /**
     * Text at a point, pushed $dist pixels away from $from (a model point) or centred if null.
     * Kinds: 'given', 'ask' (orange), 'sol' (green), 'vertex', 'part'.
     */
    public function label(array $p, string $text, string $kind = 'given', array|null $from = null, float $dist = 0, string $when = 'b'): self
    {
        $this->labels[] = ['p' => $p, 'text' => $text, 'kind' => $kind, 'from' => $from, 'dist' => $dist, 'when' => $when];

        return $this;
    }

    /**
     * Label of a side or line a–b, on the side away from $ref.
     */
    public function side(array $a, array $b, string $text, array $ref, string $kind = 'given', string $when = 'b', float $t = 0.5): self
    {
        $this->labels[] = ['seg' => [$a, $b], 'ref' => $ref, 'text' => $text, 'kind' => $kind, 'when' => $when, 't' => $t];

        return $this;
    }

    /**
     * Unknown value: "h = ?" in the question, "h = 4 cm" in the solution.
     */
    public function sideAsk(array $a, array $b, string $name, string $value, array $ref, float $t = 0.5): self
    {
        $this->side($a, $b, $name.' = ?', $ref, 'ask', 'q', $t);

        return $this->side($a, $b, $name.' = '.$value, $ref, 'sol', 's', $t);
    }

    /**
     * Right angle at $v between the directions to $p and $q.
     */
    public function right(array $v, array $p, array $q, string $when = 'b'): self
    {
        $this->rights[] = ['v' => $v, 'p' => $p, 'q' => $q, 'when' => $when];

        return $this;
    }

    /**
     * Dimension line parallel to a–b, $side = 1 on the left of a→b (model coordinates), -1 on the right.
     */
    public function dim(array $a, array $b, string $text, int $side = 1, string $kind = 'given', string $when = 'b', int $level = 1): self
    {
        $this->dims[] = ['a' => $a, 'b' => $b, 'text' => $text, 'side' => $side, 'kind' => $kind, 'when' => $when, 'level' => $level];

        return $this;
    }

    public function dot(array $p, string $when = 'b'): self
    {
        $this->dots[] = ['p' => $p, 'when' => $when];

        return $this;
    }

    /**
     * Squared paper behind the figure, $step model units per square.
     */
    public function grid(float $step = 1): self
    {
        $this->grid = $step;

        return $this;
    }

    public function render(bool $solution): string
    {
        $show = static fn (array $e): bool => 'b' === $e['when'] || ($solution ? 's' : 'q') === $e['when'];
        [$tx, $s] = $this->transform();
        $out = '';

        if (null !== $this->grid) {
            $out .= $this->gridSvg($tx, $s);
        }

        foreach ($this->shapes as $sh) {
            if (!$show($sh)) {
                continue;
            }

            $d = '';

            foreach ($sh['parts'] as $part) {
                $d .= $this->partPath($part, $tx, $s);
            }

            $stroke = $sh['dashed'] ? ' stroke-dasharray="7 5" stroke-width="2"' : ' stroke-width="2.4"';
            $out .= '<path d="'.$d.'" fill="'.$sh['fill'].'" fill-rule="evenodd" stroke="'.self::INK.'"'.$stroke.' stroke-linejoin="round"/>';
        }

        foreach ($this->lines as $l) {
            if (!$show($l)) {
                continue;
            }

            $style = match ($l['style']) {
                'sol' => 'stroke="'.self::SOL.'" stroke-width="2.2" stroke-dasharray="7 5"',
                'edge' => 'stroke="'.self::INK.'" stroke-width="2.4"',
                'thin' => 'stroke="#64748b" stroke-width="1.6" stroke-dasharray="4 4"',
                default => 'stroke="#475569" stroke-width="2" stroke-dasharray="7 5"',
            };
            $out .= self::seg($tx($l['a']), $tx($l['b']), $style);
        }

        foreach ($this->rights as $r) {
            if (!$show($r)) {
                continue;
            }

            $v = $tx($r['v']);
            $u1 = self::unit(self::sub($tx($r['p']), $v));
            $u2 = self::unit(self::sub($tx($r['q']), $v));
            $a = self::add($v, self::mul($u1, 13));
            $b = self::add($a, self::mul($u2, 13));
            $c = self::add($v, self::mul($u2, 13));
            $out .= '<path d="M'.self::f($a).' L'.self::f($b).' L'.self::f($c).'" fill="none" stroke="'.self::INK.'" stroke-width="1.6"/>';
            $m = self::add($v, self::mul(self::add($u1, $u2), 6.5));
            $out .= '<circle cx="'.round($m[0], 1).'" cy="'.round($m[1], 1).'" r="1.8" fill="'.self::INK.'"/>';
        }

        foreach ($this->dots as $d) {
            if ($show($d)) {
                $p = $tx($d['p']);
                $out .= '<circle cx="'.round($p[0], 1).'" cy="'.round($p[1], 1).'" r="3.2" fill="'.self::INK.'"/>';
            }
        }

        foreach ($this->dims as $d) {
            if ($show($d)) {
                $out .= $this->dimSvg($d, $tx);
            }
        }

        foreach ($this->labels as $l) {
            if ($show($l)) {
                $out .= $this->labelSvg($l, $tx);
            }
        }

        return '<svg class="st-areasvg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.self::W.' '.self::H.'" font-family="system-ui, sans-serif" role="img">'.$out.'</svg>';
    }

    /**
     * Model → picture: [callable, scale].
     */
    private function transform(): array
    {
        $pts = [];

        foreach ($this->shapes as $sh) {
            foreach ($sh['parts'] as $part) {
                array_push($pts, ...self::partPoints($part));
            }
        }

        foreach ($this->lines as $l) {
            $pts[] = $l['a'];
            $pts[] = $l['b'];
        }

        foreach ($this->dims as $d) {
            $pts[] = $d['a'];
            $pts[] = $d['b'];
        }

        $xs = array_column($pts, 0);
        $ys = array_column($pts, 1);
        $minX = min($xs);
        $maxX = max($xs);
        $minY = min($ys);
        $maxY = max($ys);
        $bw = max(1e-6, $maxX - $minX);
        $bh = max(1e-6, $maxY - $minY);

        // Room for dimension lines and labels
        $mx = 105 + 26 * \count(array_filter($this->dims, static fn (array $d): bool => abs($d['a'][1] - $d['b'][1]) > 1e-6 && abs($d['a'][0] - $d['b'][0]) < 1e-6));
        $mx = min(170, $mx);
        $my = 50 + 22 * min(2, \count(array_filter($this->dims, static fn (array $d): bool => abs($d['a'][1] - $d['b'][1]) < 1e-6)));
        $my = min(100, $my);
        $s = min((self::W - 2 * $mx) / $bw, (self::H - 2 * $my) / $bh);
        $ox = (self::W - $bw * $s) / 2;
        $oy = (self::H - $bh * $s) / 2;

        return [static fn (array $p): array => [$ox + ($p[0] - $minX) * $s, $oy + ($maxY - $p[1]) * $s], $s];
    }

    private static function partPoints(array $part): array
    {
        return match ($part[0]) {
            'poly' => $part[1],
            'circle' => [[$part[1][0] - $part[2], $part[1][1] - $part[2]], [$part[1][0] + $part[2], $part[1][1] + $part[2]]],
            default => array_merge(...array_map(static function (array $c): array {
                if ('arc' !== $c[0]) {
                    return [$c[1]];
                }

                $pts = [];

                for ($k = 0; $k <= 24; ++$k) {
                    $a = deg2rad($c[3] + ($c[4] - $c[3]) * $k / 24);
                    $pts[] = [$c[1][0] + $c[2] * cos($a), $c[1][1] + $c[2] * sin($a)];
                }

                return $pts;
            }, $part[1])),
        };
    }

    private function partPath(array $part, callable $tx, float $s): string
    {
        if ('poly' === $part[0]) {
            return 'M'.implode(' L', array_map(static fn (array $p): string => self::f($tx($p)), $part[1])).' Z ';
        }

        if ('circle' === $part[0]) {
            $r = round($part[2] * $s, 2);
            $c = $tx($part[1]);

            return 'M'.self::f([$c[0] - $r, $c[1]]).' A'.$r.' '.$r.' 0 1 0 '.self::f([$c[0] + $r, $c[1]]).' A'.$r.' '.$r.' 0 1 0 '.self::f([$c[0] - $r, $c[1]]).' Z ';
        }

        $d = '';

        foreach ($part[1] as $k => $c) {
            if ('arc' === $c[0]) {
                $r = round($c[2] * $s, 2);
                $from = deg2rad($c[3]);
                $to = deg2rad($c[4]);
                $start = $tx([$c[1][0] + $c[2] * cos($from), $c[1][1] + $c[2] * sin($from)]);
                $end = $tx([$c[1][0] + $c[2] * cos($to), $c[1][1] + $c[2] * sin($to)]);
                $d .= (0 === $k ? 'M' : 'L').self::f($start).' ';
                $sweep = $c[4] > $c[3] ? 0 : 1;
                $large = abs($c[4] - $c[3]) > 180 ? 1 : 0;
                $d .= 'A'.$r.' '.$r.' 0 '.$large.' '.$sweep.' '.self::f($end).' ';
            } else {
                $d .= $c[0].self::f($tx($c[1])).' ';
            }
        }

        return $d.'Z ';
    }

    private function gridSvg(callable $tx, float $s): string
    {
        $step = $this->grid * $s;
        $o = $tx([0, 0]);
        $x0 = fmod($o[0], $step);
        $y0 = fmod($o[1], $step);
        $d = '';

        for ($x = $x0; $x <= self::W; $x += $step) {
            $d .= 'M'.round($x, 1).' 0 V'.self::H.' ';
        }

        for ($y = $y0; $y <= self::H; $y += $step) {
            $d .= 'M0 '.round($y, 1).' H'.self::W.' ';
        }

        return '<path d="'.$d.'" stroke="#cbd5e1" stroke-width="1" fill="none"/>';
    }

    private function dimSvg(array $d, callable $tx): string
    {
        $a = $tx($d['a']);
        $b = $tx($d['b']);
        $u = self::unit(self::sub($b, $a));
        // Left of a→b in the model (y is flipped on the screen)
        $n = self::mul([$u[1], -$u[0]], $d['side']);
        $off = 14 + 24 * ($d['level'] - 1);
        $pa = self::add($a, self::mul($n, $off + 12));
        $pb = self::add($b, self::mul($n, $off + 12));
        $col = 'given' === $d['kind'] ? '#475569' : ('ask' === $d['kind'] ? self::ASK : self::SOL);
        $out = self::seg(self::add($a, self::mul($n, 4)), self::add($a, self::mul($n, $off + 18)), 'stroke="#94a3b8" stroke-width="1.2"');
        $out .= self::seg(self::add($b, self::mul($n, 4)), self::add($b, self::mul($n, $off + 18)), 'stroke="#94a3b8" stroke-width="1.2"');
        $out .= self::seg($pa, $pb, 'stroke="'.$col.'" stroke-width="1.6"');

        // Arrow heads
        foreach ([[$pa, $u], [$pb, self::mul($u, -1)]] as [$p, $dir]) {
            $l = self::add($p, self::add(self::mul($dir, 9), self::mul($n, 4)));
            $r = self::add($p, self::add(self::mul($dir, 9), self::mul($n, -4)));
            $out .= '<path d="M'.self::f($l).' L'.self::f($p).' L'.self::f($r).'" fill="none" stroke="'.$col.'" stroke-width="1.6"/>';
        }

        $mid = self::lerp($pa, $pb, 0.5);
        $w = self::textWidth($d['text']);
        $push = 9 + abs($n[0]) * $w / 2 + abs($n[1]) * 3;

        return $out.$this->text(self::add($mid, self::mul($n, $push)), $d['text'], $d['kind']);
    }

    private function labelSvg(array $l, callable $tx): string
    {
        if (isset($l['seg'])) {
            $a = $tx($l['seg'][0]);
            $b = $tx($l['seg'][1]);
            $mid = self::lerp($a, $b, $l['t']);
            $u = self::unit(self::sub($b, $a));
            $n = [-$u[1], $u[0]];
            $ref = $tx($l['ref']);

            if (($mid[0] - $ref[0]) * $n[0] + ($mid[1] - $ref[1]) * $n[1] < 0) {
                $n = self::mul($n, -1);
            }

            $w = self::textWidth($l['text']);

            return $this->text(self::add($mid, self::mul($n, 10 + abs($n[0]) * $w / 2 + abs($n[1]) * 4)), $l['text'], $l['kind']);
        }

        $p = $tx($l['p']);

        if (null !== $l['from'] && $l['dist'] > 0) {
            $dir = self::unit(self::sub($p, $tx($l['from'])));
            $w = self::textWidth($l['text']);
            $p = self::add($p, self::mul($dir, $l['dist'] + abs($dir[0]) * $w / 2));
        }

        return $this->text($p, $l['text'], $l['kind']);
    }

    private function text(array $p, string $text, string $kind): string
    {
        [$fill, $size, $weight] = match ($kind) {
            'ask' => [self::ASK, 16, 800],
            'sol' => [self::SOL, 16, 800],
            'vertex' => ['#0f172a', 16, 800],
            'part' => ['#334155', 15, 800],
            'note' => ['#475569', 14, 600],
            default => [self::INK, 16, 700],
        };

        return '<text x="'.round($p[0], 1).'" y="'.round($p[1], 1).'" text-anchor="middle" dominant-baseline="central" font-size="'.$size.'" font-weight="'.$weight.'" fill="'.$fill.'" stroke="#fff" stroke-width="4" stroke-linejoin="round" paint-order="stroke">'.strtr($text, ['&' => '&amp;', '<' => '&lt;', '>' => '&gt;']).'</text>';
    }

    private static function textWidth(string $text): float
    {
        return mb_strlen($text) * 8.6;
    }

    private static function seg(array $a, array $b, string $style): string
    {
        return '<line x1="'.round($a[0], 1).'" y1="'.round($a[1], 1).'" x2="'.round($b[0], 1).'" y2="'.round($b[1], 1).'" '.$style.'/>';
    }

    private static function f(array $p): string
    {
        return round($p[0], 1).' '.round($p[1], 1);
    }

    private static function add(array $a, array $b): array
    {
        return [$a[0] + $b[0], $a[1] + $b[1]];
    }

    private static function sub(array $a, array $b): array
    {
        return [$a[0] - $b[0], $a[1] - $b[1]];
    }

    private static function mul(array $a, float $k): array
    {
        return [$a[0] * $k, $a[1] * $k];
    }

    private static function lerp(array $a, array $b, float $t): array
    {
        return [$a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t];
    }

    private static function unit(array $v): array
    {
        $l = hypot($v[0], $v[1]) ?: 1;

        return [$v[0] / $l, $v[1] / $l];
    }
}
