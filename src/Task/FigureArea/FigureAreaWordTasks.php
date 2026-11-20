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
 * Word problems of the area tasks, one per figure and level. The question has no sketch,
 * the solution shows one.
 */
trait FigureAreaWordTasks
{
    private function word(string $kind, string $level): array
    {
        $i = array_search($level, self::LEVELS, true);

        return match ($kind) {
            'dreieck' => [$this->wSail(...), $this->wBed(...), $this->wGable(...)][$i](),
            'rechteck' => [$this->wRoom(...), $this->wGarden(...), $this->wTiles(...)][$i](),
            'parallelogramm' => [$this->wParaBed(...), $this->wWindow(...), $this->wParking(...)][$i](),
            'trapez' => [$this->wPlot(...), $this->wPlotPrice(...), $this->wPlotWidth(...)][$i](),
            'kreis' => [$this->wPizza(...), Util::pick([$this->wSprinkler(...), $this->wWheel(...)]), $this->wPond(...)][$i](),
            'zusammengesetzt' => [$this->wWall(...), $this->wPaint(...), $this->wLawn(...)][$i](),
            default => [$this->wScreen(...), $this->wRoof(...), $this->wKite(...)][$i](),
        };
    }

    private static function sketchRect(float $a, float $b, string $la, string $lb, string $fill = 'blue'): FigureAreaSvg
    {
        return (new FigureAreaSvg())
            ->shape([['poly', [[0, 0], [$a, 0], [$a, $b], [0, $b]]]], $fill)
            ->right([0, 0], [1, 0], [0, 1])
            ->side([0, 0], [$a, 0], $la, [0, $b])
            ->side([$a, 0], [$a, $b], $lb, [0, 0]);
    }

    private static function sketchTriangle(float $g, float $h, string $lg, string $lh): FigureAreaSvg
    {
        $px = $g * 0.4;

        return (new FigureAreaSvg())
            ->shape([['poly', [[0, 0], [$g, 0], [$px, $h]]]], 'yellow')
            ->line([$px, $h], [$px, 0])
            ->right([$px, 0], [$px, $h], [$g, 0])
            ->side([0, 0], [$g, 0], $lg, [$px, $h])
            ->side([$px, $h], [$px, 0], $lh, [0, 0]);
    }

    private static function sketchTrapezoid(float $a, float $c, float $h, string $la, string $lc, string $lh): FigureAreaSvg
    {
        $x1 = ($a - $c) * 0.4;

        return (new FigureAreaSvg())
            ->shape([['poly', [[0, 0], [$a, 0], [$x1 + $c, $h], [$x1, $h]]]], 'green')
            ->line([$x1, $h], [$x1, 0])
            ->right([$x1, 0], [$x1, $h], [$a, 0])
            ->side([0, 0], [$a, 0], $la, [$x1, $h])
            ->side([$x1, $h], [$x1 + $c, $h], $lc, [0, 0])
            ->side([$x1, $h], [$x1, 0], $lh, [$a, 0]);
    }

    /* ---------- Triangle ---------- */

    private function wSail(): array
    {
        $g = self::rs(1.5, 4);
        $h = mt_rand(3, 7);
        $A = $g * $h / 2;

        return [
            'q' => self::fill('Ein dreieckiges Segel ist unten {0} m breit und {1} m hoch. Wie viele m² Stoff braucht es?', $g, $h),
            'fields' => [['A', 'm²', $A, self::EXACT]],
            'steps' => ['A = g · h : 2 = '.self::u($g, 'm').' · '.self::u($h, 'm').' : 2 = '.self::b(self::u($A, 'm²'))],
            'svg' => self::sketchTriangle($g, $h, 'g = '.self::u($g, 'm'), 'h = '.self::u($h, 'm')),
        ];
    }

    private function wBed(): array
    {
        $g = self::rs(2, 9);
        $h = self::rs(1.5, 6);
        $A = $g * $h / 2;

        return [
            'q' => self::fill('Ein dreieckiges Beet ist {0} m² gross. Eine Seite ist {1} m lang. Wie lang ist die Höhe auf diese Seite?', $A, $g),
            'fields' => [['h', 'm', $h, self::EXACT]],
            'steps' => [
                self::p('Aus A = g · h : 2 folgt h = 2 · A : g.'),
                'h = 2 · '.self::u($A, 'm²').' : '.self::u($g, 'm').' = '.self::b(self::u($h, 'm')),
            ],
            'svg' => self::sketchTriangle($g, $h, 'g = '.self::u($g, 'm'), 'h = '.self::u($h, 'm')),
        ];
    }

    private function wGable(): array
    {
        for ($k = 0; $k < 100; ++$k) {
            $g = self::rs(6, 12, 0.4);
            $h = self::rs(2.5, 6);
            $cover = Util::pick([4, 5, 6, 8, 10]);
            $A = $g * $h / 2;
            $l = $A / $cover;

            if (self::oneDecimal($l)) {
                break;
            }
        }

        return [
            'q' => self::fill('Die Giebelwand eines Hauses ist ein Dreieck: {0} m breit und {1} m hoch. Sie wird gestrichen; 1 Liter Farbe reicht für {2} m². Wie gross ist die Fläche? Wie viele Liter Farbe braucht es?', $g, $h, $cover),
            'fields' => [['A', 'm²', $A, self::EXACT], ['Farbe', 'Liter', $l, self::EXACT]],
            'steps' => [
                'A = g · h : 2 = '.self::u($g, 'm').' · '.self::u($h, 'm').' : 2 = '.self::b(self::u($A, 'm²')),
                self::fill('{0} : {1} = ', self::u($A, 'm²'), self::u($cover, 'm²')).self::b(self::u($l, 'Liter')),
            ],
            'svg' => self::sketchTriangle($g, $h, 'g = '.self::u($g, 'm'), 'h = '.self::u($h, 'm')),
        ];
    }

    /* ---------- Rectangle ---------- */

    private function wRoom(): array
    {
        $a = self::rs(3, 7);
        $b = self::rs(2.5, $a);
        $A = $a * $b;

        return [
            'q' => self::fill('Ein Zimmer ist {0} m lang und {1} m breit. Wie viele m² Parkett braucht es?', $a, $b),
            'fields' => [['A', 'm²', $A, self::EXACT]],
            'steps' => ['A = a · b = '.self::u($a, 'm').' · '.self::u($b, 'm').' = '.self::b(self::u($A, 'm²'))],
            'svg' => self::sketchRect($a, $b, 'a = '.self::u($a, 'm'), 'b = '.self::u($b, 'm')),
        ];
    }

    private function wGarden(): array
    {
        $a = mt_rand(12, 40);
        $b = mt_rand(6, $a - 2);
        $U = 2 * ($a + $b);

        return [
            'q' => self::fill('Ein rechteckiger Garten ist {0} m lang und hat einen Umfang von {1} m. Wie breit ist er? Wie gross ist seine Fläche?', $a, $U),
            'fields' => [['b', 'm', $b, self::EXACT], ['A', 'm²', $a * $b, self::EXACT]],
            'steps' => [
                'b = U : 2 − a = '.self::u($U, 'm').' : 2 − '.self::u($a, 'm').' = '.self::b(self::u($b, 'm')),
                'A = a · b = '.self::u($a, 'm').' · '.self::u($b, 'm').' = '.self::b(self::u($a * $b, 'm²')),
            ],
            'svg' => self::sketchRect($a, $b, 'a = '.self::u($a, 'm'), 'b = '.self::u($b, 'm'), 'green'),
        ];
    }

    private function wTiles(): array
    {
        $tile = Util::pick([20, 40, 50]);
        $m = $tile / 100;
        $na = mt_rand((int) ceil(2.4 / $m), (int) floor(8 / $m));
        $nb = mt_rand((int) ceil(1.6 / $m), (int) floor(5 / $m));
        $a = round($na * $m, 6);
        $b = round($nb * $m, 6);
        $A = round($a * $b, 6);
        $At = round($m * $m, 6);

        return [
            'q' => self::fill('Eine rechteckige Terrasse ist {0} m lang und {1} m breit. Sie wird mit quadratischen Platten von {2} cm Seitenlänge belegt. Wie gross ist die Terrasse? Wie viele Platten braucht es?', $a, $b, $tile),
            'fields' => [['A', 'm²', $A, self::EXACT], ['Anzahl Platten', '', $na * $nb, self::EXACT]],
            'steps' => [
                'A = '.self::u($a, 'm').' · '.self::u($b, 'm').' = '.self::b(self::u($A, 'm²')),
                self::p('Eine Platte: {0}', self::u($m, 'm').' · '.self::u($m, 'm').' = '.self::u($At, 'm²')),
                self::fill('{0} : {1} = ', self::u($A, 'm²'), self::u($At, 'm²')).self::b((string) ($na * $nb)),
                self::p('Oder: {0} Platten in der Länge mal {1} in der Breite.', $na, $nb),
            ],
            'svg' => self::sketchRect($a, $b, 'a = '.self::u($a, 'm'), 'b = '.self::u($b, 'm'), 'orange')->grid($m),
        ];
    }

    /* ---------- Parallelogram and rhombus ---------- */

    private function wParaBed(): array
    {
        $g = self::rs(3, 9);
        $h = self::rs(1.5, 5);
        $A = $g * $h;
        $s = $g * 0.3;

        return [
            'q' => self::fill('Ein Beet hat die Form eines Parallelogramms. Eine Seite ist {0} m lang, die Höhe auf diese Seite {1} m. Wie gross ist das Beet?', $g, $h),
            'fields' => [['A', 'm²', $A, self::EXACT]],
            'steps' => ['A = g · h = '.self::u($g, 'm').' · '.self::u($h, 'm').' = '.self::b(self::u($A, 'm²'))],
            'svg' => (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$g, 0], [$g + $s, $h], [$s, $h]]]], 'violet')
                ->line([$s, $h], [$s, 0])
                ->right([$s, 0], [$s, $h], [$g, 0])
                ->side([0, 0], [$g, 0], 'g = '.self::u($g, 'm'), [$s, $h])
                ->side([$s, $h], [$s, 0], 'h = '.self::u($h, 'm'), [$g, 0]),
        ];
    }

    private function rhombusSketch(float $e, float $f, string $le, string $lf): FigureAreaSvg
    {
        return (new FigureAreaSvg())
            ->shape([['poly', [[-$e / 2, 0], [0, -$f / 2], [$e / 2, 0], [0, $f / 2]]]], 'violet')
            ->line([-$e / 2, 0], [$e / 2, 0])
            ->line([0, $f / 2], [0, -$f / 2])
            ->right([0, 0], [1, 0], [0, 1])
            ->label([$e / 4, 0], $le, 'given', [$e / 4, 1], 12)
            ->label([0, $f / 4], $lf, 'given', [1, $f / 4], 14);
    }

    private function wWindow(): array
    {
        $e = mt_rand(4, 12) * 10;
        $f = mt_rand(3, 9) * 10;
        $A = $e * $f / 200;

        return [
            'q' => self::fill('Ein Fenster hat die Form einer Raute. Die Diagonalen sind {0} cm und {1} cm lang. Wie gross ist die Glasfläche in dm²?', $e, $f),
            'fields' => [['A', 'dm²', $A, self::EXACT]],
            'steps' => [
                'e = '.self::u($e, 'cm').' = '.self::u($e / 10, 'dm').', f = '.self::u($f, 'cm').' = '.self::u($f / 10, 'dm'),
                'A = e · f : 2 = '.self::u($e / 10, 'dm').' · '.self::u($f / 10, 'dm').' : 2 = '.self::b(self::u($A, 'dm²')),
            ],
            'svg' => $this->rhombusSketch($e, $f, 'e = '.self::u($e, 'cm'), 'f = '.self::u($f, 'cm')),
        ];
    }

    private function wParking(): array
    {
        $g = mt_rand(15, 40);
        $h = mt_rand(8, 20);
        $price = Util::pick([24, 35, 42, 48, 55, 65]);
        $A = $g * $h;
        $s = $g * 0.25;

        return [
            'q' => self::fill('Ein Parkplatz hat die Form eines Parallelogramms: Eine Seite ist {0} m lang, die Höhe auf diese Seite {1} m. Der Belag kostet {2} Fr. pro m². Wie gross ist der Parkplatz? Was kostet der Belag?', $g, $h, $price),
            'fields' => [['A', 'm²', $A, self::EXACT], ['Kosten', 'Fr.', $A * $price, self::EXACT]],
            'steps' => [
                'A = g · h = '.self::u($g, 'm').' · '.self::u($h, 'm').' = '.self::b(self::u($A, 'm²')),
                $A.' · '.self::u($price, 'Fr.').' = '.self::b(self::u($A * $price, 'Fr.')),
            ],
            'svg' => (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$g, 0], [$g + $s, $h], [$s, $h]]]], 'violet')
                ->line([$s, $h], [$s, 0])
                ->right([$s, 0], [$s, $h], [$g, 0])
                ->side([0, 0], [$g, 0], 'g = '.self::u($g, 'm'), [$s, $h])
                ->side([$s, $h], [$s, 0], 'h = '.self::u($h, 'm'), [$g, 0]),
        ];
    }

    /* ---------- Trapezoid ---------- */

    private function plot(): array
    {
        $a = mt_rand(20, 60);
        $c = mt_rand(10, $a - 5);
        $h = mt_rand(10, 40);

        return [$a, $c, $h, ($a + $c) * $h / 2];
    }

    private function wPlot(): array
    {
        [$a, $c, $h, $A] = $this->plot();

        return [
            'q' => self::fill('Ein Grundstück hat die Form eines Trapezes. Die parallelen Seiten sind {0} m und {1} m lang, ihr Abstand beträgt {2} m. Wie gross ist das Grundstück?', $a, $c, $h),
            'fields' => [['A', 'm²', $A, self::EXACT]],
            'steps' => ['A = (a + c) : 2 · h = ('.self::u($a, 'm').' + '.self::u($c, 'm').') : 2 · '.self::u($h, 'm').' = '.self::b(self::u($A, 'm²'))],
            'svg' => self::sketchTrapezoid($a, $c, $h, 'a = '.self::u($a, 'm'), 'c = '.self::u($c, 'm'), 'h = '.self::u($h, 'm')),
        ];
    }

    private function wPlotPrice(): array
    {
        [$a, $c, $h, $A] = $this->plot();
        $price = Util::pick([180, 250, 320, 450, 600, 850]);

        return [
            'q' => self::fill('Ein Grundstück hat die Form eines Trapezes. Die parallelen Seiten sind {0} m und {1} m lang, ihr Abstand beträgt {2} m. 1 m² Land kostet {3} Fr. Wie gross ist das Grundstück? Wie viel kostet es?', $a, $c, $h, $price),
            'fields' => [['A', 'm²', $A, self::EXACT], ['Kosten', 'Fr.', $A * $price, self::EXACT]],
            'steps' => [
                'A = (a + c) : 2 · h = ('.self::u($a, 'm').' + '.self::u($c, 'm').') : 2 · '.self::u($h, 'm').' = '.self::b(self::u($A, 'm²')),
                self::n($A).' · '.self::u($price, 'Fr.').' = '.self::b(self::u($A * $price, 'Fr.')),
            ],
            'svg' => self::sketchTrapezoid($a, $c, $h, 'a = '.self::u($a, 'm'), 'c = '.self::u($c, 'm'), 'h = '.self::u($h, 'm')),
        ];
    }

    private function wPlotWidth(): array
    {
        [$a, $c, $h, $A] = $this->plot();

        return [
            'q' => self::fill('Ein Grundstück in Trapezform ist {0} m² gross. Die parallelen Seiten sind {1} m und {2} m lang. Wie gross ist ihr Abstand?', $A, $a, $c),
            'fields' => [['h', 'm', $h, self::EXACT]],
            'steps' => [
                '(a + c) : 2 = ('.self::u($a, 'm').' + '.self::u($c, 'm').') : 2 = '.self::u(($a + $c) / 2, 'm'),
                'h = '.self::u($A, 'm²').' : '.self::u(($a + $c) / 2, 'm').' = '.self::b(self::u($h, 'm')),
            ],
            'svg' => self::sketchTrapezoid($a, $c, $h, 'a = '.self::u($a, 'm'), 'c = '.self::u($c, 'm'), 'h = '.self::u($h, 'm')),
        ];
    }

    /* ---------- Circle ---------- */

    private static function sketchCircle(float $r, string $label, bool $diameter): FigureAreaSvg
    {
        $svg = (new FigureAreaSvg())->shape([['circle', [0, 0], $r]], 'pink')->dot([0, 0]);

        return $diameter
            ? $svg->line([-$r, 0], [$r, 0])->side([-$r, 0], [$r, 0], $label, [0, -$r])
            : $svg->line([0, 0], [$r, 0])->side([0, 0], [$r, 0], $label, [0, -$r]);
    }

    private function wPizza(): array
    {
        $d = Util::pick([24, 26, 28, 30, 32, 36, 40]);
        $r = $d / 2;
        $A = round(M_PI * $r * $r, 1);

        return [
            'q' => self::fill('Eine runde Pizza hat einen Durchmesser von {0} cm. Wie gross ist ihre Fläche?', $d),
            'fields' => [['A', 'cm²', $A, self::ROUND]],
            'steps' => [
                'r = d : 2 = '.self::u($r, 'cm'),
                'A = π · r² = π · ('.self::u($r, 'cm').')² ≈ '.self::b(self::u($A, 'cm²')),
            ],
            'svg' => self::sketchCircle($r, 'd = '.self::u($d, 'cm'), true),
        ];
    }

    private function wSprinkler(): array
    {
        $r = self::rs(2, 9);
        $A = round(M_PI * $r * $r, 1);

        return [
            'q' => self::fill('Ein Rasensprenger bewässert eine Kreisfläche mit einem Radius von {0} m. Wie gross ist diese Fläche?', $r),
            'fields' => [['A', 'm²', $A, self::ROUND]],
            'steps' => ['A = π · r² = π · ('.self::u($r, 'm').')² ≈ '.self::b(self::u($A, 'm²'))],
            'svg' => self::sketchCircle($r, 'r = '.self::u($r, 'm'), false),
        ];
    }

    private function wWheel(): array
    {
        $d = Util::pick([40, 50, 55, 60, 65, 70]);
        $U = round(M_PI * $d, 1);

        return [
            'q' => self::fill('Ein Velorad hat einen Durchmesser von {0} cm. Welche Strecke legt es bei einer Umdrehung zurück?', $d),
            'fields' => [['U', 'cm', $U, self::ROUND]],
            'steps' => [
                self::p('Bei einer Umdrehung rollt der ganze Umfang ab.'),
                'U = π · d = π · '.self::u($d, 'cm').' ≈ '.self::b(self::u($U, 'cm')),
            ],
            'svg' => self::sketchCircle($d / 2, 'd = '.self::u($d, 'cm'), true),
        ];
    }

    private function wPond(): array
    {
        $r = mt_rand(2, 8);
        $w = Util::pick([1, 1.5, 2, 2.5]);
        $R = $r + $w;
        $A = round(M_PI * ($R * $R - $r * $r), 1);

        return [
            'q' => self::fill('Um einen runden Teich mit {0} m Radius führt ein {1} m breiter Weg. Wie gross ist die Fläche des Wegs?', $r, $w),
            'fields' => [['A', 'm²', $A, self::ROUND]],
            'steps' => [
                self::p('Der Weg ist ein Kreisring: aussen R = {0}, innen r = {1}.', self::u($R, 'm'), self::u($r, 'm')),
                'A = π · R² − π · r² = π · ('.self::u($R, 'm').')² − π · ('.self::u($r, 'm').')² ≈ '.self::b(self::u($A, 'm²')),
            ],
            'svg' => (new FigureAreaSvg())
                ->shape([['circle', [0, 0], $R], ['circle', [0, 0], $r]], 'orange')
                ->shape([['circle', [0, 0], $r]], 'blue')
                ->dot([0, 0])
                ->line([0, 0], [$r, 0])
                ->side([0, 0], [$r, 0], 'r = '.self::u($r, 'm'), [0, -1])
                ->line([$r * cos(deg2rad(50)), $r * sin(deg2rad(50))], [$R * cos(deg2rad(50)), $R * sin(deg2rad(50))], 'sol')
                ->label([$R * cos(deg2rad(50)), $R * sin(deg2rad(50))], self::u($w, 'm'), 'given', [0, 0], 14),
        ];
    }

    /* ---------- Composed ---------- */

    private function wWall(): array
    {
        $L = mt_rand(3, 8);
        $H = Util::pick([2.5, 3]);
        $dw = 1;
        $dh = 2;
        $A = $L * $H - $dw * $dh;

        return [
            'q' => self::fill('Eine Wand ist {0} m lang und {1} m hoch. Darin ist eine Tür, {2} m breit und {3} m hoch. Wie viele m² Wand werden gestrichen?', $L, $H, $dw, $dh),
            'fields' => [['A', 'm²', $A, self::EXACT]],
            'steps' => [self::u($L, 'm').' · '.self::u($H, 'm').' − '.self::u($dw, 'm').' · '.self::u($dh, 'm').' = '.self::u($L * $H, 'm²').' − '.self::u($dw * $dh, 'm²').' = '.self::b(self::u($A, 'm²'))],
            'svg' => (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$L, 0], [$L, $H], [0, $H]]], ['poly', [[0.6, 0.0001], [1.6, 0.0001], [1.6, 2], [0.6, 2]]]], 'teal')
                ->dim([0, 0], [$L, 0], self::u($L, 'm'), -1)
                ->dim([0, 0], [0, $H], self::u($H, 'm'), 1)
                ->side([0.6, 2], [1.6, 2], self::u($dw, 'm'), [1.1, 3])
                ->side([1.6, 0], [1.6, 2], self::u($dh, 'm'), [2.6, 1]),
        ];
    }

    private function wPaint(): array
    {
        for ($k = 0; $k < 100; ++$k) {
            $L = mt_rand(4, 9);
            $H = Util::pick([2.5, 3]);
            $ww = Util::pick([1.2, 1.5, 2]);
            $wh = Util::pick([1, 1.2, 1.5]);
            $cover = Util::pick([5, 8, 10]);
            $A = round($L * $H - $ww * $wh - 2, 6);
            $l = $A / $cover;

            if (self::oneDecimal($l)) {
                break;
            }
        }

        $wx = $L - $ww - 0.6;

        return [
            'q' => self::fill('Eine Wand ist {0} m lang und {1} m hoch. Sie hat ein Fenster ({2} m × {3} m) und eine Tür ({4} m × {5} m). 1 Liter Farbe reicht für {6} m². Wie gross ist die Fläche zum Streichen? Wie viele Liter Farbe braucht es?', $L, $H, $ww, $wh, 1, 2, $cover),
            'fields' => [['A', 'm²', $A, self::EXACT], ['Farbe', 'Liter', $l, self::EXACT]],
            'steps' => [
                'A = '.self::u($L, 'm').' · '.self::u($H, 'm').' − '.self::u($ww, 'm').' · '.self::u($wh, 'm').' − '.self::u(1, 'm').' · '.self::u(2, 'm').' = '.self::b(self::u($A, 'm²')),
                self::fill('{0} : {1} = ', self::u($A, 'm²'), self::u($cover, 'm²')).self::b(self::u($l, 'Liter')),
            ],
            'svg' => (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$L, 0], [$L, $H], [0, $H]]], ['poly', [[0.5, 0.0001], [1.5, 0.0001], [1.5, 2], [0.5, 2]]], ['poly', [[$wx, 0.9], [$wx + $ww, 0.9], [$wx + $ww, 0.9 + $wh], [$wx, 0.9 + $wh]]]], 'teal')
                ->dim([0, 0], [$L, 0], self::u($L, 'm'), -1)
                ->dim([0, 0], [0, $H], self::u($H, 'm'), 1),
        ];
    }

    private function wLawn(): array
    {
        $L = mt_rand(12, 30);
        $B = mt_rand(8, 18);
        $d = Util::pick([3, 4, 5, 6]);
        $r = $d / 2;
        $A = round($L * $B - M_PI * $r * $r, 1);

        return [
            'q' => self::fill('Ein rechteckiger Garten ist {0} m lang und {1} m breit. Darin liegt ein runder Teich mit {2} m Durchmesser. Der Rest ist Rasen. Wie gross ist die Rasenfläche?', $L, $B, $d),
            'fields' => [['A', 'm²', $A, self::ROUND]],
            'steps' => ['A = '.self::u($L, 'm').' · '.self::u($B, 'm').' − π · ('.self::u($r, 'm').')² ≈ '.self::u($L * $B, 'm²').' − '.self::u(round(M_PI * $r * $r, 2), 'm²').' ≈ '.self::b(self::u($A, 'm²'))],
            'svg' => (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$L, 0], [$L, $B], [0, $B]]], ['circle', [$L * 0.62, $B * 0.5], max($r, $B * 0.12)]], 'green')
                ->shape([['circle', [$L * 0.62, $B * 0.5], max($r, $B * 0.12)]], 'blue')
                ->label([$L * 0.62, $B * 0.5], 'd = '.self::u($d, 'm'), 'given')
                ->dim([0, 0], [$L, 0], self::u($L, 'm'), -1)
                ->dim([0, 0], [0, $B], self::u($B, 'm'), 1),
        ];
    }

    /* ---------- Pythagoras ---------- */

    private function wScreen(): array
    {
        [$a, $b, $d] = Util::pick([[40, 30, 50], [48, 36, 60], [64, 48, 80], [32, 24, 40], [80, 60, 100], [56, 42, 70]]);

        return [
            'q' => self::fill('Ein Bildschirm hat eine Diagonale von {0} cm und ist {1} cm breit. Wie hoch ist er? Wie gross ist die Bildfläche?', $d, $a),
            'fields' => [['h', 'cm', $b, self::EXACT], ['A', 'cm²', $a * $b, self::EXACT]],
            'steps' => [
                'h = √(d² − b²) = √(('.self::u($d, 'cm').')² − ('.self::u($a, 'cm').')²) = '.self::b(self::u($b, 'cm')),
                'A = '.self::u($a, 'cm').' · '.self::u($b, 'cm').' = '.self::b(self::u($a * $b, 'cm²')),
            ],
            'svg' => self::sketchRect($a, $b, 'b = '.self::u($a, 'cm'), 'h = '.self::u($b, 'cm'))
                ->line([0, 0], [$a, $b], 'edge')
                ->label([$a / 2, $b / 2], 'd = '.self::u($d, 'cm'), 'given', [$a, 0], 14),
        ];
    }

    private function wRoof(): array
    {
        [$x, $h, $s] = Util::pick([[3, 4, 5], [4, 3, 5], [6, 8, 10], [8, 6, 10], [5, 12, 13], [4.5, 6, 7.5], [6, 4.5, 7.5], [3.6, 4.8, 6]]);
        $g = 2 * $x;
        $A = $g * $h / 2;

        return [
            'q' => self::fill('Ein Hausgiebel ist ein gleichschenkliges Dreieck: {0} m breit, die schrägen Seiten sind je {1} m lang. Wie hoch ist der Giebel? Wie gross ist die Giebelfläche?', $g, $s),
            'fields' => [['h', 'm', $h, self::EXACT], ['A', 'm²', $A, self::EXACT]],
            'steps' => [
                'h = √(('.self::u($s, 'm').')² − ('.self::u($x, 'm').')²) = '.self::b(self::u($h, 'm')),
                'A = '.self::u($g, 'm').' · '.self::u($h, 'm').' : 2 = '.self::b(self::u($A, 'm²')),
            ],
            'svg' => (new FigureAreaSvg())
                ->shape([['poly', [[0, 0], [$g, 0], [$x, $h]]]], 'orange')
                ->line([$x, $h], [$x, 0])
                ->right([$x, 0], [$x, $h], [$g, 0])
                ->side([0, 0], [$g, 0], self::u($g, 'm'), [$x, $h])
                ->side([0, 0], [$x, $h], self::u($s, 'm'), [$g, 0])
                ->side([$x, 0], [$x, $h], 'h = '.self::u($h, 'm'), [$g, 0]),
        ];
    }

    private function wKite(): array
    {
        [$x, $y, $a] = Util::pick([[30, 40, 50], [40, 30, 50], [24, 32, 40], [36, 48, 60], [18, 24, 30], [20, 48, 52]]);
        $e = 2 * $x;
        $f = 2 * $y;
        $A = $e * $f / 2;

        return [
            'q' => self::fill('Ein Drachen hat die Form einer Raute mit {0} cm Seitenlänge. Eine Diagonale ist {1} cm lang. Wie lang ist die andere Diagonale? Wie viel cm² Stoff braucht es?', $a, $e),
            'fields' => [['f', 'cm', $f, self::EXACT], ['A', 'cm²', $A, self::EXACT]],
            'steps' => [
                'f : 2 = √(('.self::u($a, 'cm').')² − ('.self::u($x, 'cm').')²) = '.self::u($y, 'cm').', f = '.self::b(self::u($f, 'cm')),
                'A = e · f : 2 = '.self::u($e, 'cm').' · '.self::u($f, 'cm').' : 2 = '.self::b(self::u($A, 'cm²')),
            ],
            'svg' => $this->rhombusSketch($e, $f, 'e = '.self::u($e, 'cm'), 'f = '.self::u($f, 'cm'))
                ->side([0, $f / 2], [$e / 2, 0], 'a = '.self::u($a, 'cm'), [0, 0]),
        ];
    }
}
