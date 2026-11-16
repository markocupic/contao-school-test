<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Angle;

use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Angle tasks (port of the "Winkel-Trainer"): vertical and adjacent angles, corresponding
 * angles at parallels, triangles, quadrilaterals, polygons and combined tasks.
 *
 * A task holds the sketch as SVG (without and with solution) and one answer field per
 * unknown angle. Texts are German, the translator turns them into fr/it/en with the
 * templates in AngleTexts.
 */
final class AngleGenerator
{
    public const KINDS = ['scheitel', 'parallel', 'dreieck', 'viereck', 'vieleck', 'kombi', 'profi'];

    public const W = 640;

    public const H = 420;

    private const GR = ['α', 'β', 'γ', 'δ', 'ε', 'ζ', 'η', 'θ', 'ι', 'κ', 'λ', 'μ'];

    private const LT = 'ABCDEFGHIJKL';

    private const COL = ['given' => '#2563eb', 'unknown' => '#ea580c', 'neutral' => '#94a3b8', 'sol' => '#16a34a', 'solved' => '#ea580c'];

    public function build(string $kind): array
    {
        $t = match ($kind) {
            'scheitel' => $this->genScheitel(),
            'parallel' => $this->genParallel(),
            'dreieck' => $this->genTriangle(),
            'viereck' => $this->genQuad(),
            'vieleck' => $this->genPoly(),
            'kombi' => $this->genCombined(),
            default => $this->genProfi(),
        };

        $unknowns = [];

        foreach ($t['S']['marks'] as $m) {
            if ('unknown' === $m['kind'] && isset($m['ans'])) {
                $unknowns[] = [$m['text'], $m['ans']];
            }
        }

        if (!$unknowns) {
            $unknowns = [[$t['label'], $t['answer']]];

            // The solution sketch shows the value at the unknown angle
            foreach ($t['S']['marks'] as &$m) {
                if ('unknown' === $m['kind'] && $m['text'] === $t['label']) {
                    $m['ans'] = $t['answer'];
                }
            }
            unset($m);
        }

        $unit = $t['unit'] ?? '°';
        $screen = $this->finalize($t['S']);
        $values = array_map(static fn (array $u): int|float => $u[1], $unknowns);

        return [
            'q' => $t['prompt'],
            'key' => md5($t['prompt'].json_encode($values).json_encode($t['S']['marks'])),
            'a' => [
                'type' => 'angles',
                'fields' => array_map(static fn (array $u): array => ['label' => $u[0], 'unit' => $unit], $unknowns),
                'v' => $values,
                'svg' => $this->render($screen, false),
                'solSvg' => $this->render($screen, true),
            ],
            'way' => '<ol class="st-steps">'.implode('', array_map(static fn (string $s): string => '<li>'.$s.'</li>', $t['steps'])).'</ol>',
            'points' => \count($unknowns),
            'area' => $kind,
        ];
    }

    /* ---------- Texts ---------- */

    /**
     * Fill a German template. Every phrase is wrapped, so the translator can replace it as a whole.
     */
    private static function p(string $tpl, string|int|float ...$args): string
    {
        return '<span class="st-t">'.self::fill($tpl, $args).'</span>';
    }

    private static function fill(string $tpl, array $args): string
    {
        foreach ($args as $i => $v) {
            $tpl = str_replace('{'.$i.'}', (string) $v, $tpl);
        }

        return $tpl;
    }

    /**
     * "α, β und γ" as two placeholders: "α, β" and "γ".
     */
    private static function listOf(array $labels): array
    {
        $last = array_pop($labels);

        return [implode(', ', $labels), $last];
    }

    /* ---------- Vectors ---------- */

    private static function rad(float $d): float
    {
        return $d * M_PI / 180;
    }

    private static function deg(float $r): float
    {
        return $r * 180 / M_PI;
    }

    private static function add(array $a, array $b): array
    {
        return [$a[0] + $b[0], $a[1] + $b[1]];
    }

    private static function sub(array $a, array $b): array
    {
        return [$a[0] - $b[0], $a[1] - $b[1]];
    }

    private static function mul(array $a, float $s): array
    {
        return [$a[0] * $s, $a[1] * $s];
    }

    private static function len(array $a): float
    {
        return hypot($a[0], $a[1]);
    }

    private static function unit(array $a): array
    {
        $l = self::len($a) ?: 1;

        return [$a[0] / $l, $a[1] / $l];
    }

    private static function lerp(array $a, array $b, float $t): array
    {
        return self::add($a, self::mul(self::sub($b, $a), $t));
    }

    private static function dirPt(array $v, float $d, float $l = 1): array
    {
        return [$v[0] + $l * cos(self::rad($d)), $v[1] + $l * sin(self::rad($d))];
    }

    private static function nrm(float $d): float
    {
        return fmod(fmod($d, 360) + 360, 360);
    }

    private static function centroid(array $ps): array
    {
        $s = [0, 0];

        foreach ($ps as $p) {
            $s = self::add($s, $p);
        }

        return self::mul($s, 1 / \count($ps));
    }

    private static function rand(): float
    {
        return mt_rand() / mt_getrandmax();
    }

    private static function rndAngle(int $a, int $b, int $avoid = 8): int
    {
        do {
            $x = Util::ri($a, $b);
        } while (abs($x - 90) < $avoid);

        return $x;
    }

    private static function rayInt(array $p, float $d1, array $q, float $d2): array
    {
        $u = self::dirPt([0, 0], $d1);
        $w = self::dirPt([0, 0], $d2);
        $den = $u[0] * $w[1] - $u[1] * $w[0];
        $t = (($q[0] - $p[0]) * $w[1] - ($q[1] - $p[1]) * $w[0]) / $den;

        return self::add($p, self::mul($u, $t));
    }

    private static function newScene(): array
    {
        return ['segs' => [], 'ext' => [], 'lines' => [], 'marks' => [], 'ticks' => [], 'labels' => [], 'chev' => [], 'solSegs' => [], 'solMarks' => [], 'arcs' => [], 'texts' => [], 'fit' => [], 'rot' => 0, 'dofit' => true, 'margin' => 70];
    }

    private static function sectorMark(array $v, array $rays, int $k, string $text = '', string $kind = 'unknown'): array
    {
        $n = \count($rays);

        return ['v' => $v, 'a' => self::dirPt($v, $rays[$k]), 'b' => self::dirPt($v, $rays[($k + 1) % $n]), 'text' => $text, 'kind' => $kind];
    }

    private static function u(array $m, string $label, int|float $ans): array
    {
        return array_merge($m, ['text' => $label, 'kind' => 'unknown', 'ans' => $ans]);
    }

    /**
     * Relation of two sectors at the same intersection of two lines.
     */
    private static function rel(int $i, int $j, int $x, string $lab): array
    {
        if ($i === $j) {
            return ['t' => self::p('{0} = <b>{1}°</b>', $lab, $x), 'v' => $x];
        }

        if (0 === abs($i - $j) % 2) {
            return ['t' => self::p('<b>Scheitelwinkel</b> sind gleich gross → {0} = <b>{1}°</b>', $lab, $x), 'v' => $x];
        }

        return ['t' => self::p('<b>Nebenwinkel</b> ergänzen sich zu 180° → {0} = 180° − {1}° = <b>{2}°</b>', $lab, $x, 180 - $x), 'v' => 180 - $x];
    }

    private static function trans(int $i, int $j, int $x, string $lab): string
    {
        return $i === $j ? self::p('{0} = <b>{1}°</b>', $lab, $x) : self::rel($i, $j, $x, $lab)['t'];
    }

    /* ---------- Polygons ---------- */

    private static function interiorAngles(array $pts): array
    {
        $n = \count($pts);
        $out = [];

        foreach ($pts as $k => $v) {
            $u = self::sub($pts[($k - 1 + $n) % $n], $v);
            $w = self::sub($pts[($k + 1) % $n], $v);
            $out[] = self::deg(acos(max(-1, min(1, ($u[0] * $w[0] + $u[1] * $w[1]) / (self::len($u) * self::len($w))))));
        }

        return $out;
    }

    private static function isConvexCCW(array $pts): bool
    {
        $n = \count($pts);

        for ($k = 0; $k < $n; ++$k) {
            $u = self::sub($pts[($k + 1) % $n], $pts[$k]);
            $w = self::sub($pts[($k + 2) % $n], $pts[($k + 1) % $n]);

            if ($u[0] * $w[1] - $u[1] * $w[0] <= 0) {
                return false;
            }
        }

        return true;
    }

    private static function regPoly(int $n, float $off = 90): array
    {
        $pts = [];

        for ($k = 0; $k < $n; ++$k) {
            $pts[] = self::dirPt([0, 0], $off + 360 / $n * $k);
        }

        return $pts;
    }

    private static function randPoly(int $n, float $minA, float $maxA): array
    {
        for ($t = 0; $t < 800; ++$t) {
            $step = 360 / $n;
            $off = self::rand() * 360;
            $pts = [];

            for ($k = 0; $k < $n; ++$k) {
                $th = $off + $step * $k + (self::rand() - 0.5) * $step * 0.55;
                $pts[] = self::dirPt([0, 0], $th, 1 + (self::rand() - 0.5) * 0.35);
            }

            if (!self::isConvexCCW($pts)) {
                continue;
            }

            $ang = self::interiorAngles($pts);

            if (min($ang) >= $minA && max($ang) <= $maxA) {
                return [$pts, $ang];
            }
        }

        $pts = self::regPoly($n);

        return [$pts, self::interiorAngles($pts)];
    }

    /**
     * Interior angles rounded to whole degrees, with the exact angle sum.
     */
    private static function roundAngles(array $ang): array
    {
        $r = array_map(static fn (float $a): int => (int) round($a), $ang);
        $diff = (\count($ang) - 2) * 180 - array_sum($r);

        // Spread the rounding error over the angles with the largest remainder
        $order = array_keys($ang);
        usort($order, static fn (int $x, int $y): int => $diff > 0 ? ($ang[$y] - $r[$y]) <=> ($ang[$x] - $r[$x]) : ($ang[$x] - $r[$x]) <=> ($ang[$y] - $r[$y]));

        for ($i = 0; 0 !== $diff; ++$i) {
            $k = $order[$i % \count($order)];
            $r[$k] += $diff > 0 ? 1 : -1;
            $diff += $diff > 0 ? -1 : 1;
        }

        return $r;
    }

    private static function polyScene(array $pts): array
    {
        $S = self::newScene();
        $n = \count($pts);
        $cen = self::centroid($pts);
        $S['fit'] = $pts;

        for ($k = 0; $k < $n; ++$k) {
            $S['segs'][] = ['a' => $pts[$k], 'b' => $pts[($k + 1) % $n]];
            $S['labels'][] = ['p' => $pts[$k], 'text' => self::LT[$k], 'away' => $cen];
        }

        return $S;
    }

    private static function angMark(array $pts, int $k, string $text = '', string $kind = 'unknown'): array
    {
        $n = \count($pts);

        return ['v' => $pts[$k], 'a' => $pts[($k - 1 + $n) % $n], 'b' => $pts[($k + 1) % $n], 'text' => $text, 'kind' => $kind];
    }

    private static function triScene(float $al, float $be): array
    {
        $A = [0, 0];
        $B = [1, 0];
        $C = self::rayInt($A, $al, $B, 180 - $be);
        $S = self::newScene();
        $S['rot'] = Util::ri(-20, 20);
        $S['fit'] = [$A, $B, $C];
        $S['segs'] = [['a' => $A, 'b' => $B], ['a' => $B, 'b' => $C], ['a' => $C, 'b' => $A]];
        $cen = self::centroid([$A, $B, $C]);
        $S['labels'] = [['p' => $A, 'text' => 'A', 'away' => $cen], ['p' => $B, 'text' => 'B', 'away' => $cen], ['p' => $C, 'text' => 'C', 'away' => $cen]];

        return [$S, $A, $B, $C];
    }

    /* ---------- 1) Vertical and adjacent angles ---------- */

    private function genScheitel(): array
    {
        $S = self::newScene();
        $S['dofit'] = false;
        $v = [self::W / 2, self::H / 2];

        if (self::rand() < 0.6) {
            $t = Util::ri(-35, 35);
            $phi = self::rndAngle(28, 152);
            $S['lines'][] = ['p' => $v, 'q' => self::dirPt($v, $t)];
            $S['lines'][] = ['p' => $v, 'q' => self::dirPt($v, $t + $phi)];
            $rays = [$t, $t + $phi, $t + 180, $t + 180 + $phi];
            $vals = [$phi, 180 - $phi, $phi, 180 - $phi];
            $i = Util::ri(0, 3);

            do {
                $j = Util::ri(0, 3);
            } while ($j === $i);

            $S['marks'][] = self::sectorMark($v, $rays, $i, $vals[$i].'°', 'given');
            $S['marks'][] = self::sectorMark($v, $rays, $j, 'α', 'unknown');
            $x = $vals[$i];

            return [
                'S' => $S, 'prompt' => self::fill('Zwei Geraden schneiden sich. Berechne {0}.', ['α']), 'label' => 'α', 'answer' => $vals[$j],
                'steps' => 2 === abs($j - $i)
                    ? [self::p('α und {0}° liegen sich am Schnittpunkt gegenüber → <b>Scheitelwinkel</b>.', $x), self::p('Scheitelwinkel sind gleich gross: α = <b>{0}°</b>', $x)]
                    : [self::p('α und {0}° liegen nebeneinander an einer Geraden → <b>Nebenwinkel</b>.', $x), self::p('Nebenwinkel ergänzen sich zu 180°: α = 180° − {0}° = <b>{1}°</b>', $x, 180 - $x)],
            ];
        }

        // Three lines through one point
        $t = Util::ri(-30, 30);
        $a = Util::ri(30, 80);
        $b = Util::ri(30, 150 - $a);
        $c = 180 - $a - $b;
        $rays = [$t, $t + $a, $t + $a + $b, $t + 180, $t + 180 + $a, $t + 180 + $a + $b];
        $S['lines'][] = ['p' => $v, 'q' => self::dirPt($v, $t)];
        $S['lines'][] = ['p' => $v, 'q' => self::dirPt($v, $t + $a)];
        $S['lines'][] = ['p' => $v, 'q' => self::dirPt($v, $t + $a + $b)];
        $ia = Util::pick([0, 3]);
        $ib = Util::pick([1, 4]);
        $ic = Util::pick([2, 5]);
        $S['marks'][] = self::sectorMark($v, $rays, $ia, $a.'°', 'given');
        $S['marks'][] = self::sectorMark($v, $rays, $ib, $b.'°', 'given');
        $S['marks'][] = self::sectorMark($v, $rays, $ic, 'α', 'unknown');
        $side = 2 === $ic ? [0, 1] : [3, 4];

        if ($side[0] !== $ia) {
            $S['solMarks'][] = self::sectorMark($v, $rays, $side[0], $a.'°', 'sol');
        }

        if ($side[1] !== $ib) {
            $S['solMarks'][] = self::sectorMark($v, $rays, $side[1], $b.'°', 'sol');
        }

        return [
            'S' => $S, 'prompt' => self::fill('Drei Geraden schneiden sich in einem Punkt. Berechne {0}.', ['α']), 'label' => 'α', 'answer' => $c,
            'steps' => [
                self::p('Gegenüberliegende Winkel sind <b>Scheitelwinkel</b> und damit gleich gross.'),
                self::p('Mit Scheitelwinkeln lassen sich {0}° und {1}° direkt neben α verschieben (grün gestrichelt).', $a, $b),
                self::p('Die drei Winkel bilden zusammen einen gestreckten Winkel (180°): α = 180° − {0}° − {1}° = <b>{2}°</b>', $a, $b, $c),
            ],
        ];
    }

    /* ---------- 2) Corresponding angles at parallels ---------- */

    private function genParallel(): array
    {
        $S = self::newScene();
        $S['dofit'] = false;
        $S['rot'] = Util::ri(-20, 20);
        $m = self::rand() < 0.65 ? 2 : 3;
        $ys = 2 === $m ? [140, 280] : [90, 210, 330];
        $names = 2 === $m ? ['h', 'g'] : ['k', 'h', 'g'];
        $phi = self::rndAngle(35, 145, 10);
        $x0 = self::W / 2 + Util::ri(-40, 40);
        $P = array_map(static fn (int $y): array => [$x0 + ($y - 210) / tan(self::rad($phi)), $y], $ys);

        foreach ($ys as $k => $y) {
            $S['lines'][] = ['p' => [0, $y], 'q' => [100, $y], 'chev' => 1, 'label' => $names[$k]];
        }

        $S['lines'][] = ['p' => $P[0], 'q' => self::dirPt($P[0], $phi), 'label' => 's'];
        $rays = [0, $phi, 180, 180 + $phi];
        $vals = [$phi, 180 - $phi, $phi, 180 - $phi];
        $ka = Util::ri(0, $m - 1);

        do {
            $kb = Util::ri(0, $m - 1);
        } while ($kb === $ka);

        $i = Util::ri(0, 3);
        $j = Util::ri(0, 3);
        $S['marks'][] = self::sectorMark($P[$ka], $rays, $i, $vals[$i].'°', 'given');
        $S['marks'][] = self::sectorMark($P[$kb], $rays, $j, 'α', 'unknown');

        if ($i !== $j) {
            $S['solMarks'][] = self::sectorMark($P[$kb], $rays, $i, $vals[$i].'°', 'sol');
        }

        $x = $vals[$i];
        $par = 2 === $m ? 'g ∥ h' : 'g ∥ h ∥ k';
        $steps = [$i !== $j
            ? self::p('{0}: Der Winkel {1}° taucht beim Schnittpunkt von α an der <b>gleichen Lage</b> wieder auf → <b>Stufenwinkel</b> (gleich gross) – grün gestrichelt.', $par, $x)
            : self::p('{0}: Der Winkel {1}° taucht beim Schnittpunkt von α an der <b>gleichen Lage</b> wieder auf → <b>Stufenwinkel</b> (gleich gross).', $par, $x)];

        if ($i === $j) {
            $steps[] = self::p('α ist genau dieser Stufenwinkel: α = <b>{0}°</b>', $x);
        } else {
            $steps[] = self::rel($i, $j, $x, 'α')['t'];

            if (2 === abs($i - $j)) {
                $steps[] = self::p('Merke: Stufenwinkel + Scheitelwinkel ergeben <b>Wechselwinkel</b> – auch sie sind an Parallelen gleich gross.');
            }
        }

        return [
            'S' => $S, 'prompt' => self::fill('Die Geraden sind parallel ({0}) und werden von s geschnitten. Berechne {1}.', [$par, 'α']),
            'label' => 'α', 'answer' => self::rel($i, $j, $x, 'α')['v'], 'steps' => $steps,
        ];
    }

    /* ---------- 3) Triangle ---------- */

    private function genTriangle(): array
    {
        $v = Util::pick(['allg', 'allg', 'gleichschenklig', 'rechtwinklig']);

        if ('allg' === $v) {
            $al = Util::ri(25, 110);
            $be = Util::ri(25, 155 - $al);
            $ga = 180 - $al - $be;
            $vals = [$al, $be, $ga];
            $k = Util::ri(0, 2);
            [$S, $A, $B, $C] = self::triScene($al, $be);
            $V = [[$A, $B, $C], [$B, $C, $A], [$C, $A, $B]];

            foreach ($vals as $idx => $x) {
                $S['marks'][] = ['v' => $V[$idx][0], 'a' => $V[$idx][1], 'b' => $V[$idx][2], 'text' => $idx === $k ? self::GR[$idx] : $x.'°', 'kind' => $idx === $k ? 'unknown' : 'given'];
            }

            $g = array_values(array_filter($vals, static fn (int $i): bool => $i !== $k, ARRAY_FILTER_USE_KEY));

            return [
                'S' => $S, 'prompt' => self::fill('Berechne {0} im Dreieck ABC.', [self::GR[$k]]), 'label' => self::GR[$k], 'answer' => $vals[$k],
                'steps' => [self::p('Winkelsumme im Dreieck: α + β + γ = 180°'), self::p('{0} = 180° − {1}° − {2}° = <b>{3}°</b>', self::GR[$k], $g[0], $g[1], $vals[$k])],
            ];
        }

        if ('gleichschenklig' === $v) {
            $b = Util::ri(25, 75);
            $ga = 180 - 2 * $b;
            [$S, $A, $B, $C] = self::triScene($b, $b);
            $S['ticks'][] = ['a' => $A, 'b' => $C, 'n' => 1];
            $S['ticks'][] = ['a' => $B, 'b' => $C, 'n' => 1];

            if (self::rand() < 0.55) {
                $S['marks'][] = ['v' => $C, 'a' => $A, 'b' => $B, 'text' => $ga.'°', 'kind' => 'given'];
                $S['marks'][] = ['v' => $A, 'a' => $B, 'b' => $C, 'text' => 'α', 'kind' => 'unknown'];
                $S['solMarks'][] = ['v' => $B, 'a' => $C, 'b' => $A, 'text' => 'α', 'kind' => 'sol'];

                return [
                    'S' => $S, 'prompt' => self::fill('Das Dreieck ABC ist gleichschenklig (AC = BC). Berechne {0}.', ['α']), 'label' => 'α', 'answer' => $b,
                    'steps' => [self::p('Gleichschenkliges Dreieck: Die Basiswinkel α und β sind gleich gross.'), self::p('α + β = 180° − {0}° = {1}°', $ga, 2 * $b), self::p('α = {0}° : 2 = <b>{1}°</b>', 2 * $b, $b)],
                ];
            }

            $atA = self::rand() < 0.5;
            $S['marks'][] = $atA ? ['v' => $A, 'a' => $B, 'b' => $C, 'text' => $b.'°', 'kind' => 'given'] : ['v' => $B, 'a' => $C, 'b' => $A, 'text' => $b.'°', 'kind' => 'given'];
            $S['marks'][] = ['v' => $C, 'a' => $A, 'b' => $B, 'text' => 'γ', 'kind' => 'unknown'];
            $S['solMarks'][] = $atA ? ['v' => $B, 'a' => $C, 'b' => $A, 'text' => $b.'°', 'kind' => 'sol'] : ['v' => $A, 'a' => $B, 'b' => $C, 'text' => $b.'°', 'kind' => 'sol'];

            return [
                'S' => $S, 'prompt' => self::fill('Das Dreieck ABC ist gleichschenklig (AC = BC). Berechne {0}.', ['γ']), 'label' => 'γ', 'answer' => $ga,
                'steps' => [self::p('Gleichschenkliges Dreieck: Beide Basiswinkel messen {0}°.', $b), self::p('γ = 180° − {0}° − {0}° = <b>{1}°</b>', $b, $ga)],
            ];
        }

        // Right angle at C
        $al = Util::ri(20, 70);
        $be = 90 - $al;
        [$S, $A, $B, $C] = self::triScene($al, $be);
        $S['marks'][] = ['v' => $C, 'a' => $A, 'b' => $B, 'text' => '', 'kind' => 'right'];
        $askB = self::rand() < 0.5;
        $S['marks'][] = ['v' => $A, 'a' => $B, 'b' => $C, 'text' => $askB ? $al.'°' : 'α', 'kind' => $askB ? 'given' : 'unknown'];
        $S['marks'][] = ['v' => $B, 'a' => $C, 'b' => $A, 'text' => $askB ? 'β' : $be.'°', 'kind' => $askB ? 'unknown' : 'given'];
        $lab = $askB ? 'β' : 'α';
        $g = $askB ? $al : $be;
        $ans = $askB ? $be : $al;

        return [
            'S' => $S, 'prompt' => self::fill('Das Dreieck ABC hat bei C einen rechten Winkel. Berechne {0}.', [$lab]), 'label' => $lab, 'answer' => $ans,
            'steps' => [self::p('Rechter Winkel bei C: γ = 90°'), self::p('{0} = 180° − 90° − {1}° = <b>{2}°</b>', $lab, $g, $ans)],
        ];
    }

    /* ---------- 4) Quadrilateral ---------- */

    private function genQuad(): array
    {
        if (self::rand() < 0.65) {
            [$pts, $ang] = self::randPoly(4, 55, 140);
            $A = self::roundAngles($ang);
            $u = Util::ri(0, 3);
            $S = self::polyScene($pts);
            $giv = [];

            for ($k = 0; $k < 4; ++$k) {
                if ($k === $u) {
                    $S['marks'][] = self::angMark($pts, $k, self::GR[$k], 'unknown');
                } else {
                    $S['marks'][] = self::angMark($pts, $k, $A[$k].'°', 'given');
                    $giv[] = $A[$k];
                }
            }

            $ans = 360 - array_sum($giv);
            $S['solSegs'][] = ['a' => $pts[$u], 'b' => $pts[($u + 2) % 4]];

            return [
                'S' => $S, 'prompt' => self::fill('Berechne {0} im Viereck ABCD.', [self::GR[$u]]), 'label' => self::GR[$u], 'answer' => $ans,
                'steps' => [
                    self::p('Eine Diagonale teilt das Viereck in zwei Dreiecke → Winkelsumme im Viereck = 2 · 180° = 360°.'),
                    self::p('{0} = 360° − ({1}°) = 360° − {2}° = <b>{3}°</b>', self::GR[$u], implode('° + ', $giv), array_sum($giv), $ans),
                ],
            ];
        }

        // Parallelogram
        $al = self::rndAngle(50, 130, 6);
        $A = [0, 0];
        $B = [1.4, 0];
        $D = self::dirPt($A, $al, 0.9);
        $C = self::add($B, $D);
        $pts = [$A, $B, $C, $D];
        $S = self::polyScene($pts);
        $S['rot'] = Util::ri(-15, 15);
        $S['chev'] = [['a' => $A, 'b' => $B, 'n' => 1], ['a' => $D, 'b' => $C, 'n' => 1], ['a' => $A, 'b' => $D, 'n' => 2], ['a' => $B, 'b' => $C, 'n' => 2]];
        $vals = [$al, 180 - $al, $al, 180 - $al];
        $g = Util::ri(0, 3);

        do {
            $u = Util::ri(0, 3);
        } while ($u === $g);

        $S['marks'][] = self::angMark($pts, $g, $vals[$g].'°', 'given');
        $S['marks'][] = self::angMark($pts, $u, self::GR[$u], 'unknown');
        $lab = self::GR[$u];

        return [
            'S' => $S, 'prompt' => self::fill('ABCD ist ein Parallelogramm (AB ∥ DC und AD ∥ BC). Berechne {0}.', [$lab]), 'label' => $lab, 'answer' => $vals[$u],
            'steps' => 2 === abs($g - $u)
                ? [self::p('{0} liegt dem gegebenen Winkel gegenüber.', $lab), self::p('Im Parallelogramm sind gegenüberliegende Winkel gleich gross: {0} = <b>{1}°</b>', $lab, $vals[$u])]
                : [self::p('{0} und {1}° liegen an derselben Seite zwischen zwei Parallelen.', $lab, $vals[$g]), self::p('Solche Winkel ergänzen sich zu 180° (Stufenwinkel + Nebenwinkel): {0} = 180° − {1}° = <b>{2}°</b>', $lab, $vals[$g], $vals[$u])],
        ];
    }

    /* ---------- 5) Polygon ---------- */

    private function genPoly(): array
    {
        $v = Util::pick(['sum', 'miss', 'miss', 'reg', 'inv']);

        if ('sum' === $v) {
            $n = Util::ri(5, 10);
            [$pts] = self::randPoly($n, 60, 172);
            $S = self::polyScene($pts);

            for ($k = 0; $k < $n; ++$k) {
                $S['marks'][] = self::angMark($pts, $k, '', 'neutral');
            }

            for ($k = 2; $k <= $n - 2; ++$k) {
                $S['solSegs'][] = ['a' => $pts[0], 'b' => $pts[$k]];
            }

            $tot = 180 * ($n - 2);

            return [
                'S' => $S, 'prompt' => self::fill('Wie gross ist die Winkelsumme in diesem {0}-Eck?', [$n]), 'label' => '∑', 'answer' => $tot,
                'steps' => [self::p('Von Ecke A aus lässt sich das {0}-Eck in {1} Dreiecke zerlegen (grün gestrichelt).', $n, $n - 2), self::p('Winkelsumme = (n − 2) · 180° = {0} · 180° = <b>{1}°</b>', $n - 2, $tot)],
            ];
        }

        if ('miss' === $v) {
            $n = Util::ri(5, 8);
            [$pts, $ang] = self::randPoly($n, 60, 170);
            $A = self::roundAngles($ang);
            $u = Util::ri(0, $n - 1);
            $S = self::polyScene($pts);
            $giv = [];

            for ($k = 0; $k < $n; ++$k) {
                if ($k === $u) {
                    $S['marks'][] = self::angMark($pts, $k, self::GR[$k], 'unknown');
                } else {
                    $S['marks'][] = self::angMark($pts, $k, $A[$k].'°', 'given');
                    $giv[] = $A[$k];
                }
            }

            for ($k = 2; $k <= $n - 2; ++$k) {
                $S['solSegs'][] = ['a' => $pts[$u], 'b' => $pts[($u + $k) % $n]];
            }

            $tot = 180 * ($n - 2);
            $ans = $tot - array_sum($giv);

            return [
                'S' => $S, 'prompt' => self::fill('Berechne {0} im {1}-Eck.', [self::GR[$u], $n]), 'label' => self::GR[$u], 'answer' => $ans,
                'steps' => [
                    self::p('Winkelsumme im {0}-Eck: ({0} − 2) · 180° = {1}°', $n, $tot),
                    self::p('{0} = {1}° − ({2}°) = {1}° − {3}° = <b>{4}°</b>', self::GR[$u], $tot, implode('° + ', $giv), array_sum($giv), $ans),
                ],
            ];
        }

        if ('reg' === $v) {
            $n = Util::pick([5, 6, 8, 9, 10, 12]);
            $pts = self::regPoly($n, 90 + 180 / $n * Util::ri(0, 1));
            $S = self::polyScene($pts);
            $u = Util::ri(0, $n - 1);

            for ($k = 0; $k < $n; ++$k) {
                $S['marks'][] = self::angMark($pts, $k, $k === $u ? self::GR[$k] : '', $k === $u ? 'unknown' : 'neutral');
                $S['ticks'][] = ['a' => $pts[$k], 'b' => $pts[($k + 1) % $n], 'n' => 1];
            }

            $tot = 180 * ($n - 2);
            $ans = intdiv($tot, $n);

            return [
                'S' => $S, 'prompt' => self::fill('Das {0}-Eck ist regelmässig (alle Seiten und alle Winkel gleich gross). Berechne {1}.', [$n, self::GR[$u]]), 'label' => self::GR[$u], 'answer' => $ans,
                'steps' => [self::p('Winkelsumme: ({0} − 2) · 180° = {1}°', $n, $tot), self::p('Alle {0} Winkel sind gleich gross: {1} = {2}° : {0} = <b>{3}°</b>', $n, self::GR[$u], $tot, $ans)],
            ];
        }

        // Number of corners from the angle sum
        $n = Util::ri(5, 14);
        $tot = 180 * ($n - 2);
        $S = self::newScene();
        $S['dofit'] = false;
        $S['texts'][] = ['x' => self::W / 2, 'y' => self::H / 2 - 20, 'text' => 'n = ?', 'size' => 64, 'col' => '#cbd5e1'];
        $S['texts'][] = ['x' => self::W / 2, 'y' => self::H / 2 + 50, 'text' => '∑ = '.$tot.'°', 'size' => 30, 'col' => '#2563eb'];

        return [
            'S' => $S, 'prompt' => self::fill('Ein Vieleck hat die Winkelsumme {0}°. Wie viele Ecken hat es?', [$tot]), 'label' => 'n', 'unit' => '', 'answer' => $n,
            'steps' => [self::p('Winkelsumme = (n − 2) · 180°'), self::p('n − 2 = {0}° : 180° = {1}', $tot, $n - 2), self::p('n = {0} + 2 = <b>{1}</b>', $n - 2, $n)],
        ];
    }

    /* ---------- 6) Combined: several unknown angles ---------- */

    private static function stuf(int $x, string $tpl, string ...$args): string
    {
        return self::p($tpl, $x, ...$args);
    }

    private function kAussen(): array
    {
        $al = Util::ri(35, 80);
        $be = Util::ri(40, min(110, 150 - $al));
        $ga = 180 - $al - $be;
        $ep = 180 - $be;
        $ph = 180 - $ga;
        [$S, $A, $B, $C] = self::triScene($al, $be);
        $E = self::add($B, self::mul(self::sub($B, $A), 0.45));
        $G = self::add($C, self::mul(self::sub($C, $B), 0.45));
        $S['ext'] = [['a' => $B, 'b' => $E], ['a' => $C, 'b' => $G]];
        array_push($S['fit'], $E, $G);
        array_push(
            $S['marks'],
            ['v' => $A, 'a' => $B, 'b' => $C, 'text' => $al.'°', 'kind' => 'given'],
            ['v' => $B, 'a' => $E, 'b' => $C, 'text' => $ep.'°', 'kind' => 'given'],
            self::u(['v' => $B, 'a' => $C, 'b' => $A], 'β', $be),
            self::u(['v' => $C, 'a' => $A, 'b' => $B], 'γ', $ga),
            self::u(['v' => $C, 'a' => $G, 'b' => $A], 'φ', $ph),
        );

        return ['S' => $S, 'prompt' => self::fill('Die Seiten AB und BC sind verlängert. Berechne {0} und {1}.', self::listOf(['β', 'γ', 'φ'])),
            'steps' => [
                self::p('{0} und {1}° sind <b>Nebenwinkel</b>: {0} = 180° − {1}° = <b>{2}°</b>', 'β', $ep, $be),
                self::p('<b>Winkelsumme im Dreieck</b>: {0} = 180° − {1}° − {2}° = <b>{3}°</b>', 'γ', $al, $be, $ga),
                self::p('{0} und {1} sind <b>Nebenwinkel</b>: {0} = 180° − {2}° = <b>{3}°</b>', 'φ', 'γ', $ga, $ph),
            ]];
    }

    private function kTrapez(): array
    {
        do {
            $al = Util::ri(50, 115);
            $be = Util::ri(50, 115);
            $h = Util::ri(55, 85) / 100;
            $A = [0, 0];
            $B = [1.6, 0];
            $D = [$h / tan(self::rad($al)), $h];
            $C = [1.6 - $h / tan(self::rad($be)), $h];
        } while ($C[0] - $D[0] < 0.45);

        $pts = [$A, $B, $C, $D];
        $S = self::polyScene($pts);
        $S['rot'] = Util::ri(-12, 12);
        $S['chev'] = [['a' => $A, 'b' => $B, 'n' => 1], ['a' => $D, 'b' => $C, 'n' => 1]];
        $ga = 180 - $be;
        $de = 180 - $al;
        $E = self::add($C, self::mul(self::sub($C, $D), 0.4));
        $S['ext'][] = ['a' => $C, 'b' => $E];
        $S['fit'][] = $E;
        array_push(
            $S['marks'],
            self::angMark($pts, 0, $al.'°', 'given'),
            ['v' => $C, 'a' => $E, 'b' => $B, 'text' => $be.'°', 'kind' => 'given'],
            self::u(self::angMark($pts, 2), 'γ', $ga),
            self::u(self::angMark($pts, 1), 'β', $be),
            self::u(self::angMark($pts, 3), 'δ', $de),
        );

        return ['S' => $S, 'prompt' => self::fill('ABCD ist ein Trapez mit AB ∥ DC. Die Seite DC ist verlängert. Berechne {0} und {1}.', self::listOf(['γ', 'β', 'δ'])),
            'steps' => [
                self::p('{0} und {1}° sind <b>Nebenwinkel</b>: {0} = 180° − {1}° = <b>{2}°</b>', 'γ', $be, $ga),
                self::p('{0} und der Winkel {1}° bei C sind <b>Wechselwinkel</b> (AB ∥ DC): {0} = <b>{1}°</b>', 'β', $be),
                self::p('α und δ liegen am Schenkel AD zwischen den Parallelen → sie ergänzen sich zu 180°: δ = 180° − {0}° = <b>{1}°</b>', $al, $de),
            ]];
    }

    private function kParC(): array
    {
        $al = Util::ri(35, 80);
        $be = Util::ri(35, min(85, 150 - $al));
        $ga = 180 - $al - $be;
        [$S, $A, $B, $C] = self::triScene($al, $be);
        $S['rot'] = Util::ri(-10, 10);
        $S['margin'] = 80;
        $S['lines'][] = ['p' => $A, 'q' => $B, 'chev' => 1, 'label' => 'h'];
        $S['lines'][] = ['p' => $C, 'q' => self::add($C, [1, 0]), 'chev' => 1, 'label' => 'g'];
        $Cl = self::add($C, [-1, 0]);
        $Cr = self::add($C, [1, 0]);
        array_push(
            $S['marks'],
            ['v' => $C, 'a' => $Cl, 'b' => $A, 'text' => $al.'°', 'kind' => 'given'],
            ['v' => $B, 'a' => $C, 'b' => $A, 'text' => $be.'°', 'kind' => 'given'],
            self::u(['v' => $A, 'a' => $B, 'b' => $C], 'α', $al),
            self::u(['v' => $C, 'a' => $Cr, 'b' => $B], 'δ', $be),
            self::u(['v' => $C, 'a' => $A, 'b' => $B], 'γ', $ga),
        );

        return ['S' => $S, 'prompt' => self::fill('Die Gerade g geht durch C und ist parallel zu h. Berechne {0} und {1}.', self::listOf(['α', 'δ', 'γ'])),
            'steps' => [
                self::p('{0} und {1}° bei C sind <b>Wechselwinkel</b> (g ∥ h): {0} = <b>{1}°</b>', 'α', $al),
                self::p('{0} und {1} sind <b>Wechselwinkel</b> (g ∥ h): {0} = <b>{2}°</b>', 'δ', 'β', $be),
                self::p('Die drei Winkel bei C liegen auf g → <b>gestreckter Winkel</b> 180°: γ = 180° − {0}° − {1}° = <b>{2}°</b>', $al, $be, $ga),
            ]];
    }

    private function kViereck(): array
    {
        [$pts, $ang] = self::randPoly(4, 60, 135);
        $A = self::roundAngles($ang);
        $e = Util::ri(0, 3);
        $u = ($e + 2) % 4;
        $w = ($e + Util::pick([1, 3])) % 4;
        $o = array_values(array_diff([0, 1, 2, 3], [$e, $u, $w]))[0];
        $ext = 180 - $A[$e];
        $S = self::polyScene($pts);
        $Ee = self::add($pts[$e], self::mul(self::sub($pts[$e], $pts[($e + 3) % 4]), 0.45));
        $Ew = self::add($pts[$w], self::mul(self::sub($pts[$w], $pts[($w + 3) % 4]), 0.45));
        $S['ext'] = [['a' => $pts[$e], 'b' => $Ee], ['a' => $pts[$w], 'b' => $Ew]];
        array_push($S['fit'], $Ee, $Ew);
        $au = 360 - $A[$e] - $A[$w] - $A[$o];
        array_push(
            $S['marks'],
            ['v' => $pts[$e], 'a' => $Ee, 'b' => $pts[($e + 1) % 4], 'text' => $ext.'°', 'kind' => 'given'],
            self::angMark($pts, $w, $A[$w].'°', 'given'),
            self::angMark($pts, $o, $A[$o].'°', 'given'),
            self::u(self::angMark($pts, $e), self::GR[$e], $A[$e]),
            self::u(self::angMark($pts, $u), self::GR[$u], $au),
            self::u(['v' => $pts[$w], 'a' => $Ew, 'b' => $pts[($w + 1) % 4]], 'φ', 180 - $A[$w]),
        );

        return ['S' => $S, 'prompt' => self::fill('Zwei Seiten des Vierecks ABCD sind verlängert. Berechne {0} und {1}.', self::listOf([self::GR[$e], self::GR[$u], 'φ'])),
            'steps' => [
                self::p('{0} und {1}° sind <b>Nebenwinkel</b>: {0} = 180° − {1}° = <b>{2}°</b>', self::GR[$e], $ext, $A[$e]),
                self::p('<b>Winkelsumme im Viereck</b> 360°: {0} = 360° − {1}° − {2}° − {3}° = <b>{4}°</b>', self::GR[$u], $A[$e], $A[$w], $A[$o], $au),
                self::p('{0} und {1}° sind <b>Nebenwinkel</b>: {0} = 180° − {1}° = <b>{2}°</b>', 'φ', $A[$w], 180 - $A[$w]),
            ]];
    }

    private function kParTri(): array
    {
        $al = Util::ri(40, 72);
        $be = Util::ri(40, 72);
        $ga = 180 - $al - $be;
        $A = [0, 0];
        $B = [1, 0];
        $C = self::rayInt($A, $al, $B, 180 - $be);
        $P = self::lerp($A, $C, 0.45);
        $Q = self::lerp($B, $C, 0.45);
        $S = self::newScene();
        $S['fit'] = [$A, $B, $C];
        $S['margin'] = 80;
        $S['rot'] = Util::ri(-12, 12);
        $S['lines'] = [['p' => $A, 'q' => $B, 'chev' => 1, 'label' => 'h'], ['p' => $P, 'q' => $Q, 'chev' => 1, 'label' => 'g'], ['p' => $A, 'q' => $C], ['p' => $B, 'q' => $C]];
        $cen = self::centroid([$A, $B, $C]);
        $S['labels'] = [['p' => $A, 'text' => 'A', 'away' => $cen], ['p' => $B, 'text' => 'B', 'away' => $cen], ['p' => $C, 'text' => 'C', 'away' => $cen]];
        $rP = [0, $al, 180, 180 + $al];
        $vP = [$al, 180 - $al, $al, 180 - $al];
        $rQ = [0, 180 - $be, 180, 360 - $be];
        $vQ = [180 - $be, $be, 180 - $be, $be];
        $i = Util::ri(0, 3);
        $j = Util::ri(0, 3);
        $sA = self::stuf($vP[$i], 'Stufenwinkel (g ∥ h): {0}° liegt auch bei {1} an gleicher Lage.', 'A').' '.self::trans($i, 0, $vP[$i], 'α');
        array_push($S['marks'], self::sectorMark($P, $rP, $i, $vP[$i].'°', 'given'), self::u(['v' => $A, 'a' => $B, 'b' => $C], 'α', $al), self::u(['v' => $B, 'a' => $C, 'b' => $A], 'β', $be));

        if (self::rand() < 0.5) {
            array_push($S['marks'], self::sectorMark($Q, $rQ, $j, $vQ[$j].'°', 'given'), self::u(['v' => $C, 'a' => $A, 'b' => $B], 'γ', $ga));

            return ['S' => $S, 'prompt' => self::fill('g ∥ h. Berechne {0} und {1}.', self::listOf(['α', 'β', 'γ'])),
                'steps' => [$sA, self::stuf($vQ[$j], 'Stufenwinkel (g ∥ h): {0}° liegt auch bei {1} an gleicher Lage.', 'B').' '.self::trans($j, 1, $vQ[$j], 'β'),
                    self::p('<b>Winkelsumme im Dreieck</b>: {0} = 180° − {1}° − {2}° = <b>{3}°</b>', 'γ', $al, $be, $ga)]];
        }

        do {
            $k = Util::ri(0, 3);
        } while (1 === $k);

        array_push($S['marks'], ['v' => $C, 'a' => $A, 'b' => $B, 'text' => $ga.'°', 'kind' => 'given'], self::u(self::sectorMark($Q, $rQ, $k), 'ε', $vQ[$k]));

        return ['S' => $S, 'prompt' => self::fill('g ∥ h. Berechne {0} und {1}.', self::listOf(['α', 'β', 'ε'])),
            'steps' => [$sA, self::p('<b>Winkelsumme im Dreieck</b>: {0} = 180° − {1}° − {2}° = <b>{3}°</b>', 'β', $al, $ga, $be),
                self::stuf($be, 'Stufenwinkel (g ∥ h): {0}° liegt auch beim Schnittpunkt von {1} an gleicher Lage.', 'ε').' '.self::trans(1, $k, $be, 'ε')]];
    }

    private function kParSchar(): array
    {
        $S = self::newScene();
        $S['dofit'] = false;
        $S['rot'] = Util::ri(-12, 12);
        $ys = [290, 130];
        $ps = Util::ri(102, 118);
        $pt = Util::ri(62, 78);
        $at = static fn (float $x0, float $phi, float $y): array => [$x0 + ($y - 210) / tan(self::rad($phi)), $y];
        $Sg = $at(190, $ps, $ys[0]);
        $Sh = $at(190, $ps, $ys[1]);
        $Tg = $at(450, $pt, $ys[0]);
        $Th = $at(450, $pt, $ys[1]);
        $S['lines'] = [
            ['p' => [0, $ys[0]], 'q' => [100, $ys[0]], 'chev' => 1, 'label' => 'g'],
            ['p' => [0, $ys[1]], 'q' => [100, $ys[1]], 'chev' => 1, 'label' => 'h'],
            ['p' => $Sh, 'q' => self::dirPt($Sh, $ps), 'label' => 's'],
            ['p' => $Th, 'q' => self::dirPt($Th, $pt), 'label' => 't'],
        ];
        $rs = [0, $ps, 180, 180 + $ps];
        $vs = [$ps, 180 - $ps, $ps, 180 - $ps];
        $rt = [0, $pt, 180, 180 + $pt];
        $vt = [$pt, 180 - $pt, $pt, 180 - $pt];
        $i = Util::ri(0, 3);
        $j = Util::ri(0, 3);

        do {
            $b = Util::ri(0, 3);
        } while ($b === $i);

        do {
            $d = Util::ri(0, 3);
        } while ($d === $j);

        $a = Util::ri(0, 3);
        $c = Util::ri(0, 3);
        array_push(
            $S['marks'],
            self::sectorMark($Sg, $rs, $i, $vs[$i].'°', 'given'),
            self::sectorMark($Th, $rt, $j, $vt[$j].'°', 'given'),
            self::u(self::sectorMark($Sg, $rs, $b), 'α', $vs[$b]),
            self::u(self::sectorMark($Sh, $rs, $a), 'β', $vs[$a]),
            self::u(self::sectorMark($Th, $rt, $d), 'γ', $vt[$d]),
            self::u(self::sectorMark($Tg, $rt, $c), 'δ', $vt[$c]),
        );
        $at2 = static fn (string $lab, string $l1, string $l2): string => self::p('{0} (bei {1} und {2}):', $lab, $l1, $l2);
        $tpl = 'Stufenwinkel (g ∥ h): {0}° liegt auch bei {1} und {2} an gleicher Lage.';

        return ['S' => $S, 'prompt' => self::fill('g ∥ h. Berechne {0} und {1}.', self::listOf(['α', 'β', 'γ', 'δ'])),
            'steps' => [
                $at2('α', 's', 'g').' '.self::rel($i, $b, $vs[$i], 'α')['t'],
                'β: '.self::stuf($vs[$i], $tpl, 's', 'h').' '.self::trans($i, $a, $vs[$i], 'β'),
                $at2('γ', 't', 'h').' '.self::rel($j, $d, $vt[$j], 'γ')['t'],
                'δ: '.self::stuf($vt[$j], $tpl, 't', 'g').' '.self::trans($j, $c, $vt[$j], 'δ'),
            ]];
    }

    /* ---------- 7) Isosceles and equilateral triangles ---------- */

    private static function isoArc(array &$S, array $A, array $B, array $C): void
    {
        $S['arcs'][] = ['c' => $C, 'a' => $A, 'b' => $B];
        $S['fit'][] = self::add($C, self::mul(self::unit(self::sub(self::lerp($A, $B, 0.5), $C)), self::len(self::sub($A, $C)) * 1.04));
    }

    private function pIsoParC(): array
    {
        $b = Util::ri(35, 75);
        $ga = 180 - 2 * $b;
        [$S, $A, $B, $C] = self::triScene($b, $b);
        $S['rot'] = Util::ri(-10, 10);
        $S['margin'] = 80;
        $S['lines'][] = ['p' => $A, 'q' => $B, 'chev' => 1, 'label' => 'h'];
        $S['lines'][] = ['p' => $C, 'q' => self::add($C, [1, 0]), 'chev' => 1, 'label' => 'g'];
        self::isoArc($S, $A, $B, $C);
        $left = self::rand() < 0.5;
        $Cl = self::add($C, [-1, 0]);
        $Cr = self::add($C, [1, 0]);
        array_push(
            $S['marks'],
            $left ? ['v' => $C, 'a' => $Cl, 'b' => $A, 'text' => $b.'°', 'kind' => 'given'] : ['v' => $C, 'a' => $Cr, 'b' => $B, 'text' => $b.'°', 'kind' => 'given'],
            self::u(['v' => $A, 'a' => $B, 'b' => $C], 'α', $b),
            self::u(['v' => $B, 'a' => $C, 'b' => $A], 'β', $b),
            self::u(['v' => $C, 'a' => $A, 'b' => $B], 'γ', $ga),
        );
        [$x, $y] = $left ? ['α', 'β'] : ['β', 'α'];

        return ['S' => $S, 'prompt' => self::fill('Das Dreieck ABC ist gleichschenklig (Bogen um C). Die Gerade g geht durch C und ist parallel zu h. Berechne {0} und {1}.', self::listOf(['α', 'β', 'γ'])),
            'steps' => [
                self::p('{0} und {1}° bei C sind <b>Wechselwinkel</b> (g ∥ h): {0} = <b>{1}°</b>', $x, $b),
                self::p('Gleichschenkliges Dreieck: Die <b>Basiswinkel</b> sind gleich gross → {0} = <b>{1}°</b>', $y, $b),
                self::p('<b>Winkelsumme im Dreieck</b>: {0} = 180° − {1}° − {2}° = <b>{3}°</b>', 'γ', $b, $b, $ga),
            ]];
    }

    private function pIsoBetween(): array
    {
        $b = self::rndAngle(40, 72, 0);
        $ga = 180 - 2 * $b;
        $A = [0, 0];
        $B = [1, 0];
        $C = self::rayInt($A, $b, $B, 180 - $b);
        $P = self::lerp($A, $C, 0.45);
        $Q = self::lerp($B, $C, 0.45);
        $S = self::newScene();
        $S['fit'] = [$A, $B, $C];
        $S['margin'] = 80;
        $S['rot'] = Util::ri(-12, 12);
        $S['lines'] = [['p' => $A, 'q' => $B, 'chev' => 1, 'label' => 'h'], ['p' => $P, 'q' => $Q, 'chev' => 1, 'label' => 'g'], ['p' => $A, 'q' => $C], ['p' => $B, 'q' => $C]];
        $cen = self::centroid([$A, $B, $C]);
        $S['labels'] = [['p' => $A, 'text' => 'A', 'away' => $cen], ['p' => $B, 'text' => 'B', 'away' => $cen], ['p' => $C, 'text' => 'C', 'away' => $cen]];
        self::isoArc($S, $A, $B, $C);
        $rP = [0, $b, 180, 180 + $b];
        $vP = [$b, 180 - $b, $b, 180 - $b];
        $rQ = [0, 180 - $b, 180, 360 - $b];
        $vQ = [180 - $b, $b, 180 - $b, $b];
        $i = Util::ri(0, 3);
        $k = Util::ri(0, 3);
        array_push(
            $S['marks'],
            self::sectorMark($P, $rP, $i, $vP[$i].'°', 'given'),
            self::u(['v' => $A, 'a' => $B, 'b' => $C], 'α', $b),
            self::u(['v' => $B, 'a' => $C, 'b' => $A], 'β', $b),
            self::u(['v' => $C, 'a' => $A, 'b' => $B], 'γ', $ga),
            self::u(self::sectorMark($Q, $rQ, $k), 'ε', $vQ[$k]),
        );

        return ['S' => $S, 'prompt' => self::fill('Das Dreieck ABC ist gleichschenklig (Bogen um C), g ∥ h. Berechne {0} und {1}.', self::listOf(['α', 'β', 'γ', 'ε'])),
            'steps' => [
                self::stuf($vP[$i], 'Stufenwinkel (g ∥ h): {0}° liegt auch bei {1} an gleicher Lage.', 'A').' '.self::trans($i, 0, $vP[$i], 'α'),
                self::p('Gleichschenkliges Dreieck: Die <b>Basiswinkel</b> sind gleich gross → {0} = <b>{1}°</b>', 'β', $b),
                self::p('<b>Winkelsumme im Dreieck</b>: {0} = 180° − {1}° − {2}° = <b>{3}°</b>', 'γ', $b, $b, $ga),
                self::stuf($b, 'Stufenwinkel (g ∥ h): {0}° liegt auch beim Schnittpunkt von {1} an gleicher Lage.', 'ε').' '.self::trans(1, $k, $b, 'ε'),
            ]];
    }

    private function pIsoExt(): array
    {
        $b = Util::ri(30, 75);
        $ga = 180 - 2 * $b;
        [$S, $A, $B, $C] = self::triScene($b, $b);
        self::isoArc($S, $A, $B, $C);

        if (self::rand() < 0.5) {
            $A2 = self::add($C, self::mul(self::sub($C, $A), 0.4));
            $B2 = self::add($C, self::mul(self::sub($C, $B), 0.4));
            $E = self::add($B, self::mul(self::sub($B, $A), 0.4));
            $S['ext'] = [['a' => $C, 'b' => $A2], ['a' => $C, 'b' => $B2], ['a' => $B, 'b' => $E]];
            array_push($S['fit'], $A2, $B2, $E);
            array_push(
                $S['marks'],
                ['v' => $C, 'a' => $A2, 'b' => $B2, 'text' => $ga.'°', 'kind' => 'given'],
                self::u(['v' => $C, 'a' => $A, 'b' => $B], 'γ', $ga),
                self::u(['v' => $A, 'a' => $B, 'b' => $C], 'α', $b),
                self::u(['v' => $B, 'a' => $E, 'b' => $C], 'ε', 180 - $b),
            );

            return ['S' => $S, 'prompt' => self::fill('Das Dreieck ABC ist gleichschenklig (Bogen um C). Drei Seiten sind verlängert. Berechne {0} und {1}.', self::listOf(['γ', 'α', 'ε'])),
                'steps' => [
                    self::p('{0} und {1}° sind <b>Scheitelwinkel</b>: {0} = <b>{1}°</b>', 'γ', $ga),
                    self::p('Gleichschenkliges Dreieck: α = β (<b>Basiswinkel</b>) → α = (180° − {0}°) : 2 = <b>{1}°</b>', $ga, $b),
                    self::p('ε und β sind <b>Nebenwinkel</b>, β = α: ε = 180° − {0}° = <b>{1}°</b>', $b, 180 - $b),
                ]];
        }

        $A2 = self::add($A, self::mul(self::sub($A, $B), 0.4));
        $B2 = self::add($B, self::mul(self::sub($B, $A), 0.4));
        $S['ext'] = [['a' => $A, 'b' => $A2], ['a' => $B, 'b' => $B2]];
        array_push($S['fit'], $A2, $B2);
        array_push(
            $S['marks'],
            ['v' => $A, 'a' => $A2, 'b' => $C, 'text' => (180 - $b).'°', 'kind' => 'given'],
            self::u(['v' => $A, 'a' => $B, 'b' => $C], 'α', $b),
            self::u(['v' => $B, 'a' => $C, 'b' => $A], 'β', $b),
            self::u(['v' => $C, 'a' => $A, 'b' => $B], 'γ', $ga),
            self::u(['v' => $B, 'a' => $B2, 'b' => $C], 'φ', 180 - $b),
        );

        return ['S' => $S, 'prompt' => self::fill('Das Dreieck ABC ist gleichschenklig (Bogen um C). Die Basis AB ist auf beiden Seiten verlängert. Berechne {0} und {1}.', self::listOf(['α', 'β', 'γ', 'φ'])),
            'steps' => [
                self::p('{0} und {1}° sind <b>Nebenwinkel</b>: {0} = 180° − {1}° = <b>{2}°</b>', 'α', 180 - $b, $b),
                self::p('Gleichschenkliges Dreieck: <b>Basiswinkel</b> β = α = <b>{0}°</b>', $b),
                self::p('<b>Winkelsumme im Dreieck</b>: {0} = 180° − {1}° − {2}° = <b>{3}°</b>', 'γ', $b, $b, $ga),
                self::p('{0} und {1} sind <b>Nebenwinkel</b>: {0} = 180° − {2}° = <b>{3}°</b>', 'φ', 'β', $b, 180 - $b),
            ]];
    }

    private function pEquiLine(): array
    {
        $x = Util::ri(15, 45);
        $A = [0, 0];
        $B = [1, 0];
        $C = self::dirPt($A, 60);
        $D = self::rayInt($A, $x, $B, 120);
        $S = self::newScene();
        $S['rot'] = Util::ri(-20, 20);
        $S['fit'] = [$A, $B, $C];
        $S['segs'] = [['a' => $A, 'b' => $B], ['a' => $B, 'b' => $C], ['a' => $C, 'b' => $A]];
        $S['ticks'] = [['a' => $A, 'b' => $B, 'n' => 1], ['a' => $B, 'b' => $C, 'n' => 1], ['a' => $C, 'b' => $A, 'n' => 1]];
        $S['lines'][] = ['p' => $A, 'q' => $D, 'label' => 's'];
        $cen = self::centroid([$A, $B, $C]);
        $S['labels'] = [['p' => $A, 'text' => 'A', 'away' => $cen], ['p' => $B, 'text' => 'B', 'away' => $cen], ['p' => $C, 'text' => 'C', 'away' => $cen], ['p' => $D, 'text' => 'D', 'away' => $A]];
        array_push(
            $S['marks'],
            ['v' => $A, 'a' => $B, 'b' => $D, 'text' => $x.'°', 'kind' => 'given'],
            self::u(['v' => $A, 'a' => $D, 'b' => $C], 'ε', 60 - $x),
            self::u(['v' => $D, 'a' => $A, 'b' => $B], 'δ', 120 - $x),
            self::u(['v' => $D, 'a' => $C, 'b' => $A], 'φ', 60 + $x),
        );

        return ['S' => $S, 'prompt' => self::fill('Das Dreieck ABC ist gleichseitig. Die Gerade s geht durch A und schneidet BC in D. Berechne {0} und {1}.', self::listOf(['ε', 'δ', 'φ'])),
            'steps' => [
                self::p('Gleichseitiges Dreieck: Alle Winkel messen <b>60°</b>.'),
                self::p('ε = 60° − {0}° = <b>{1}°</b>', $x, 60 - $x),
                self::p('<b>Winkelsumme im Dreieck ABD</b> (Winkel bei B = 60°): δ = 180° − {0}° − 60° = <b>{1}°</b>', $x, 120 - $x),
                self::p('{0} und {1} sind <b>Nebenwinkel</b>: {0} = 180° − {2}° = <b>{3}°</b>', 'φ', 'δ', 120 - $x, 60 + $x),
            ]];
    }

    private function pEquiPar(): array
    {
        $A = [0, 0];
        $B = [1, 0];
        $C = self::dirPt($A, 60);
        $P = self::lerp($A, $C, 0.45);
        $Q = self::lerp($B, $C, 0.45);
        $S = self::newScene();
        $S['fit'] = [$A, $B, $C];
        $S['margin'] = 80;
        $S['rot'] = Util::ri(-15, 15);
        $S['lines'] = [['p' => $A, 'q' => $B, 'chev' => 1, 'label' => 'h'], ['p' => $P, 'q' => $Q, 'chev' => 1, 'label' => 'g'], ['p' => $A, 'q' => $C], ['p' => $B, 'q' => $C]];
        $S['ticks'] = [['a' => $A, 'b' => $B, 'n' => 1, 't' => 0.7], ['a' => $B, 'b' => $C, 'n' => 1, 't' => 0.22], ['a' => $A, 'b' => $C, 'n' => 1, 't' => 0.22]];
        $cen = self::centroid([$A, $B, $C]);
        $S['labels'] = [['p' => $A, 'text' => 'A', 'away' => $cen], ['p' => $B, 'text' => 'B', 'away' => $cen], ['p' => $C, 'text' => 'C', 'away' => $cen]];
        $rP = [0, 60, 180, 240];
        $vP = [60, 120, 60, 120];
        $rQ = [0, 120, 180, 300];
        $vQ = [120, 60, 120, 60];
        $i = Util::ri(0, 3);

        do {
            $j = Util::ri(0, 3);
        } while ($vQ[$j] === $vP[$i] && self::rand() < 0.6);

        array_push($S['marks'], self::u(self::sectorMark($P, $rP, $i), 'ε', $vP[$i]), self::u(self::sectorMark($Q, $rQ, $j), 'φ', $vQ[$j]));
        $tpl = 'Stufenwinkel (g ∥ h): {0}° (Winkel bei {1}) liegt auch beim Schnittpunkt von {2} an gleicher Lage.';

        return ['S' => $S, 'prompt' => self::fill('Das Dreieck ABC ist gleichseitig, g ∥ h. Berechne {0} und {1} – ganz ohne gegebenen Winkel!', ['ε', 'φ']),
            'steps' => [
                self::p('Gleichseitiges Dreieck: Alle Winkel messen <b>60°</b> – auch die Winkel bei A und B.'),
                'ε: '.self::stuf(60, $tpl, 'A', 'ε').' '.self::trans(0, $i, 60, 'ε'),
                'φ: '.self::stuf(60, $tpl, 'B', 'φ').' '.self::trans(1, $j, 60, 'φ'),
            ]];
    }

    private function genProfi(): array
    {
        return match (Util::ri(0, 5)) {
            0 => $this->pIsoParC(),
            1, 2 => $this->pIsoBetween(),
            3 => $this->pIsoExt(),
            4 => $this->pEquiLine(),
            default => $this->pEquiPar(),
        };
    }

    private function genCombined(): array
    {
        if (self::rand() < 0.25) {
            return $this->genProfi();
        }

        return match (Util::ri(0, 7)) {
            0 => $this->kAussen(),
            1 => $this->kTrapez(),
            2 => $this->kParC(),
            3 => $this->kViereck(),
            4, 5 => $this->kParTri(),
            default => $this->kParSchar(),
        };
    }

    /* ---------- Transformation and drawing ---------- */

    private function finalize(array $S): array
    {
        $r = self::rad($S['rot']);
        $c = cos($r);
        $s = sin($r);
        $R = static fn (array $p): array => [$p[0] * $c - $p[1] * $s, $p[0] * $s + $p[1] * $c];

        if ($S['dofit']) {
            $ps = array_map($R, $S['fit']);
            $xs = array_column($ps, 0);
            $ys = array_column($ps, 1);
            $x0 = min($xs);
            $x1 = max($xs);
            $y0 = min($ys);
            $y1 = max($ys);
            $m = $S['margin'];
            $k = min((self::W - 2 * $m) / max($x1 - $x0, 1e-6), (self::H - 2 * $m) / max($y1 - $y0, 1e-6));
            $cx = ($x0 + $x1) / 2;
            $cy = ($y0 + $y1) / 2;
            $T = static function (array $p) use ($R, $k, $cx, $cy): array {
                $q = $R($p);

                return [self::W / 2 + ($q[0] - $cx) * $k, self::H / 2 + ($q[1] - $cy) * $k];
            };
        } else {
            $C = [self::W / 2, self::H / 2];
            $T = static fn (array $p): array => self::add($C, $R(self::sub($p, $C)));
        }

        $F = static function (array $p) use ($T): array {
            $q = $T($p);

            return [$q[0], self::H - $q[1]];
        };
        $cen = $S['fit'] ? $F(self::centroid($S['fit'])) : [self::W / 2, self::H / 2];
        $ab = static fn (array $o): array => array_merge($o, ['a' => $F($o['a']), 'b' => $F($o['b'])]);
        $mk = static fn (array $m): array => array_merge($m, ['v' => $F($m['v']), 'a' => $F($m['a']), 'b' => $F($m['b'])]);

        return [
            'segs' => array_map($ab, $S['segs']),
            'ext' => array_map($ab, $S['ext']),
            'solSegs' => array_map($ab, $S['solSegs']),
            'ticks' => array_map($ab, $S['ticks']),
            'chev' => array_map($ab, $S['chev']),
            'lines' => array_map(static fn (array $l): array => array_merge($l, ['p' => $F($l['p']), 'q' => $F($l['q'])]), $S['lines']),
            'marks' => array_map($mk, $S['marks']),
            'solMarks' => array_map($mk, $S['solMarks']),
            'labels' => array_map(static fn (array $l): array => array_merge($l, ['p' => $F($l['p']), 'away' => isset($l['away']) ? $F($l['away']) : $cen]), $S['labels']),
            'arcs' => array_map(static fn (array $o): array => ['c' => $F($o['c']), 'a' => $F($o['a']), 'b' => $F($o['b'])], $S['arcs']),
            'texts' => $S['texts'],
        ];
    }

    private static function f(array $p): string
    {
        return round($p[0], 1).','.round($p[1], 1);
    }

    private static function line(array $a, array $b, string $attr): string
    {
        return '<line x1="'.round($a[0], 1).'" y1="'.round($a[1], 1).'" x2="'.round($b[0], 1).'" y2="'.round($b[1], 1).'" '.$attr.'/>';
    }

    private static function clipT(array $p, array $u, float $ins): array|null
    {
        $t0 = -1e9;
        $t1 = 1e9;
        $lo = [$ins, $ins];
        $hi = [self::W - $ins, self::H - $ins];

        for ($k = 0; $k < 2; ++$k) {
            if (abs($u[$k]) < 1e-9) {
                if ($p[$k] < $lo[$k] || $p[$k] > $hi[$k]) {
                    return null;
                }

                continue;
            }

            $a = ($lo[$k] - $p[$k]) / $u[$k];
            $b = ($hi[$k] - $p[$k]) / $u[$k];

            if ($a > $b) {
                [$a, $b] = [$b, $a];
            }

            $t0 = max($t0, $a);
            $t1 = min($t1, $b);
        }

        return $t0 < $t1 ? [$t0, $t1] : null;
    }

    private static function chevron(array $c, array $u, int $n, string $col): string
    {
        $m = $n + 1;
        $w = self::rad(75);
        $v = [$u[0] * cos($w) - $u[1] * sin($w), $u[0] * sin($w) + $u[1] * cos($w)];
        $s = '';

        for ($i = 0; $i < $m; ++$i) {
            $cc = self::add($c, self::mul($u, ($i - ($m - 1) / 2) * 6));
            $s .= self::line(self::add($cc, self::mul($v, 8)), self::add($cc, self::mul($v, -8)), 'stroke="'.$col.'" stroke-width="2.2" stroke-linecap="round"');
        }

        return $s;
    }

    private static function text(array $p, string $text, int $size, int $weight, string $fill, string $extra = ''): string
    {
        return '<text x="'.round($p[0], 1).'" y="'.round($p[1], 1).'" text-anchor="middle" dominant-baseline="central" font-size="'.$size.'" font-weight="'.$weight.'" fill="'.$fill.'"'.$extra.'>'.htmlspecialchars($text, ENT_QUOTES | ENT_XML1).'</text>';
    }

    private static function markSvg(array $m): array
    {
        $out = ['fill' => '', 'stroke' => '', 'label' => ''];
        $ua = self::unit(self::sub($m['a'], $m['v']));
        $ub = self::unit(self::sub($m['b'], $m['v']));

        if ('right' === $m['kind']) {
            $q = 16;
            $p1 = self::add($m['v'], self::mul($ua, $q));
            $p3 = self::add($m['v'], self::mul($ub, $q));
            $p2 = self::add($p1, self::mul($ub, $q));
            $dot = self::add($m['v'], self::mul(self::add($ua, $ub), $q / 2));
            $out['stroke'] = '<path d="M'.self::f($p1).' L'.self::f($p2).' L'.self::f($p3).'" fill="none" stroke="#475569" stroke-width="1.6"/><circle cx="'.round($dot[0], 1).'" cy="'.round($dot[1], 1).'" r="2" fill="#475569"/>';

            return $out;
        }

        $a1 = self::deg(atan2($ua[1], $ua[0]));
        $a2 = self::deg(atan2($ub[1], $ub[0]));
        $st = $a1;
        $sp = self::nrm($a2 - $a1);

        if ($sp > 180) {
            $st = $a2;
            $sp = 360 - $sp;
        }

        $col = self::COL[$m['kind']] ?? '#333';
        $r = 'neutral' === $m['kind'] ? 20 : ($sp < 28 ? 50 : ($sp < 55 ? 38 : 30));
        $P = static fn (float $a): array => self::add($m['v'], [$r * cos(self::rad($a)), $r * sin(self::rad($a))]);
        $p1 = $P($st);
        $p2 = $P($st + $sp);
        $dash = 'sol' === $m['kind'] ? ' stroke-dasharray="5 4"' : '';
        $op = 'unknown' === $m['kind'] ? 0.22 : ('sol' === $m['kind'] ? 0.1 : 0.13);
        $out['fill'] = '<path d="M'.self::f($m['v']).' L'.self::f($p1).' A'.$r.' '.$r.' 0 0 1 '.self::f($p2).' Z" fill="'.$col.'" fill-opacity="'.$op.'"/>';
        $out['stroke'] = '<path d="M'.self::f($p1).' A'.$r.' '.$r.' 0 0 1 '.self::f($p2).'" fill="none" stroke="'.$col.'" stroke-width="'.('unknown' === $m['kind'] ? 2.6 : 1.9).'"'.$dash.'/>';

        if ('' !== ($m['text'] ?? '')) {
            $lr = $r + ($sp < 28 ? 20 : 15) + (mb_strlen($m['text']) > 3 ? 4 : 0);
            $mid = self::rad($st + $sp / 2);
            $big = 'unknown' === $m['kind'];
            $out['label'] = self::text(self::add($m['v'], [$lr * cos($mid), $lr * sin($mid)]), $m['text'], $big ? 20 : 15, $big ? 800 : 700, $col, ' stroke="#fff" stroke-width="4" paint-order="stroke"');
        }

        return $out;
    }

    private function render(array $sc, bool $showSol): string
    {
        $marks = [];

        foreach ($sc['marks'] as $m) {
            if ($showSol && 'unknown' === $m['kind'] && isset($m['ans'])) {
                $m = array_merge($m, ['text' => $m['text'].'='.$m['ans'].'°', 'kind' => 'solved']);
            }

            $marks[] = self::markSvg($m);
        }

        if ($showSol) {
            foreach ($sc['solMarks'] as $m) {
                $marks[] = self::markSvg($m);
            }
        }

        $s = implode('', array_column($marks, 'fill'));
        $lineLabels = [];

        foreach ($sc['lines'] as $l) {
            $u = self::unit(self::sub($l['q'], $l['p']));
            $tt = self::clipT($l['p'], $u, 4);

            if (!$tt) {
                continue;
            }

            $s .= self::line(self::add($l['p'], self::mul($u, $tt[0])), self::add($l['p'], self::mul($u, $tt[1])), 'stroke="#334155" stroke-width="2"');
            $ti = self::clipT($l['p'], $u, 26);

            if ($ti && !empty($l['chev'])) {
                $s .= self::chevron(self::add($l['p'], self::mul($u, $ti[0] + 30)), $u, $l['chev'], '#334155');
            }

            if ($ti && !empty($l['label'])) {
                $pos = self::add(self::add($l['p'], self::mul($u, $ti[1])), self::mul([$u[1], -$u[0]], 14));
                $lineLabels[] = self::text($pos, $l['label'], 19, 700, '#334155', ' font-style="italic"');
            }
        }

        foreach ($sc['ext'] as $e) {
            $s .= self::line($e['a'], $e['b'], 'stroke="#64748b" stroke-width="2" stroke-dasharray="7 5"');
        }

        foreach ($sc['segs'] as $e) {
            $s .= self::line($e['a'], $e['b'], 'stroke="#1e293b" stroke-width="2.4" stroke-linecap="round"');
        }

        if ($showSol) {
            foreach ($sc['solSegs'] as $e) {
                $s .= self::line($e['a'], $e['b'], 'stroke="#16a34a" stroke-width="2" stroke-dasharray="6 5"');
            }
        }

        foreach ($sc['chev'] as $c) {
            $s .= self::chevron(self::lerp($c['a'], $c['b'], 0.5), self::unit(self::sub($c['b'], $c['a'])), $c['n'], '#1e293b');
        }

        foreach ($sc['ticks'] as $t) {
            $u = self::unit(self::sub($t['b'], $t['a']));
            $perp = [-$u[1], $u[0]];
            $mid = self::lerp($t['a'], $t['b'], $t['t'] ?? 0.5);

            for ($i = 0; $i < $t['n']; ++$i) {
                $c = self::add($mid, self::mul($u, ($i - ($t['n'] - 1) / 2) * 5));
                $s .= self::line(self::add($c, self::mul($perp, 7)), self::add($c, self::mul($perp, -7)), 'stroke="#1e293b" stroke-width="2"');
            }
        }

        foreach ($sc['arcs'] as $o) {
            $r = self::len(self::sub($o['a'], $o['c']));
            $t1 = self::deg(atan2($o['a'][1] - $o['c'][1], $o['a'][0] - $o['c'][0]));
            $t2 = self::deg(atan2($o['b'][1] - $o['c'][1], $o['b'][0] - $o['c'][0]));
            $st = $t1;
            $sp = self::nrm($t2 - $t1);

            if ($sp > 180) {
                $st = $t2;
                $sp = 360 - $sp;
            }

            $Pa = static fn (float $a): array => self::add($o['c'], [$r * cos(self::rad($a)), $r * sin(self::rad($a))]);
            $s .= '<path d="M'.self::f($Pa($st - 7)).' A'.round($r, 1).' '.round($r, 1).' 0 0 1 '.self::f($Pa($st + $sp + 7)).'" fill="none" stroke="#64748b" stroke-width="1.6"/>';
        }

        $s .= implode('', array_column($marks, 'stroke'));
        $s .= implode('', array_column($marks, 'label'));

        foreach ($sc['labels'] as $l) {
            $s .= self::text(self::add($l['p'], self::mul(self::unit(self::sub($l['p'], $l['away'])), 20)), $l['text'], 18, 800, '#0f172a');
        }

        $s .= implode('', $lineLabels);

        foreach ($sc['texts'] as $t) {
            $s .= self::text([$t['x'], $t['y']], $t['text'], $t['size'], 800, $t['col']);
        }

        return '<svg class="st-angsvg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.self::W.' '.self::H.'" font-family="system-ui, sans-serif" role="img">'.$s.'</svg>';
    }
}
