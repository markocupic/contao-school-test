<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Triangle;

use Markocupic\ContaoSchoolTest\Task\Util as U;

/**
 * Triangle constructions in GeoGebra: SSS, SWS, WSW, SsW and constructions with a height,
 * an angle bisector or a median (Schwerelinie).
 *
 * Answer type "triangle": the student constructs the points A, B and C anywhere on the sheet.
 * Every given measure that the triangle has (within the tolerance) is worth one point, one more
 * point if the three sides are drawn as segments. Position and orientation do not matter.
 */
final class TriangleGenerator
{
    public const KINDS = ['sss', 'sws', 'wsw', 'ssw', 'hoehe', 'winkelhalbierende', 'schwerelinie'];

    public const LEVELS = ['easy', 'medium', 'hard'];

    /**
     * Allowed deviation: [cm, degrees].
     */
    public const TOLERANCE = ['easy' => [0.2, 2.0], 'medium' => [0.1, 1.0], 'hard' => [0.05, 0.5]];

    /**
     * Drawing sheet in cm (GeoGebra: 1 unit = 1 cm, 40 pixels).
     */
    public const SHEET = [16, 11];

    public const SCALE = 40;

    private const ANGLE_AT = ['A' => 'alpha', 'B' => 'beta', 'C' => 'gamma'];

    private const SIDE_OPPOSITE = ['A' => 'a', 'B' => 'b', 'C' => 'c'];

    public const LABELS = [
        'a' => 'a', 'b' => 'b', 'c' => 'c',
        'alpha' => 'α', 'beta' => 'β', 'gamma' => 'γ',
        'ha' => 'h<sub>a</sub>', 'hb' => 'h<sub>b</sub>', 'hc' => 'h<sub>c</sub>',
        'wa' => 'w<sub>α</sub>', 'wb' => 'w<sub>β</sub>', 'wc' => 'w<sub>γ</sub>',
        'sa' => 's<sub>a</sub>', 'sb' => 's<sub>b</sub>', 'sc' => 's<sub>c</sub>',
    ];

    /**
     * Allowed deviation in mm and degrees as text, for the instruction ("1", "1").
     *
     * @return array{0: string, 1: string}
     */
    public static function toleranceText(string $level): array
    {
        [$len, $ang] = self::TOLERANCE[$level] ?? self::TOLERANCE['medium'];

        return [self::num($len * 10), self::num($ang)];
    }

    public function build(string $kind, string $level): array
    {
        $kind = \in_array($kind, self::KINDS, true) ? $kind : 'sss';
        $level = \in_array($level, self::LEVELS, true) ? $level : 'medium';

        for ($k = 0; $k < 5000; ++$k) {
            $start = $this->randomTriangle();
            $m = TriangleGeometry::measures(...$start);

            if (!$this->fits($m)) {
                continue;
            }

            $variant = $this->variant($kind, $level, $m);

            if (null === $variant) {
                continue;
            }

            [$keys, $steps] = $variant;
            // Question and given values in the usual order: sides, angles, heights …
            usort($keys, static fn (string $x, string $y): int => array_search($x, array_keys(self::LABELS), true) <=> array_search($y, array_keys(self::LABELS), true));
            $given = [];

            foreach ($keys as $key) {
                $given[$key] = $this->round($key, $m[$key], $level);
            }

            // The triangle that has exactly the rounded values
            $shape = TriangleGeometry::solve($given, $start);

            if (null === $shape) {
                continue;
            }

            $sm = TriangleGeometry::measures(...$shape);

            if (!$this->fits($sm) || !$this->unambiguousEnough($kind, $keys, $sm)) {
                continue;
            }

            $texts = array_map(fn (string $key): string => $this->measure($key, $given[$key]), $keys);
            $tol = self::TOLERANCE[$level];

            return [
                'q' => self::fill('Konstruiere das Dreieck ABC mit {0}, {1} und {2}.', $texts),
                'key' => md5(json_encode([$kind, $given], JSON_THROW_ON_ERROR)),
                'a' => [
                    'type' => 'triangle',
                    'given' => array_map(static fn (string $key, float $v): array => ['k' => $key, 'v' => $v], array_keys($given), array_values($given)),
                    'tol' => $tol,
                    'shape' => array_map(static fn (array $p): array => [round($p[0], 4), round($p[1], 4)], $shape),
                    'draw' => ['free' => true, 'ggb' => true, 'tri' => true, 'n' => 3, 'names' => ['A', 'B', 'C'], 'grid' => self::SHEET, 'scale' => self::SCALE, 'pts' => []],
                ],
                'way' => '<ol class="st-steps">'.implode('', array_map(static fn (string $s): string => '<li>'.$s.'</li>', [...$steps($given), self::p('Verbinde A, B und C mit Strecken zum Dreieck.')])).'</ol>',
                'points' => \count($keys) + 1,
                'area' => $kind,
            ];
        }

        throw new \RuntimeException('Could not generate a triangle construction.');
    }

    /* ---------- Variants: given measures and construction steps ---------- */

    /**
     * @return array{0: list<string>, 1: \Closure}|null
     */
    private function variant(string $kind, string $level, array $m): array|null
    {
        $easy = 'easy' === $level;

        // Base side and its ends: c from A to B on the easy level, otherwise any side
        $bases = $easy ? [['c', 'A', 'B', 'C']] : [['c', 'A', 'B', 'C'], ['a', 'B', 'C', 'A'], ['b', 'C', 'A', 'B']];
        [$side, $P, $Q, $R] = U::pick($bases);
        $sideP = self::SIDE_OPPOSITE[$Q];
        $sideQ = self::SIDE_OPPOSITE[$P];
        $angP = self::ANGLE_AT[$P];
        $angQ = self::ANGLE_AT[$Q];
        $seg = $P.$Q;
        $M = fn (array $g, string $key): string => $this->measure($key, $g[$key]);

        switch ($kind) {
            case 'sss':
                return [[$sideQ, $sideP, $side], fn (array $g): array => [
                    self::p('Zeichne die Strecke {0} mit {1}.', $seg, $M($g, $side)),
                    self::p('Zeichne um {0} einen Kreis mit dem Radius {1}.', $P, $M($g, $sideP)),
                    self::p('Zeichne um {0} einen Kreis mit dem Radius {1}.', $Q, $M($g, $sideQ)),
                    self::p('Die Kreise schneiden sich in {0}.', $R),
                ]];

            case 'sws':
                // Angle between the base and the second side, at P (or Q)
                if (!$easy && U::chance()) {
                    [$P, $Q, $sideP, $sideQ, $angP] = [$Q, $P, $sideQ, $sideP, $angQ];
                    $seg = $Q.$P;
                }

                return [[$side, $sideP, $angP], fn (array $g): array => [
                    self::p('Zeichne die Strecke {0} mit {1}.', $seg, $M($g, $side)),
                    self::p('Trage in {0} den Winkel {1} an.', $P, $M($g, $angP)),
                    self::p('Trage auf dem freien Schenkel von {0} aus {1} ab, das gibt {2}.', $P, $M($g, $sideP), $R),
                ]];

            case 'wsw':
                return [[$side, $angP, $angQ], fn (array $g): array => [
                    self::p('Zeichne die Strecke {0} mit {1}.', $seg, $M($g, $side)),
                    self::p('Trage in {0} den Winkel {1} an.', $P, $M($g, $angP)),
                    self::p('Trage in {0} den Winkel {1} an.', $Q, $M($g, $angQ)),
                    self::p('Die freien Schenkel schneiden sich in {0}.', $R),
                ]];

            case 'ssw':
                // SsW: the angle lies opposite the longer of the two sides. Angle at P, side PQ known,
                // the side QR opposite P is the longer one.
                if (!$easy && U::chance()) {
                    [$P, $Q, $sideP, $sideQ, $angP] = [$Q, $P, $sideQ, $sideP, $angQ];
                    $seg = $Q.$P;
                }

                $opp = self::SIDE_OPPOSITE[$P];

                if ($m[$opp] < 1.15 * $m[$side]) {
                    return null;
                }

                return [[$side, $opp, $angP], fn (array $g): array => [
                    self::p('Zeichne die Strecke {0} mit {1}.', $seg, $M($g, $side)),
                    self::p('Trage in {0} den Winkel {1} an.', $P, $M($g, $angP)),
                    self::p('Zeichne um {0} einen Kreis mit dem Radius {1}.', $Q, $M($g, $opp)),
                    self::p('Der Kreis schneidet den freien Schenkel in {0}.', $R),
                ]];

            case 'hoehe':
                $h = 'h'.$side;
                $options = ['angle', 'side'];

                if ('hard' === $level) {
                    $options = ['angle', 'side', 'angles', 'sides'];
                }

                $option = U::pick($options);

                if (!$easy && U::chance()) {
                    [$P, $Q, $sideP, $sideQ, $angP, $angQ] = [$Q, $P, $sideQ, $sideP, $angQ, $angP];
                    $seg = $Q.$P;
                }

                $parallel = fn (array $g): string => self::p('Zeichne im Abstand {0} eine Parallele zu {1} auf der Seite, wo {2} liegen soll.', $M($g, $h), $seg, $R);

                if ('angle' === $option) {
                    return [[$side, $h, $angP], fn (array $g): array => [
                        self::p('Zeichne die Strecke {0} mit {1}.', $seg, $M($g, $side)),
                        $parallel($g),
                        self::p('Trage in {0} den Winkel {1} an.', $P, $M($g, $angP)),
                        self::p('Der freie Schenkel schneidet die Parallele in {0}.', $R),
                    ]];
                }

                if ('side' === $option) {
                    if ($m[$sideP] < 1.15 * $m[$h]) {
                        return null;
                    }

                    return [[$side, $h, $sideP], fn (array $g): array => [
                        self::p('Zeichne die Strecke {0} mit {1}.', $seg, $M($g, $side)),
                        $parallel($g),
                        self::p('Zeichne um {0} einen Kreis mit dem Radius {1}.', $P, $M($g, $sideP)),
                        self::p('Der Kreis schneidet die Parallele in {0}.', $R),
                    ]];
                }

                $line = fn (array $g): string => self::p('Zeichne eine Gerade g und im Abstand {0} eine Parallele dazu.', $M($g, $h));

                if ('angles' === $option) {
                    // The angle at Q appears again at R between the parallel and RQ (alternate angles)
                    return [[$h, $angP, $angQ], fn (array $g): array => [
                        $line($g),
                        self::p('Wähle auf g den Punkt {0} und trage dort den Winkel {1} an.', $P, $M($g, $angP)),
                        self::p('Der freie Schenkel schneidet die Parallele in {0}.', $R),
                        self::p('Trage in {0} an der Parallelen den Winkel {1} an (Wechselwinkel). Der Schenkel schneidet g in {2}.', $R, $M($g, $angQ), $Q),
                    ]];
                }

                if ($m[$sideP] < 1.15 * $m[$h] || $m[$sideQ] < 1.15 * $m[$h]) {
                    return null;
                }

                return [[$h, $sideP, $sideQ], fn (array $g): array => [
                    $line($g),
                    self::p('Wähle auf der Parallelen den Punkt {0}.', $R),
                    self::p('Zeichne um {0} einen Kreis mit dem Radius {1}. Er schneidet g in {2}.', $R, $M($g, $sideP), $P),
                    self::p('Zeichne um {0} einen Kreis mit dem Radius {1}. Er schneidet g auf der anderen Seite in {2}.', $R, $M($g, $sideQ), $Q),
                ]];

            case 'winkelhalbierende':
                // Angle at P, its bisector w and the side PQ: D on the bisector lies on the side QR,
                // the line QD cuts the free arm in R
                if (!$easy && U::chance()) {
                    [$P, $Q, $sideP, $sideQ, $angP] = [$Q, $P, $sideQ, $sideP, $angQ];
                    $seg = $Q.$P;
                }

                $w = 'w'.strtolower($P);

                return [[$side, $angP, $w], fn (array $g): array => [
                    self::p('Zeichne die Strecke {0} mit {1}.', $seg, $M($g, $side)),
                    self::p('Trage in {0} den Winkel {1} an.', $P, $M($g, $angP)),
                    self::p('Zeichne die Winkelhalbierende von {0} und trage darauf von {1} aus {2} ab, das gibt {3}.', self::LABELS[$angP], $P, $M($g, $w), 'D'),
                    self::p('Die Gerade durch {0} und {1} schneidet den freien Schenkel in {2}.', $Q, 'D', $R),
                ]];

            case 'schwerelinie':
                if (!$easy && U::chance()) {
                    [$P, $Q, $sideP, $sideQ, $angP] = [$Q, $P, $sideQ, $sideP, $angQ];
                    $seg = $Q.$P;
                }

                // Median from R to the middle M of PQ
                $sR = 's'.strtolower($R);
                $options = 'hard' === $level ? ['side', 'angle', 'own'] : ['side', 'angle'];
                $option = U::pick($options);
                $mid = fn (array $g): string => self::p('Bestimme den Mittelpunkt {0} der Strecke {1}.', 'M', $seg);

                if ('side' === $option) {
                    // Median from R to the middle of PQ and the side PR: two circles
                    return [[$side, $sideP, $sR], fn (array $g): array => [
                        self::p('Zeichne die Strecke {0} mit {1}.', $seg, $M($g, $side)),
                        $mid($g),
                        self::p('Zeichne um {0} einen Kreis mit dem Radius {1}.', $P, $M($g, $sideP)),
                        self::p('Zeichne um {0} einen Kreis mit dem Radius {1}.', 'M', $M($g, $sR)),
                        self::p('Die Kreise schneiden sich in {0}.', $R),
                    ]];
                }

                if ('angle' === $option) {
                    return [[$side, $angP, $sR], fn (array $g): array => [
                        self::p('Zeichne die Strecke {0} mit {1}.', $seg, $M($g, $side)),
                        $mid($g),
                        self::p('Trage in {0} den Winkel {1} an.', $P, $M($g, $angP)),
                        self::p('Zeichne um {0} einen Kreis mit dem Radius {1}.', 'M', $M($g, $sR)),
                        self::p('Der Kreis schneidet den freien Schenkel in {0}.', $R),
                    ]];
                }

                // Median to a given side: PQ and PR known, the median from Q ends in the middle of PR
                $sQ = 's'.$sideP;

                return [[$side, $sideP, $sQ], fn (array $g): array => [
                    self::p('Zeichne die Strecke {0} mit {1}.', $seg, $M($g, $side)),
                    self::p('Zeichne um {0} einen Kreis mit dem Radius {1} (halb so lang wie {2}).', $P, self::num($g[$sideP] / 2).' cm', $sideP),
                    self::p('Zeichne um {0} einen Kreis mit dem Radius {1}.', $Q, $M($g, $sQ)),
                    self::p('Die Kreise schneiden sich im Mittelpunkt {0} der Seite {1}.', 'M', $sideP),
                    self::p('Verlängere {0} über {1} hinaus auf die doppelte Länge, das gibt {2}.', $P.'M', 'M', $R),
                ]];
        }

        return null;
    }

    /* ---------- Shape ---------- */

    /**
     * Random triangle with angles between 25° and 125°, base 4 to 10 cm.
     */
    private function randomTriangle(): array
    {
        do {
            $alpha = mt_rand(25, 125);
            $beta = mt_rand(25, 125);
        } while (180 - $alpha - $beta < 25);

        $c = mt_rand(40, 100) / 10;
        $gamma = 180 - $alpha - $beta;
        $b = $c * sin(deg2rad($beta)) / sin(deg2rad($gamma));

        return [[0.0, 0.0], [$c, 0.0], [$b * cos(deg2rad($alpha)), $b * sin(deg2rad($alpha))]];
    }

    /**
     * Sides 3 to 11 cm, heights at most 9 cm, no angle under 20°: the triangle fits on the sheet.
     */
    private function fits(array $m): bool
    {
        foreach (['a', 'b', 'c'] as $s) {
            if ($m[$s] < 3 || $m[$s] > 11) {
                return false;
            }
        }

        foreach (['ha', 'hb', 'hc'] as $h) {
            if ($m[$h] > 9 || $m[$h] < 1.5) {
                return false;
            }
        }

        return min($m['alpha'], $m['beta'], $m['gamma']) >= 20;
    }

    /**
     * Circle and line must cross clearly, not just touch.
     */
    private function unambiguousEnough(string $kind, array $keys, array $m): bool
    {
        // SsW: the side opposite the given angle is clearly the longer one
        if ('ssw' === $kind) {
            $angle = array_values(array_intersect($keys, TriangleGeometry::ANGLES))[0];
            $opp = strtolower(array_search($angle, self::ANGLE_AT, true));
            $other = array_values(array_diff($keys, [$angle, $opp]))[0];

            return $m[$opp] >= 1.1 * $m[$other];
        }

        if ('hoehe' === $kind) {
            foreach ($keys as $key) {
                if (\in_array($key, ['a', 'b', 'c'], true)) {
                    $h = array_values(array_filter($keys, static fn (string $k): bool => 'h' === $k[0]))[0];

                    if ($m[$key] < 1.1 * $m[$h]) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function round(string $key, float $v, string $level): float
    {
        if (\in_array($key, TriangleGeometry::ANGLES, true)) {
            return 'easy' === $level ? (float) (5 * round($v / 5)) : (float) round($v);
        }

        return 'easy' === $level ? round($v * 2) / 2 : round($v, 1);
    }

    /* ---------- Texts ---------- */

    /**
     * "a = 5.2 cm", "α = 50°", "h<sub>c</sub> = 4 cm".
     */
    public function measure(string $key, float $v): string
    {
        return self::LABELS[$key].' = '.self::num($v).(\in_array($key, TriangleGeometry::ANGLES, true) ? '°' : ' cm');
    }

    public static function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }

    private static function p(string $tpl, string ...$args): string
    {
        return '<span class="st-t">'.self::fill($tpl, $args).'</span>';
    }

    private static function fill(string $tpl, array $args): string
    {
        foreach ($args as $i => $v) {
            $tpl = str_replace('{'.$i.'}', $v, $tpl);
        }

        return $tpl;
    }
}
