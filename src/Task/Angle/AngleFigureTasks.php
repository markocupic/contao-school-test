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
 * Angle tasks with parallels cut by two intersecting lines and figures with diagonals and
 * heights (trapezoid, regular pentagon, parallelogram, isosceles triangle). Uses the
 * scene and text helpers of AngleGenerator.
 */
trait AngleFigureTasks
{
    /* ---------- 8) Parallels cut by two intersecting lines ---------- */

    private function genTransversal(): array
    {
        for ($try = 0; ; ++$try) {
            $m = self::rand() < 0.5 ? 2 : 3;
            $ys = 2 === $m ? [100, 320] : [70, 195, 340];
            $names = 2 === $m ? ['h', 'g'] : ['k', 'h', 'g'];
            $yX = 2 === $m ? 245 : 285;
            // The line below S forms the triangle PQS with s and t
            $tl = 2 === $m ? 0 : 1;
            $ds = self::rndAngle(45, 135, 10);
            $be = Util::ri(30, 75);
            $dt = $ds + (self::rand() < 0.5 ? $be : -$be);

            if ($dt < 35 || $dt > 145 || abs($dt - 90) < 10) {
                continue;
            }

            $X = [self::W / 2 + Util::ri(-40, 40), $yX];
            $at = static fn (float $d, float $y): array => [$X[0] + ($y - $yX) / tan(self::rad($d)), $y];
            $Ps = array_map(static fn (int $y): array => $at($ds, $y), $ys);
            $Pt = array_map(static fn (int $y): array => $at($dt, $y), $ys);
            $ok = true;

            foreach ($ys as $k => $y) {
                // A roomy triangle PQS, the other crossings apart and inside the sketch
                $ok = $ok && min($Ps[$k][0], $Pt[$k][0]) > 60 && max($Ps[$k][0], $Pt[$k][0]) < self::W - 60 && abs($Ps[$k][0] - $Pt[$k][0]) > ($k === $tl ? 120 : 60);
            }

            if ($ok || $try > 300) {
                break;
            }
        }

        $S = self::newScene();
        $S['dofit'] = false;
        $S['rot'] = Util::ri(-15, 15);

        foreach ($ys as $k => $y) {
            $S['lines'][] = ['p' => [0, $y], 'q' => [100, $y], 'chev' => 1, 'label' => $names[$k]];
        }

        $S['lines'][] = ['p' => $X, 'q' => self::dirPt($X, $ds), 'label' => 's'];
        $S['lines'][] = ['p' => $X, 'q' => self::dirPt($X, $dt), 'label' => 't'];
        $rs = [0, $ds, 180, 180 + $ds];
        $vs = [$ds, 180 - $ds, $ds, 180 - $ds];
        $rt = [0, $dt, 180, 180 + $dt];
        $vt = [$dt, 180 - $dt, $dt, 180 - $dt];
        $P = $Ps[$tl];
        $Q = $Pt[$tl];
        // Triangle angles at P and Q: between the ray up to S and the ray to the other point
        $triS = $Q[0] > $P[0] ? 0 : 1;
        $triT = $P[0] > $Q[0] ? 0 : 1;
        $rx = [self::nrm($ds), self::nrm($dt), self::nrm($ds + 180), self::nrm($dt + 180)];
        sort($rx);
        $secX = static fn (int $k): int => (int) round(self::nrm($rx[($k + 1) % 4] - $rx[$k]));
        $triX = 0;

        for ($k = 0; $k < 4; ++$k) {
            $pair = [$rx[$k], $rx[($k + 1) % 4]];

            if (\in_array(self::nrm($ds + 180), $pair, true) && \in_array(self::nrm($dt + 180), $pair, true)) {
                $triX = $k;
            }
        }

        $phi = $vs[$triS];
        $psi = $secX($triX);
        $chi = 180 - $phi - $psi;

        // Given: α at s and a parallel, β at S
        $ka = Util::ri(0, $m - 1);
        $i = Util::ri(0, 3);
        $iB = Util::ri(0, 3);
        $al = $vs[$i];
        $bb = $secX($iB);
        // Unknown: γ at Q, δ at t and another parallel, ε at s and another parallel
        $j = Util::ri(0, 3);
        $kb = Util::pick(array_values(array_diff(range(0, $m - 1), [$tl])));
        $d = Util::ri(0, 3);
        $ke = Util::pick(array_values(array_diff(range(0, $m - 1), [$ka])));
        $e = Util::ri(0, 3);
        array_push(
            $S['marks'],
            self::sectorMark($Ps[$ka], $rs, $i, $al.'°', 'given'),
            self::sectorMark($X, $rx, $iB, $bb.'°', 'given'),
            self::u(self::sectorMark($Q, $rt, $j), 'γ', $vt[$j]),
            self::u(self::sectorMark($Pt[$kb], $rt, $d), 'δ', $vt[$d]),
            self::u(self::sectorMark($Ps[$ke], $rs, $e), 'ε', $vs[$e]),
        );
        // The triangle angles in the solution, unless a mark already shows them
        if ($ka !== $tl || $i !== $triS) {
            $S['solMarks'][] = self::sectorMark($P, $rs, $triS, 'φ='.$phi.'°', 'sol');
        }

        if ($iB !== $triX) {
            $S['solMarks'][] = self::sectorMark($X, $rx, $triX, 'ψ='.$psi.'°', 'sol');
        }

        if ($j !== $triT) {
            $S['solMarks'][] = self::sectorMark($Q, $rt, $triT, 'χ='.$chi.'°', 'sol');
        }
        // Point names: P and Q below their line, S in a free sector
        $free = array_values(array_diff([0, 1, 2, 3], [$iB, $triX]))[0];
        $mid = self::rad($rx[$free] + $secX($free) / 2);
        $S['labels'] = [
            ['p' => $P, 'text' => 'P', 'away' => self::add($P, [0, 10])],
            ['p' => $Q, 'text' => 'Q', 'away' => self::add($Q, [0, 10])],
            ['p' => $X, 'text' => 'S', 'away' => self::sub($X, [cos($mid), sin($mid)])],
        ];
        $tpl = 'Stufenwinkel an den Parallelen: {0}° liegt auch beim Schnittpunkt von {1} an gleicher Lage.';
        $steps = [
            $ka === $tl
                ? 'φ: '.self::trans($i, $triS, $al, 'φ')
                : 'φ: '.self::p('Stufenwinkel an den Parallelen: {0}° liegt auch bei {1} an gleicher Lage.', $al, 'P').' '.self::trans($i, $triS, $al, 'φ'),
            'ψ: '.self::trans($iB, $triX, $bb, 'ψ'),
            self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'PQS', 'χ', $phi, $psi, $chi),
            'γ: '.self::trans($triT, $j, $chi, 'γ'),
            'δ: '.self::stuf($chi, $tpl, 'δ').' '.self::trans($triT, $d, $chi, 'δ'),
            'ε: '.self::stuf($phi, $tpl, 'ε').' '.self::trans($triS, $e, $phi, 'ε'),
        ];
        $prompt = 2 === $m
            ? 'Die Geraden g und h sind parallel. Sie werden von s und t geschnitten; s und t schneiden sich in S. Berechne {0} und {1}.'
            : 'Die Geraden g, h und k sind parallel. Sie werden von s und t geschnitten; s und t schneiden sich in S. Berechne {0} und {1}.';

        return ['S' => $S, 'prompt' => self::fill($prompt, self::listOf(['γ', 'δ', 'ε'])), 'steps' => $steps];
    }

    /* ---------- 9) Figures with diagonals and heights ---------- */

    private function genFigure(): array
    {
        return match (Util::ri(0, 3)) {
            0 => $this->fTrapez(),
            1 => $this->fPentagon(),
            2 => $this->fParallelogram(),
            default => $this->fIsoHeight(),
        };
    }

    /**
     * Trapezoid ABCD (AB ∥ DC) with the diagonal AC.
     */
    private function fTrapez(): array
    {
        do {
            $a1 = Util::ri(15, 40);
            $a2 = Util::ri(20, 45);
            $b = Util::ri(55, min(115, 150 - $a2));
            $h = 0.7;
            $A = [0, 0];
            $C = [$h / tan(self::rad($a2)), $h];
            $B = [$C[0] + $h / tan(self::rad($b)), 0];
            $D = [$h / tan(self::rad($a1 + $a2)), $h];
        } while ($C[0] - $D[0] < 0.35 * $B[0] || $B[0] < 0.8);

        $pts = [$A, $B, $C, $D];
        $S = self::polyScene($pts);
        $S['rot'] = Util::ri(-12, 12);
        $S['segs'][] = ['a' => $A, 'b' => $C];
        $S['chev'] = [['a' => $A, 'b' => $B, 'n' => 1], ['a' => $D, 'b' => $C, 'n' => 1]];
        $al = 180 - $a1 - $a2;
        $ga = 180 - $a2 - $b;
        array_push(
            $S['marks'],
            ['v' => $A, 'a' => $D, 'b' => $C, 'text' => $a1.'°', 'kind' => 'given'],
            ['v' => $A, 'a' => $C, 'b' => $B, 'text' => $a2.'°', 'kind' => 'given'],
            ['v' => $B, 'a' => $C, 'b' => $A, 'text' => $b.'°', 'kind' => 'given'],
            self::u(['v' => $D, 'a' => $A, 'b' => $C], 'α', $al),
            self::u(['v' => $C, 'a' => $D, 'b' => $A], 'β', $a2),
            self::u(['v' => $C, 'a' => $A, 'b' => $B], 'γ', $ga),
        );

        return ['S' => $S, 'prompt' => self::fill('Im Trapez ABCD ist AB ∥ DC. Berechne {0} und {1}.', self::listOf(['α', 'β', 'γ'])),
            'steps' => [
                self::p('{0} und der Winkel {1}° bei A sind <b>Wechselwinkel</b> (AB ∥ DC): {0} = <b>{1}°</b>', 'β', $a2),
                self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'ACD', 'α', $a1, $a2, $al),
                self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'ABC', 'γ', $a2, $b, $ga),
            ]];
    }

    /**
     * Regular pentagon ABCDE with two diagonals: interior angle 108°, base angles 36°.
     */
    private function fPentagon(): array
    {
        $pts = self::regPoly(5, 90);
        $S = self::polyScene($pts);
        $S['rot'] = Util::ri(-30, 30);
        $L = self::LT;
        $diags = \array_slice(Util::shuffle([0, 1, 2, 3, 4]), 0, 2);

        foreach ($diags as $i) {
            $S['segs'][] = ['a' => $pts[$i], 'b' => $pts[($i + 2) % 5]];
        }

        for ($k = 0; $k < 5; ++$k) {
            $S['ticks'][] = ['a' => $pts[$k], 'b' => $pts[($k + 1) % 5], 'n' => 1];
        }

        // Rays at a vertex as offsets: 1 and 4 are the sides, 2 and 3 the diagonals
        $offsets = static function (int $v) use ($diags): array {
            $o = [1, 4];

            if (\in_array($v, $diags, true)) {
                $o[] = 2;
            }

            if (\in_array(($v + 3) % 5, $diags, true)) {
                $o[] = 3;
            }

            sort($o);

            return $o;
        };
        $tri = static fn (int $v, int $dir): string => $L[$v].$L[($v + $dir + 5) % 5].$L[($v + 2 * $dir + 10) % 5];
        $free = [];
        $subs = [];

        for ($v = 0; $v < 5; ++$v) {
            $o = $offsets($v);

            if (2 === \count($o)) {
                $free[] = $v;

                continue;
            }

            for ($k = 0; $k + 1 < \count($o); ++$k) {
                $subs[] = [$v, $o[$k], $o[$k + 1]];
            }
        }

        $av = Util::pick($free);
        $first = Util::pick($subs);
        $rest = array_values(array_filter($subs, static fn (array $s): bool => $s[0] !== $first[0]));
        $other = array_values(array_filter($rest, static fn (array $s): bool => $s[2] - $s[1] !== $first[2] - $first[1]));
        $second = Util::pick($other ?: $rest);
        $S['marks'][] = self::u(self::angMark($pts, $av), 'α', 108);
        $steps = [
            self::p('Winkelsumme im Fünfeck: (5 − 2) · 180° = 540°. Regelmässig → jeder Innenwinkel misst 540° : 5 = <b>108°</b>.'),
            self::p('{0} ist ein Innenwinkel: {0} = <b>108°</b>', 'α'),
        ];
        $example = null;
        $explain = [];

        foreach ([[$first, 'β'], [$second, 'γ']] as [$sub, $lab]) {
            [$v, $o1, $o2] = $sub;
            $val = 36 * ($o2 - $o1);
            $S['marks'][] = self::u(['v' => $pts[$v], 'a' => $pts[($v + $o1) % 5], 'b' => $pts[($v + $o2) % 5]], $lab, $val);
            [$t, $line] = match ("$o1$o2") {
                '12' => [$tri($v, 1), self::p('{0} ist ein solcher Basiswinkel (Dreieck {1}): {0} = <b>36°</b>', $lab, $tri($v, 1))],
                '34' => [$tri($v, -1), self::p('{0} ist ein solcher Basiswinkel (Dreieck {1}): {0} = <b>36°</b>', $lab, $tri($v, -1))],
                '13' => [$tri($v, -1), self::p('{0} ist der Rest des Innenwinkels bei {1} (Basiswinkel im Dreieck {2}): {0} = 108° − 36° = <b>72°</b>', $lab, $L[$v], $tri($v, -1))],
                '24' => [$tri($v, 1), self::p('{0} ist der Rest des Innenwinkels bei {1} (Basiswinkel im Dreieck {2}): {0} = 108° − 36° = <b>72°</b>', $lab, $L[$v], $tri($v, 1))],
                default => [$tri($v, 1), self::p('Bei {1} liegen zwei Basiswinkel: {0} = 108° − 36° − 36° = <b>36°</b>', $lab, $L[$v])],
            };
            $example ??= $t;
            $explain[] = $line;
        }

        $steps[] = self::p('Eine Diagonale schneidet mit zwei Seiten ein <b>gleichschenkliges Dreieck</b> ab (z. B. {0}). Seine Basiswinkel messen (180° − 108°) : 2 = <b>36°</b>.', $example);
        array_push($steps, ...$explain);
        $labels = ['α', 'β', 'γ'];

        // Crossing diagonals: the angle δ at their intersection S
        [$i1, $i2] = $diags;
        $e1 = [$i1, ($i1 + 2) % 5];
        $e2 = [$i2, ($i2 + 2) % 5];

        if (!array_intersect($e1, $e2) && self::rand() < 0.6) {
            $X = self::lineInt($pts[$e1[0]], $pts[$e1[1]], $pts[$e2[0]], $pts[$e2[1]]);
            $side = null;

            foreach ($e1 as $p) {
                foreach ($e2 as $q) {
                    $op = ($q - $p + 5) % 5;
                    $oq = ($p - $q + 5) % 5;
                    $dp = (array_sum($e1) - $p - $p + 10) % 5;
                    $dq = (array_sum($e2) - $q - $q + 10) % 5;

                    if (\in_array($op, [1, 4], true) && 1 === abs($op - $dp) && 1 === abs($oq - $dq)) {
                        $side = [$p, $q];
                    }
                }
            }

            if ($side) {
                [$p, $q] = $side;
                $dirs = [];

                foreach ([$e1[0], $e1[1], $e2[0], $e2[1]] as $k) {
                    $dirs[$k] = self::nrm(self::deg(atan2($pts[$k][1] - $X[1], $pts[$k][0] - $X[0])));
                }

                $rx = array_values($dirs);
                sort($rx);
                $triX = 0;

                for ($k = 0; $k < 4; ++$k) {
                    $pair = [$rx[$k], $rx[($k + 1) % 4]];

                    if (\in_array($dirs[$p], $pair, true) && \in_array($dirs[$q], $pair, true)) {
                        $triX = $k;
                    }
                }

                $k = Util::ri(0, 3);
                $S['marks'][] = self::u(self::sectorMark($X, $rx, $k), 'δ', 0 === ($k - $triX) % 2 ? 108 : 72);
                $fs = ($k + 1) % 4;
                $mid = self::rad($rx[$fs] + self::nrm($rx[($fs + 1) % 4] - $rx[$fs]) / 2);
                $S['labels'][] = ['p' => $X, 'text' => 'S', 'away' => self::sub($X, [cos($mid), sin($mid)])];
                $tname = $L[$p].$L[$q].'S';
                $steps[] = $k === $triX
                    ? self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', $tname, 'δ', 36, 36, 108)
                    : self::p('Im Dreieck {0} misst der Winkel bei {1}: 180° − {2}° − {3}° = {4}°.', $tname, 'S', 36, 36, 108).' '.self::rel($triX, $k, 108, 'δ')['t'];
                $labels[] = 'δ';
            }
        }

        return ['S' => $S, 'prompt' => self::fill('Das Fünfeck ABCDE ist regelmässig. Berechne {0} und {1}.', self::listOf($labels)), 'steps' => $steps];
    }

    /**
     * Parallelogram ABCD with the diagonal AC and the height DF on AB.
     */
    private function fParallelogram(): array
    {
        do {
            $p = Util::ri(18, 40);
            $q = Util::ri(22, 50);
            $gb = 180 - $p - $q;
            $A = [0, 0];
            $B = [1, 0];
            $C = self::rayInt($A, $p, $B, 180 - $gb);
            $D = self::sub($C, $B);
        } while ($p + $q > 76 || $D[0] < 0.18 || $D[0] > 0.75 || $D[1] < 0.35);

        $F = [$D[0], 0];
        $X = [$D[0], $D[0] * tan(self::rad($p))];
        $pts = [$A, $B, $C, $D];
        $S = self::polyScene($pts);
        $S['rot'] = Util::ri(-15, 15);
        $S['segs'][] = ['a' => $A, 'b' => $C];
        $S['segs'][] = ['a' => $D, 'b' => $F];
        $S['chev'] = [['a' => $A, 'b' => $B, 'n' => 1], ['a' => $D, 'b' => $C, 'n' => 1], ['a' => $A, 'b' => $D, 'n' => 2], ['a' => $B, 'b' => $C, 'n' => 2]];
        $S['labels'][] = ['p' => $F, 'text' => 'F', 'away' => self::add($F, [0, 1])];
        // Rays at S: to C, D, A, F; sector 2 (between SA and SF) lies in the triangle AFS
        $rx = [$p, 90, 180 + $p, 270];
        $k = Util::ri(0, 3);
        $al = 90 - $p - $q;
        $tri = 90 - $p;
        // Name S in a sector without β (left of the height or below the diagonal)
        $fs = 1 === $k ? 3 : 1;
        $mid = self::rad(1 === $fs ? (90 + 180 + $p) / 2 : (270 + 360 + $p) / 2);
        $S['labels'][] = ['p' => $X, 'text' => 'S', 'away' => self::sub($X, [cos($mid), sin($mid)])];
        array_push(
            $S['marks'],
            ['v' => $A, 'a' => $B, 'b' => $C, 'text' => $p.'°', 'kind' => 'given'],
            ['v' => $C, 'a' => $A, 'b' => $B, 'text' => $q.'°', 'kind' => 'given'],
            ['v' => $F, 'a' => $D, 'b' => $B, 'text' => '', 'kind' => 'right'],
            self::u(['v' => $D, 'a' => $A, 'b' => $F], 'α', $al),
            self::u(self::sectorMark($X, $rx, $k), 'β', 0 === $k % 2 ? $tri : 180 - $tri),
            self::u(['v' => $B, 'a' => $C, 'b' => $A], 'γ', $gb),
        );

        return ['S' => $S, 'prompt' => self::fill('ABCD ist ein Parallelogramm. DF ist eine Höhe, S der Schnittpunkt mit der Diagonale AC. Berechne {0} und {1}.', self::listOf(['α', 'β', 'γ'])),
            'steps' => [
                self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'ABC', 'γ', $p, $q, $gb),
                self::p('Der Winkel CAD und {0}° bei C sind <b>Wechselwinkel</b> (AD ∥ BC) → der Winkel bei A misst {1}° + {0}° = {2}°.', $q, $p, $p + $q),
                self::p('<b>Winkelsumme im Dreieck {0}</b> (rechter Winkel bei {1}): {2} = 180° − 90° − {3}° = <b>{4}°</b>', 'AFD', 'F', 'α', $p + $q, $al),
                2 === $k
                    ? self::p('<b>Winkelsumme im Dreieck {0}</b> (rechter Winkel bei {1}): {2} = 180° − 90° − {3}° = <b>{4}°</b>', 'AFS', 'F', 'β', $p, $tri)
                    : self::p('Im Dreieck {0} misst der Winkel bei {1}: 180° − {2}° − {3}° = {4}°.', 'AFS', 'S', 90, $p, $tri).' '.self::rel(2, $k, $tri, 'β')['t'],
            ]];
    }

    /**
     * Isosceles triangle ABC (AC = BC) with the height BH on AC.
     */
    private function fIsoHeight(): array
    {
        $givenTop = self::rand() < 0.4;
        $x = $givenTop ? Util::ri(15, 38) : Util::ri(12, 38);
        $ga = 2 * $x;
        $al = 90 - $x;
        $be = 90 - 2 * $x;
        [$S, $A, $B, $C] = self::triScene($al, $al);
        $S['rot'] = Util::ri(-8, 8);
        $u = self::unit(self::sub($C, $A));
        $H = self::add($A, self::mul($u, ($B[0] - $A[0]) * $u[0] + ($B[1] - $A[1]) * $u[1]));
        $S['segs'][] = ['a' => $B, 'b' => $H];
        $S['ticks'][] = ['a' => $A, 'b' => $C, 'n' => 1, 't' => 0.7];
        $S['ticks'][] = ['a' => $B, 'b' => $C, 'n' => 1];
        $S['labels'][] = ['p' => $H, 'text' => 'H', 'away' => self::lerp($A, $B, 0.7)];
        $S['marks'][] = ['v' => $H, 'a' => $C, 'b' => $B, 'text' => '', 'kind' => 'right'];

        if ($givenTop) {
            array_push(
                $S['marks'],
                ['v' => $C, 'a' => $A, 'b' => $B, 'text' => $ga.'°', 'kind' => 'given'],
                self::u(['v' => $A, 'a' => $B, 'b' => $C], 'α', $al),
                self::u(['v' => $B, 'a' => $A, 'b' => $H], 'δ', $x),
                self::u(['v' => $B, 'a' => $H, 'b' => $C], 'β', $be),
            );

            return ['S' => $S, 'prompt' => self::fill('Das Dreieck ABC ist gleichschenklig (AC = BC). BH ist eine Höhe. Berechne {0} und {1}.', self::listOf(['α', 'δ', 'β'])),
                'steps' => [
                    self::p('Gleichschenkliges Dreieck: Die <b>Basiswinkel</b> bei A und B sind gleich gross → {0} = (180° − {1}°) : 2 = <b>{2}°</b>', 'α', $ga, $al),
                    self::p('<b>Winkelsumme im Dreieck {0}</b> (rechter Winkel bei {1}): {2} = 180° − 90° − {3}° = <b>{4}°</b>', 'ABH', 'H', 'δ', $al, $x),
                    self::p('Der Winkel bei B misst ebenfalls {0}° (<b>Basiswinkel</b>): {1} = {0}° − {2}° = <b>{3}°</b>', $al, 'β', $x, $be),
                ]];
        }

        array_push(
            $S['marks'],
            ['v' => $B, 'a' => $A, 'b' => $H, 'text' => $x.'°', 'kind' => 'given'],
            self::u(['v' => $A, 'a' => $B, 'b' => $C], 'α', $al),
            self::u(['v' => $B, 'a' => $H, 'b' => $C], 'β', $be),
            self::u(['v' => $C, 'a' => $A, 'b' => $B], 'γ', $ga),
        );

        return ['S' => $S, 'prompt' => self::fill('Das Dreieck ABC ist gleichschenklig (AC = BC). BH ist eine Höhe. Berechne {0} und {1}.', self::listOf(['α', 'β', 'γ'])),
            'steps' => [
                self::p('<b>Winkelsumme im Dreieck {0}</b> (rechter Winkel bei {1}): {2} = 180° − 90° − {3}° = <b>{4}°</b>', 'ABH', 'H', 'α', $x, $al),
                self::p('Der Winkel bei B misst ebenfalls {0}° (<b>Basiswinkel</b>): {1} = {0}° − {2}° = <b>{3}°</b>', $al, 'β', $x, $be),
                self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'ABC', 'γ', $al, $al, $ga),
            ]];
    }

    private static function lineInt(array $a, array $b, array $c, array $d): array
    {
        $r = self::sub($b, $a);
        $s = self::sub($d, $c);
        $den = $r[0] * $s[1] - $r[1] * $s[0];
        $t = (($c[0] - $a[0]) * $s[1] - ($c[1] - $a[1]) * $s[0]) / $den;

        return self::add($a, self::mul($r, $t));
    }
}
