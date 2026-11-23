<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Geometry;

use Markocupic\ContaoSchoolTest\Task\Util as U;

/**
 * Symmetry cards: a picture of grey figures (or tiles with a grey half) that is only axially
 * symmetric, only point symmetric, both or neither. On a simple drawing board the student
 * draws every axis of symmetry (two clicks) and sets the centre of symmetry Z, or clicks
 * "Keine Symmetrie". Roughly right is enough.
 *
 * The picture is built from a motif and the images of a symmetry group around a point; the
 * axes and the centre are then measured on the finished picture, so the solution always fits.
 *
 * Answer type "symmetry": the solution (axes, center) stays on the server.
 */
final class SymmetryGenerator
{
    public const SIZE = 10;

    /**
     * Allowed deviation (roughly right is enough): distance in units of the card (10 × 10)
     * and angle in degrees.
     */
    public const TOLERANCE = ['easy' => [0.6, 8], 'medium' => [0.5, 6], 'hard' => [0.4, 5]];

    /**
     * Operations about the origin: (x, y) → (a·x + b·y, c·x + d·y).
     */
    private const OPS = [
        'e' => [1, 0, 0, 1],
        'v' => [-1, 0, 0, 1],
        'h' => [1, 0, 0, -1],
        'd1' => [0, 1, 1, 0],
        'd2' => [0, -1, -1, 0],
        'r90' => [0, -1, 1, 0],
        'r180' => [-1, 0, 0, -1],
        'r270' => [0, 1, -1, 0],
    ];

    /**
     * Direction of the axis of each reflection in degrees.
     */
    private const AXES = ['v' => 90, 'h' => 0, 'd1' => 45, 'd2' => 135];

    public function build(string $level): array
    {
        $level = isset(self::TOLERANCE[$level]) ? $level : 'medium';

        for ($try = 0; $try < 5000; ++$try) {
            $cat = U::pick(['axis', 'point', 'both', 'none']);
            $card = $this->card($cat, $level);

            if (null === $card) {
                continue;
            }

            [$shapes, $axes, $center] = $card;
            $found = (bool) $axes || null !== $center;
            $real = $axes ? ($center ? 'both' : 'axis') : ($center ? 'point' : 'none');

            if ($real !== $cat || ('none' === $cat && $found)) {
                continue;
            }

            [$tol, $angle] = self::TOLERANCE[$level];

            return [
                'q' => 'Zeichne alle Symmetrieachsen und das Symmetriezentrum Z ein.',
                'key' => md5(json_encode($shapes, JSON_THROW_ON_ERROR)),
                'a' => [
                    'type' => 'symmetry',
                    'axes' => $axes,
                    'center' => $center,
                    'tol' => $tol,
                    'angle' => $angle,
                    'draw' => ['sym' => true, 'grid' => [self::SIZE, self::SIZE], 'shapes' => $shapes],
                ],
                'way' => self::way(\count($axes), null !== $center),
                // One point for the axes, one for the centre
                'points' => 2,
                'area' => 'symmetrie',
            ];
        }

        throw new \RuntimeException('Could not generate a symmetry card.');
    }

    /**
     * Grey figures, axes [[point, direction]] and centre (or null) on the card, null if it does not fit.
     */
    private function card(string $cat, string $level): array|null
    {
        $groups = match ($cat) {
            'axis' => [['e', 'v'], ['e', 'h'], ['e', 'd1'], ['e', 'd2']],
            'point' => 'easy' === $level ? [['e', 'r180']] : [['e', 'r180'], ['e', 'r180'], ['e', 'r90', 'r180', 'r270']],
            'both' => 'hard' === $level ? [['e', 'v', 'h', 'r180'], ['e', 'd1', 'd2', 'r180'], ['e', 'v', 'h', 'd1', 'd2', 'r90', 'r180', 'r270']] : [['e', 'v', 'h', 'r180'], ['e', 'd1', 'd2', 'r180']],
            default => [['e', 'v'], ['e', 'r180'], ['e', 'v', 'h', 'r180'], ['e', 'r90', 'r180', 'r270']],
        };
        $group = U::pick($groups);
        $n = \count($group);
        $size = match (true) {
            $n >= 8 => U::ri(9, 13) / 10,
            $n >= 4 => U::ri(13, 19) / 10,
            default => U::ri(18, 26) / 10,
        };
        $motif = 'easy' !== $level && U::chance(0.4) ? self::tiles($size) : self::polygon($size);

        // Place the motif away from the centre, so the copies do not touch
        $at = [U::ri(-35, 35) / 10, U::ri(-35, 35) / 10];
        $motif = array_map(static fn (array $s): array => ['p' => array_map(static fn (array $q): array => [$q[0] + $at[0], $q[1] + $at[1]], $s['p']), 'f' => $s['f']], $motif);
        $copies = array_map(static fn (string $op): array => self::apply($motif, self::OPS[$op]), $group);

        // Neither: one copy is placed differently, or a copy is shifted instead of reflected
        if ('none' === $cat) {
            if (2 === $n && U::chance(0.5)) {
                $t = [U::ri(-40, 40) / 10, U::ri(-40, 40) / 10];
                $copies = [self::shift($motif, [-$t[0] / 2, -$t[1] / 2]), self::shift($motif, [$t[0] / 2, $t[1] / 2])];
            } else {
                $k = U::ri(1, $n - 1);
                $other = array_values(array_diff(array_keys(self::OPS), $group));
                $copies[$k] = self::apply($motif, self::OPS[U::pick($other)]);
            }
        }

        // Copies apart from each other
        $boxes = array_map(self::box(...), $copies);

        foreach ($boxes as $i => $b) {
            foreach ($boxes as $j => $c) {
                if ($i < $j && $b[0] < $c[2] + 0.3 && $c[0] < $b[2] + 0.3 && $b[1] < $c[3] + 0.3 && $c[1] < $b[3] + 0.3) {
                    return null;
                }
            }
        }

        $shapes = array_merge(...$copies);
        $ops = array_keys(array_filter(self::OPS, static fn (array $m): bool => self::same($shapes, self::apply($shapes, $m))));
        $axes = array_values(array_intersect(['v', 'h', 'd1', 'd2'], $ops));
        $point = \in_array('r180', $ops, true);

        // The whole picture: shifted (medium, hard) and turned (hard), inside the card
        $theta = 'hard' === $level ? U::ri(10, 80) * (U::chance() ? 1 : -1) : ('medium' === $level && U::chance(0.3) ? 45 : 0);
        $off = 'easy' === $level ? [0, 0] : [U::ri(-12, 12) / 10, U::ri(-12, 12) / 10];
        $c = [self::SIZE / 2 + $off[0], self::SIZE / 2 + $off[1]];
        $cos = cos(deg2rad($theta));
        $sin = sin(deg2rad($theta));
        $place = static fn (array $q): array => [round($c[0] + $q[0] * $cos - $q[1] * $sin, 3), round($c[1] + $q[0] * $sin + $q[1] * $cos, 3)];
        $shapes = array_map(static fn (array $s): array => ['p' => array_map($place, $s['p']), 'f' => $s['f']], $shapes);

        foreach ($shapes as $s) {
            foreach ($s['p'] as $q) {
                if ($q[0] < 0.3 || $q[0] > self::SIZE - 0.3 || $q[1] < 0.3 || $q[1] > self::SIZE - 0.3) {
                    return null;
                }
            }
        }

        $center = [round($c[0], 3), round($c[1], 3)];
        $lines = array_map(static function (string $op) use ($center, $theta): array {
            $phi = deg2rad(self::AXES[$op] + $theta);

            return [$center, [round(cos($phi), 6), round(sin($phi), 6)]];
        }, $axes);

        return [$shapes, $lines, $point ? $center : null];
    }

    /**
     * Random convex polygon without symmetry around the origin.
     */
    private static function polygon(float $size): array
    {
        do {
            $n = U::ri(5, 6);
            $off = U::ri(0, 359);
            $angles = [];

            for ($k = 0; $k < $n; ++$k) {
                $angles[] = $off + 360 / $n * $k + U::ri(-20, 20);
            }

            $pts = array_map(static fn (float $a): array => [
                round(cos(deg2rad($a)) * $size / 2 * U::ri(60, 100) / 100, 3),
                round(sin(deg2rad($a)) * $size / 2 * U::ri(60, 100) / 100, 3),
            ], $angles);
            $shape = [['p' => $pts, 'f' => 1]];
            $symmetric = array_filter(self::OPS, static fn (array $m, string $op): bool => 'e' !== $op && self::sameShifted($shape, self::apply($shape, $m)), ARRAY_FILTER_USE_BOTH);
        } while (!self::convex($pts) || $symmetric);

        return $shape;
    }

    /**
     * Two or three squares in a row or an L, each with one grey half (cut along a diagonal).
     */
    private static function tiles(float $size): array
    {
        $t = round($size / 2, 3);

        do {
            $cells = U::pick([[[0, 0], [1, 0]], [[0, 0], [1, 0], [2, 0]], [[0, 0], [1, 0], [0, 1]], [[0, 0], [0, 1]]]);
            $out = [];

            foreach ($cells as [$i, $j]) {
                $x = ($i - 0.5) * $t;
                $y = ($j - 0.5) * $t;
                $sq = [[$x, $y], [$x + $t, $y], [$x + $t, $y + $t], [$x, $y + $t]];
                $out[] = ['p' => $sq, 'f' => 0];
                $k = U::ri(0, 3);
                $out[] = ['p' => [$sq[$k], $sq[($k + 1) % 4], $sq[($k + 2) % 4]], 'f' => 1];
            }

            // The motif itself must not be symmetric
            $symmetric = array_filter(self::OPS, static fn (array $m, string $op): bool => 'e' !== $op && self::sameShifted($out, self::apply($out, $m)), ARRAY_FILTER_USE_BOTH);
        } while ($symmetric);

        return $out;
    }

    private static function apply(array $shapes, array $m): array
    {
        return array_map(static fn (array $s): array => ['p' => array_map(static fn (array $q): array => [
            round($m[0] * $q[0] + $m[1] * $q[1], 6),
            round($m[2] * $q[0] + $m[3] * $q[1], 6),
        ], $s['p']), 'f' => $s['f']], $shapes);
    }

    private static function shift(array $shapes, array $t): array
    {
        return array_map(static fn (array $s): array => ['p' => array_map(static fn (array $q): array => [$q[0] + $t[0], $q[1] + $t[1]], $s['p']), 'f' => $s['f']], $shapes);
    }

    /**
     * Same picture: every figure has a figure with the same colour and the same corners.
     */
    private static function same(array $a, array $b): bool
    {
        if (\count($a) !== \count($b)) {
            return false;
        }

        $used = [];

        foreach ($a as $shape) {
            $hit = false;

            foreach ($b as $j => $other) {
                if (isset($used[$j]) || $shape['f'] !== $other['f'] || \count($shape['p']) !== \count($other['p'])) {
                    continue;
                }

                $all = true;

                foreach ($shape['p'] as $u) {
                    $near = array_filter($other['p'], static fn (array $v): bool => abs($u[0] - $v[0]) < 0.01 && abs($u[1] - $v[1]) < 0.01);
                    $all = $all && (bool) $near;
                }

                if ($all) {
                    $used[$j] = true;
                    $hit = true;

                    break;
                }
            }

            if (!$hit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Same figure up to a shift (symmetry of the motif itself about some point or axis).
     */
    private static function sameShifted(array $a, array $b): bool
    {
        $ca = self::centroid($a);
        $cb = self::centroid($b);

        return self::same(self::shift($a, [-$ca[0], -$ca[1]]), self::shift($b, [-$cb[0], -$cb[1]]));
    }

    private static function centroid(array $shapes): array
    {
        $all = array_merge(...array_column($shapes, 'p'));

        return [array_sum(array_column($all, 0)) / \count($all), array_sum(array_column($all, 1)) / \count($all)];
    }

    private static function box(array $shapes): array
    {
        $all = array_merge(...array_column($shapes, 'p'));
        $xs = array_column($all, 0);
        $ys = array_column($all, 1);

        return [min($xs), min($ys), max($xs), max($ys)];
    }

    private static function convex(array $pts): bool
    {
        $n = \count($pts);

        for ($k = 0; $k < $n; ++$k) {
            $a = $pts[$k];
            $b = $pts[($k + 1) % $n];
            $c = $pts[($k + 2) % $n];

            if (($b[0] - $a[0]) * ($c[1] - $b[1]) - ($b[1] - $a[1]) * ($c[0] - $b[0]) <= 0.01) {
                return false;
            }
        }

        return true;
    }

    /**
     * Solution way: which symmetry the picture has and how to find it.
     */
    private static function way(int $axes, bool $point): string
    {
        $t = static fn (string $s, string|int ...$args): string => '<span class="st-t">'.(\count($args) ? str_replace('{0}', (string) $args[0], $s) : $s).'</span>';
        $steps = [
            match (true) {
                0 === $axes => $t('Das Bild ist <b>nicht achsensymmetrisch</b>: Es gibt keine Gerade, an der gespiegelt es mit sich selbst zusammenfällt.'),
                1 === $axes => $t('Das Bild ist <b>achsensymmetrisch</b> mit einer Symmetrieachse (grün gestrichelt): An ihr gespiegelt fällt es mit sich selbst zusammen.'),
                default => $t('Das Bild ist <b>achsensymmetrisch</b> mit {0} Symmetrieachsen (grün gestrichelt): An jeder von ihnen gespiegelt fällt es mit sich selbst zusammen.', $axes),
            },
            $point
                ? $t('Das Bild ist <b>punktsymmetrisch</b>: Nach einer halben Drehung (180°) um das Zentrum Z fällt es mit sich selbst zusammen.')
                : $t('Das Bild ist <b>nicht punktsymmetrisch</b>: Nach einer halben Drehung um einen Punkt fällt es nicht mit sich selbst zusammen.'),
        ];

        if ($axes) {
            $steps[] = $t('Tipp: Eine Symmetrieachse ist die <b>Mittelsenkrechte</b> zweier Punkte, die einander entsprechen.');
        }

        if ($point) {
            $steps[] = $t('Tipp: Das Zentrum Z ist die <b>Mitte</b> zwischen zwei Punkten, die einander entsprechen.');
        }

        if (!$axes && !$point) {
            $steps[] = $t('Hier ist «Keine Symmetrie» richtig.');
        }

        return '<ol class="st-steps">'.implode('', array_map(static fn (string $s): string => '<li>'.$s.'</li>', $steps)).'</ol>';
    }

    /**
     * The card for the printed evaluation: figures, the solution (green dashed) and the
     * student's axes and Z (green right, red wrong, grey "keine Symmetrie").
     */
    public static function svg(array $a, array|null $answer = null, array $correct = [], bool $solution = true): string
    {
        // Worksheet: only the card, without axes and centre
        if (!$solution) {
            $a['axes'] = [];
            $a['center'] = null;
        }

        $s = 30;
        $p = 10;
        $x = static fn (float $v): float => round($p + $v * $s, 1);
        $y = static fn (float $v): float => round($p + (self::SIZE - $v) * $s, 1);
        $w = self::SIZE * $s + 2 * $p;
        $out = '<rect x="'.$p.'" y="'.$p.'" width="'.(self::SIZE * $s).'" height="'.(self::SIZE * $s).'" fill="#fff" stroke="#333" stroke-width="1.5"/>';

        foreach ($a['draw']['shapes'] as $shape) {
            $pts = implode(' ', array_map(static fn (array $q): string => $x($q[0]).','.$y($q[1]), $shape['p']));
            $out .= '<polygon points="'.$pts.'" fill="'.($shape['f'] ? '#bbb' : '#fff').'" stroke="#333" stroke-width="1.2"/>';
        }

        $line = static function (array $pt, array $d, string $attr) use ($x, $y): string {
            $k = 20;

            return '<line x1="'.$x($pt[0] - $d[0] * $k).'" y1="'.$y($pt[1] - $d[1] * $k).'" x2="'.$x($pt[0] + $d[0] * $k).'" y2="'.$y($pt[1] + $d[1] * $k).'" '.$attr.'/>';
        };
        $clip = '<clipPath id="symclip"><rect x="'.$p.'" y="'.$p.'" width="'.(self::SIZE * $s).'" height="'.(self::SIZE * $s).'"/></clipPath>';
        $lines = '';

        foreach ($a['axes'] as [$pt, $d]) {
            $lines .= $line($pt, $d, 'stroke="#16a34a" stroke-width="2" stroke-dasharray="7 5"');
        }

        if (null !== $answer) {
            $col = !empty($correct[0]) ? '#16a34a' : '#c42b2b';

            foreach ($answer['axes'] ?? [] as $l) {
                $lines .= $line([$l[0], $l[1]], [$l[2], $l[3]], 'stroke="'.$col.'" stroke-width="1.6"');
            }
        }

        $out .= $clip.'<g clip-path="url(#symclip)">'.$lines.'</g>';

        if (null !== $a['center']) {
            $out .= '<circle cx="'.$x($a['center'][0]).'" cy="'.$y($a['center'][1]).'" r="5" fill="none" stroke="#16a34a" stroke-width="2"/>';
        }

        if (null !== ($answer['z'] ?? null)) {
            $z = $answer['z'];
            $out .= '<circle cx="'.$x($z[0]).'" cy="'.$y($z[1]).'" r="3.5" fill="'.(!empty($correct[1]) ? '#16a34a' : '#c42b2b').'"/><text x="'.($x($z[0]) + 7).'" y="'.($y($z[1]) - 6).'" font-size="12" font-weight="700">Z</text>';
        }

        if (!empty($answer['none'])) {
            $out .= '<text x="'.($w / 2).'" y="'.($w - 2).'" font-size="11" text-anchor="middle" fill="'.(!empty($correct[0]) ? '#16a34a' : '#c42b2b').'">«Keine Symmetrie» gewählt</text>';
        }

        return '<svg class="symsvg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.($w + 8).'" width="'.$w.'">'.$out.'</svg>';
    }
}
