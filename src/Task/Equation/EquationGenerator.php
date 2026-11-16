<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Equation;

use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * Linear equations in ten levels (port of the "Gleichungstrainer").
 *
 * Every task is solved for x. The solution is an integer or a fraction, the steps are
 * shown as a table with the operation of every line ("| − 3x", "| : 4").
 */
final class EquationGenerator
{
    public const LEVELS = [
        1 => 'x + a = b',
        2 => 'ax + b = c',
        3 => 'x auf beiden Seiten',
        4 => 'Lösung als Bruch',
        5 => 'Klammern auflösen',
        6 => 'Brüche als Koeffizienten',
        7 => 'Bruchterme',
        8 => 'Profi-Mix',
        9 => 'Binome (x² fällt weg)',
        10 => 'Binome mit Koeffizienten',
    ];

    /**
     * Largest denominator and numerator of the solution, share of fraction solutions
     * that is kept when fractions are wanted.
     */
    private const LIMITS = [
        1 => [1, 25, 0], 2 => [1, 20, 0], 3 => [1, 20, 0], 4 => [9, 40, .75], 5 => [12, 40, .5],
        6 => [12, 40, .5], 7 => [15, 50, .5], 8 => [20, 60, .4], 9 => [12, 40, .4], 10 => [12, 40, .4],
    ];

    /**
     * Points per task: one for the first three levels, two up to level 7, then three.
     */
    public static function points(int $level): int
    {
        return $level <= 3 ? 1 : ($level <= 7 ? 2 : 3);
    }

    /**
     * Difficulty: "easy" = whole-number solutions only, "medium" = fractions possible,
     * "hard" = fractions preferred (as in the trainer).
     */
    public function build(int $level, string $difficulty): array
    {
        $level = max(1, min(10, $level));
        $intOnly = 'easy' === $difficulty;
        [$maxDen, $maxNum, $fracPref] = self::LIMITS[$level];

        if ('medium' === $difficulty) {
            $fracPref = 0;
        }

        for ($i = 0; $i < 20000; ++$i) {
            $e = $intOnly && 4 === $level ? $this->level4Int() : $this->level($level);

            if (!$e || !$e['lhs']['q']->eq($e['rhs']['q'])) {
                continue;
            }

            $x = self::solve($e['lhs'], $e['rhs']);

            if (!$x || $x->d > $maxDen || abs($x->n) > $maxNum) {
                continue;
            }

            if ($intOnly) {
                if (!$x->isInt() || abs($x->n) > 20) {
                    continue;
                }
            } elseif ($fracPref && $x->isInt() && self::rand() < $fracPref) {
                continue;
            }

            if (!empty($e['swap']) && self::rand() < .35) {
                $e = ['lt' => $e['rt'], 'rt' => $e['lt'], 'lhs' => $e['rhs'], 'rhs' => $e['lhs']];
            }

            return $this->task($e, $x, $level);
        }

        return $this->build(1, 'easy');
    }

    private function task(array $e, Fraction $x, int $level): array
    {
        $q = '<span class="st-eq">'.$e['lt'].' = '.$e['rt'].'</span>';

        return [
            'q' => $q,
            'key' => md5($q),
            'a' => ['type' => 'rat', 'n' => $x->n, 'd' => $x->d],
            'way' => $this->steps($e, $x),
            'points' => self::points($level),
            'area' => 'level'.$level,
        ];
    }

    /* ---------- Random ---------- */

    private static function rand(): float
    {
        return mt_rand() / mt_getrandmax();
    }

    private static function rnz(int $min, int $max): int
    {
        do {
            $v = Util::ri($min, $max);
        } while (0 === $v);

        return $v;
    }

    private static function rfrac(int $maxN, int $maxD): Fraction
    {
        return new Fraction(self::rnz(-$maxN, $maxN), Util::ri(2, $maxD));
    }

    /* ---------- HTML building blocks ---------- */

    private const X = '<i>x</i>';

    /**
     * Coefficient in front of a term (1 is left out).
     */
    private static function coef(Fraction $c, bool $first): string
    {
        $neg = $c->n < 0;
        $prefix = $first ? ($neg ? '−' : '') : ($neg ? ' − ' : ' + ');
        $a = $c->abs();
        $body = $a->isInt() ? (1 === $a->n ? '' : (string) $a->n) : Util::f($a->n, $a->d);

        return $prefix.$body;
    }

    /**
     * Number as a summand.
     */
    private static function constant(Fraction $c, bool $first): string
    {
        $neg = $c->n < 0;

        return ($first ? ($neg ? '−' : '') : ($neg ? ' − ' : ' + ')).$c->abs()->html();
    }

    /**
     * a·x + b
     */
    private static function lin(Fraction $a, Fraction $b, bool $constFirst = false): string
    {
        if ($a->isZero() && $b->isZero()) {
            return '0';
        }

        $s = '';
        $first = true;

        foreach ($constFirst ? ['b', 'a'] : ['a', 'b'] as $k) {
            if ('a' === $k && !$a->isZero()) {
                $s .= self::coef($a, $first).self::X;
                $first = false;
            }

            if ('b' === $k && !$b->isZero()) {
                $s .= self::constant($b, $first);
                $first = false;
            }
        }

        return $s;
    }

    private static function F(int $n, int $d = 1): Fraction
    {
        return new Fraction($n, $d);
    }

    /**
     * k·(x + b)
     */
    private static function bracket(Fraction $k, Fraction $b, bool $first): string
    {
        return self::coef($k, $first).'('.self::lin(self::F(1), $b).')';
    }

    private static function binom(int $a, int $b): string
    {
        return '('.self::lin(self::F($a), self::F($b)).')';
    }

    /**
     * q·x² + a·x + b
     */
    private static function quad(array $s): string
    {
        if ($s['q']->isZero()) {
            return self::lin($s['a'], $s['b']);
        }

        $t = self::coef($s['q'], true).self::X.'<sup>2</sup>';

        if (!$s['a']->isZero()) {
            $t .= self::coef($s['a'], false).self::X;
        }

        if (!$s['b']->isZero()) {
            $t .= self::constant($s['b'], false);
        }

        return $t;
    }

    /**
     * Side of an equation: q·x² + a·x + b.
     */
    private static function side(Fraction|int $a, Fraction|int $b, Fraction|int $q = 0): array
    {
        return ['q' => Fraction::of($q), 'a' => Fraction::of($a), 'b' => Fraction::of($b)];
    }

    /**
     * (a·x + b)(c·x + d) multiplied out.
     */
    private static function prod(int $a, int $b, int $c, int $d): array
    {
        return self::side($a * $d + $b * $c, $b * $d, $a * $c);
    }

    private static function plus(array $s, array $t, int $sign = 1): array
    {
        $k = self::F($sign);

        return ['q' => $s['q']->add($t['q']->mul($k)), 'a' => $s['a']->add($t['a']->mul($k)), 'b' => $s['b']->add($t['b']->mul($k))];
    }

    private static function pickFactors(int $n): array
    {
        $out = [];

        for ($d = 1; $d <= $n; ++$d) {
            if (0 === $n % $d) {
                $out[] = [$d, intdiv($n, $d)];
            }
        }

        return Util::pick($out);
    }

    /* ---------- Levels ---------- */

    private function level(int $level): array|null
    {
        return match ($level) {
            1 => $this->level1(),
            2 => $this->level2(),
            3 => $this->level3(),
            4 => $this->level4(),
            5 => $this->level5(),
            6 => $this->level6(),
            7 => $this->level7(),
            8 => $this->level8(),
            9 => $this->level9(),
            default => $this->level10(),
        };
    }

    private function level1(): array
    {
        // No negative numbers: solution, summands and result are all >= 0
        $x0 = Util::ri(0, 20);
        $a = Util::ri(1, 15);

        if ($x0 - $a >= 0 && self::rand() < .35) {
            return ['lt' => self::X.' − '.$a, 'rt' => (string) ($x0 - $a), 'lhs' => self::side(1, -$a), 'rhs' => self::side(0, $x0 - $a), 'swap' => true];
        }

        return ['lt' => self::lin(self::F(1), self::F($a), self::rand() < .3), 'rt' => (string) ($x0 + $a), 'lhs' => self::side(1, $a), 'rhs' => self::side(0, $x0 + $a), 'swap' => true];
    }

    private function level2(): array
    {
        do {
            $a = self::rnz(-9, 9);
        } while (1 === abs($a));

        $b = self::rand() < .25 ? 0 : self::rnz(-12, 12);
        $x0 = Util::ri(-10, 10);
        $c = $a * $x0 + $b;

        return ['lt' => self::lin(self::F($a), self::F($b), self::rand() < .3), 'rt' => self::F($c)->html(), 'lhs' => self::side($a, $b), 'rhs' => self::side(0, $c), 'swap' => true];
    }

    private function level3(): array
    {
        $a = self::rnz(-9, 9);

        do {
            $c = self::rnz(-9, 9);
        } while ($c === $a);

        $b = Util::ri(-15, 15);
        $x0 = Util::ri(-10, 10);
        $d = ($a - $c) * $x0 + $b;

        return ['lt' => self::lin(self::F($a), self::F($b), self::rand() < .3), 'rt' => self::lin(self::F($c), self::F($d), self::rand() < .3), 'lhs' => self::side($a, $b), 'rhs' => self::side($c, $d), 'swap' => true];
    }

    private function level4Int(): array
    {
        $a = self::rnz(-12, 12);

        do {
            $c = self::rand() < .3 ? 0 : self::rnz(-12, 12);
        } while ($c === $a);

        $b = self::rnz(-30, 30);
        $x0 = Util::ri(-15, 15);
        $d = ($a - $c) * $x0 + $b;

        return ['lt' => self::lin(self::F($a), self::F($b), self::rand() < .3), 'rt' => self::lin(self::F($c), self::F($d), self::rand() < .3), 'lhs' => self::side($a, $b), 'rhs' => self::side($c, $d), 'swap' => true];
    }

    private function level4(): array
    {
        $a = self::rnz(-9, 9);
        $c = self::rand() < .4 ? 0 : self::rnz(-9, 9);
        $b = self::rnz(-15, 15);
        $d = Util::ri(-15, 15);

        return ['lt' => self::lin(self::F($a), self::F($b), self::rand() < .3), 'rt' => self::lin(self::F($c), self::F($d), self::rand() < .3), 'lhs' => self::side($a, $b), 'rhs' => self::side($c, $d), 'swap' => true];
    }

    private function level5(): array
    {
        $a = self::rnz(-6, 6);
        $b = self::rnz(-9, 9);
        $c = self::rnz(-6, 6);
        $d = self::rnz(-9, 9);
        $e = self::rnz(-7, 7);
        $f = Util::ri(-20, 20);

        return [
            'lt' => self::bracket(self::F($a), self::F($b), true).self::bracket(self::F($c), self::F($d), false),
            'rt' => self::lin(self::F($e), self::F($f)),
            'lhs' => self::side($a + $c, $a * $b + $c * $d), 'rhs' => self::side($e, $f),
        ];
    }

    private function level6(): array|null
    {
        $a = self::rfrac(7, 6);

        if ($a->isInt()) {
            return null;
        }

        $c = self::rand() < .5 ? self::rfrac(7, 6) : self::F(self::rnz(-5, 5));
        $b = self::rand() < .5 ? self::rfrac(9, 5) : self::F(self::rnz(-12, 12));
        $d = self::rand() < .5 ? self::rfrac(9, 5) : self::F(Util::ri(-12, 12));

        return ['lt' => self::lin($a, $b), 'rt' => self::lin($c, $d), 'lhs' => self::side($a, $b), 'rhs' => self::side($c, $d)];
    }

    private function level7(): array|null
    {
        $a = self::rnz(-5, 5);
        $b = self::rnz(-9, 9);
        $q = Util::ri(2, 6);
        $c = self::rnz(-5, 5);
        $d = Util::ri(-9, 9);
        $s = Util::ri(2, 6);
        $r = Util::ri(-5, 5);

        if ($q === $s) {
            return null;
        }

        $R = self::F($r);

        return [
            'lt' => Util::f(self::lin(self::F($a), self::F($b)), (string) $q).($r ? self::constant($R, false) : ''),
            'rt' => Util::f(self::lin(self::F($c), self::F($d)), (string) $s),
            'lhs' => self::side(self::F($a, $q), self::F($b, $q)->add($R)), 'rhs' => self::side(self::F($c, $s), self::F($d, $s)),
        ];
    }

    private function level8(): array|null
    {
        $p = self::rfrac(5, 6);

        if ($p->isInt()) {
            return null;
        }

        $r = self::rfrac(7, 5);

        if ($r->isInt()) {
            return null;
        }

        $c = self::rnz(-5, 5);
        $d = self::rnz(-9, 9);
        $t = Util::ri(2, 6);
        $u = self::rand() < .6 ? self::rfrac(5, 6) : self::F(self::rnz(-4, 4));
        $w = self::F(Util::ri(-6, 6));

        return [
            'lt' => self::bracket($p, $r, true).' − '.Util::f(self::lin(self::F($c), self::F($d)), (string) $t),
            'rt' => self::lin($u, $w),
            'lhs' => self::side($p->sub(self::F($c, $t)), $p->mul($r)->sub(self::F($d, $t))), 'rhs' => self::side($u, $w),
        ];
    }

    private function level9(): array
    {
        $b = self::rnz(-9, 9);
        $d = self::rnz(-9, 9);
        $f = self::rnz(-9, 9);
        $h = self::rnz(-9, 9);

        return match (Util::ri(0, 2)) {
            // (x+b)(x+d) = (x+f)(x+h)
            0 => ['lt' => self::binom(1, $b).self::binom(1, $d), 'rt' => self::binom(1, $f).self::binom(1, $h), 'lhs' => self::prod(1, $b, 1, $d), 'rhs' => self::prod(1, $f, 1, $h)],
            // (x+b)² = (x+f)(x+h)
            1 => ['lt' => self::binom(1, $b).'<sup>2</sup>', 'rt' => self::binom(1, $f).self::binom(1, $h), 'lhs' => self::prod(1, $b, 1, $b), 'rhs' => self::prod(1, $f, 1, $h)],
            // (x+b)(x+d) = x(x+f) + h
            default => ['lt' => self::binom(1, $b).self::binom(1, $d), 'rt' => self::X.self::binom(1, $f).self::constant(self::F($h), false), 'lhs' => self::prod(1, $b, 1, $d), 'rhs' => self::plus(self::prod(1, 0, 1, $f), self::side(0, $h))],
        };
    }

    private function level10(): array|null
    {
        $a = Util::ri(1, 4);
        $c = Util::ri(1, 4);

        if (1 === $a * $c) {
            return null;
        }

        [$e, $g] = self::pickFactors($a * $c);
        $b = self::rnz(-9, 9);
        $d = self::rnz(-9, 9);
        $f = self::rnz(-9, 9);
        $h = self::rnz(-9, 9);
        $kind = Util::ri(0, 2);

        if (0 === $kind) {
            // (ax+b)(cx+d) = (ex+f)(gx+h) with ac = eg
            return ['lt' => self::binom($a, $b).self::binom($c, $d), 'rt' => self::binom($e, $f).self::binom($g, $h), 'lhs' => self::prod($a, $b, $c, $d), 'rhs' => self::prod($e, $f, $g, $h)];
        }

        if (1 === $kind) {
            // (ax+b)² = (ex+f)(gx+h) with a² = eg
            $a = Util::ri(2, 3);
            [$e2, $g2] = self::pickFactors($a * $a);

            return ['lt' => self::binom($a, $b).'<sup>2</sup>', 'rt' => self::binom($e2, $f).self::binom($g2, $h), 'lhs' => self::prod($a, $b, $a, $b), 'rhs' => self::prod($e2, $f, $g2, $h)];
        }

        // (ax+b)(cx+d) − (ex+f)(gx+h) = px + r
        $pp = Util::ri(-9, 9);
        $r = Util::ri(-15, 15);

        return [
            'lt' => self::binom($a, $b).self::binom($c, $d).' − '.self::binom($e, $f).self::binom($g, $h),
            'rt' => self::lin(self::F($pp), self::F($r)),
            'lhs' => self::plus(self::prod($a, $b, $c, $d), self::prod($e, $f, $g, $h), -1), 'rhs' => self::side($pp, $r),
        ];
    }

    private static function solve(array $lhs, array $rhs): Fraction|null
    {
        $A = $lhs['a']->sub($rhs['a']);

        if ($A->isZero()) {
            return null;
        }

        return $rhs['b']->sub($lhs['b'])->div($A);
    }

    /* ---------- Solution steps ---------- */

    private function steps(array $e, Fraction $x): string
    {
        $zero = self::F(0);
        $one = self::F(1);
        $rows = [[$e['lt'], $e['rt']]];
        $ops = [];
        $L = $e['lhs'];
        $R = $e['rhs'];
        $push = static function (string $op) use (&$rows, &$ops, &$L, &$R): void {
            $ops[] = $op;
            $rows[] = [self::quad($L), self::quad($R)];
        };
        $plain = static fn (string $t, array $s): bool => $s['q']->isZero() && ($t === self::lin($s['a'], $s['b']) || $t === self::lin($s['a'], $s['b'], true));
        // Absolute value of a term for the operation column (4, 3x, x, 6x²)
        $absTerm = static function (Fraction $c, string $sym): string {
            $a = $c->abs();

            if ('' === $sym) {
                return $a->html();
            }

            return ($a->isInt() && 1 === $a->n ? '' : $a->html()).$sym;
        };
        // Remove a term on both sides: positive term "−", negative term "+"
        $remove = static fn (Fraction $c, string $sym): string => ($c->n > 0 ? '− ' : '+ ').$absTerm($c, $sym);

        // 1. Multiply out, simplify
        if (!$plain($e['lt'], $L) || !$plain($e['rt'], $R)) {
            $push('<span class="st-t">TU</span>');
        }

        // 2. x² cancels on both sides
        if (!$L['q']->isZero()) {
            $t = $L['q'];
            $L = ['q' => $zero, 'a' => $L['a'], 'b' => $L['b']];
            $R = ['q' => $zero, 'a' => $R['a'], 'b' => $R['b']];
            $push($remove($t, self::X.'<sup>2</sup>'));
        }

        // 3. Clear fractions
        $N = 1;

        foreach ([$L['a'], $L['b'], $R['a'], $R['b']] as $f) {
            $N = Util::lcm($N, $f->d);
        }

        if ($N > 1) {
            $k = self::F($N);
            $L = ['q' => $zero, 'a' => $L['a']->mul($k), 'b' => $L['b']->mul($k)];
            $R = ['q' => $zero, 'a' => $R['a']->mul($k), 'b' => $R['b']->mul($k)];
            $push('· '.$N);
        }

        // 4. x term to the left
        if (!$R['a']->isZero()) {
            $t = $R['a'];
            $L = ['q' => $zero, 'a' => $L['a']->sub($t), 'b' => $L['b']];
            $R = ['q' => $zero, 'a' => $zero, 'b' => $R['b']];
            $push($remove($t, self::X));
        }

        // 5. Number to the right
        if (!$L['b']->isZero()) {
            $t = $L['b'];
            $L = ['q' => $zero, 'a' => $L['a'], 'b' => $zero];
            $R = ['q' => $zero, 'a' => $zero, 'b' => $R['b']->sub($t)];
            $push($remove($t, ''));
        }

        // 6. Divide by the coefficient
        if (!$L['a']->eq($one)) {
            $A = $L['a'];
            $ops[] = ': '.($A->n < 0 ? '('.$A->html().')' : $A->html());
            $rows[] = [self::X, $x->html()];
        }

        $html = '<table class="st-eqsteps">';
        $last = \count($rows) - 1;

        foreach ($rows as $i => [$l, $r]) {
            $op = $ops[$i] ?? '';
            $html .= '<tr'.($i === $last ? ' class="st-final"' : '').'><td class="st-l">'.$l.'</td><td>=</td><td class="st-r">'.$r.'</td><td class="st-op">'.('' !== $op ? '| '.$op : '').'</td></tr>';
        }

        return $html.'</table>';
    }
}
