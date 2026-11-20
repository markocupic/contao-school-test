<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\FigureArea;

use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Flächenberechnung: triangles, rectangles, parallelograms, trapezoids, circles, composed
 * figures and areas with Pythagoras, each in three levels, with sketches and word problems.
 *
 * The tasks use the answer type "angles" (one field per value, sketch with and without the
 * solution); every field has its own tolerance: exact, or rounded to one decimal place.
 */
final class FigureAreaGenerator
{
    use FigureAreaComposedTasks;
    use FigureAreaWordTasks;

    public const KINDS = ['dreieck', 'rechteck', 'parallelogramm', 'trapez', 'kreis', 'zusammengesetzt', 'pythagoras'];

    public const LEVELS = ['easy', 'medium', 'hard'];

    /**
     * [absolute, relative]: exact values, or rounded to one decimal place (π ≈ 3.14 counts too).
     */
    public const EXACT = [0.001, 0.0];

    public const ROUND = [0.051, 0.0006];

    private const NBSP = "\u{00A0}";

    /**
     * Pythagorean triples (a, b, c) with c² = a² + b².
     */
    private const TRIPLES = [[3, 4, 5], [5, 12, 13], [8, 15, 17], [7, 24, 25], [20, 21, 29], [9, 12, 15], [6, 8, 10], [12, 16, 20], [15, 20, 25], [10, 24, 26]];

    /**
     * One task. $word: a word problem (no sketch in the question, the solution has one).
     */
    public function build(string $kind, string $level, bool $word = false): array
    {
        $level = \in_array($level, self::LEVELS, true) ? $level : 'medium';
        $kind = \in_array($kind, self::KINDS, true) ? $kind : 'rechteck';

        $t = $word ? $this->word($kind, $level) : match ($kind) {
            'dreieck' => $this->triangle($level),
            'rechteck' => $this->rectangle($level),
            'parallelogramm' => $this->parallelogram($level),
            'trapez' => $this->trapezoid($level),
            'kreis' => $this->circle($level),
            'zusammengesetzt' => $this->composed($level),
            default => $this->pythagoras($level),
        };

        $fields = [];
        $values = [];
        $tol = [];

        foreach ($t['fields'] as [$label, $unit, $value, $tolerance]) {
            $fields[] = ['label' => $label, 'unit' => '' === $unit ? '' : self::NBSP.$unit];
            $values[] = $value;
            $tol[] = $tolerance;
        }

        /** @var FigureAreaSvg $svg */
        $svg = $t['svg'];

        return [
            'q' => $t['q'],
            'key' => md5($t['q'].json_encode($values).($word ? 'w' : '').json_encode($t['steps'])),
            'a' => [
                'type' => 'angles',
                'fields' => $fields,
                'v' => $values,
                'tol' => $tol,
                'svg' => $word ? '' : $svg->render(false),
                'solSvg' => $svg->render(true),
            ],
            'way' => '<ol class="st-steps">'.implode('', array_map(static fn (string $s): string => '<li>'.$s.'</li>', $t['steps'])).'</ol>',
            'points' => \count($fields),
            'area' => $kind,
        ];
    }

    /* ---------- Helpers ---------- */

    private static function n(float|int $x): string
    {
        return Util::fmt($x);
    }

    /**
     * Value with unit: "4.5 cm".
     */
    private static function u(float|int $x, string $unit): string
    {
        return Util::fmt($x).self::NBSP.$unit;
    }

    private static function b(string $s): string
    {
        return '<b>'.$s.'</b>';
    }

    /**
     * Fill a German template (question).
     */
    private static function fill(string $tpl, string|int|float ...$args): string
    {
        foreach ($args as $i => $v) {
            $tpl = str_replace('{'.$i.'}', \is_string($v) ? $v : Util::fmt($v), $tpl);
        }

        return $tpl;
    }

    /**
     * Phrase of the solution way, translated as a whole.
     */
    private static function p(string $tpl, string|int|float ...$args): string
    {
        return '<span class="st-t">'.self::fill($tpl, ...$args).'</span>';
    }

    /**
     * Random multiple of $step between $min and $max.
     */
    private static function rs(float $min, float $max, float $step = 0.5): float
    {
        return round(mt_rand((int) round($min / $step), (int) round($max / $step)) * $step, 6);
    }

    private static function oneDecimal(float $x): bool
    {
        return abs($x * 10 - round($x * 10)) < 1e-7;
    }

    /**
     * Tolerance of a rounded value that may be computed from an already rounded value.
     */
    private static function roundTol(float $exact, float $fromRounded): array
    {
        return [max(0.051, abs(round($exact, 1) - $fromRounded) + 0.051), 0.0006];
    }

    private static function triple(int $maxC = 30): array
    {
        $options = [];

        foreach (self::TRIPLES as $t) {
            // No very flat figures
            if ($t[0] / $t[1] < 0.3) {
                continue;
            }

            for ($k = 1; $k * $t[2] <= $maxC; ++$k) {
                $options[] = [$t[0] * $k, $t[1] * $k, $t[2] * $k];
            }
        }

        $t = Util::pick($options);

        return Util::chance() ? $t : [$t[1], $t[0], $t[2]];
    }

    private static function unit(string $level): string
    {
        return 'easy' === $level ? 'cm' : Util::pick(['cm', 'cm', 'm']);
    }

    /* ---------- Triangle ---------- */

    private function triangle(string $level): array
    {
        $variant = match ($level) {
            'easy' => Util::pick(['gh', 'gh', 'right']),
            'medium' => Util::pick(['inverse', 'obtuse', 'gh']),
            default => Util::pick(['heights', 'units']),
        };

        if ('heights' === $variant) {
            return $this->triangleHeights();
        }

        $unit = self::unit($level);

        if ('right' === $variant) {
            $a = mt_rand(3, 12);
            $b = mt_rand(3, 10);
            $A = $a * $b / 2;
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$a, 0], [0, $b]]]], 'yellow')
                ->right([0, 0], [1, 0], [0, 1])
                ->side([0, 0], [$a, 0], 'a = '.self::u($a, $unit), [0, $b])
                ->side([0, 0], [0, $b], 'b = '.self::u($b, $unit), [$a, 0])
                ->label([$a / 3, $b / 3], 'A = ?', 'ask', null, 0, 'q')
                ->label([$a / 3, $b / 3], 'A = '.self::u($A, $unit.'²'), 'sol', null, 0, 's');

            return [
                'q' => 'Das Dreieck hat einen rechten Winkel. Berechne den Flächeninhalt A.',
                'fields' => [['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Die Katheten stehen senkrecht aufeinander: Die eine ist die Grundseite, die andere die Höhe.'),
                    'A = a · b : 2 = '.self::u($a, $unit).' · '.self::u($b, $unit).' : 2 = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        $g = 'easy' === $level ? mt_rand(4, 12) : self::rs(3, 12);
        $h = 'easy' === $level ? mt_rand(3, 9) : self::rs(2.5, 9);
        $obtuse = 'obtuse' === $variant;
        $px = $obtuse ? $g + self::rs(1, max(1.5, $g * 0.4)) : $g * (mt_rand(25, 75) / 100);
        $A = $g * $h / 2;
        $C = [$px, $h];
        $F = [$px, 0];

        if ('units' === $variant) {
            $g = self::rs(1.2, 4.8, 0.2);
            $hCm = mt_rand(3, 18) * 5;
            $h = $hCm / 100;
            $A = round($g * $h / 2, 6);
            $px = $g * (mt_rand(25, 75) / 100);
            $C = [$px, max($h * 3, $g * 0.35)];
            $F = [$px, 0];
        }

        $svg = (new FigureAreaSvg())
            ->shape([['poly', [[0, 0], [$g, 0], $C]]], 'yellow')
            ->line($C, $F)
            ->right($F, $C, $obtuse ? [0, 0] : [$g, 0]);

        if ($obtuse) {
            $svg->line([$g, 0], $F, 'thin');
        }

        $ref = $px > $g / 2 ? [$g, 0] : [0, 0];

        if ('units' === $variant) {
            $svg->side([0, 0], [$g, 0], 'g = '.self::u($g, 'm'), $C)
                ->side($C, $F, 'h = '.self::u($hCm, 'cm'), $obtuse ? [0, 0] : $ref);

            return [
                'q' => self::fill('Berechne den Flächeninhalt A des Dreiecks in {0}.', 'm²'),
                'fields' => [['A', 'm²', $A, self::EXACT]],
                'steps' => [
                    self::p('Zuerst in die gleiche Einheit umwandeln: {0}', 'h = '.self::u($hCm, 'cm').' = '.self::u($h, 'm')),
                    'A = g · h : 2 = '.self::u($g, 'm').' · '.self::u($h, 'm').' : 2 = '.self::b(self::u($A, 'm²')),
                ],
                'svg' => $svg,
            ];
        }

        $svg->side([0, 0], [$g, 0], 'g = '.self::u($g, $unit), $C);

        if ('inverse' === $variant) {
            $svg->sideAsk($C, $F, 'h', self::u($h, $unit), $ref);

            return [
                'q' => self::fill('Das Dreieck hat den Flächeninhalt A = {0}. Berechne die Höhe h.', self::u($A, $unit.'²')),
                'fields' => [['h', $unit, $h, self::EXACT]],
                'steps' => [
                    self::p('Aus A = g · h : 2 folgt h = 2 · A : g.'),
                    'h = 2 · '.self::u($A, $unit.'²').' : '.self::u($g, $unit).' = '.self::b(self::u($h, $unit)),
                ],
                'svg' => $svg,
            ];
        }

        $svg->side($C, $F, 'h = '.self::u($h, $unit), $obtuse ? [0, 0] : $ref);
        $steps = [];

        if ($obtuse) {
            $steps[] = self::p('Bei einem stumpfwinkligen Dreieck liegt die Höhe ausserhalb. Die Formel bleibt gleich.');
        }

        $steps[] = 'A = g · h : 2 = '.self::u($g, $unit).' · '.self::u($h, $unit).' : 2 = '.self::b(self::u($A, $unit.'²'));

        return [
            'q' => 'Berechne den Flächeninhalt A des Dreiecks.',
            'fields' => [['A', $unit.'²', $A, self::EXACT]],
            'steps' => $steps,
            'svg' => $svg,
        ];
    }

    /**
     * Triangle with sides a, b and the height h₁ on a: area and the height h₂ on b.
     */
    private function triangleHeights(): array
    {
        for ($i = 0; $i < 200; ++$i) {
            [$p, $h1, $b] = self::triple(25);
            $a = mt_rand($p + 2, $p + 10);
            $h2 = $a * $h1 / $b;

            if (self::oneDecimal($h2) && $a * $p <= 0.85 * $b * $b) {
                break;
            }
        }

        $A = $a * $h1 / 2;
        $C = [0, 0];
        $B = [$a, 0];
        $P = [$p, $h1];
        $F1 = [$p, 0];
        $t = $a * $p / ($b * $b);
        $F2 = [$p * $t, $h1 * $t];

        $svg = (new FigureAreaSvg())
            ->shape([['poly', [$C, $B, $P]]], 'yellow')
            ->line($P, $F1)
            ->right($F1, $P, $B)
            ->line($B, $F2, 'aux')
            ->right($F2, $B, $C)
            ->label($C, 'C', 'vertex', [$a / 2, $h1 / 3], 16)
            ->label($B, 'B', 'vertex', [$a / 2, $h1 / 3], 16)
            ->label($P, 'A', 'vertex', [$a / 2, $h1 / 3], 16)
            ->side($C, $B, 'a = '.self::u($a, 'cm'), $P)
            ->side($C, $P, 'b = '.self::u($b, 'cm'), $B)
            ->side($P, $F1, 'h₁ = '.self::u($h1, 'cm'), $B, 'given', 'b', 0.8)
            ->sideAsk($B, $F2, 'h₂', self::u($h2, 'cm'), $P);

        if ($t > 1) {
            $svg->line($P, $F2, 'thin');
        }

        return [
            'q' => 'h₁ ist die Höhe auf die Seite a, h₂ die Höhe auf die Seite b. Berechne den Flächeninhalt A und die Höhe h₂.',
            'fields' => [['A', 'cm²', $A, self::EXACT], ['h₂', 'cm', $h2, self::EXACT]],
            'steps' => [
                'A = a · h₁ : 2 = '.self::u($a, 'cm').' · '.self::u($h1, 'cm').' : 2 = '.self::b(self::u($A, 'cm²')),
                self::p('Der Flächeninhalt ist mit jeder Seite gleich: A = b · h₂ : 2, also h₂ = 2 · A : b.'),
                'h₂ = 2 · '.self::u($A, 'cm²').' : '.self::u($b, 'cm').' = '.self::b(self::u($h2, 'cm')),
            ],
            'svg' => $svg,
        ];
    }

    /* ---------- Rectangle and square ---------- */

    private function rectangle(string $level): array
    {
        $variant = match ($level) {
            'easy' => Util::pick(['rect', 'square', 'grid']),
            'medium' => Util::pick(['fromA', 'fromU', 'squareU']),
            default => Util::pick(['units', 'ratio', 'squareA']),
        };
        $unit = self::unit($level);

        if ('grid' === $variant) {
            $a = mt_rand(3, 10);
            $b = mt_rand(2, 6);
            $svg = (new FigureAreaSvg())->grid(1)->shape([['poly', [[0, 0], [$a, 0], [$a, $b], [0, $b]]]], 'blue');
            $svg->side([0, 0], [$a, 0], 'a = '.self::u($a, 'cm'), [0, $b], 'sol', 's')
                ->side([$a, 0], [$a, $b], 'b = '.self::u($b, 'cm'), [0, 0], 'sol', 's');

            return [
                'q' => 'Jedes Häuschen ist 1 cm lang. Berechne den Flächeninhalt A und den Umfang U des Rechtecks.',
                'fields' => [['A', 'cm²', $a * $b, self::EXACT], ['U', 'cm', 2 * ($a + $b), self::EXACT]],
                'steps' => [
                    self::p('Häuschen zählen: a = {0}, b = {1}.', self::u($a, 'cm'), self::u($b, 'cm')),
                    'A = a · b = '.self::u($a, 'cm').' · '.self::u($b, 'cm').' = '.self::b(self::u($a * $b, 'cm²')),
                    'U = 2 · (a + b) = 2 · ('.self::u($a, 'cm').' + '.self::u($b, 'cm').') = '.self::b(self::u(2 * ($a + $b), 'cm')),
                ],
                'svg' => $svg,
            ];
        }

        if (\in_array($variant, ['square', 'squareU', 'squareA'], true)) {
            $a = 'squareA' === $variant ? Util::pick([1.2, 1.5, 2.5, 0.8, 1.1, 1.3, 1.4, 0.9, 2.2]) : mt_rand(3, 15);
            $unit = 'squareA' === $variant ? 'm' : $unit;
            $A = round($a * $a, 6);
            $U = 4 * $a;
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$a, 0], [$a, $a], [0, $a]]]], 'blue')
                ->right([0, 0], [1, 0], [0, 1]);

            if ('square' === $variant) {
                $svg->side([0, 0], [$a, 0], 'a = '.self::u($a, $unit), [0, $a]);

                return [
                    'q' => 'Berechne den Flächeninhalt A und den Umfang U des Quadrats.',
                    'fields' => [['A', $unit.'²', $A, self::EXACT], ['U', $unit, $U, self::EXACT]],
                    'steps' => [
                        'A = a · a = '.self::u($a, $unit).' · '.self::u($a, $unit).' = '.self::b(self::u($A, $unit.'²')),
                        'U = 4 · a = 4 · '.self::u($a, $unit).' = '.self::b(self::u($U, $unit)),
                    ],
                    'svg' => $svg,
                ];
            }

            $svg->sideAsk([0, 0], [$a, 0], 'a', self::u($a, $unit), [0, $a]);

            if ('squareU' === $variant) {
                return [
                    'q' => self::fill('Das Quadrat hat den Umfang U = {0}. Berechne die Seite a und den Flächeninhalt A.', self::u($U, $unit)),
                    'fields' => [['a', $unit, $a, self::EXACT], ['A', $unit.'²', $A, self::EXACT]],
                    'steps' => [
                        'a = U : 4 = '.self::u($U, $unit).' : 4 = '.self::b(self::u($a, $unit)),
                        'A = a · a = '.self::u($a, $unit).' · '.self::u($a, $unit).' = '.self::b(self::u($A, $unit.'²')),
                    ],
                    'svg' => $svg,
                ];
            }

            return [
                'q' => self::fill('Das Quadrat hat den Flächeninhalt A = {0}. Berechne die Seite a und den Umfang U.', self::u($A, $unit.'²')),
                'fields' => [['a', $unit, $a, self::EXACT], ['U', $unit, $U, self::EXACT]],
                'steps' => [
                    self::p('Welche Zahl mal sich selbst ergibt {0}? a = √A', self::n($A)),
                    'a = √'.self::u($A, $unit.'²').' = '.self::b(self::u($a, $unit)),
                    'U = 4 · a = 4 · '.self::u($a, $unit).' = '.self::b(self::u($U, $unit)),
                ],
                'svg' => $svg,
            ];
        }

        $a = 'easy' === $level ? mt_rand(4, 15) : self::rs(3, 14);
        $b = 'easy' === $level ? mt_rand(2, max(3, $a - 1)) : self::rs(2, max(2.5, $a - 1));

        if ('ratio' === $variant) {
            $k = mt_rand(2, 4);
            $b = mt_rand(2, 8);
            $a = $k * $b;
        }

        $A = round($a * $b, 6);
        $U = round(2 * ($a + $b), 6);
        $draw = static fn (float $a, float $b): FigureAreaSvg => (new FigureAreaSvg())
            ->shape([['poly', [[0, 0], [$a, 0], [$a, $b], [0, $b]]]], 'blue')
            ->right([0, 0], [1, 0], [0, 1]);

        if ('units' === $variant) {
            $a = self::rs(1.5, 6, 0.1);
            $bCm = mt_rand(4, 19) * 5;
            $b = $bCm / 100;
            $A = round($a * $b, 6);
            $U = round(2 * ($a + $b), 6);
            $svg = $draw($a, max($b, $a / 4))
                ->side([0, 0], [$a, 0], 'a = '.self::u($a, 'm'), [0, 1])
                ->side([$a, 0], [$a, max($b, $a / 4)], 'b = '.self::u($bCm, 'cm'), [0, 0]);

            return [
                'q' => self::fill('Berechne den Flächeninhalt A und den Umfang U des Rechtecks in {0} und {1}.', 'm²', 'm'),
                'fields' => [['A', 'm²', $A, self::EXACT], ['U', 'm', $U, self::EXACT]],
                'steps' => [
                    self::p('Zuerst in die gleiche Einheit umwandeln: {0}', 'b = '.self::u($bCm, 'cm').' = '.self::u($b, 'm')),
                    'A = a · b = '.self::u($a, 'm').' · '.self::u($b, 'm').' = '.self::b(self::u($A, 'm²')),
                    'U = 2 · (a + b) = 2 · ('.self::u($a, 'm').' + '.self::u($b, 'm').') = '.self::b(self::u($U, 'm')),
                ],
                'svg' => $svg,
            ];
        }

        $svg = $draw($a, $b);

        if ('rect' === $variant) {
            $svg->side([0, 0], [$a, 0], 'a = '.self::u($a, $unit), [0, $b])
                ->side([$a, 0], [$a, $b], 'b = '.self::u($b, $unit), [0, 0]);

            return [
                'q' => 'Berechne den Flächeninhalt A und den Umfang U des Rechtecks.',
                'fields' => [['A', $unit.'²', $A, self::EXACT], ['U', $unit, $U, self::EXACT]],
                'steps' => [
                    'A = a · b = '.self::u($a, $unit).' · '.self::u($b, $unit).' = '.self::b(self::u($A, $unit.'²')),
                    'U = 2 · (a + b) = 2 · ('.self::u($a, $unit).' + '.self::u($b, $unit).') = '.self::b(self::u($U, $unit)),
                ],
                'svg' => $svg,
            ];
        }

        if ('ratio' === $variant) {
            $svg->sideAsk([0, 0], [$a, 0], 'a', self::u($a, $unit), [0, $b])
                ->sideAsk([$a, 0], [$a, $b], 'b', self::u($b, $unit), [0, 0]);

            return [
                'q' => self::fill('Die Seite a ist {0}-mal so lang wie die Seite b. Der Umfang beträgt U = {1}. Berechne die Seite b und den Flächeninhalt A.', $k, self::u($U, $unit)),
                'fields' => [['b', $unit, $b, self::EXACT], ['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Der Umfang besteht aus {0}-mal der Seite b: U = 2 · ({1} · b + b) = {0} · b.', 2 * ($k + 1), $k),
                    'b = '.self::u($U, $unit).' : '.(2 * ($k + 1)).' = '.self::b(self::u($b, $unit)).', a = '.$k.' · '.self::u($b, $unit).' = '.self::u($a, $unit),
                    'A = a · b = '.self::u($a, $unit).' · '.self::u($b, $unit).' = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        $svg->side([0, 0], [$a, 0], 'a = '.self::u($a, $unit), [0, $b])
            ->sideAsk([$a, 0], [$a, $b], 'b', self::u($b, $unit), [0, 0]);

        if ('fromA' === $variant) {
            return [
                'q' => self::fill('Das Rechteck hat den Flächeninhalt A = {0}. Berechne die Seite b und den Umfang U.', self::u($A, $unit.'²')),
                'fields' => [['b', $unit, $b, self::EXACT], ['U', $unit, $U, self::EXACT]],
                'steps' => [
                    'b = A : a = '.self::u($A, $unit.'²').' : '.self::u($a, $unit).' = '.self::b(self::u($b, $unit)),
                    'U = 2 · (a + b) = 2 · ('.self::u($a, $unit).' + '.self::u($b, $unit).') = '.self::b(self::u($U, $unit)),
                ],
                'svg' => $svg,
            ];
        }

        return [
            'q' => self::fill('Das Rechteck hat den Umfang U = {0}. Berechne die Seite b und den Flächeninhalt A.', self::u($U, $unit)),
            'fields' => [['b', $unit, $b, self::EXACT], ['A', $unit.'²', $A, self::EXACT]],
            'steps' => [
                'b = U : 2 − a = '.self::u($U, $unit).' : 2 − '.self::u($a, $unit).' = '.self::b(self::u($b, $unit)),
                'A = a · b = '.self::u($a, $unit).' · '.self::u($b, $unit).' = '.self::b(self::u($A, $unit.'²')),
            ],
            'svg' => $svg,
        ];
    }

    /* ---------- Parallelogram (rhomboid) and rhombus ---------- */

    private function parallelogram(string $level): array
    {
        $variant = match ($level) {
            'easy' => Util::pick(['rhomboid', 'diagonals']),
            'medium' => Util::pick(['inverse', 'rhombus', 'diagonalF']),
            default => Util::pick(['heights', 'units']),
        };
        $unit = self::unit($level);

        if ('diagonals' === $variant || 'diagonalF' === $variant || 'units' === $variant) {
            $e = 'units' === $variant ? self::rs(0.8, 2.4, 0.2) : mt_rand(3, 14);
            $f = 'units' === $variant ? mt_rand(4, 18) * 5 : mt_rand(3, 12);

            if ('diagonals' !== $variant && 'units' !== $variant) {
                $f = 2 * mt_rand(2, 6);
            }

            $fm = 'units' === $variant ? $f / 100 : $f;
            $de = 'units' === $variant ? $e * 10 : $e;
            $df = 'units' === $variant ? $f / 10 : $f;
            $A = round($de * $df / 2, 6);
            $hw = $e;
            $hh = 'units' === $variant ? max($fm, $e * 0.35) : $f;
            $L = [-$hw / 2, 0];
            $R = [$hw / 2, 0];
            $T = [0, $hh / 2];
            $Bt = [0, -$hh / 2];
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [$L, $Bt, $R, $T]]], 'violet')
                ->line($L, $R)
                ->line($T, $Bt)
                ->right([0, 0], $R, $T)
                ->label([$hw / 4, 0], 'e = '.('units' === $variant ? self::u($e, 'm') : self::u($e, $unit)), 'given', [$hw / 4, 1], 12);

            if ('diagonalF' === $variant) {
                $svg->label([0, $hh / 4], 'f = ?', 'ask', [1, $hh / 4], 14, 'q')
                    ->label([0, $hh / 4], 'f = '.self::u($f, $unit), 'sol', [1, $hh / 4], 14, 's');

                return [
                    'q' => self::fill('Die Raute hat den Flächeninhalt A = {0}. Berechne die Diagonale f.', self::u($A, $unit.'²')),
                    'fields' => [['f', $unit, $f, self::EXACT]],
                    'steps' => [
                        self::p('Aus A = e · f : 2 folgt f = 2 · A : e.'),
                        'f = 2 · '.self::u($A, $unit.'²').' : '.self::u($e, $unit).' = '.self::b(self::u($f, $unit)),
                    ],
                    'svg' => $svg,
                ];
            }

            if ('units' === $variant) {
                $svg->label([0, $hh / 4], 'f = '.self::u($f, 'cm'), 'given', [1, $hh / 4], 14);

                return [
                    'q' => self::fill('Berechne den Flächeninhalt A der Raute in {0}.', 'dm²'),
                    'fields' => [['A', 'dm²', $A, self::EXACT]],
                    'steps' => [
                        self::p('Zuerst in dm umwandeln: {0}', 'e = '.self::u($e, 'm').' = '.self::u($de, 'dm').', f = '.self::u($f, 'cm').' = '.self::u($df, 'dm')),
                        'A = e · f : 2 = '.self::u($de, 'dm').' · '.self::u($df, 'dm').' : 2 = '.self::b(self::u($A, 'dm²')),
                    ],
                    'svg' => $svg,
                ];
            }

            $svg->label([0, $hh / 4], 'f = '.self::u($f, $unit), 'given', [1, $hh / 4], 14);

            return [
                'q' => 'Berechne den Flächeninhalt A der Raute.',
                'fields' => [['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Die Raute ist halb so gross wie das Rechteck aus den Diagonalen.'),
                    'A = e · f : 2 = '.self::u($e, $unit).' · '.self::u($f, $unit).' : 2 = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('heights' === $variant) {
            for ($i = 0; $i < 200; ++$i) {
                [$s, $h1, $b] = self::triple(20);
                $a = mt_rand(max(4, $b - 4), $b + 8);
                $h2 = $a * $h1 / $b;

                if (self::oneDecimal($h2) && $a * $s <= 0.85 * $b * $b) {
                    break;
                }
            }

            $A = $a * $h1;
            $P = [[0, 0], [$a, 0], [$a + $s, $h1], [$s, $h1]];
            $t = $a * $s / ($b * $b);
            $F2 = [$s * $t, $h1 * $t];
            $mid = [($a + $s) / 2, $h1 / 2];
            $svg = (new FigureAreaSvg())
                ->shape([['poly', $P]], 'violet')
                ->line($P[3], [$s, 0])
                ->right([$s, 0], $P[3], $P[1])
                ->line($P[1], $F2)
                ->right($F2, $P[1], [0, 0])
                ->side($P[0], $P[1], 'a = '.self::u($a, 'cm'), $P[3])
                ->side($P[0], $P[3], 'b = '.self::u($b, 'cm'), $mid)
                ->side($P[3], [$s, 0], 'h₁ = '.self::u($h1, 'cm'), $P[0], 'given', 'b', 0.3)
                ->sideAsk($P[1], $F2, 'h₂', self::u($h2, 'cm'), $P[3]);

            if ($t > 1) {
                $svg->line($P[3], $F2, 'thin');
            }

            return [
                'q' => 'h₁ ist die Höhe auf die Seite a, h₂ die Höhe auf die Seite b. Berechne den Flächeninhalt A und die Höhe h₂.',
                'fields' => [['A', 'cm²', $A, self::EXACT], ['h₂', 'cm', $h2, self::EXACT]],
                'steps' => [
                    'A = a · h₁ = '.self::u($a, 'cm').' · '.self::u($h1, 'cm').' = '.self::b(self::u($A, 'cm²')),
                    self::p('Der Flächeninhalt ist mit jeder Seite gleich: A = b · h₂, also h₂ = A : b.'),
                    'h₂ = '.self::u($A, 'cm²').' : '.self::u($b, 'cm').' = '.self::b(self::u($h2, 'cm')),
                ],
                'svg' => $svg,
            ];
        }

        if ('rhombus' === $variant) {
            [$s, $h, $a] = self::triple(20);
            $A = $a * $h;
            $P = [[0, 0], [$a, 0], [$a + $s, $h], [$s, $h]];
            $svg = (new FigureAreaSvg())
                ->shape([['poly', $P]], 'violet')
                ->line($P[3], [$s, 0])
                ->right([$s, 0], $P[3], $P[1])
                ->side($P[0], $P[1], 'a = '.self::u($a, $unit), $P[3])
                ->side($P[1], $P[2], 'a', $P[0])
                ->side($P[3], [$s, 0], 'h = '.self::u($h, $unit), $P[0], 'given', 'b', 0.6);

            return [
                'q' => 'Die Figur ist eine Raute (alle Seiten gleich lang). Berechne den Flächeninhalt A.',
                'fields' => [['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Die Raute ist ein Parallelogramm: Grundseite mal Höhe.'),
                    'A = a · h = '.self::u($a, $unit).' · '.self::u($h, $unit).' = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        // Rhomboid with the slanted side as a distractor
        [$s, $h, $b] = self::triple(15);
        $g = 'easy' === $level ? mt_rand(max(4, $s + 2), 16) : self::rs(max(4, $s + 2), 16);
        $A = round($g * $h, 6);
        $P = [[0, 0], [$g, 0], [$g + $s, $h], [$s, $h]];
        $svg = (new FigureAreaSvg())
            ->shape([['poly', $P]], 'violet')
            ->line($P[3], [$s, 0])
            ->right([$s, 0], $P[3], $P[1])
            ->side($P[0], $P[1], 'g = '.self::u($g, $unit), $P[3])
            ->side($P[1], $P[2], 'b = '.self::u($b, $unit), $P[0]);

        if ('inverse' === $variant) {
            $svg->sideAsk($P[3], [$s, 0], 'h', self::u($h, $unit), $P[0], 0.6);

            return [
                'q' => self::fill('Das Parallelogramm hat den Flächeninhalt A = {0}. Berechne die Höhe h.', self::u($A, $unit.'²')),
                'fields' => [['h', $unit, $h, self::EXACT]],
                'steps' => [
                    self::p('Aus A = g · h folgt h = A : g.'),
                    'h = '.self::u($A, $unit.'²').' : '.self::u($g, $unit).' = '.self::b(self::u($h, $unit)),
                ],
                'svg' => $svg,
            ];
        }

        $svg->side($P[3], [$s, 0], 'h = '.self::u($h, $unit), $P[0], 'given', 'b', 0.6);

        return [
            'q' => 'Berechne den Flächeninhalt A des Parallelogramms.',
            'fields' => [['A', $unit.'²', $A, self::EXACT]],
            'steps' => [
                self::p('Grundseite mal Höhe; die schräge Seite b brauchst du nicht.'),
                'A = g · h = '.self::u($g, $unit).' · '.self::u($h, $unit).' = '.self::b(self::u($A, $unit.'²')),
            ],
            'svg' => $svg,
        ];
    }

    /* ---------- Trapezoid ---------- */

    private function trapezoid(string $level): array
    {
        $variant = match ($level) {
            'easy' => 'area',
            'medium' => Util::pick(['area', 'inverseH', 'inverseC']),
            default => Util::pick(['units', 'difference', 'midline']),
        };
        $unit = self::unit($level);
        $int = 'easy' === $level || 'difference' === $variant;
        $a = $int ? mt_rand(6, 16) : self::rs(5, 16);
        $c = $int ? mt_rand(max(2, (int) ceil($a * 0.3)), $a - 2) : self::rs(max(2, ceil($a * 0.3)), $a - 1.5);
        $h = $int ? mt_rand(3, 9) : self::rs(2.5, 9);
        $aCm = $cCm = $hCm = 0;

        if ('units' === $variant) {
            $a = self::rs(1.6, 4.8, 0.2);
            $cCm = mt_rand(3, (int) round($a * 100 / 20) - 1) * 20;
            $hCm = mt_rand(3, 9) * 10;
            $c = $cCm / 100;
            $h = $hCm / 100;
        }

        $A = round(($a + $c) / 2 * $h, 6);
        $x1 = ($a - $c) * mt_rand(15, 85) / 100;
        $hd = 'units' === $variant ? max($h, $a / 3) : $h;
        $P = [[0, 0], [$a, 0], [$x1 + $c, $hd], [$x1, $hd]];
        $foot = [$x1, 0];
        $svg = (new FigureAreaSvg())
            ->shape([['poly', $P]], 'green')
            ->line($P[3], $foot)
            ->right($foot, $P[3], $P[1]);
        $f = 'A = (a + c) : 2 · h';

        if ('units' === $variant) {
            $svg->side($P[0], $P[1], 'a = '.self::u($a, 'm'), $P[3])
                ->side($P[3], $P[2], 'c = '.self::u($cCm, 'cm'), $P[0])
                ->side($P[3], $foot, 'h = '.self::u($hCm, 'cm'), $P[0], 'given', 'b', 0.6);

            return [
                'q' => self::fill('Berechne den Flächeninhalt A des Trapezes in {0}.', 'm²'),
                'fields' => [['A', 'm²', $A, self::EXACT]],
                'steps' => [
                    self::p('Zuerst in die gleiche Einheit umwandeln: {0}', 'c = '.self::u($cCm, 'cm').' = '.self::u($c, 'm').', h = '.self::u($hCm, 'cm').' = '.self::u($h, 'm')),
                    $f.' = ('.self::u($a, 'm').' + '.self::u($c, 'm').') : 2 · '.self::u($h, 'm').' = '.self::b(self::u($A, 'm²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('midline' === $variant) {
            $m = ($a + $c) / 2;
            $M1 = [$x1 / 2, $h / 2];
            $M2 = [($a + $x1 + $c) / 2, $h / 2];
            $svg->line($M1, $M2, 'thin')
                ->label($M2, 'm = '.self::u($m, $unit), 'given', $M1, 12)
                ->side($P[3], $foot, 'h = '.self::u($h, $unit), $P[0], 'given', 'b', 0.6);

            return [
                'q' => 'm ist die Mittellinie des Trapezes. Berechne den Flächeninhalt A.',
                'fields' => [['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Die Mittellinie ist der Durchschnitt der parallelen Seiten: m = (a + c) : 2.'),
                    'A = m · h = '.self::u($m, $unit).' · '.self::u($h, $unit).' = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('difference' === $variant) {
            $d = $a - $c;
            $svg->sideAsk($P[0], $P[1], 'a', self::u($a, $unit), $P[3])
                ->sideAsk($P[3], $P[2], 'c', self::u($c, $unit), $P[0])
                ->side($P[3], $foot, 'h = '.self::u($h, $unit), $P[0], 'given', 'b', 0.6);

            return [
                'q' => self::fill('Das Trapez hat den Flächeninhalt A = {0}. Die Seite a ist {1} länger als die Seite c. Berechne c und a.', self::u($A, $unit.'²'), self::u($d, $unit)),
                'fields' => [['c', $unit, $c, self::EXACT], ['a', $unit, $a, self::EXACT]],
                'steps' => [
                    'a + c = 2 · A : h = 2 · '.self::u($A, $unit.'²').' : '.self::u($h, $unit).' = '.self::u($a + $c, $unit),
                    self::p('Ziehe den Unterschied ab und halbiere: {0}', 'c = ('.self::u($a + $c, $unit).' − '.self::u($d, $unit).') : 2 = '.self::b(self::u($c, $unit))),
                    'a = '.self::u($c, $unit).' + '.self::u($d, $unit).' = '.self::b(self::u($a, $unit)),
                ],
                'svg' => $svg,
            ];
        }

        $svg->side($P[0], $P[1], 'a = '.self::u($a, $unit), $P[3]);

        if ('inverseC' === $variant) {
            $svg->sideAsk($P[3], $P[2], 'c', self::u($c, $unit), $P[0])
                ->side($P[3], $foot, 'h = '.self::u($h, $unit), $P[0], 'given', 'b', 0.6);

            return [
                'q' => self::fill('Das Trapez hat den Flächeninhalt A = {0}. Berechne die Seite c.', self::u($A, $unit.'²')),
                'fields' => [['c', $unit, $c, self::EXACT]],
                'steps' => [
                    'a + c = 2 · A : h = 2 · '.self::u($A, $unit.'²').' : '.self::u($h, $unit).' = '.self::u($a + $c, $unit),
                    'c = '.self::u($a + $c, $unit).' − '.self::u($a, $unit).' = '.self::b(self::u($c, $unit)),
                ],
                'svg' => $svg,
            ];
        }

        $svg->side($P[3], $P[2], 'c = '.self::u($c, $unit), $P[0]);

        if ('inverseH' === $variant) {
            $svg->sideAsk($P[3], $foot, 'h', self::u($h, $unit), $P[0], 0.6);

            return [
                'q' => self::fill('Das Trapez hat den Flächeninhalt A = {0}. Berechne die Höhe h.', self::u($A, $unit.'²')),
                'fields' => [['h', $unit, $h, self::EXACT]],
                'steps' => [
                    self::p('Aus A = (a + c) : 2 · h folgt h = A : ((a + c) : 2).'),
                    '(a + c) : 2 = ('.self::u($a, $unit).' + '.self::u($c, $unit).') : 2 = '.self::u(($a + $c) / 2, $unit),
                    'h = '.self::u($A, $unit.'²').' : '.self::u(($a + $c) / 2, $unit).' = '.self::b(self::u($h, $unit)),
                ],
                'svg' => $svg,
            ];
        }

        $svg->side($P[3], $foot, 'h = '.self::u($h, $unit), $P[0], 'given', 'b', 0.6);

        return [
            'q' => 'Berechne den Flächeninhalt A des Trapezes.',
            'fields' => [['A', $unit.'²', $A, self::EXACT]],
            'steps' => [
                self::p('Durchschnitt der parallelen Seiten mal Höhe.'),
                $f.' = ('.self::u($a, $unit).' + '.self::u($c, $unit).') : 2 · '.self::u($h, $unit).' = '.self::b(self::u($A, $unit.'²')),
            ],
            'svg' => $svg,
        ];
    }

    /* ---------- Circle ---------- */

    private function circle(string $level): array
    {
        $variant = match ($level) {
            'easy' => 'radius',
            'medium' => Util::pick(['diameter', 'half', 'quarter', 'fromU']),
            default => Util::pick(['ring', 'sector', 'fromA']),
        };
        $unit = self::unit($level);
        $r = 'easy' === $level ? mt_rand(2, 12) : mt_rand(2, 20);
        $pi = 'π · r² = π · ('.self::u($r, $unit).')²';
        $A = M_PI * $r * $r;

        if ('half' === $variant || 'quarter' === $variant) {
            $half = 'half' === $variant;
            $Ap = $A / ($half ? 2 : 4);
            $svg = (new FigureAreaSvg())->dot([0, 0]);

            if ($half) {
                $svg->shape([['path', [['arc', [0, 0], $r, 0, 180]]]], 'pink')
                    ->side([-$r, 0], [$r, 0], 'd = '.self::u(2 * $r, $unit), [0, $r]);
            } else {
                $svg->shape([['path', [['M', [0, 0]], ['L', [$r, 0]], ['arc', [0, 0], $r, 0, 90]]]], 'pink')
                    ->right([0, 0], [1, 0], [0, 1])
                    ->side([0, 0], [$r, 0], 'r = '.self::u($r, $unit), [0, $r]);
            }

            return [
                'q' => $half ? 'Berechne den Flächeninhalt A des Halbkreises.' : 'Berechne den Flächeninhalt A des Viertelkreises.',
                'fields' => [['A', $unit.'²', round($Ap, 1), [0.051, 0.0006]]],
                'steps' => array_values(array_filter([
                    $half ? 'r = d : 2 = '.self::u(2 * $r, $unit).' : 2 = '.self::u($r, $unit) : null,
                    'A = '.$pi.' : '.($half ? 2 : 4).' ≈ '.self::b(self::u(round($Ap, 1), $unit.'²')),
                ])),
                'svg' => $svg,
            ];
        }

        if ('ring' === $variant) {
            $r = mt_rand(3, 10);
            $R = $r + mt_rand(2, 8);
            $pi = 'π · r² = π · ('.self::u($r, $unit).')²';
            $Ar = M_PI * ($R * $R - $r * $r);
            $svg = (new FigureAreaSvg())
                ->shape([['circle', [0, 0], $R], ['circle', [0, 0], $r]], 'pink')
                ->dot([0, 0])
                ->line([0, 0], [$R * cos(deg2rad(40)), $R * sin(deg2rad(40))])
                ->line([0, 0], [$r * cos(deg2rad(-140)), $r * sin(deg2rad(-140))])
                ->label([$R * 0.95 * cos(deg2rad(40)), $R * 0.95 * sin(deg2rad(40))], 'R = '.self::u($R, $unit), 'given', [0, 0], 14)
                ->label([$r * 0.6 * cos(deg2rad(-140)), $r * 0.6 * sin(deg2rad(-140))], 'r = '.self::u($r, $unit), 'given', [$R, 0], 6);

            return [
                'q' => 'Berechne den Flächeninhalt A des Kreisrings.',
                'fields' => [['A', $unit.'²', round($Ar, 1), self::ROUND]],
                'steps' => [
                    self::p('Grosser Kreis minus kleiner Kreis.'),
                    'A = π · R² − π · r² = π · ('.self::u($R, $unit).')² − π · ('.self::u($r, $unit).')² ≈ '.self::b(self::u(round($Ar, 1), $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('sector' === $variant) {
            $alpha = Util::pick([30, 40, 45, 60, 72, 80, 100, 120, 135, 150, 210, 240, 270, 300]);
            $As = $A * $alpha / 360;
            $start = 90 - $alpha / 2;
            $svg = (new FigureAreaSvg())
                ->shape([['path', [['M', [0, 0]], ['L', [$r * cos(deg2rad($start)), $r * sin(deg2rad($start))]], ['arc', [0, 0], $r, $start, $start + $alpha]]]], 'pink')
                ->dot([0, 0])
                ->side([0, 0], [$r * cos(deg2rad($start)), $r * sin(deg2rad($start))], 'r = '.self::u($r, $unit), [0, $r])
                ->label([0, ($alpha < 90 ? 0.6 : 0.35) * $r], 'α = '.$alpha.'°', 'given');

            return [
                'q' => 'Berechne den Flächeninhalt A des Kreissektors.',
                'fields' => [['A', $unit.'²', round($As, 1), self::ROUND]],
                'steps' => [
                    self::p('Der Sektor ist der Teil {0} des ganzen Kreises.', $alpha.'° : 360°'),
                    'A = π · r² · α : 360° = π · ('.self::u($r, $unit).')² · '.$alpha.'° : 360° ≈ '.self::b(self::u(round($As, 1), $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        $svg = (new FigureAreaSvg())
            ->shape([['circle', [0, 0], $r]], 'pink')
            ->dot([0, 0]);

        if ('fromA' === $variant || 'fromU' === $variant) {
            $svg->line([0, 0], [$r, 0])
                ->sideAsk([0, 0], [$r, 0], 'r', self::u($r, $unit), [0, -$r]);

            if ('fromA' === $variant) {
                $given = round($A, 1);

                return [
                    'q' => self::fill('Der Kreis hat den Flächeninhalt A = {0}. Berechne den Radius r.', self::u($given, $unit.'²')),
                    'fields' => [['r', $unit, $r, self::ROUND]],
                    'steps' => [
                        self::p('Aus A = π · r² folgt r = √(A : π).'),
                        'r = √('.self::u($given, $unit.'²').' : π) ≈ '.self::b(self::u($r, $unit)),
                    ],
                    'svg' => $svg,
                ];
            }

            $U = round(2 * M_PI * $r, 1);
            $rr = round($U / (2 * M_PI), 1);

            return [
                'q' => self::fill('Der Kreis hat den Umfang U = {0}. Berechne den Radius r und den Flächeninhalt A.', self::u($U, $unit)),
                'fields' => [['r', $unit, round($U / (2 * M_PI), 1), self::ROUND], ['A', $unit.'²', round($A, 1), self::roundTol($A, M_PI * $rr * $rr)]],
                'steps' => [
                    self::p('Aus U = 2 · π · r folgt r = U : (2 · π).'),
                    'r = '.self::u($U, $unit).' : (2 · π) ≈ '.self::b(self::u($rr, $unit)),
                    'A = '.$pi.' ≈ '.self::b(self::u(round($A, 1), $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        $U = 2 * M_PI * $r;

        if ('diameter' === $variant) {
            $svg->line([-$r, 0], [$r, 0])->side([-$r, 0], [$r, 0], 'd = '.self::u(2 * $r, $unit), [0, -$r]);
            $steps = ['r = d : 2 = '.self::u(2 * $r, $unit).' : 2 = '.self::u($r, $unit)];
        } else {
            $svg->line([0, 0], [$r, 0])->side([0, 0], [$r, 0], 'r = '.self::u($r, $unit), [0, -$r]);
            $steps = [];
        }

        $steps[] = 'A = '.$pi.' ≈ '.self::b(self::u(round($A, 1), $unit.'²'));
        $steps[] = 'U = 2 · π · r = 2 · π · '.self::u($r, $unit).' ≈ '.self::b(self::u(round($U, 1), $unit));

        return [
            'q' => 'Berechne den Flächeninhalt A und den Umfang U des Kreises.',
            'fields' => [['A', $unit.'²', round($A, 1), self::ROUND], ['U', $unit, round($U, 1), self::ROUND]],
            'steps' => $steps,
            'svg' => $svg,
        ];
    }

    /* ---------- Composed figures ---------- */

    private function composed(string $level): array
    {
        $variant = match ($level) {
            'easy' => Util::pick(['grid', 'lshape', 'house', 'tshape', 'stairs', 'arrow']),
            'medium' => Util::pick(['frame', 'tunnel', 'notch', 'ushape', 'star', 'gridpoly', 'hall', 'workpiece', 'roundsquare', 'coordPoly']),
            default => Util::pick(['quarter', 'track', 'holes', 'circleInSquare', 'squareInCircle', 'lens', 'semicut', 'roundsquare', 'coordArc']),
        };
        $unit = 'easy' === $level ? 'cm' : Util::pick(['cm', 'm']);
        $q = 'Berechne den Flächeninhalt A der farbigen Fläche.';

        if (\in_array($variant, self::MORE_COMPOSED, true)) {
            return $this->composedMore($variant, $unit);
        }

        if ('grid' === $variant || 'lshape' === $variant) {
            $W = mt_rand(5, 11);
            $H = mt_rand(4, 8);
            $w1 = mt_rand(2, $W - 2);
            $h2 = mt_rand(1, $H - 2);
            $A1 = $w1 * $H;
            $A2 = ($W - $w1) * $h2;
            $A = $A1 + $A2;
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$W, 0], [$W, $h2], [$w1, $h2], [$w1, $H], [0, $H]]]], 'teal')
                ->line([$w1, 0], [$w1, $h2], 'sol', 's')
                ->label([$w1 / 2, $H / 2], 'A₁', 'part', null, 0, 's')
                ->label([($W + $w1) / 2, $h2 / 2], 'A₂', 'part', null, 0, 's');

            if ('grid' === $variant) {
                $svg->grid(1);
                $q = 'Jedes Häuschen ist 1 cm lang. Berechne den Flächeninhalt A der farbigen Fläche.';
            } else {
                $svg->dim([0, 0], [$W, 0], self::u($W, $unit), -1)
                    ->dim([0, 0], [0, $H], self::u($H, $unit), 1)
                    ->dim([0, $H], [$w1, $H], self::u($w1, $unit), 1)
                    ->dim([$W, 0], [$W, $h2], self::u($h2, $unit), -1);
            }

            return [
                'q' => $q,
                'fields' => [['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Zerlege die Figur in zwei Rechtecke.'),
                    'A₁ = '.self::u($w1, $unit).' · '.self::u($H, $unit).' = '.self::u($A1, $unit.'²'),
                    'A₂ = ('.self::u($W, $unit).' − '.self::u($w1, $unit).') · '.self::u($h2, $unit).' = '.self::u($W - $w1, $unit).' · '.self::u($h2, $unit).' = '.self::u($A2, $unit.'²'),
                    'A = A₁ + A₂ = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('house' === $variant) {
            $W = 2 * mt_rand(2, 6);
            $H = mt_rand(3, 8);
            $t = mt_rand(2, 6);
            $A1 = $W * $H;
            $A2 = $W * $t / 2;
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$W, 0], [$W, $H], [$W / 2, $H + $t], [0, $H]]]], 'orange')
                ->line([0, $H], [$W, $H], 'sol', 's')
                ->line([$W / 2, $H], [$W / 2, $H + $t], 'thin')
                ->right([$W / 2, $H], [$W, $H], [$W / 2, $H + $t])
                ->label([$W / 2, $H / 2], 'A₁', 'part', null, 0, 's')
                ->label([$W / 3, $H + $t / 3], 'A₂', 'part', null, 0, 's')
                ->dim([0, 0], [$W, 0], self::u($W, $unit), -1)
                ->dim([$W, 0], [$W, $H], self::u($H, $unit), -1)
                ->dim([$W, $H], [$W, $H + $t], self::u($t, $unit), -1);

            return [
                'q' => $q,
                'fields' => [['A', $unit.'²', $A1 + $A2, self::EXACT]],
                'steps' => [
                    self::p('Zerlege die Figur in ein Rechteck und ein Dreieck.'),
                    'A₁ = '.self::u($W, $unit).' · '.self::u($H, $unit).' = '.self::u($A1, $unit.'²'),
                    'A₂ = '.self::u($W, $unit).' · '.self::u($t, $unit).' : 2 = '.self::u($A2, $unit.'²'),
                    'A = A₁ + A₂ = '.self::b(self::u($A1 + $A2, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('frame' === $variant) {
            $W = mt_rand(8, 16);
            $H = mt_rand(6, 12);
            $w = mt_rand(2, $W - 4);
            $h = mt_rand(2, $H - 4);
            $x = mt_rand(1, $W - $w - 1);
            $y = mt_rand(1, $H - $h - 1);
            $A = $W * $H - $w * $h;
            $in = [[$x, $y], [$x + $w, $y], [$x + $w, $y + $h], [$x, $y + $h]];
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$W, 0], [$W, $H], [0, $H]]], ['poly', $in]], 'teal')
                ->dim([0, 0], [$W, 0], self::u($W, $unit), -1)
                ->dim([0, 0], [0, $H], self::u($H, $unit), 1)
                ->side($in[3], $in[2], self::u($w, $unit), $in[0])
                ->side($in[1], $in[2], self::u($h, $unit), $in[3]);

            return [
                'q' => $q,
                'fields' => [['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Grosses Rechteck minus das Loch.'),
                    'A = '.self::u($W, $unit).' · '.self::u($H, $unit).' − '.self::u($w, $unit).' · '.self::u($h, $unit).' = '.self::u($W * $H, $unit.'²').' − '.self::u($w * $h, $unit.'²').' = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('notch' === $variant) {
            $W = mt_rand(8, 14);
            $H = mt_rand(5, 9);
            $bw = 2 * mt_rand(1, (int) floor(($W - 2) / 2));
            $t = mt_rand(2, $H - 2);
            $x1 = mt_rand(1, $W - $bw - 1);
            $A = $W * $H - $bw * $t / 2;
            $tip = [$x1 + $bw / 2, $H - $t];
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$W, 0], [$W, $H], [$x1 + $bw, $H], $tip, [$x1, $H], [0, $H]]]], 'orange')
                ->line([$x1, $H], [$x1 + $bw, $H], 'thin')
                ->line($tip, [$tip[0], $H], 'thin')
                ->dim([0, 0], [$W, 0], self::u($W, $unit), -1)
                ->dim([0, 0], [0, $H], self::u($H, $unit), 1)
                ->dim([$x1, $H], [$x1 + $bw, $H], self::u($bw, $unit), 1)
                ->side($tip, [$tip[0], $H], self::u($t, $unit), [$x1 + $bw, $H]);

            return [
                'q' => $q,
                'fields' => [['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Rechteck minus das ausgeschnittene Dreieck.'),
                    'A = '.self::u($W, $unit).' · '.self::u($H, $unit).' − '.self::u($bw, $unit).' · '.self::u($t, $unit).' : 2 = '.self::u($W * $H, $unit.'²').' − '.self::u($bw * $t / 2, $unit.'²').' = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('tunnel' === $variant) {
            $W = 2 * mt_rand(2, 7);
            $H = mt_rand(3, 10);
            $r = $W / 2;
            $A1 = $W * $H;
            $A2 = M_PI * $r * $r / 2;
            $A = round($A1 + $A2, 1);
            $svg = (new FigureAreaSvg())
                ->shape([['path', [['M', [0, 0]], ['L', [$W, 0]], ['L', [$W, $H]], ['arc', [$r, $H], $r, 0, 180]]]], 'blue')
                ->line([0, $H], [$W, $H], 'sol', 's')
                ->label([$r, $H / 2], 'A₁', 'part', null, 0, 's')
                ->label([$r, $H + $r / 2], 'A₂', 'part', null, 0, 's')
                ->dim([0, 0], [$W, 0], self::u($W, $unit), -1)
                ->dim([0, 0], [0, $H], self::u($H, $unit), 1);

            return [
                'q' => $q,
                'fields' => [['A', $unit.'²', $A, self::ROUND]],
                'steps' => [
                    self::p('Zerlege die Figur in ein Rechteck und einen Halbkreis mit r = {0}.', self::u($r, $unit)),
                    'A₁ = '.self::u($W, $unit).' · '.self::u($H, $unit).' = '.self::u($A1, $unit.'²'),
                    'A₂ = π · ('.self::u($r, $unit).')² : 2 ≈ '.self::u(round($A2, 2), $unit.'²'),
                    'A = A₁ + A₂ ≈ '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('quarter' === $variant) {
            $a = mt_rand(4, 12);
            $A = $a * $a - M_PI * $a * $a / 4;
            $svg = (new FigureAreaSvg())
                ->shape([['path', [['M', [$a, 0]], ['L', [$a, $a]], ['L', [0, $a]], ['arc', [0, 0], $a, 90, 0]]]], 'orange')
                ->line([0, 0], [$a, 0], 'thin')
                ->line([0, 0], [0, $a], 'thin')
                ->dot([0, 0])
                ->side([0, $a], [$a, $a], 'a = '.self::u($a, $unit), [0, 0])
                ->side([$a, 0], [$a, $a], 'a', [0, 0])
                ->label([0.35 * $a, 0.35 * $a], 'r = a', 'note');

            return [
                'q' => 'Der Viertelkreis hat den Radius a. Berechne den Flächeninhalt A der farbigen Fläche.',
                'fields' => [['A', $unit.'²', round($A, 1), self::ROUND]],
                'steps' => [
                    self::p('Quadrat minus Viertelkreis.'),
                    'A = a² − π · a² : 4 = ('.self::u($a, $unit).')² − π · ('.self::u($a, $unit).')² : 4 ≈ '.self::u($a * $a, $unit.'²').' − '.self::u(round(M_PI * $a * $a / 4, 2), $unit.'²').' ≈ '.self::b(self::u(round($A, 1), $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('track' === $variant) {
            $L = mt_rand(6, 20);
            $r = mt_rand(2, 6);
            $A1 = $L * 2 * $r;
            $A2 = M_PI * $r * $r;
            $A = round($A1 + $A2, 1);
            $svg = (new FigureAreaSvg())
                ->shape([['path', [['M', [0, 0]], ['L', [$L, 0]], ['arc', [$L, $r], $r, -90, 90], ['L', [0, 2 * $r]], ['arc', [0, $r], $r, 90, 270]]]], 'green')
                ->line([0, 0], [0, 2 * $r], 'sol', 's')
                ->line([$L, 0], [$L, 2 * $r], 'thin')
                ->label([$L / 2, $r], 'A₁', 'part', null, 0, 's')
                ->dim([0, 0], [$L, 0], self::u($L, $unit), -1)
                ->dim([$L + $r, 0], [$L + $r, 2 * $r], 'd = '.self::u(2 * $r, $unit), -1);

            return [
                'q' => $q,
                'fields' => [['A', $unit.'²', $A, self::ROUND]],
                'steps' => [
                    self::p('Rechteck in der Mitte und zwei Halbkreise, die zusammen einen ganzen Kreis mit r = {0} ergeben.', self::u($r, $unit)),
                    'A₁ = '.self::u($L, $unit).' · '.self::u(2 * $r, $unit).' = '.self::u($A1, $unit.'²'),
                    'A₂ = π · ('.self::u($r, $unit).')² ≈ '.self::u(round($A2, 2), $unit.'²'),
                    'A = A₁ + A₂ ≈ '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        // Rectangle with two round holes
        $d = 2 * mt_rand(1, 3);
        $W = 2 * $d + mt_rand(4, 10);
        $H = $d + mt_rand(2, 6);
        $r = $d / 2;
        $c1 = [$W / 4, $H / 2];
        $c2 = [3 * $W / 4, $H / 2];
        $A = $W * $H - 2 * M_PI * $r * $r;
        $svg = (new FigureAreaSvg())
            ->shape([['poly', [[0, 0], [$W, 0], [$W, $H], [0, $H]]], ['circle', $c1, $r], ['circle', $c2, $r]], 'teal')
            ->line([$c2[0] - $r, $c2[1]], [$c2[0] + $r, $c2[1]])
            ->label([$c2[0], $c2[1] + $r], 'd = '.self::u($d, $unit), 'given', $c2, 12)
            ->dim([0, 0], [$W, 0], self::u($W, $unit), -1)
            ->dim([0, 0], [0, $H], self::u($H, $unit), 1);

        return [
            'q' => 'Das Rechteck hat zwei gleich grosse runde Löcher. Berechne den Flächeninhalt A der farbigen Fläche.',
            'fields' => [['A', $unit.'²', round($A, 1), self::ROUND]],
            'steps' => [
                self::p('Rechteck minus zwei Kreise mit r = {0}.', self::u($r, $unit)),
                'A = '.self::u($W, $unit).' · '.self::u($H, $unit).' − 2 · π · ('.self::u($r, $unit).')² ≈ '.self::u($W * $H, $unit.'²').' − '.self::u(round(2 * M_PI * $r * $r, 2), $unit.'²').' ≈ '.self::b(self::u(round($A, 1), $unit.'²')),
            ],
            'svg' => $svg,
        ];
    }

    /* ---------- Areas with Pythagoras ---------- */

    private function pythagoras(string $level): array
    {
        $variant = match ($level) {
            'easy' => 'right',
            'medium' => Util::pick(['rectangle', 'isosceles', 'rhombus', 'pyHouse']),
            default => Util::pick(['trapezoid', 'equilateral', 'rightTrapezoid', 'pyWorkpiece', 'pyWorkpiece']),
        };
        $unit = Util::pick(['cm', 'cm', 'm']);

        if ('pyHouse' === $variant || 'pyWorkpiece' === $variant) {
            return $this->pyComposed('pyWorkpiece' === $variant, $unit);
        }
        $pyth = static fn (string $x): string => self::fill('Berechne zuerst {0} mit dem Satz des Pythagoras, dann den Flächeninhalt A.', $x);

        if ('right' === $variant) {
            [$a, $b, $c] = self::triple(30);
            $A = $a * $b / 2;
            $C = [0, 0];
            $B = [$a, 0];
            $P = [0, $b];
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [$C, $B, $P]]], 'yellow')
                ->right($C, $B, $P)
                ->side($C, $B, 'a = '.self::u($a, $unit), $P)
                ->side($B, $P, 'c = '.self::u($c, $unit), $C)
                ->sideAsk($C, $P, 'b', self::u($b, $unit), $B);

            return [
                'q' => $pyth('b'),
                'fields' => [['b', $unit, $b, self::EXACT], ['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    'b² = c² − a² = ('.self::u($c, $unit).')² − ('.self::u($a, $unit).')² = '.self::u($c * $c - $a * $a, $unit.'²'),
                    'b = √'.self::u($c * $c - $a * $a, $unit.'²').' = '.self::b(self::u($b, $unit)),
                    'A = a · b : 2 = '.self::u($a, $unit).' · '.self::u($b, $unit).' : 2 = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('rectangle' === $variant) {
            [$a, $b, $d] = self::triple(30);
            $A = $a * $b;
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$a, 0], [$a, $b], [0, $b]]]], 'blue')
                ->line([0, 0], [$a, $b], 'edge')
                ->right([$a, 0], [0, 0], [$a, $b])
                ->side([0, 0], [$a, 0], 'a = '.self::u($a, $unit), [0, $b])
                ->label([$a / 2, $b / 2], 'd = '.self::u($d, $unit), 'given', [$a, 0], 14)
                ->sideAsk([$a, 0], [$a, $b], 'b', self::u($b, $unit), [0, 0]);

            return [
                'q' => $pyth('b'),
                'fields' => [['b', $unit, $b, self::EXACT], ['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Die Diagonale d teilt das Rechteck in zwei rechtwinklige Dreiecke.'),
                    'b = √(d² − a²) = √(('.self::u($d, $unit).')² − ('.self::u($a, $unit).')²) = '.self::b(self::u($b, $unit)),
                    'A = a · b = '.self::u($a, $unit).' · '.self::u($b, $unit).' = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('isosceles' === $variant) {
            [$x, $h, $s] = self::triple(26);
            $g = 2 * $x;
            $A = $g * $h / 2;
            $top = [$x, $h];
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$g, 0], $top]]], 'yellow')
                ->line($top, [$x, 0])
                ->right([$x, 0], $top, [$g, 0])
                ->side([0, 0], [$g, 0], 'c = '.self::u($g, $unit), $top)
                ->side([0, 0], $top, 's = '.self::u($s, $unit), [$g, 0])
                ->side([$g, 0], $top, 's', [0, 0])
                ->sideAsk($top, [$x, 0], 'h', self::u($h, $unit), [$g, 0], 0.72);

            return [
                'q' => 'Das Dreieck ist gleichschenklig. '.$pyth('h'),
                'fields' => [['h', $unit, $h, self::EXACT], ['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Die Höhe halbiert die Basis: {0}', 'c : 2 = '.self::u($x, $unit)),
                    'h = √(s² − (c : 2)²) = √(('.self::u($s, $unit).')² − ('.self::u($x, $unit).')²) = '.self::b(self::u($h, $unit)),
                    'A = c · h : 2 = '.self::u($g, $unit).' · '.self::u($h, $unit).' : 2 = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('rhombus' === $variant) {
            [$x, $y, $a] = self::triple(26);
            $e = 2 * $x;
            $f = 2 * $y;
            $A = $e * $f / 2;
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [[-$x, 0], [0, -$y], [$x, 0], [0, $y]]]], 'violet')
                ->line([-$x, 0], [$x, 0])
                ->line([0, $y], [0, -$y])
                ->right([0, 0], [$x, 0], [0, $y])
                ->side([0, $y], [$x, 0], 'a = '.self::u($a, $unit), [0, 0])
                ->label([$x / 2, 0], 'e = '.self::u($e, $unit), 'given', [$x / 2, 1], 12)
                ->label([0, -$y / 2], 'f = ?', 'ask', [1, -$y / 2], 14, 'q')
                ->label([0, -$y / 2], 'f = '.self::u($f, $unit), 'sol', [1, -$y / 2], 14, 's');

            return [
                'q' => 'Die Figur ist eine Raute. '.$pyth('f'),
                'fields' => [['f', $unit, $f, self::EXACT], ['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Die Diagonalen halbieren sich und stehen senkrecht aufeinander.'),
                    'f : 2 = √(a² − (e : 2)²) = √(('.self::u($a, $unit).')² − ('.self::u($x, $unit).')²) = '.self::u($y, $unit).', f = '.self::b(self::u($f, $unit)),
                    'A = e · f : 2 = '.self::u($e, $unit).' · '.self::u($f, $unit).' : 2 = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('equilateral' === $variant) {
            $s = 2 * mt_rand(2, 8);
            $h = $s * sqrt(3) / 2;
            $hr = round($h, 1);
            $A = $s * $h / 2;
            $top = [$s / 2, $h];
            $svg = (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$s, 0], $top]]], 'yellow')
                ->line($top, [$s / 2, 0])
                ->right([$s / 2, 0], $top, [$s, 0])
                ->side([0, 0], [$s, 0], 's = '.self::u($s, $unit), $top)
                ->side([0, 0], $top, 's', [$s, 0])
                ->side([$s, 0], $top, 's', [0, 0])
                ->sideAsk($top, [$s / 2, 0], 'h', self::u($hr, $unit), [$s, 0], 0.72);

            return [
                'q' => 'Das Dreieck ist gleichseitig. '.$pyth('h'),
                'fields' => [['h', $unit, $hr, self::ROUND], ['A', $unit.'²', round($A, 1), self::roundTol($A, $s * $hr / 2)]],
                'steps' => [
                    'h = √(s² − (s : 2)²) = √(('.self::u($s, $unit).')² − ('.self::u($s / 2, $unit).')²) = √'.self::u($s * $s * 3 / 4, $unit.'²').' ≈ '.self::b(self::u($hr, $unit)),
                    'A = s · h : 2 ≈ '.self::u($s, $unit).' · '.self::u($hr, $unit).' : 2 ≈ '.self::b(self::u(round($A, 1), $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        if ('rightTrapezoid' === $variant) {
            [$x, $h, $d] = self::triple(26);
            $c = mt_rand(2, 14);
            $a = $c + $x;
            $A = ($a + $c) * $h / 2;
            $P = [[0, 0], [$a, 0], [$c, $h], [0, $h]];
            $svg = (new FigureAreaSvg())
                ->shape([['poly', $P]], 'green')
                ->right([0, 0], [1, 0], [0, 1])
                ->right([0, $h], [1, $h], [0, 0])
                ->line([$c, $h], [$c, 0], 'thin')
                ->side($P[0], $P[1], 'a = '.self::u($a, $unit), $P[3])
                ->side($P[3], $P[2], 'c = '.self::u($c, $unit), $P[0])
                ->side($P[1], $P[2], 'd = '.self::u($d, $unit), $P[0])
                ->sideAsk($P[0], $P[3], 'h', self::u($h, $unit), $P[1]);

            return [
                'q' => 'Das Trapez hat zwei rechte Winkel. '.$pyth('h'),
                'fields' => [['h', $unit, $h, self::EXACT], ['A', $unit.'²', $A, self::EXACT]],
                'steps' => [
                    self::p('Das rechtwinklige Dreieck rechts hat die Seiten {0}, h und d.', 'a − c = '.self::u($x, $unit)),
                    'h = √(d² − (a − c)²) = √(('.self::u($d, $unit).')² − ('.self::u($x, $unit).')²) = '.self::b(self::u($h, $unit)),
                    'A = (a + c) : 2 · h = ('.self::u($a, $unit).' + '.self::u($c, $unit).') : 2 · '.self::u($h, $unit).' = '.self::b(self::u($A, $unit.'²')),
                ],
                'svg' => $svg,
            ];
        }

        // Isosceles trapezoid
        [$x, $h, $s] = self::triple(26);
        $c = mt_rand(2, 14);
        $a = $c + 2 * $x;
        $A = ($a + $c) * $h / 2;
        $P = [[0, 0], [$a, 0], [$x + $c, $h], [$x, $h]];
        $svg = (new FigureAreaSvg())
            ->shape([['poly', $P]], 'green')
            ->line($P[3], [$x, 0])
            ->right([$x, 0], $P[3], $P[1])
            ->side($P[0], $P[1], 'a = '.self::u($a, $unit), $P[3])
            ->side($P[3], $P[2], 'c = '.self::u($c, $unit), $P[0])
            ->side($P[0], $P[3], 's = '.self::u($s, $unit), $P[1])
            ->side($P[1], $P[2], 's', $P[0])
            ->sideAsk($P[3], [$x, 0], 'h', self::u($h, $unit), $P[0], 0.6);

        return [
            'q' => 'Das Trapez ist gleichschenklig. '.$pyth('h'),
            'fields' => [['h', $unit, $h, self::EXACT], ['A', $unit.'²', $A, self::EXACT]],
            'steps' => [
                self::p('Links und rechts ragt die Seite a um {0} über c hinaus.', '(a − c) : 2 = '.self::u($x, $unit)),
                'h = √(s² − ('.self::u($x, $unit).')²) = √(('.self::u($s, $unit).')² − ('.self::u($x, $unit).')²) = '.self::b(self::u($h, $unit)),
                'A = (a + c) : 2 · h = ('.self::u($a, $unit).' + '.self::u($c, $unit).') : 2 · '.self::u($h, $unit).' = '.self::b(self::u($A, $unit.'²')),
            ],
            'svg' => $svg,
        ];
    }
}
