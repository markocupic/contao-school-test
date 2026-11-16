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

namespace Markocupic\ContaoSchoolTest\Task\Geometry;

use Markocupic\ContaoSchoolTest\Task\Util as U;

/**
 * Spiegeln, Drehen, Verschieben auf Häuschenpapier (20 × 14).
 *
 * A task shows the original figure A, B, C … and the axis s, the centre Z or the arrow v.
 * The student places the image points A′, B′, C′ … on grid points.
 *
 * Answer type "points": the solution (img) stays on the server, everything else is
 * drawing data for the browser and the evaluation sheet.
 */
final class TransformGenerator
{
    public const W = 20;

    public const H = 14;

    public const KINDS = ['achse', 'punkt', 'drehung', 'verschiebung', 'achse_ggb', 'punkt_ggb'];

    /**
     * Without squares: construction in GeoGebra. One square of the board is 5 mm
     * (as on squared paper), an image point counts within the tolerance of the level.
     */
    public const FREE_KINDS = ['achse_ggb' => 'achse', 'punkt_ggb' => 'punkt'];

    public const GGB_KINDS = ['achse_ggb', 'punkt_ggb'];

    /**
     * Earlier constructions on the own board, now done with GeoGebra.
     */
    public const LEGACY_KINDS = ['achse_frei' => 'achse_ggb', 'punkt_frei' => 'punkt_ggb'];

    /**
     * Allowed deviation in squares of 5 mm: easy 4 mm, medium 3 mm, hard 1.5 mm.
     */
    public const FREE_TOLERANCE = ['easy' => 0.8, 'medium' => 0.6, 'hard' => 0.3];

    /**
     * Allowed deviation in mm as text ("1.5").
     */
    public static function toleranceMm(string $level): string
    {
        return rtrim(rtrim(number_format((self::FREE_TOLERANCE[$level] ?? 0.6) * 5, 1, '.', ''), '0'), '.');
    }

    public const LEVELS = ['easy', 'medium', 'hard'];

    private const LETTERS = 'ABCDEFGH';

    public function build(string $kind, string $level): array
    {
        $kind = self::LEGACY_KINDS[$kind] ?? $kind;

        if (isset(self::FREE_KINDS[$kind])) {
            return $this->buildFree($kind, $level);
        }

        $kind = \in_array($kind, self::KINDS, true) ? $kind : 'achse';
        $level = \in_array($level, self::LEVELS, true) ? $level : 'medium';
        $n = 'easy' === $level ? 3 : ('medium' === $level ? 4 : U::pick([4, 5, 5]));

        for ($k = 0; $k < 8000; ++$k) {
            $t = $this->transform($kind, $level);

            if (!$t) {
                continue;
            }

            $pts = $this->figure($n, $level);

            if (!$pts) {
                continue;
            }

            $img = array_map(fn (array $p): array => $this->apply($t, $p), $pts);

            if (!array_reduce($img, static fn (bool $c, array $p): bool => $c && self::inside($p), true)) {
                continue;
            }

            $overlap = false;

            foreach ($img as $q) {
                foreach ($pts as $p) {
                    $overlap = $overlap || self::eq($p, $q);
                }
            }

            if ('achse' === $kind) {
                $sides = array_map(fn (array $p): float => $this->side($t, $p), $pts);
                $oneSide = min($sides) > 0 || max($sides) < 0;

                if ('hard' !== $level && (!$oneSide || $overlap)) {
                    continue;
                }

                if ('hard' === $level && $oneSide && U::chance()) {
                    continue;
                }
            } elseif ($overlap) {
                continue;
            }

            if (isset($t['center'])) {
                $z = $t['center'];

                if (array_filter($pts, static fn (array $p): bool => self::eq($p, $z))) {
                    continue;
                }

                if ('hard' !== $level) {
                    $xs = array_column($pts, 0);
                    $ys = array_column($pts, 1);

                    if ($z[0] >= min($xs) && $z[0] <= max($xs) && $z[1] >= min($ys) && $z[1] <= max($ys)) {
                        continue;
                    }
                }
            }

            if ('verschiebung' === $kind) {
                $t['vecStart'] = $this->placeVector($t['vec'], array_merge($pts, $img));

                if (!$t['vecStart']) {
                    continue;
                }
            }

            $task = $this->task($t, $pts, $img, $n);

            // Hard tasks on squared paper: no Geodreieck, the student has to count
            if ('hard' === $level) {
                $task['a']['draw']['noGeo'] = true;
            }

            return $task;
        }

        throw new \RuntimeException('Could not generate a transformation task.');
    }

    /* ---------- Transformations ---------- */

    private function transform(string $kind, string $level): array|null
    {
        switch ($kind) {
            case 'achse':
                $type = U::pick('easy' === $level ? ['v', 'h'] : ['v', 'h', 'd', 'a', 'd', 'a']);
                $half = 'hard' === $level && \in_array($type, ['v', 'h'], true) && U::chance();
                $off = $half ? 0.5 : 0.0;

                $t = match ($type) {
                    'v' => ['axType' => 'v', 'c' => U::ri(5, self::W - 5) + $off, 'p0' => null, 'dir' => [0, 1]],
                    'h' => ['axType' => 'h', 'c' => U::ri(4, self::H - 4) + $off, 'p0' => null, 'dir' => [1, 0]],
                    'd' => ['axType' => 'd', 'c' => U::ri(-(self::W - 8), self::H - 8), 'p0' => null, 'dir' => [1, 1]],
                    'a' => ['axType' => 'a', 'c' => U::ri(8, self::W + self::H - 8), 'p0' => null, 'dir' => [1, -1]],
                };

                $t['p0'] = match ($type) {
                    'v' => [$t['c'], 0],
                    'h' => [0, $t['c']],
                    default => [0, $t['c']],
                };

                $seg = self::clipLine($t['p0'], $t['dir']);

                if (!$seg || hypot($seg[1][0] - $seg[0][0], $seg[1][1] - $seg[0][1]) < 8) {
                    return null;
                }

                return ['kind' => 'achse', 'half' => $half, 'seg' => $seg] + $t;

            case 'punkt':
                return ['kind' => 'punkt', 'center' => [U::ri(3, self::W - 3), U::ri(3, self::H - 3)]];

            case 'drehung':
                $ang = 'hard' === $level ? U::pick([90, 270, 90, 270, 180]) : U::pick([90, 270]);

                return ['kind' => 'drehung', 'center' => [U::ri(3, self::W - 3), U::ri(3, self::H - 3)], 'ang' => $ang];

            default:
                do {
                    $dx = U::ri(-9, 9);
                    $dy = U::ri(-6, 6);

                    if ('easy' === $level && U::chance(0.6)) {
                        if (U::chance()) {
                            $dx = 0;
                        } else {
                            $dy = 0;
                        }
                    }
                } while (abs($dx) + abs($dy) < 4);

                return ['kind' => 'verschiebung', 'vec' => [$dx, $dy]];
        }
    }

    /**
     * Image of point p.
     */
    public function apply(array $t, array $p, int|null $angle = null): array
    {
        switch ($t['kind']) {
            case 'achse':
                $c = $t['c'];

                return match ($t['axType']) {
                    'v' => [2 * $c - $p[0], $p[1]],
                    'h' => [$p[0], 2 * $c - $p[1]],
                    'd' => [$p[1] - $c, $p[0] + $c],
                    'a' => [$c - $p[1], $c - $p[0]],
                };

            case 'punkt':
                return [2 * $t['center'][0] - $p[0], 2 * $t['center'][1] - $p[1]];

            case 'drehung':
                [$zx, $zy] = $t['center'];

                return match ($angle ?? $t['ang']) {
                    90 => [$zx - ($p[1] - $zy), $zy + ($p[0] - $zx)],
                    270 => [$zx + ($p[1] - $zy), $zy - ($p[0] - $zx)],
                    default => [2 * $zx - $p[0], 2 * $zy - $p[1]],
                };

            default:
                return [$p[0] + $t['vec'][0], $p[1] + $t['vec'][1]];
        }
    }

    private function side(array $t, array $p): float
    {
        $c = $t['c'];

        return match ($t['axType']) {
            'v' => $p[0] - $c,
            'h' => $p[1] - $c,
            'd' => $p[1] - $p[0] - $c,
            'a' => $p[1] + $p[0] - $c,
        };
    }

    /* ---------- Figure ---------- */

    private function figure(int $n, string $level): array|null
    {
        $bw = 'easy' === $level ? 5 : ('medium' === $level ? 6 : 7);
        $bh = 'easy' === $level ? 4 : 5;
        $x0 = U::ri(1, self::W - 1 - $bw);
        $y0 = U::ri(1, self::H - 1 - $bh);
        $pts = [];

        for ($g = 0; \count($pts) < $n && $g < 200; ++$g) {
            $p = [U::ri($x0, $x0 + $bw), U::ri($y0, $y0 + $bh)];
            $ok = true;

            foreach ($pts as $q) {
                $ok = $ok && hypot($p[0] - $q[0], $p[1] - $q[1]) >= 2;
            }

            if ($ok) {
                $pts[] = $p;
            }
        }

        if (\count($pts) < $n) {
            return null;
        }

        [$cx, $cy] = self::centroid($pts);
        usort($pts, static fn ($a, $b) => atan2($a[1] - $cy, $a[0] - $cx) <=> atan2($b[1] - $cy, $b[0] - $cx));

        for ($i = 0; $i < $n; ++$i) {
            $a = $pts[($i + $n - 1) % $n];
            $b = $pts[$i];
            $c = $pts[($i + 1) % $n];

            if (abs(($b[0] - $a[0]) * ($c[1] - $a[1]) - ($b[1] - $a[1]) * ($c[0] - $a[0])) < 1e-9) {
                return null;
            }
        }

        $area = 0;

        for ($i = 0; $i < $n; ++$i) {
            $j = ($i + 1) % $n;
            $area += $pts[$i][0] * $pts[$j][1] - $pts[$j][0] * $pts[$i][1];
        }

        if (abs($area) / 2 < (3 === $n ? 3 : 5)) {
            return null;
        }

        // Random start point, counter-clockwise order stays
        $s = U::ri(0, $n - 1);

        return array_merge(\array_slice($pts, $s), \array_slice($pts, 0, $s));
    }

    private function placeVector(array $vec, array $all): array|null
    {
        for ($k = 0; $k < 400; ++$k) {
            $s = [U::ri(1, self::W - 1), U::ri(1, self::H - 1)];
            $e = [$s[0] + $vec[0], $s[1] + $vec[1]];

            if (!self::inside($e)) {
                continue;
            }

            $free = true;

            foreach ($all as $p) {
                $free = $free && self::segDist($p, $s, $e) >= 1.5;
            }

            if ($free) {
                return $s;
            }
        }

        return null;
    }

    /* ---------- Task ---------- */

    private function task(array $t, array $pts, array $img, int $n): array
    {
        // Half axes produce floats like 6.0: store whole numbers only
        $int = static fn (array $list): array => array_map(static fn (array $p): array => [(int) round($p[0]), (int) round($p[1])], $list);
        $pts = $int($pts);
        $img = $int($img);
        $name = '<b>'.substr(self::LETTERS, 0, $n).'</b>';

        switch ($t['kind']) {
            case 'achse':
                $q = "Spiegle die Figur $name an der Achse <b>s</b>.";
                $way = \in_array($t['axType'], ['d', 'a'], true)
                    ? 'Die Achse ist schräg. Gehe vom Punkt diagonal durch die Häuschen-Ecken bis zur Achse und auf der anderen Seite gleich weit weiter.'
                    : 'Gehe vom Punkt rechtwinklig zur Achse und zähle die Häuschen. Auf der anderen Seite gehst du gleich weit.'.($t['half'] ? ' Achtung: Die Achse liegt in der Mitte zwischen zwei Gitterlinien.' : '');
                break;

            case 'punkt':
                $q = "Spiegle die Figur $name am Punkt <b>Z</b>.";
                $way = 'Gehe vom Punkt zu Z und zähle dabei die Häuschen nach links oder rechts und nach oben oder unten. Von Z aus gehst du genau gleich weit in dieselbe Richtung weiter.';
                break;

            case 'drehung':
                $q = 270 === $t['ang']
                    ? "Drehe die Figur $name um den Punkt <b>Z</b> um <b>270°</b> im Gegenuhrzeigersinn (das ist dasselbe wie 90° im Uhrzeigersinn)."
                    : "Drehe die Figur $name um den Punkt <b>Z</b> um <b>{$t['ang']}°</b> im Gegenuhrzeigersinn.";
                $way = match ($t['ang']) {
                    180 => 'Eine Drehung um 180° ist dasselbe wie eine Punktspiegelung an Z.',
                    90 => 'Zähle den Weg von Z zum Punkt: a Häuschen nach rechts, b nach oben. Nach der Drehung um 90° im Gegenuhrzeigersinn wird daraus: b nach links und a nach oben. Für «nach links» oder «nach unten» gilt dieselbe Regel mit umgekehrter Richtung.',
                    default => 'Zähle den Weg von Z zum Punkt: a Häuschen nach rechts, b nach oben. Nach der Drehung um 270° im Gegenuhrzeigersinn wird daraus: b nach rechts und a nach unten. Für «nach links» oder «nach unten» gilt dieselbe Regel mit umgekehrter Richtung.',
                };
                break;

            default:
                [$dx, $dy] = $t['vec'];
                $q = "Verschiebe die Figur $name um den Pfeil <b>v</b>.";
                $way = 'Zähle die Häuschen des Pfeils: '.abs($dx).' nach '.($dx >= 0 ? 'rechts' : 'links').' und '.abs($dy).' nach '.($dy >= 0 ? 'oben' : 'unten').'. Jeder Punkt wandert genau gleich.';
        }

        // Drawing data, safe to send to the browser (no solution)
        $draw = ['kind' => $t['kind'], 'grid' => [self::W, self::H], 'pts' => $pts, 'n' => $n];

        foreach (['axType', 'seg', 'center', 'ang', 'vec', 'vecStart'] as $key) {
            if (isset($t[$key])) {
                $draw[$key] = $t[$key];
            }
        }

        $a = ['type' => 'points', 'img' => $img, 'draw' => $draw];

        // A rotation in the wrong direction gets its own feedback in practice mode
        if ('drehung' === $t['kind'] && 180 !== $t['ang']) {
            $a['wrongDirection'] = $int(array_map(fn (array $p): array => $this->apply($t, $p, 90 === $t['ang'] ? 270 : 90), $pts));
        }

        return [
            'q' => $q,
            'key' => md5(json_encode([$pts, $draw], JSON_THROW_ON_ERROR)),
            'a' => $a,
            'way' => $way,
            'points' => $n,
            'area' => $t['kind'],
        ];
    }

    /* ---------- Without squares ---------- */

    /**
     * A grid task turned by an odd angle and moved: axis and centre no longer lie on the
     * squares, the image points have to be constructed.
     */
    private function buildFree(string $kind, string $level): array
    {
        $base = self::FREE_KINDS[$kind];

        for ($k = 0; $k < 400; ++$k) {
            $task = $this->build($base, $level);
            $draw = $task['a']['draw'];

            // Angle away from multiples of 45°, so nothing lines up with an imaginary grid
            do {
                $phi = mt_rand(0, 3599) / 10;
                $off = fmod($phi, 45);
            } while ($off < 8 || $off > 37);

            $cos = cos(deg2rad($phi));
            $sin = sin(deg2rad($phi));
            $shift = [mt_rand(-150, 150) / 100, mt_rand(-100, 100) / 100];
            $c = [self::W / 2, self::H / 2];
            $f = static fn (array $p): array => [
                round($c[0] + ($p[0] - $c[0]) * $cos - ($p[1] - $c[1]) * $sin + $shift[0], 3),
                round($c[1] + ($p[0] - $c[0]) * $sin + ($p[1] - $c[1]) * $cos + $shift[1], 3),
            ];

            $pts = array_map($f, $draw['pts']);
            $img = array_map($f, $task['a']['img']);
            $check = array_merge($pts, $img);
            $tol = self::FREE_TOLERANCE[$level] ?? 0.6;
            $out = ['kind' => $base, 'free' => true, 'tol' => $tol, 'grid' => [self::W, self::H], 'pts' => $pts, 'n' => $draw['n']];

            if ('achse' === $base) {
                [$p, $q] = [$f($draw['seg'][0]), $f($draw['seg'][1])];
                $seg = self::clipLine($p, [$q[0] - $p[0], $q[1] - $p[1]]);

                if (!$seg || hypot($seg[1][0] - $seg[0][0], $seg[1][1] - $seg[0][1]) < 9) {
                    continue;
                }

                $out['seg'] = [array_map(static fn (float $v): float => round($v, 3), $seg[0]), array_map(static fn (float $v): float => round($v, 3), $seg[1])];
            } else {
                $out['center'] = $f($draw['center']);
                $check[] = $out['center'];
            }

            // Everything at least 0.8 squares away from the border
            $inside = array_filter($check, static fn (array $p): bool => $p[0] < 0.8 || $p[0] > self::W - 0.8 || $p[1] < 0.8 || $p[1] > self::H - 0.8);

            if ($inside) {
                continue;
            }

            $out['ggb'] = true;

            $way = 'achse' === $base
                ? 'Zeichne mit «Senkrechte Gerade» die Senkrechte zur Achse s durch den Punkt. Zeichne mit «Kreis mit Mittelpunkt durch Punkt» einen Kreis um den Schnittpunkt der Senkrechten mit s durch den Punkt. Der Kreis schneidet die Senkrechte auf der anderen Seite im Bildpunkt. Verbinde zum Schluss die Bildpunkte mit «Strecke» zur Bildfigur.'
                : 'Zeichne mit «Gerade» die Gerade durch den Punkt und Z. Zeichne mit «Kreis mit Mittelpunkt durch Punkt» den Kreis um Z durch den Punkt. Er schneidet die Gerade auf der anderen Seite im Bildpunkt. Verbinde zum Schluss die Bildpunkte mit «Strecke» zur Bildfigur.';

            return [
                'q' => $task['q'],
                'key' => md5(json_encode([$pts, $out], JSON_THROW_ON_ERROR)),
                'a' => ['type' => 'points', 'img' => $img, 'tol' => $tol, 'draw' => $out],
                'way' => $way,
                // One more point for joining the image points with segments
                'points' => $task['points'] + 1,
                'area' => $kind,
            ];
        }

        throw new \RuntimeException('Could not generate a construction task.');
    }

    /* ---------- Geometry helpers ---------- */

    public static function eq(array|null $p, array|null $q): bool
    {
        return null !== $p && null !== $q && abs($p[0] - $q[0]) < 1e-9 && abs($p[1] - $q[1]) < 1e-9;
    }

    public static function inside(array $p): bool
    {
        return $p[0] > 0 && $p[0] < self::W && $p[1] > 0 && $p[1] < self::H;
    }

    public static function clipLine(array $p, array $d): array|null
    {
        $t0 = -\INF;
        $t1 = \INF;

        foreach ([[$p[0], $d[0], 0, self::W], [$p[1], $d[1], 0, self::H]] as [$pi, $di, $lo, $hi]) {
            if (abs($di) < 1e-12) {
                if ($pi < $lo || $pi > $hi) {
                    return null;
                }

                continue;
            }

            $a = ($lo - $pi) / $di;
            $b = ($hi - $pi) / $di;

            if ($a > $b) {
                [$a, $b] = [$b, $a];
            }

            $t0 = max($t0, $a);
            $t1 = min($t1, $b);
        }

        if (!($t0 < $t1)) {
            return null;
        }

        return [[$p[0] + $t0 * $d[0], $p[1] + $t0 * $d[1]], [$p[0] + $t1 * $d[0], $p[1] + $t1 * $d[1]]];
    }

    private static function segDist(array $p, array $a, array $b): float
    {
        $dx = $b[0] - $a[0];
        $dy = $b[1] - $a[1];
        $t = max(0, min(1, (($p[0] - $a[0]) * $dx + ($p[1] - $a[1]) * $dy) / ($dx * $dx + $dy * $dy)));

        return hypot($p[0] - $a[0] - $t * $dx, $p[1] - $a[1] - $t * $dy);
    }

    private static function centroid(array $pts): array
    {
        return [array_sum(array_column($pts, 0)) / \count($pts), array_sum(array_column($pts, 1)) / \count($pts)];
    }
}
