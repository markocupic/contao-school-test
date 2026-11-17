<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Term;

use Markocupic\ContaoSchoolTest\Task\Equation\Fraction;

/**
 * Reads terms as students type them: "3a^2 + 2ab", "3a²+2ab", "a(3a+2b)", "(x−3)·(x+5)",
 * "0,5x", "a/5", "2(x+1)^2". Single letters are variables, a missing multiplication sign is
 * allowed. Checks whether an answer is equivalent to the solution and has the required form.
 *
 * Forms: any (just equivalent), sum (without brackets and combined), product (a product with
 * at least the given number of bracket factors), complete (product, every bracket factor
 * cannot be factored out further).
 */
final class TermParser
{
    public const FORMS = ['any', 'sum', 'product', 'complete'];

    private const MAX_LENGTH = 120;

    private array $tokens = [];

    private int $pos = 0;

    /**
     * @return array{ok: bool, equivalent: bool}
     */
    public function check(mixed $answer, string $solution, string $form = 'any', int $factors = 1): array
    {
        $ast = \is_string($answer) ? $this->parse($answer) : null;
        $expected = $this->polynomial($solution);

        if (null === $ast || null === $expected) {
            return ['ok' => false, 'equivalent' => false];
        }

        try {
            $equivalent = $this->expand($ast)->equals($expected);
        } catch (\Throwable) {
            return ['ok' => false, 'equivalent' => false];
        }

        return ['ok' => $equivalent && $this->hasForm($ast, $answer, $form, $factors), 'equivalent' => $equivalent];
    }

    public function polynomial(string $term): Polynomial|null
    {
        $ast = $this->parse($term);

        if (null === $ast) {
            return null;
        }

        try {
            return $this->expand($ast);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Syntax tree, null if the input is not a term.
     */
    public function parse(string $input): array|null
    {
        $s = $this->normalize($input);

        if ('' === $s || mb_strlen($s) > self::MAX_LENGTH) {
            return null;
        }

        if (!preg_match_all('/\d+(?:\.\d+)?|\.\d+|[a-z]|[-+*\/^()]/', $s, $m) || implode('', $m[0]) !== $s) {
            return null;
        }

        $this->tokens = $m[0];
        $this->pos = 0;

        try {
            $ast = $this->sum();
        } catch (\Throwable) {
            return null;
        }

        return $this->pos === \count($this->tokens) ? $ast : null;
    }

    public function normalize(string $input): string
    {
        $s = mb_strtolower(trim($input));
        $s = preg_replace('/^[a-z]\s*=\s*/u', '', $s) ?? '';
        $s = str_replace(['−', '–', '—', '·', '⋅', '×', '∙', ':', ',', '²', '³', '[', ']', '{', '}'], ['-', '-', '-', '*', '*', '*', '*', '/', '.', '^2', '^3', '(', ')', '(', ')'], $s);

        return preg_replace('/\s+/u', '', $s) ?? '';
    }

    /* ---------- Form ---------- */

    private function hasForm(array $ast, string $answer, string $form, int $factors): bool
    {
        if ('any' === $form) {
            return true;
        }

        if ('sum' === $form) {
            if (str_contains($this->normalize($answer), '(')) {
                return false;
            }

            // Combined: every monomial only once
            $keys = [];

            foreach ($this->summands($ast) as $node) {
                $p = $this->expand($node);

                if (1 !== \count($p->terms)) {
                    return false;
                }

                $key = array_key_first($p->terms);

                if (isset($keys[$key])) {
                    return false;
                }

                $keys[$key] = true;
            }

            return true;
        }

        // Product: the outermost operation is a multiplication or a power
        while ('neg' === $ast[0]) {
            $ast = $ast[1];
        }

        if (!\in_array($ast[0], ['mul', 'pow'], true)) {
            return false;
        }

        $all = $this->factors($ast);

        // At least two factors: "(a + b)" alone is not a product
        if (\count($all) < 2) {
            return false;
        }

        $brackets = [];

        foreach ($all as $node) {
            $p = $this->expand($node);

            if (\count($p->terms) > 1) {
                $brackets[] = $p;
            }
        }

        if (\count($brackets) < $factors) {
            return false;
        }

        if ('complete' === $form) {
            foreach ($brackets as $p) {
                if (!$p->isPrimitive()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return list<array>
     */
    private function summands(array $ast): array
    {
        if ('add' === $ast[0]) {
            return array_map(static fn (array $s): array => $s[1], $ast[1]);
        }

        return [$ast];
    }

    /**
     * Factors of a product, powers counted with their exponent.
     *
     * @return list<array>
     */
    private function factors(array $ast): array
    {
        return match ($ast[0]) {
            'mul' => array_merge(...array_map(fn (array $f): array => $this->factors($f), $ast[1])),
            'pow' => array_merge(...array_fill(0, max(1, $ast[2]), $this->factors($ast[1]))),
            'neg' => $this->factors($ast[1]),
            default => [$ast],
        };
    }

    /* ---------- Expansion ---------- */

    private function expand(array $ast): Polynomial
    {
        return match ($ast[0]) {
            'n' => Polynomial::constant($ast[1]),
            'v' => Polynomial::variable($ast[1]),
            'neg' => $this->expand($ast[1])->neg(),
            'add' => array_reduce($ast[1], fn (Polynomial $acc, array $s): Polynomial => '-' === $s[0] ? $acc->sub($this->expand($s[1])) : $acc->add($this->expand($s[1])), Polynomial::constant(new Fraction(0))),
            'mul' => array_reduce($ast[1], fn (Polynomial $acc, array $f): Polynomial => $acc->mul($this->expand($f)), Polynomial::constant(new Fraction(1))),
            'pow' => $this->expand($ast[1])->pow($ast[2]),
            'inv' => $this->inverse($this->expand($ast[1])),
        };
    }

    private function inverse(Polynomial $p): Polynomial
    {
        $c = $p->constantValue();

        if (null === $c || $c->isZero()) {
            throw new \InvalidArgumentException('Division by a term');
        }

        return Polynomial::constant((new Fraction(1))->div($c));
    }

    /* ---------- Grammar ---------- */

    private function sum(): array
    {
        $parts = [['+', $this->product()]];

        while (\in_array($this->peek(), ['+', '-'], true)) {
            $op = $this->next();
            $parts[] = [$op, $this->product()];
        }

        return 1 === \count($parts) && '+' === $parts[0][0] ? $parts[0][1] : ['add', $parts];
    }

    private function product(): array
    {
        $factors = [$this->unary()];

        while (true) {
            $t = $this->peek();

            if ('*' === $t) {
                $this->next();
                $factors[] = $this->unary();
            } elseif ('/' === $t) {
                $this->next();
                $factors[] = ['inv', $this->unary()];
            } elseif (null !== $t && (preg_match('/^[\d.a-z]/', $t) || '(' === $t)) {
                // Missing multiplication sign: 3a, ab, 2(x+1), (x+1)(x+2)
                $factors[] = $this->power();
            } else {
                break;
            }
        }

        return 1 === \count($factors) ? $factors[0] : ['mul', $factors];
    }

    private function unary(): array
    {
        if ('-' === $this->peek()) {
            $this->next();

            return ['neg', $this->unary()];
        }

        if ('+' === $this->peek()) {
            $this->next();

            return $this->unary();
        }

        return $this->power();
    }

    private function power(): array
    {
        $base = $this->primary();

        if ('^' === $this->peek()) {
            $this->next();
            $e = $this->next();

            if (null === $e || !preg_match('/^\d$/', $e)) {
                throw new \InvalidArgumentException('Exponent');
            }

            return ['pow', $base, (int) $e];
        }

        return $base;
    }

    private function primary(): array
    {
        $t = $this->next();

        if (null === $t) {
            throw new \InvalidArgumentException('End');
        }

        if ('(' === $t) {
            $inner = $this->sum();

            if (')' !== $this->next()) {
                throw new \InvalidArgumentException('Bracket');
            }

            // Keep the bracket as its own node: (a+b) is a factor, not a sum, for the form checks
            return 'add' === $inner[0] ? ['mul', [$inner]] : $inner;
        }

        if (preg_match('/^[a-z]$/', $t)) {
            return ['v', $t];
        }

        if (preg_match('/^(\d*)(?:\.(\d+))?$/', $t, $m) && \strlen($t) <= 9) {
            $dec = $m[2] ?? '';
            $n = (int) (('' === $m[1] ? '0' : $m[1]).$dec);

            return ['n', new Fraction($n, 10 ** \strlen($dec))];
        }

        throw new \InvalidArgumentException('Token');
    }

    private function peek(): string|null
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function next(): string|null
    {
        return $this->tokens[$this->pos++] ?? null;
    }
}
