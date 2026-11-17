<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Binomial;

use Markocupic\ContaoSchoolTest\Task\Equation\Fraction;
use Markocupic\ContaoSchoolTest\Task\Term\Polynomial;
use Markocupic\ContaoSchoolTest\Task\Term\TermParser;
use Markocupic\ContaoSchoolTest\Task\Util as U;

/**
 * Produkte von Binomen: figures made of rectangles, calculating cleverly, the rectangle
 * model, multiplying out and factorising.
 *
 * Answer type "terms": several fields, each one point. A field is a term or a number
 * (checked for equivalence and, if required, for its form) or a choice between
 * "Summenterm" and "Produktterm".
 *
 * Levels: easy = basic requirements, hard = additional requirements, medium mixes both
 * task by task.
 */
final class BinomialGenerator
{
    public const TOPICS = ['rechteckfiguren', 'geschickt', 'rechteckmodell', 'ausmultiplizieren', 'faktorisieren'];

    public const LEVELS = ['easy', 'medium', 'hard'];

    private const LETTERS = ['x', 'a', 'b', 'm', 'y', 'z', 'p', 'n'];

    private readonly TermParser $parser;

    public function __construct()
    {
        $this->parser = new TermParser();
    }

    public function build(string $topic, string $level): array
    {
        $topic = \in_array($topic, self::TOPICS, true) ? $topic : 'ausmultiplizieren';
        $level = \in_array($level, self::LEVELS, true) ? $level : 'medium';

        // Medium: task by task the basic or the additional requirements
        $hard = 'hard' === $level || ('medium' === $level && U::chance());

        return match ($topic) {
            'rechteckfiguren' => $this->rechteckfiguren($hard),
            'geschickt' => $this->geschickt($hard),
            'rechteckmodell' => $this->rechteckmodell($hard),
            'ausmultiplizieren' => $this->ausmultiplizieren($hard),
            'faktorisieren' => $this->faktorisieren($hard),
        };
    }

    /* ---------- Rechteckfiguren: area and perimeter ---------- */

    private function rechteckfiguren(bool $hard): array
    {
        $variant = U::pick($hard ? ['werte', 'terme', 'terme', 'seite'] : ['werte', 'terme', 'seite']);

        if ('seite' === $variant) {
            return $this->seite($hard);
        }

        [$nc, $nr] = [mt_rand(2, $hard ? 4 : 3), mt_rand(2, 3)];
        $filled = $this->shape($nc, $nr);

        if ('werte' === $variant) {
            $pool = ['a', 'b'];
        } else {
            $n = (string) U::pick($hard ? [3, 4, 5, 6, 8, 10, 12] : [2, 3, 4, 5, 10]);
            $pool = $hard ? ['a', 'b', $n] : ['a', $n];
        }

        do {
            $cols = array_map(static fn (): string => U::pick($pool), range(1, $nc));
            $rows = array_map(static fn (): string => U::pick($pool), range(1, $nr));
            $labels = array_unique([...$cols, ...$rows]);
        } while (\count($labels) < min(\count($pool), 2) || !\in_array('a', $labels, true));

        [$area, $perimeter] = $this->measures($cols, $rows, $filled);
        $svg = BinomialSvg::figure($cols, $rows, $filled);

        if ('terme' === $variant) {
            return $this->task('rechteckfiguren', 'Gib den Flächeninhalt A und den Umfang u der blauen Figur als Term an.', [
                $this->term('A =', $area->plain(), 'any'),
                $this->term('u =', $perimeter->plain(), 'any'),
            ], ['A = '.$area->html(), 'u = '.$perimeter->html()], ['svg' => $svg]);
        }

        // Values for a and b, on the hard level in different units (results in cm)
        $a = mt_rand($hard ? 5 : 3, $hard ? 15 : 9);
        $b = mt_rand(1, max(1, $a - 1));
        [$ta, $tb] = [$a.' cm', $b.' cm'];

        if ($hard) {
            [$ta, $tb] = U::pick([
                [($a * 10).' mm', $b.' cm'],
                [$a.' cm', ($b * 10).' mm'],
            ]);
        }

        $A = $this->evaluate($area, ['a' => $a, 'b' => $b]);
        $u = $this->evaluate($perimeter, ['a' => $a, 'b' => $b]);
        $q = $hard
            ? 'Berechne den Flächeninhalt A und den Umfang u der blauen Figur für {0} und {1}. Gib die Resultate in cm an.'
            : 'Berechne den Flächeninhalt A und den Umfang u der blauen Figur für {0} und {1}.';

        return $this->task('rechteckfiguren', self::fill($q, ['<i>a</i> = '.$ta, '<i>b</i> = '.$tb]), [
            $this->term('A =', $A, 'any', 1, ' cm²', 'num'),
            $this->term('u =', $u, 'any', 1, ' cm', 'num'),
        ], [
            'A = '.$area->html().' = '.$A.' cm²',
            'u = '.$perimeter->html().' = '.$u.' cm',
        ], ['svg' => $svg]);
    }

    /**
     * Side length x from the area or the perimeter.
     */
    private function seite(bool $hard): array
    {
        if ($hard) {
            [$nc, $nr] = [mt_rand(2, 3), mt_rand(2, 3)];
            $filled = $this->shape($nc, $nr, true);
            $rows = array_map(static fn (): string => (string) U::pick([10, 20, 30, 40]), range(1, $nr));
            $cols = array_map(static fn (): string => (string) U::pick([10, 20, 30, 40]), range(1, $nc));
            $cols[mt_rand(0, $nc - 1)] = 'x';
        } else {
            [$cols, $rows, $filled] = [['x'], [(string) mt_rand(3, 12)], [[true]]];
        }

        [$area, $perimeter] = $this->measures($cols, $rows, $filled);
        $useArea = U::chance();
        $p = $useArea ? $area : $perimeter;
        $x = mt_rand(4, $hard ? 60 : 40);
        $value = $this->evaluate($p, ['x' => $x]);
        $name = $useArea ? 'A' : 'u';
        $q = $useArea ? 'Bestimme x. Der Flächeninhalt A beträgt {0}.' : 'Bestimme x. Der Umfang u beträgt {0}.';

        return $this->task('rechteckfiguren', self::fill($q, [$value]), [
            $this->term('<i>x</i> =', (string) $x, 'any', 1, '', 'num'),
        ], [$name.' = '.$p->html().' = '.$value.' → <i>x</i> = '.$x], ['svg' => BinomialSvg::figure($cols, $rows, $filled)]);
    }

    /**
     * Connected figure in a grid, every row and column used.
     *
     * @return list<list<bool>>
     */
    private function shape(int $nc, int $nr, bool $notFull = false): array
    {
        for ($k = 0; $k < 500; ++$k) {
            $filled = [];
            $count = 0;

            for ($r = 0; $r < $nr; ++$r) {
                for ($c = 0; $c < $nc; ++$c) {
                    $filled[$r][$c] = mt_rand(0, 99) < 65;
                    $count += (int) $filled[$r][$c];
                }
            }

            if ($count < 3 || ($notFull && $count === $nc * $nr)) {
                continue;
            }

            $rowsOk = array_filter($filled, static fn (array $row): bool => \in_array(true, $row, true));
            $colsOk = array_filter(range(0, $nc - 1), static fn (int $c): bool => \in_array(true, array_column($filled, $c), true));

            if (\count($rowsOk) === $nr && \count($colsOk) === $nc && $this->connected($filled, $count)) {
                return $filled;
            }
        }

        return array_fill(0, $nr, array_fill(0, $nc, true));
    }

    private function connected(array $filled, int $count): bool
    {
        foreach ($filled as $r => $row) {
            foreach ($row as $c => $on) {
                if ($on) {
                    $seen = ["$r,$c" => true];
                    $stack = [[$r, $c]];

                    while ($stack) {
                        [$y, $x] = array_pop($stack);

                        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dy, $dx]) {
                            $ny = $y + $dy;
                            $nx = $x + $dx;

                            if (!empty($filled[$ny][$nx]) && !isset($seen["$ny,$nx"])) {
                                $seen["$ny,$nx"] = true;
                                $stack[] = [$ny, $nx];
                            }
                        }
                    }

                    return \count($seen) === $count;
                }
            }
        }

        return false;
    }

    /**
     * Area and perimeter of the filled cells as polynomials.
     *
     * @return array{0: Polynomial, 1: Polynomial}
     */
    private function measures(array $cols, array $rows, array $filled): array
    {
        $area = [];
        $edges = [];

        foreach ($filled as $r => $row) {
            foreach ($row as $c => $on) {
                if (!$on) {
                    continue;
                }

                $area[] = "({$cols[$c]})*({$rows[$r]})";

                // An edge counts if there is no filled cell on the other side
                foreach ([[-1, 0, $cols[$c]], [1, 0, $cols[$c]], [0, -1, $rows[$r]], [0, 1, $rows[$r]]] as [$dy, $dx, $len]) {
                    if (empty($filled[$r + $dy][$c + $dx])) {
                        $edges[] = "($len)";
                    }
                }
            }
        }

        return [$this->parser->polynomial(implode('+', $area)), $this->parser->polynomial(implode('+', $edges))];
    }

    /**
     * Value of a polynomial for numbers instead of the variables, as text.
     */
    private function evaluate(Polynomial $p, array $values): string
    {
        $sum = new Fraction(0);

        foreach ($p->terms as $t) {
            $c = $t['c'];

            foreach ($t['v'] as $name => $e) {
                $c = $c->mul(new Fraction($values[$name] ** $e));
            }

            $sum = $sum->add($c);
        }

        return U::fmt($sum->n / $sum->d);
    }

    /* ---------- Geschickt rechnen ---------- */

    private function geschickt(bool $hard): array
    {
        return match (U::pick(['differenz', 'quadrat', 'malkreuz'])) {
            'differenz' => $this->differenz($hard),
            'quadrat' => $this->quadrat($hard),
            'malkreuz' => $this->malkreuz($hard),
        };
    }

    /**
     * 19 · 21 = (20 − 1)(20 + 1) = 400 − 1.
     */
    private function differenz(bool $hard): array
    {
        $n = U::pick($hard ? [100, 200, 60, 70, 80, 90, 1] : [10, 20, 30, 40, 50]);
        $steps = 1 === $n ? [0.1, 0.2, 0.3, 0.4] : ($n >= 100 ? [1, 2, 3, 4, 5] : [1, 2, 3, 4]);
        $ks = U::shuffle($steps);
        $ks = \array_slice($ks, 0, 3);
        sort($ks);
        $fields = [];
        $way = [];

        foreach ($ks as $k) {
            $lo = U::fmt($n - $k);
            $hi = U::fmt($n + $k);
            $res = U::fmt(round($n * $n - $k * $k, 4));
            $fields[] = $this->term($lo.' · '.$hi.' =', $res, 'any', 1, '', 'num');
            $way[] = $lo.' · '.$hi.' = ('.$n.' − '.U::fmt($k).')('.$n.' + '.U::fmt($k).') = '.$n.'<sup>2</sup> − '.U::fmt($k).'<sup>2</sup> = '.U::fmt($n * $n).' − '.U::fmt(round($k * $k, 4)).' = '.$res;
        }

        return $this->task('geschickt', 'Rechne geschickt ohne Taschenrechner: Benutze (a + b)(a − b) = a² − b².', $fields, $way);
    }

    /**
     * 29² = (30 − 1)² = 900 − 60 + 1.
     */
    private function quadrat(bool $hard): array
    {
        $fields = [];
        $way = [];
        $seen = [];

        while (\count($fields) < 3) {
            $base = $hard ? U::pick([20, 30, 40, 50, 60, 70, 80, 90, 100, 200]) : U::pick([10, 20, 30, 40, 50]);
            $k = mt_rand(1, $hard ? 3 : 2) * (U::chance() ? 1 : -1);
            $n = $base + $k;

            if (isset($seen[$n]) || $n < 11) {
                continue;
            }

            $seen[$n] = true;
            $op = $k > 0 ? '+' : '−';
            $fields[] = $this->term($n.'<sup>2</sup> =', (string) ($n * $n), 'any', 1, '', 'num');
            $way[] = $n.'<sup>2</sup> = ('.$base.' '.$op.' '.abs($k).')<sup>2</sup> = '.($base * $base).' '.$op.' '.(2 * $base * abs($k)).' + '.($k * $k).' = '.($n * $n);
        }

        return $this->task('geschickt', 'Berechne im Kopf. Benutze die binomischen Formeln.', $fields, $way);
    }

    /**
     * Multiplication cross for (p + q)²: complete it and give the square.
     */
    private function malkreuz(bool $hard): array
    {
        $p = $hard ? mt_rand(5, 20) : U::pick([10, 20, 30]);

        do {
            $q = mt_rand(2, $hard ? 15 : 9);
        } while ($q === $p);

        // Cells: header row, then rows with header and products
        $values = [[null, $p, $q], [$p, $p * $p, $p * $q], [$q, $p * $q, $q * $q]];

        if ($hard) {
            // Two products given, the rest asked
            $given = U::pick([[[1, 1], [1, 2]], [[1, 1], [2, 2]], [[2, 2], [2, 1]], [[1, 2], [2, 2]]]);
        } else {
            $given = [[0, 1], [0, 2], [1, 0], [2, 0]];
        }

        $fields = [];
        $cells = [];

        foreach ($values as $r => $row) {
            foreach ($row as $c => $v) {
                if (0 === $r && 0 === $c) {
                    $cells[$r][$c] = ['t' => '·'];
                } elseif (\in_array([$r, $c], $given, true)) {
                    $cells[$r][$c] = ['t' => (string) $v];
                } elseif (0 === $c && !\in_array([0, $r], $given, true)) {
                    // Left header: the same number as above, filled in with the field of the top row
                    $cells[$r][$c] = ['m' => null, 'src' => [0, $r]];
                } else {
                    $cells[$r][$c] = ['f' => \count($fields)];
                    $fields[] = $this->term('', (string) $v, 'any', 1, '', 'num');
                }
            }
        }

        // Mirrored left headers point to the field of the top row
        foreach ($cells as $r => $row) {
            foreach ($row as $c => $cell) {
                if (\array_key_exists('m', $cell)) {
                    $cells[$r][$c] = ['m' => $cells[$cell['src'][0]][$cell['src'][1]]['f']];
                }
            }
        }

        $total = ($p + $q) ** 2;
        $fields[] = $this->term('('.($hard ? '…' : $p).' + '.($hard ? '…' : $q).')<sup>2</sup> =', (string) $total, 'any', 1, '', 'num');
        $cells[3] = ['total' => \count($fields) - 1];

        return $this->task('geschickt', 'Ergänze das Malkreuz. Welche Quadratzahl ist dargestellt?', $fields, [
            '('.$p.' + '.$q.')<sup>2</sup> = '.($p * $p).' + '.($p * $q).' + '.($p * $q).' + '.($q * $q).' = '.$total,
        ], ['kreuz' => $cells]);
    }

    /* ---------- Terme am Rechteck ---------- */

    private function rechteckmodell(bool $hard): array
    {
        $variant = U::pick($hard ? ['koeffizient', 'zwei', 'quadrat', 'koeffizient'] : ['monom', 'monom', 'einfach']);
        $v = U::pick(['x', 'a', 'y']);
        $w = 'x' === $v ? 'y' : 'b';

        switch ($variant) {
            case 'monom':
                // m · (t1 + t2): one row
                $m = U::pick(['', '2', '3']).$v;
                [$p, $q] = $this->coprime(1, 9);
                $cols = [$p.$v, $q.$w];
                $rows = [$m];
                break;

            case 'einfach':
                [$cols, $rows] = [[$v, (string) mt_rand(1, 9)], [$v, (string) mt_rand(1, 9)]];
                break;

            case 'koeffizient':
                [$a, $p] = $this->coprime(2, 5, 1, 9);
                [$b, $q] = $this->coprime(1, 4, 1, 9);
                $cols = [$a.$v, (string) $p];
                $rows = [(1 === $b ? '' : $b).$v, (string) $q];
                break;

            case 'zwei':
                [$a, $c] = $this->coprime(1, 4);
                $cols = [(1 === $a ? '' : $a).$v, $w];
                $rows = [$v, (1 === $c ? '' : $c).'c'];
                break;

            default:
                // Square: (p v + q)²
                [$a, $p] = $this->coprime(1, 5, 1, 9);
                $cols = [(1 === $a ? '' : $a).$v, (string) $p];
                $rows = $cols;
        }

        $cols = array_map(static fn (string $t): string => preg_replace('/^1(?=[a-z])/', '', $t) ?? $t, $cols);
        $rows = array_map(static fn (string $t): string => preg_replace('/^1(?=[a-z])/', '', $t) ?? $t, $rows);
        $cells = [];

        foreach ($rows as $r => $rt) {
            foreach ($cols as $c => $ct) {
                $cells[$r][$c] = $this->parser->polynomial("($rt)*($ct)")->html();
            }
        }

        $width = implode('+', $cols);
        $height = implode('+', $rows);
        $factor = static fn (array $t): string => 1 === \count($t) ? $t[0] : '('.implode('+', $t).')';
        $product = 'quadrat' === $variant ? '('.$width.')^2' : $factor($rows).'*'.$factor($cols);
        $sum = $this->parser->polynomial($product);
        $brackets = (int) (\count($cols) > 1) + (int) (\count($rows) > 1);

        return $this->task('rechteckmodell', 'In den Feldern stehen die Flächeninhalte. Gib den Flächeninhalt des ganzen Rechtecks als Summe und als Produkt an.', [
            $this->term('A =', $sum->plain(), 'sum', 1, '', 'term', 'sum'),
            $this->term('A =', $product, 'complete', $brackets, '', 'term', 'product'),
        ], [
            'A = '.$sum->html().' = '.self::h($product),
        ], ['svg' => BinomialSvg::model($cols, $rows, $cells)]);
    }

    /* ---------- Ausmultiplizieren ---------- */

    private function ausmultiplizieren(bool $hard): array
    {
        $fields = [];
        $way = [];
        $seen = [];

        while (\count($fields) < 3) {
            [$expr, $line] = $hard ? $this->hardProduct() : $this->easyProduct();

            if (isset($seen[$expr])) {
                continue;
            }

            $seen[$expr] = true;
            $p = $this->parser->polynomial($expr);
            $fields[] = $this->term(self::h($expr).' =', $p->plain(), 'sum');
            $way[] = $line.' = '.$p->html();
        }

        return $this->task('ausmultiplizieren', 'Schreibe ohne Klammern und fasse zusammen.', $fields, $way);
    }

    /**
     * @return array{0: string, 1: string} product (plain) and the first step of the solution (HTML)
     */
    private function easyProduct(): array
    {
        $x = U::pick(self::LETTERS);
        $n = mt_rand(1, 9);

        switch (U::pick(['plus', 'minus', 'diff', 'mono', 'two'])) {
            case 'plus':
                return ["($x+$n)^2", self::h("$x^2").' + 2 · '.$n.self::h($x).' + '.$n.'<sup>2</sup>'];

            case 'minus':
                return ["($x-$n)^2", self::h("$x^2").' − 2 · '.$n.self::h($x).' + '.$n.'<sup>2</sup>'];

            case 'diff':
                return ["($x+$n)*($x-$n)", self::h("$x^2").' − '.$n.'<sup>2</sup>'];

            case 'mono':
                $k = mt_rand(2, 6);
                $y = U::pick(array_diff(self::LETTERS, [$x]));

                $ny = (1 === $n ? '' : $n).$y;

                return ["$k$x*($x+$ny)", "$k".self::h($x).' · '.self::h($x).' + '.$k.self::h($x).' · '.self::h($ny)];

            default:
                $m = mt_rand(1, 9);

                return ["($x+$n)*($x+$m)", self::h("$x^2").' + '.$m.self::h($x).' + '.$n.self::h($x).' + '.($n * $m)];
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function hardProduct(): array
    {
        [$x, $y] = \array_slice(U::shuffle(self::LETTERS), 0, 2);
        $p = mt_rand(2, 9);
        $q = mt_rand(2, 9);

        // Square of a binomial with the middle term written out
        $square = static function (string $s, string $t, string $op): array {
            $first = self::h("($s)^2");
            $second = self::h("($t)^2");

            return ["($s$op$t)^2", $first.' '.('+' === $op ? '+' : '−').' 2 · '.self::h($s).' · '.self::h($t).' + '.$second];
        };

        switch (U::pick(['quadrat', 'quadrat', 'dezimal', 'bruch', 'diff', 'allgemein'])) {
            case 'quadrat':
                return $square("$p$x", "$q$y", U::pick(['+', '-']));

            case 'dezimal':
                $d = U::pick(['0.5', '0.1', '0.2', '1.5']);

                return U::chance() ? $square("$d$x", (string) $q, U::pick(['+', '-'])) : $square("$q$y", "$d$x", '-');

            case 'bruch':
                $k = U::pick([2, 3, 4, 5]);

                return $square("$x/$k", "$k$y", U::pick(['+', '-']));

            case 'diff':
                $d = U::pick(["$q", '0.1', '0.5']);

                return ["($p$x+$d)*($p$x-$d)", self::h("($p$x)^2").' − '.self::h("($d)^2")];

            default:
                [$a, $b, $c, $e] = [mt_rand(2, 5), mt_rand(1, 5), mt_rand(2, 5), mt_rand(1, 5)];

                return ["($a$x+$b$y)*($c$x-$e$y)", self::h("$a$x*$c$x").' − '.self::h("$a$x*$e$y").' + '.self::h("$b$y*$c$x").' − '.self::h("$b$y*$e$y")];
        }
    }

    /* ---------- Produkt und Summe: faktorisieren ---------- */

    private function faktorisieren(bool $hard): array
    {
        $variant = U::pick($hard ? ['erkennen', 'ausklammern', 'binomisch', 'aufspalten', 'kombi'] : ['erkennen', 'ausklammern', 'binomisch', 'aufspalten']);

        if ('erkennen' === $variant) {
            return $this->erkennen($hard);
        }

        $q = [
            'ausklammern' => 'Klammere so weit wie möglich aus.',
            'binomisch' => 'Schreibe als Produkt. Benutze eine binomische Formel.',
            'aufspalten' => 'Faktorisiere durch Aufspalten: Schreibe als Produkt von zwei Klammern.',
            'kombi' => 'Faktorisiere vollständig: zuerst ausklammern, dann eine binomische Formel oder Aufspalten.',
        ][$variant];

        $fields = [];
        $way = [];
        $seen = [];

        while (\count($fields) < 3) {
            [$product, $brackets, $line] = $this->factorisable($variant, $hard);
            $p = $this->parser->polynomial($product);

            if (isset($seen[$p->plain()])) {
                continue;
            }

            $seen[$p->plain()] = true;
            $fields[] = $this->term($p->html().' =', $product, 'complete', $brackets);
            $way[] = $p->html().' = '.$line;
        }

        return $this->task('faktorisieren', $q, $fields, $way);
    }

    /**
     * @return array{0: string, 1: int, 2: string} product (plain), bracket factors, solution step (HTML)
     */
    private function factorisable(string $variant, bool $hard): array
    {
        $x = U::pick(self::LETTERS);
        $y = U::pick(array_diff(self::LETTERS, [$x]));

        switch ($variant) {
            case 'ausklammern':
                $g = mt_rand(2, $hard ? 12 : 9);

                if ($hard && U::chance()) {
                    // Three terms, sometimes with a variable in the common factor
                    $z = U::pick(array_diff(self::LETTERS, [$x, $y]));

                    do {
                        [$a, $b, $c] = [mt_rand(1, 9), mt_rand(1, 9), mt_rand(1, 9)];
                    } while (1 !== U::gcd(U::gcd($a, $b), $c));

                    $common = U::pick(["$g", "$g$x", $x]);
                    $inner = U::pick(["$a$x-$b$y+$c$z", "$a$x^2+$b$x+$c", "$a$y-$b$z+$c"]);
                    $inner = preg_replace('/(?<![\d.])1(?=[a-z])/', '', $inner) ?? $inner;

                    if (!$this->parser->polynomial($inner)->isPrimitive()) {
                        return $this->factorisable($variant, $hard);
                    }

                    return ["$common*($inner)", 1, self::h("$common($inner)")];
                }

                [$a, $b] = $this->coprime(1, 9);
                $common = U::pick(["$g", "$g$x", $x]);
                $inner = preg_replace('/(?<![\d.])1(?=[a-z])/', '', U::pick(["$a$x+$b$y", "$a$x-$b$y", "$a$x+$b"])) ?? '';

                return ["$common*($inner)", 1, self::h("$common($inner)")];

            case 'binomisch':
                if ($hard) {
                    [$a, $b] = $this->coprime(2, 11, 1, 9);
                    $s = "$a$x";
                    $t = U::pick(["$b$y", $y, (string) $b]);
                } else {
                    $s = $x;
                    $t = U::pick([(string) mt_rand(1, 10), $y]);
                }

                return match (U::pick(['plus', 'minus', 'diff'])) {
                    'plus' => ["($s+$t)^2", 2, self::h("($s)^2").' + 2 · '.self::h($s).' · '.self::h($t).' + '.self::h("($t)^2").' = '.self::h("($s+$t)^2")],
                    'minus' => ["($s-$t)^2", 2, self::h("($s)^2").' − 2 · '.self::h($s).' · '.self::h($t).' + '.self::h("($t)^2").' = '.self::h("($s-$t)^2")],
                    'diff' => ["($s+$t)*($s-$t)", 2, self::h("($s)^2").' − '.self::h("($t)^2").' = '.self::h("($s+$t)($s-$t)")],
                };

            case 'aufspalten':
                do {
                    $p = $hard ? mt_rand(-12, 12) : mt_rand(1, 9);
                    $q = $hard ? mt_rand(-12, 12) : mt_rand(1, 9);
                } while (0 === $p || 0 === $q || 0 === $p + $q || $p === $q || ($hard && $p > 0 && $q > 0));

                $bracket = static fn (int $n): string => "($x".($n < 0 ? '-' : '+').abs($n).')';
                $product = $bracket($p).'*'.$bracket($q);
                $sum = $p + $q;

                return [$product, 2, self::h($bracket($p).$bracket($q)).', <span class="st-t">'.self::fill('denn {0} und {1}', [
                    self::num($p).' · '.self::paren($q).' = '.self::num($p * $q),
                    self::num($p).' + '.self::paren($q).' = '.self::num($sum),
                ]).'</span>'];

            default:
                // kombi: k · (binomial formula or two brackets)
                $k = mt_rand(2, 5);
                $n = mt_rand(1, 6);
                $m = mt_rand(1, 6);
                [$inner, $brackets] = U::pick([["($x+$n)^2", 2], ["($x-$n)^2", 2], ["($x+$n)*($x-$n)", 2], ["($x+$n)*($x+$m)", 2]]);

                if (str_contains($inner, '*') && "($x+$n)*($x+$m)" === $inner && $n === $m) {
                    $inner = "($x+$n)^2";
                }

                $expanded = $this->parser->polynomial($inner);

                return ["$k*$inner", $brackets, $k.'('.$expanded->html().') = '.self::h("$k$inner")];
        }
    }

    /**
     * Summenterm or Produktterm: four terms, two of each.
     */
    private function erkennen(bool $hard): array
    {
        [$n, $m, $k] = [mt_rand(2, 9), mt_rand(2, 9), mt_rand(2, 5)];
        $products = ["$k"."x(x+$n)", "(x+$n"."y)^2", "x^2($n+y)", "$k(x+y)(x+$n)", "(x-$n)(x+$n)", "$k"."a(b-$n)"];
        $sums = ["x^2+$n"."x+$m", "$k"."x(x+$n)-$m"."x", "x+$n(x+$m)", "(x+$n"."y)-$m", "xy+$n"."y+1", "x-($n"."x+$m)", "$k(x+$n)+$m", "(a+$n)^2-$m"];
        $terms = [
            ...array_map(static fn (string $t): array => [$t, 1], \array_slice(U::shuffle($products), 0, 2)),
            ...array_map(static fn (string $t): array => [$t, 0], \array_slice(U::shuffle($sums), 0, 2)),
        ];
        $terms = U::shuffle($terms);

        $fields = array_map(fn (array $t): array => $this->choice(self::h($t[0]), $t[1]), $terms);
        $way = array_map(static fn (array $t): string => '<span class="st-t">'.self::fill($t[1] ? '{0} ist ein Produktterm.' : '{0} ist ein Summenterm.', [self::h($t[0])]).'</span>', $terms);

        return $this->task('faktorisieren', 'Ist der Term ein Summenterm oder ein Produktterm? Achte darauf, was zuletzt gerechnet wird.', $fields, $way);
    }

    /* ---------- Helpers ---------- */

    /**
     * Two numbers with greatest common divisor 1.
     *
     * @return array{0: int, 1: int}
     */
    private function coprime(int $min, int $max, int $min2 = 0, int $max2 = 0): array
    {
        $min2 = $min2 ?: $min;
        $max2 = $max2 ?: $max;

        do {
            $a = mt_rand($min, $max);
            $b = mt_rand($min2, $max2);
        } while (1 !== U::gcd($a, $b));

        return [$a, $b];
    }

    /**
     * Term or number field: [public part, private part].
     */
    private function term(string $prompt, string $answer, string $form = 'any', int $factors = 1, string $unit = '', string $kind = 'term', string|null $tag = null): array
    {
        $p = $this->parser->polynomial($answer);

        if (null === $p) {
            throw new \LogicException('Answer cannot be read: '.$answer);
        }

        $show = match ($form) {
            'product', 'complete' => self::h($answer),
            default => $p->html(),
        };

        return [
            'pub' => array_filter(['kind' => $kind, 'prompt' => $prompt, 'unit' => $unit, 'tag' => $tag], static fn ($v): bool => null !== $v && '' !== $v),
            'priv' => ['ans' => $answer, 'form' => $form, 'factors' => $factors, 'show' => $show.$unit],
        ];
    }

    private function choice(string $prompt, int $answer): array
    {
        return [
            'pub' => ['kind' => 'choice', 'prompt' => $prompt],
            'priv' => ['choice' => $answer, 'show' => $answer ? 'Produktterm' : 'Summenterm'],
        ];
    }

    private function task(string $topic, string $q, array $fields, array $way, array $extra = []): array
    {
        return [
            'q' => $q,
            'key' => md5(json_encode([$topic, $q, array_column(array_column($fields, 'priv'), 'ans'), array_column(array_column($fields, 'pub'), 'prompt'), $extra['svg'] ?? ''], JSON_THROW_ON_ERROR)),
            'a' => ['type' => 'terms', 'fields' => array_column($fields, 'pub'), 'v' => array_column($fields, 'priv'), ...$extra],
            'way' => '<ol class="st-steps">'.implode('', array_map(static fn (string $l): string => '<li>'.$l.'</li>', $way)).'</ol>',
            'points' => \count($fields),
            'area' => $topic,
        ];
    }

    /**
     * Plain term to HTML: (3x-2y)^2 → (3x − 2y)², a/5 as a fraction, * as a dot.
     */
    public static function h(string $plain): string
    {
        $s = preg_replace_callback('/(?<![\w)])([a-z0-9.]+)\/(\d+)/', static fn (array $m): string => U::f($m[1], $m[2]), $plain) ?? $plain;
        $s = preg_replace('/\^(\d)/', '<sup>$1</sup>', $s) ?? $s;
        $s = preg_replace('/(?<=[\w)>])([+\-])(?=[\w(<])/', ' $1 ', $s) ?? $s;
        $s = str_replace(['*', '-'], [' · ', '−'], $s);

        // No dot between a number and a bracket or a letter: 2(x + 1), 3x
        return preg_replace('/(\d|[a-z]|\)) · (?=\(|[a-z])/', '$1', $s) ?? $s;
    }

    private static function paren(int $n): string
    {
        return $n < 0 ? '('.self::num($n).')' : self::num($n);
    }

    private static function num(int $n): string
    {
        return str_replace('-', '−', (string) $n);
    }

    private static function fill(string $tpl, array $args): string
    {
        foreach ($args as $i => $v) {
            $tpl = str_replace('{'.$i.'}', (string) $v, $tpl);
        }

        return $tpl;
    }
}
