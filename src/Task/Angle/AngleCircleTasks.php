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
 * Angles in a circle (inscribed and central angles): crossing chords, cyclic quadrilateral
 * with a diagonal, triangles over a diameter and a central angle with inscribed angles.
 * Several unknown angles per task. The arcs between the points are even numbers of degrees,
 * so every angle is a whole number and the sketch is true to scale.
 */
trait AngleCircleTasks
{
    /**
     * Variants not used yet in this run, so successive tasks show different figures.
     */
    private array $circleQueue = [];

    private function genCircle(): array
    {
        if (!$this->circleQueue) {
            $this->circleQueue = Util::shuffle(['chords', 'quad', 'diameter', 'centre']);
        }

        $variant = array_shift($this->circleQueue);

        // New figure until the angle marks and their labels do not overlap
        for ($try = 0; $try < 60; ++$try) {
            $t = match ($variant) {
                'chords' => $this->cChords(),
                'quad' => $this->cQuad(),
                'diameter' => $this->cDiameter(),
                default => $this->cCentre(),
            };

            if (!self::crowded($t['S']['marks'])) {
                break;
            }
        }

        return $t;
    }

    /**
     * Whether two angle labels overlap, or a label lies on the arc of another angle. Same radii
     * as AngleGenerator::markSvg(), in pixels of the circle sketch (unit radius = $k pixels).
     */
    public static function crowded(array $marks): bool
    {
        $k = (self::H - 2 * 34) / 2.32;
        $info = [];

        foreach ($marks as $m) {
            if ('right' === $m['kind']) {
                continue;
            }

            $ua = self::unit(self::sub($m['a'], $m['v']));
            $ub = self::unit(self::sub($m['b'], $m['v']));
            $sp = self::deg(acos(max(-1, min(1, $ua[0] * $ub[0] + $ua[1] * $ub[1]))));
            $r = 'neutral' === $m['kind'] ? 20 : ($sp < 28 ? 50 : ($sp < 55 ? 38 : 30));
            $text = (string) ($m['text'] ?? '');
            $lr = $r + ($sp < 28 ? 20 : 15) + (mb_strlen($text) > 3 ? 4 : 0);
            $v = self::mul($m['v'], $k);
            $info[] = [
                'v' => $v,
                'r' => $r,
                'label' => '' === $text ? null : self::add($v, self::mul(self::unit(self::add($ua, $ub)), $lr)),
            ];
        }

        foreach ($info as $i => $a) {
            if (null === $a['label']) {
                continue;
            }

            foreach ($info as $j => $b) {
                if ($i === $j) {
                    continue;
                }

                // Two labels: at least about the width of «45°»
                if (null !== $b['label'] && $j > $i && self::len(self::sub($a['label'], $b['label'])) < 34) {
                    return true;
                }

                // A label on or inside the arc of an angle at another vertex
                if (self::len(self::sub($a['v'], $b['v'])) > 1 && self::len(self::sub($a['label'], $b['v'])) < $b['r'] + 16) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Point on the unit circle.
     */
    private static function cp(float $deg): array
    {
        return [cos(self::rad($deg)), sin(self::rad($deg))];
    }

    /**
     * Four even arcs (degrees), each at least $min, together 360°.
     */
    private static function arcs(int $min = 50): array
    {
        do {
            $a = [2 * Util::ri($min / 2, 90), 2 * Util::ri($min / 2, 90), 2 * Util::ri($min / 2, 90)];
            $a[] = 360 - array_sum($a);
        } while ($a[3] < $min || $a[3] > 180);

        return Util::shuffle($a);
    }

    /**
     * Scene with the circle, the points on it (names outside) and optionally the centre M.
     */
    private static function circleScene(array $pts, bool $centre = false): array
    {
        $S = self::newScene();
        $S['rot'] = Util::ri(0, 359);
        // Points around the circle, so the size does not depend on the rotation
        $S['fit'] = array_map(static fn (int $k): array => [1.16 * cos(M_PI * $k / 8), 1.16 * sin(M_PI * $k / 8)], range(0, 15));
        $S['margin'] = 34;
        $S['circles'][] = ['c' => [0, 0], 'r' => 1];

        foreach ($pts as $name => $p) {
            $S['labels'][] = ['p' => $p, 'text' => $name, 'away' => [0, 0]];
        }

        if ($centre) {
            $S['dots'][] = [0, 0];
        }

        return $S;
    }

    private static function seg(array &$S, array $a, array $b): void
    {
        $S['segs'][] = ['a' => $a, 'b' => $b];
    }

    /* ---------- Two crossing chords («butterfly») ---------- */

    private function cChords(): array
    {
        [$a1, $a2, $a3, $a4] = self::arcs(56);
        $A = self::cp(0);
        $B = self::cp($a1);
        $C = self::cp($a1 + $a2);
        $D = self::cp($a1 + $a2 + $a3);
        $X = self::rayInt($A, self::deg(atan2($C[1] - $A[1], $C[0] - $A[0])), $B, self::deg(atan2($D[1] - $B[1], $D[0] - $B[0])));
        $S = self::circleScene(['A' => $A, 'B' => $B, 'C' => $C, 'D' => $D]);
        self::seg($S, $A, $C);
        self::seg($S, $B, $D);
        self::seg($S, $A, $B);
        self::seg($S, $C, $D);
        // Angles: at A and D over the chord BC, at B and C over the chord AD
        $wA = $a2 / 2;
        $wB = $a4 / 2;
        $wX = 180 - $wA - $wB;
        $mA = ['v' => $A, 'a' => $B, 'b' => $C];
        $mB = ['v' => $B, 'a' => $A, 'b' => $D];
        $mC = ['v' => $C, 'a' => $D, 'b' => $A];
        $mD = ['v' => $D, 'a' => $C, 'b' => $B];
        $mX = ['v' => $X, 'a' => $A, 'b' => $B];
        $mX2 = ['v' => $X, 'a' => $B, 'b' => $C];
        $same = '<b>Peripheriewinkel</b> über derselben Sehne {0} sind gleich gross → {1} = <b>{2}°</b>';

        if (self::rand() < 0.5) {
            // Given: one angle in each triangle
            array_push(
                $S['marks'],
                $mA + ['text' => $wA.'°', 'kind' => 'given'],
                $mC + ['text' => $wB.'°', 'kind' => 'given'],
                self::u($mD, 'ω₁', $wA),
                self::u($mB, 'ω₂', $wB),
                self::u($mX, 'ω₃', $wX),
                self::u($mX2, 'ω₄', 180 - $wX),
            );
            $steps = [
                self::p($same, 'BC', 'ω₁', $wA),
                self::p($same, 'AD', 'ω₂', $wB),
                self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'ABS', 'ω₃', $wA, $wB, $wX),
                self::p('<b>Nebenwinkel</b> ergänzen sich zu 180° → {0} = 180° − {1}° = <b>{2}°</b>', 'ω₄', $wX, 180 - $wX),
            ];
        } else {
            // Given: an angle at A and the angle at the crossing
            array_push(
                $S['marks'],
                $mA + ['text' => $wA.'°', 'kind' => 'given'],
                $mX + ['text' => $wX.'°', 'kind' => 'given'],
                self::u($mB, 'ω₁', $wB),
                self::u($mX2, 'ω₂', 180 - $wX),
                self::u($mC, 'ω₃', $wB),
                self::u($mD, 'ω₄', $wA),
            );
            $steps = [
                self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'ABS', 'ω₁', $wA, $wX, $wB),
                self::p('<b>Nebenwinkel</b> ergänzen sich zu 180° → {0} = 180° − {1}° = <b>{2}°</b>', 'ω₂', $wX, 180 - $wX),
                self::p($same, 'AD', 'ω₃', $wB),
                self::p($same, 'BC', 'ω₄', $wA),
            ];
        }

        // S in the free sector between SD and SA
        $S['labels'][] = ['p' => $X, 'text' => 'S', 'away' => self::sub(self::mul($X, 2), self::lerp($A, $D, 0.5))];

        return ['S' => $S, 'prompt' => self::fill('Die Punkte A, B, C und D liegen auf dem Kreis. Berechne {0} und {1}.', self::listOf(['ω₁', 'ω₂', 'ω₃', 'ω₄'])), 'steps' => $steps];
    }

    /* ---------- Cyclic quadrilateral with a diagonal ---------- */

    private function cQuad(): array
    {
        [$a1, $a2, $a3, $a4] = self::arcs(56);
        $A = self::cp(0);
        $B = self::cp($a1);
        $C = self::cp($a1 + $a2);
        $D = self::cp($a1 + $a2 + $a3);
        $S = self::circleScene(['A' => $A, 'B' => $B, 'C' => $C, 'D' => $D]);
        self::seg($S, $A, $B);
        self::seg($S, $B, $C);
        self::seg($S, $C, $D);
        self::seg($S, $D, $A);
        self::seg($S, $A, $C);
        // Interior angle at B over the arc C-D-A, at A the parts over BC and CD
        $wB = ($a3 + $a4) / 2;
        $w1 = 180 - $wB;
        $p1 = $a2 / 2;
        $p2 = $a3 / 2;
        $w2 = 180 - $wB - $p1;
        $w3 = 180 - $w1 - $p2;
        array_push(
            $S['marks'],
            ['v' => $B, 'a' => $A, 'b' => $C, 'text' => $wB.'°', 'kind' => 'given'],
            ['v' => $A, 'a' => $B, 'b' => $C, 'text' => $p1.'°', 'kind' => 'given'],
            ['v' => $A, 'a' => $C, 'b' => $D, 'text' => $p2.'°', 'kind' => 'given'],
            self::u(['v' => $D, 'a' => $A, 'b' => $C], 'ω₁', $w1),
            self::u(['v' => $C, 'a' => $A, 'b' => $B], 'ω₂', $w2),
            self::u(['v' => $C, 'a' => $D, 'b' => $A], 'ω₃', $w3),
        );

        return ['S' => $S, 'prompt' => self::fill('Die Punkte A, B, C und D liegen auf dem Kreis. Berechne {0} und {1}.', self::listOf(['ω₁', 'ω₂', 'ω₃'])),
            'steps' => [
                self::p('<b>Sehnenviereck</b> {0}: Gegenüberliegende Winkel ergänzen sich zu 180° → {1} = 180° − {2}° = <b>{3}°</b>', 'ABCD', 'ω₁', $wB, $w1),
                self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'ABC', 'ω₂', $wB, $p1, $w2),
                self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'ACD', 'ω₃', $w1, $p2, $w3),
            ]];
    }

    /* ---------- Two triangles over a diameter ---------- */

    private function cDiameter(): array
    {
        $tc = 2 * Util::ri(25, 65);
        $td = 2 * Util::ri(115, 155);
        $A = self::cp(180);
        $B = self::cp(0);
        $C = self::cp($tc);
        $D = self::cp($td);
        $S = self::circleScene(['A' => $A, 'B' => $B, 'C' => $C, 'D' => $D], true);
        self::seg($S, $A, $B);
        self::seg($S, $A, $C);
        self::seg($S, $B, $C);
        self::seg($S, $A, $D);
        self::seg($S, $B, $D);
        self::seg($S, $C, $D);
        $al = $tc / 2;
        $be = ($td - 180) / 2;
        array_push(
            $S['marks'],
            ['v' => $A, 'a' => $C, 'b' => $B, 'text' => $al.'°', 'kind' => 'given'],
            ['v' => $B, 'a' => $A, 'b' => $D, 'text' => $be.'°', 'kind' => 'given'],
            self::u(['v' => $C, 'a' => $A, 'b' => $B], 'ω₁', 90),
            self::u(['v' => $B, 'a' => $C, 'b' => $A], 'ω₂', 90 - $al),
            self::u(['v' => $D, 'a' => $C, 'b' => $B], 'ω₃', $al),
            self::u(['v' => $A, 'a' => $B, 'b' => $D], 'ω₄', 90 - $be),
        );
        $S['labels'][] = ['p' => [0, 0], 'text' => 'M', 'away' => self::lerp($C, $B, 0.5)];

        return ['S' => $S, 'prompt' => self::fill('M ist der Mittelpunkt des Kreises, A, B, C und D liegen auf dem Kreis. Berechne {0} und {1}.', self::listOf(['ω₁', 'ω₂', 'ω₃', 'ω₄'])),
            'steps' => [
                self::p('<b>Thaleskreis</b>: {0} ist ein Durchmesser → {1} = <b>90°</b>', 'AB', 'ω₁'),
                self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'ABC', 'ω₂', 90, $al, 90 - $al),
                self::p('<b>Peripheriewinkel</b> über derselben Sehne {0} sind gleich gross → {1} = <b>{2}°</b>', 'BC', 'ω₃', $al),
                self::p('<b>Thaleskreis</b>: {0} ist ein Durchmesser → {1} = <b>90°</b>', 'AB', '∢ADB'),
                self::p('<b>Winkelsumme im Dreieck {0}</b>: {1} = 180° − {2}° − {3}° = <b>{4}°</b>', 'ABD', 'ω₄', 90, $be, 90 - $be),
            ]];
    }

    /* ---------- Central angle and inscribed angles ---------- */

    private function cCentre(): array
    {
        $mu = 2 * Util::ri(35, 65);
        $tA = 270 - $mu / 2;
        $tB = 270 + $mu / 2;
        // C on the large arc, D on the small arc
        $tc = $tB + 2 * Util::ri(25, (int) ((360 - $mu) / 2) - 25);
        $td = 270 + 2 * Util::ri((int) (-$mu / 8), (int) ($mu / 8));
        $A = self::cp($tA);
        $B = self::cp($tB);
        $C = self::cp($tc);
        $D = self::cp($td);
        $M = [0, 0];
        $S = self::circleScene(['A' => $A, 'B' => $B, 'C' => $C, 'D' => $D], true);
        self::seg($S, $M, $A);
        self::seg($S, $M, $B);
        self::seg($S, $A, $B);
        self::seg($S, $C, $A);
        self::seg($S, $C, $B);
        self::seg($S, $D, $A);
        self::seg($S, $D, $B);
        $ga = $mu / 2;
        $iso = (180 - $mu) / 2;
        $de = 180 - $ga;
        $mM = ['v' => $M, 'a' => $A, 'b' => $B];
        $mC = ['v' => $C, 'a' => $A, 'b' => $B];
        $chord = '<b>Zentriwinkel</b> = 2 · Peripheriewinkel über der Sehne {0}';

        if (self::rand() < 0.5) {
            array_push($S['marks'], $mM + ['text' => $mu.'°', 'kind' => 'given'], self::u($mC, 'ω₁', $ga));
            $first = self::p($chord.' → {1} = {2}° : 2 = <b>{3}°</b>', 'AB', 'ω₁', $mu, $ga);
        } else {
            array_push($S['marks'], $mC + ['text' => $ga.'°', 'kind' => 'given'], self::u($mM, 'ω₁', $mu));
            $first = self::p($chord.' → {1} = 2 · {2}° = <b>{3}°</b>', 'AB', 'ω₁', $ga, $mu);
        }

        array_push(
            $S['marks'],
            self::u(['v' => $A, 'a' => $M, 'b' => $B], 'ω₂', $iso),
            self::u(['v' => $D, 'a' => $A, 'b' => $B], 'ω₃', $de),
        );
        $S['labels'][] = ['p' => $M, 'text' => 'M', 'away' => self::cp(270)];

        return ['S' => $S, 'prompt' => self::fill('M ist der Mittelpunkt des Kreises, A, B, C und D liegen auf dem Kreis. Berechne {0} und {1}.', self::listOf(['ω₁', 'ω₂', 'ω₃'])),
            'steps' => [
                $first,
                self::p('<b>Gleichschenkliges Dreieck</b> {0} (zwei Radien): {1} = (180° − {2}°) : 2 = <b>{3}°</b>', 'ABM', 'ω₂', $mu, $iso),
                self::p('<b>Sehnenviereck</b> {0}: Gegenüberliegende Winkel ergänzen sich zu 180° → {1} = 180° − {2}° = <b>{3}°</b>', 'ADBC', 'ω₃', $ga, $de),
            ]];
    }
}
