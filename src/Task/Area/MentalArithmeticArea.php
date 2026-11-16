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

namespace Markocupic\ContaoSchoolTest\Task\Area;

use Markocupic\ContaoSchoolTest\Task\Util as U;

/**
 * Kopfrechnen bis 1000 (wording follows the "Mit Köpfchen" worksheets).
 */
final class MentalArithmeticArea extends AbstractArea
{
    public function getAlias(): string
    {
        return 'kr';
    }

    public function getTitle(): string
    {
        return 'Kopfrechnen bis 1000';
    }

    public function getInstruction(): string
    {
        return 'Rechne im Kopf und schreibe nur das Ergebnis auf.';
    }

    protected function generators(int $level): array
    {
        return match ($level) {
            1 => $this->level1(),
            2 => [...$this->level2(), ...$this->terms2()],
            3 => $this->level3(),
            4 => [...$this->level4(), ...$this->terms4()],
            5 => [...$this->level5(), ...$this->terms5()],
            6 => [...$this->level3(), ...$this->level4(), ...$this->terms4(), ...$this->level5(), ...$this->terms5()],
        };
    }

    private function level1(): array
    {
        return [
            static function (): array {
                $a = U::ri(11, 60);
                $b = U::ri(11, 99 - $a);

                return U::num('Die Summe von '.U::b($a).' und '.U::b($b).' ist:', $a + $b, "$a + $b = ".($a + $b));
            },
            static function (): array {
                $a = U::ri(30, 99);
                $b = U::ri(11, $a - 5);

                return U::num('Die Differenz von '.U::b($a).' und '.U::b($b).' ist:', $a - $b, "$a − $b = ".($a - $b));
            },
            static function (): array {
                $a = U::ri(11, 49);

                return U::num('Das Doppelte von '.U::b($a).' ist:', 2 * $a, "$a + $a = ".(2 * $a));
            },
            static function (): array {
                $a = 2 * U::ri(6, 49);

                return U::num('Halbiere '.U::b($a).'!', $a / 2, "$a : 2 = ".($a / 2));
            },
            static function (): array {
                $a = U::ri(2, 10);
                $b = U::ri(2, 10);

                return U::num('Das Produkt von '.U::b($a).' und '.U::b($b).' ist:', $a * $b, "$a · $b = ".($a * $b));
            },
            static function (): array {
                $b = U::ri(2, 10);
                $q = U::ri(2, 10);
                $a = $b * $q;

                return U::num('Der Quotient von '.U::b($a).' und '.U::b($b).' ist:', $q, "$a : $b = $q");
            },
        ];
    }

    private function level2(): array
    {
        return [
            static function (): array {
                $a = 10 * U::ri(12, 70);
                $b = 10 * U::ri(3, min(60, intdiv(1000 - $a, 10)));

                return U::num('Die Summe von '.U::b($a).' und '.U::b($b).' ist:', $a + $b, "$a + $b = ".($a + $b));
            },
            static function (): array {
                $a = 10 * U::ri(25, 100);
                $b = 10 * U::ri(3, intdiv($a, 10) - 5);

                return U::num('Die Differenz von '.U::b($a).' und '.U::b($b).' ist:', $a - $b, "$a − $b = ".($a - $b));
            },
            static function (): array {
                $a = 10 * U::ri(11, 49);

                return U::num('Verdopple '.U::b($a).'!', 2 * $a, "$a · 2 = ".(2 * $a));
            },
            static function (): array {
                $a = 20 * U::ri(6, 49);

                return U::num('Halbiere '.U::b($a).'!', $a / 2, "$a : 2 = ".($a / 2));
            },
            static function (): array {
                $b = U::ri(2, 9);
                $a = 10 * U::ri(2, intdiv(100, $b));
                $t = intdiv($a, 10);

                return U::num('Multipliziere '.U::b($a).' mit '.U::b($b).'!', $a * $b, "$t · $b = ".($t * $b).", also $a · $b = ".($a * $b));
            },
            static function (): array {
                $b = U::ri(2, 9);
                $q = 10 * U::ri(2, intdiv(100, $b));
                $a = $b * $q;

                return U::num('Der Quotient von '.U::b($a).' und '.U::b($b).' ist:', $q, ($a / 10)." : $b = ".($q / 10).", also $a : $b = $q");
            },
            static function (): array {
                $a = 100 * U::ri(1, 8) + 10 * U::ri(0, 9);
                $b = 10 * U::ri(2, 9);

                return U::num('Addiere '.U::b($b).' zu '.U::b($a).'!', $a + $b, "$a + $b = ".($a + $b));
            },
            static function (): array {
                $a = 100 * U::ri(2, 10);
                $b = U::ri(11, 99);

                return U::num('Subtrahiere '.U::b($b).' von '.U::b($a).'!', $a - $b, "$a − $b = ".($a - $b));
            },
        ];
    }

    private function level3(): array
    {
        return [
            static function (): array {
                $a = U::ri(120, 900);
                $b = U::ri(12, min(99, 1000 - $a));

                return U::num('Die Summe von '.U::b($a).' und '.U::b($b).' ist:', $a + $b, "$a + $b = ".($a + $b));
            },
            static function (): array {
                $a = U::chance() ? 100 * U::ri(3, 10) : U::ri(250, 999);
                $b = U::ri(101, $a - 30);

                return U::num('Die Differenz von '.U::b($a).' und '.U::b($b).' ist:', $a - $b, "$a − $b = ".($a - $b));
            },
            static function (): array {
                $a = 2 * U::ri(51, 499);

                return U::num('Halbiere '.U::b($a).'!', $a / 2, "$a : 2 = ".($a / 2));
            },
            static function (): array {
                $a = U::ri(101, 499);

                return U::num('Verdopple '.U::b($a).'!', 2 * $a, "$a + $a = ".(2 * $a));
            },
            static function (): array {
                $a = U::ri(13, 99);

                return U::num('Berechne das Vierfache von '.U::b($a).'!', 4 * $a, "$a · 4 = $a · 2 · 2 = ".(4 * $a));
            },
            static function (): array {
                $a = U::ri(13, 99);

                return U::num('Berechne das Fünffache von '.U::b($a).'!', 5 * $a, "$a · 5 = $a · 10 : 2 = ".(5 * $a));
            },
            static function (): array {
                $b = U::ri(2, 9);
                $q = U::ri(11, min(150, intdiv(1000, $b)));
                $a = $b * $q;

                return U::num('Der Quotient von '.U::b($a).' und '.U::b($b).' ist:', $q, "$a : $b = $q");
            },
            static function (): array {
                $a = U::ri(11, 19);
                $b = U::ri(2, 12);

                return U::num('Das Produkt von '.U::b($a).' und '.U::b($b).' ist:', $a * $b, "$a · $b = 10 · $b + ".($a - 10)." · $b = ".($a * $b));
            },
            static function (): array {
                $a = U::ri(21, 99);
                $b = U::ri(2, min(9, intdiv(1000, $a)));

                return U::num('Multipliziere '.U::b($a).' mit '.U::b($b).'!', $a * $b, "$a · $b = ".($a * $b));
            },
            static function (): array {
                $a = U::ri(150, 999);
                $b = U::ri(12, 99);

                return U::num('Vermindere '.U::b($a).' um '.U::b($b).'!', $a - $b, "$a − $b = ".($a - $b));
            },
        ];
    }

    private function level4(): array
    {
        return [
            static function (): array {
                $b = U::ri(2, 12);
                $c = U::ri(2, 12);
                $a = U::ri(100, 1000 - $b * $c);

                return U::num('Die Summe von '.U::b($a).' und '.U::b("$b · $c").' ist:', $a + $b * $c, "Punkt vor Strich: $b · $c = ".($b * $c).", dann $a + ".($b * $c).' = '.($a + $b * $c));
            },
            static function (): array {
                $c = U::ri(2, 9);
                $q = U::ri(2, 12);
                $b = $c * $q;
                $a = U::ri(100, 1000 - $q);

                return U::num('Die Summe von '.U::b($a).' und '.U::b("$b : $c").' ist:', $a + $q, "Punkt vor Strich: $b : $c = $q, dann $a + $q = ".($a + $q));
            },
            static function (): array {
                $b = U::ri(2, 12);
                $c = U::ri(2, 12);
                $a = U::ri(100, 1000 - $b * $c);

                return U::num('Addiere '.U::b("$b · $c").' zu '.U::b($a).'!', $a + $b * $c, "$b · $c = ".($b * $c).", dann $a + ".($b * $c).' = '.($a + $b * $c));
            },
            static function (): array {
                $c = U::ri(2, 9);
                $q = U::ri(2, 12);
                $b = $c * $q;
                $a = U::ri(150, 999);

                return U::num('Subtrahiere '.U::b("$b : $c").' von '.U::b($a).'!', $a - $q, "$b : $c = $q, dann $a − $q = ".($a - $q));
            },
            static function (): array {
                do {
                    $b = U::ri(2, 12);
                    $c = U::ri(2, 12);
                } while (($b * $c) % 2);

                return U::num('Halbiere '.U::b("$b · $c").'!', $b * $c / 2, "$b · $c = ".($b * $c).', dann '.($b * $c).' : 2 = '.($b * $c / 2));
            },
            static function (): array {
                $b = U::ri(2, 12);
                $c = U::ri(2, 12);

                return U::num('Verdopple '.U::b("$b · $c").'!', 2 * $b * $c, "$b · $c = ".($b * $c).', dann '.($b * $c).' · 2 = '.(2 * $b * $c));
            },
            static function (): array {
                $a = 2 * U::ri(6, 60);

                return U::num('Verdreifache '.U::b("$a : 2").'!', $a / 2 * 3, "$a : 2 = ".($a / 2).', dann '.($a / 2).' · 3 = '.($a / 2 * 3));
            },
            static function (): array {
                do {
                    $a = U::ri(20, 99);
                    $b = U::ri(2, 12);
                } while (($a + $b) % 3);

                return U::num('Drittle '.U::b("$a + $b").'!', ($a + $b) / 3, "$a + $b = ".($a + $b).', dann '.($a + $b).' : 3 = '.(($a + $b) / 3));
            },
            static function (): array {
                do {
                    $a = U::ri(3, 12);
                    $b = U::ri(3, 20);
                } while (($a * $b) % 2);

                return U::num('Halbiere das Produkt von '.U::b($a).' und '.U::b($b).'!', $a * $b / 2, "$a · $b = ".($a * $b).', dann '.($a * $b).' : 2 = '.($a * $b / 2));
            },
            static function (): array {
                $a = U::ri(3, 12);
                $b = U::ri(3, 12);
                $c = U::ri(2, $a * $b - 1);

                return U::num('Berechne die Differenz von '.U::b("$a · $b").' und '.U::b($c).'!', $a * $b - $c, "$a · $b = ".($a * $b).', dann '.($a * $b)." − $c = ".($a * $b - $c));
            },
        ];
    }

    private function level5(): array
    {
        return [
            static function (): array {
                do {
                    [$a, $b, $c, $d] = [U::ri(2, 12), U::ri(2, 12), U::ri(2, 12), U::ri(2, 12)];
                } while ($a * $b >= $c * $d);

                return U::num('Um wie viel ist '.U::b("$a · $b").' kleiner als '.U::b("$c · $d").'?', $c * $d - $a * $b, "$a · $b = ".($a * $b).", $c · $d = ".($c * $d).', '.($c * $d).' − '.($a * $b).' = '.($c * $d - $a * $b));
            },
            static function (): array {
                do {
                    [$a, $b, $c, $d] = [U::ri(2, 12), U::ri(2, 12), U::ri(2, 12), U::ri(2, 12)];
                } while ($a * $b <= $c * $d);

                return U::num('Um wie viel ist '.U::b("$a · $b").' grösser als '.U::b("$c · $d").'?', $a * $b - $c * $d, "$a · $b = ".($a * $b).", $c · $d = ".($c * $d).', '.($a * $b).' − '.($c * $d).' = '.($a * $b - $c * $d));
            },
            static function (): array {
                $a = U::ri(3, 12);
                $b = U::ri(3, 12);
                $c = U::ri(2, $a * $b - 1);

                return U::num('Um wie viel ist '.U::b("$a · $b").' grösser als '.U::b($c).'?', $a * $b - $c, "$a · $b = ".($a * $b).', '.($a * $b)." − $c = ".($a * $b - $c));
            },
            static function (): array {
                $a = U::ri(5, 60);
                $b = U::ri(5, 99);
                $c = U::ri($a + $b + 1, $a + $b + 80);

                return U::num('Um wie viel ist '.U::b("$a + $b").' kleiner als '.U::b($c).'?', $c - $a - $b, "$a + $b = ".($a + $b).", $c − ".($a + $b).' = '.($c - $a - $b));
            },
            static function (): array {
                $b = U::ri(2, 9);
                $q = U::ri(3, 15);
                $a = $b * $q;
                $c = U::ri($q + 1, $q + 90);

                return U::num('Um wie viel ist '.U::b("$a : $b").' kleiner als '.U::b($c).'?', $c - $q, "$a : $b = $q, $c − $q = ".($c - $q));
            },
            static function (): array {
                do {
                    $a = U::pick([2, 4, 5, 10, 20, 25]);
                    $b = U::ri(2, 9);
                    $c = U::pick([2, 4, 5]);
                } while ($a * $b * $c > 1000);

                return U::num('Berechne das Produkt der Zahlen '.U::b($a).', '.U::b($b).' und '.U::b($c).'!', $a * $b * $c, "$a · $b = ".($a * $b).', '.($a * $b)." · $c = ".($a * $b * $c));
            },
            static function (): array {
                do {
                    [$a, $b, $c, $d] = [U::ri(3, 12), U::ri(3, 12), U::ri(2, 12), U::ri(2, 12)];
                } while ($a * $b <= $c * $d);

                return U::num('Berechne die Differenz von '.U::b("$a · $b").' und '.U::b("$c · $d").'!', $a * $b - $c * $d, ($a * $b).' − '.($c * $d).' = '.($a * $b - $c * $d));
            },
        ];
    }

    /**
     * Fachbegriffe mit runden Zahlen bis 1000.
     */
    private function terms2(): array
    {
        return [
            static function (): array {
                $a = 10 * U::ri(2, 30);
                $b = U::ri(2, min(9, intdiv(1000, $a)));

                return U::num('Das Produkt von '.U::b($a).' und '.U::b($b).' ist:', $a * $b, "$a · $b = ".($a * $b));
            },
            static function (): array {
                $b = 10 * U::ri(11, 89);

                return U::num('Die Differenz von '.U::b(1000).' und '.U::b($b).' ist:', 1000 - $b, "1000 − $b = ".(1000 - $b));
            },
            static function (): array {
                $a = 100 * U::ri(1, 6);
                $b = 10 * U::ri(1, 9);
                $c = 100 * U::ri(1, 9 - intdiv($a, 100));

                return U::num('Die Summe von '.U::b($a).', '.U::b($b).' und '.U::b($c).' ist:', $a + $b + $c, "$a + $b + $c = ".($a + $b + $c));
            },
        ];
    }

    /**
     * Zwei Schritte mit Fachbegriffen: «Die Summe aus dem Produkt von … und …».
     */
    private function terms4(): array
    {
        return [
            static function (): array {
                $b = U::ri(3, 12);
                $c = U::ri(3, 12);
                $a = U::ri(100, 1000 - $b * $c);

                return U::num('Berechne die Summe aus dem Produkt von '.U::b($b).' und '.U::b($c).' und der Zahl '.U::b($a).'.', $a + $b * $c, "Produkt: $b · $c = ".($b * $c).', Summe: '.($b * $c)." + $a = ".($a + $b * $c));
            },
            static function (): array {
                $c = U::ri(2, 9);
                $q = U::ri(3, 15);
                $b = $c * $q;
                $a = U::ri(100, 999);

                return U::num('Berechne die Differenz aus der Zahl '.U::b($a).' und dem Quotienten von '.U::b($b).' und '.U::b($c).'.', $a - $q, "Quotient: $b : $c = $q, Differenz: $a − $q = ".($a - $q));
            },
            static function (): array {
                do {
                    $a = U::ri(11, 60);
                    $b = U::ri(5, 40);
                    $c = U::ri(2, 9);
                } while (($a + $b) * $c > 1000);

                return U::num('Berechne das Produkt aus der Summe von '.U::b($a).' und '.U::b($b).' und der Zahl '.U::b($c).'.', ($a + $b) * $c, "Summe: $a + $b = ".($a + $b).', Produkt: '.($a + $b)." · $c = ".(($a + $b) * $c));
            },
            static function (): array {
                do {
                    $a = U::ri(20, 400);
                    $b = U::ri(5, 300);
                    $c = U::ri(2, 9);
                } while (($a + $b) % $c || $a + $b > 1000);

                return U::num('Berechne den Quotienten aus der Summe von '.U::b($a).' und '.U::b($b).' und der Zahl '.U::b($c).'.', ($a + $b) / $c, "Summe: $a + $b = ".($a + $b).', Quotient: '.($a + $b)." : $c = ".(($a + $b) / $c));
            },
            static function (): array {
                $a = U::ri(150, 600);
                $b = U::ri(max(20, $a - 500), $a - 20);

                return U::num('Berechne das Doppelte der Differenz von '.U::b($a).' und '.U::b($b).'.', 2 * ($a - $b), "Differenz: $a − $b = ".($a - $b).', Doppeltes: '.($a - $b).' · 2 = '.(2 * ($a - $b)));
            },
        ];
    }

    /**
     * Verschachtelte Fachbegriffe: zwei Teilergebnisse werden verknüpft.
     */
    private function terms5(): array
    {
        return [
            static function (): array {
                $a = U::ri(3, 12);
                $b = U::ri(3, 12);
                $d = U::ri(2, 9);
                $q = U::ri(2, 12);
                $c = $d * $q;

                return U::num('Berechne die Summe aus dem Produkt von '.U::b($a).' und '.U::b($b).' und dem Quotienten von '.U::b($c).' und '.U::b($d).'.', $a * $b + $q, "Produkt: $a · $b = ".($a * $b).", Quotient: $c : $d = $q, Summe: ".($a * $b)." + $q = ".($a * $b + $q));
            },
            static function (): array {
                do {
                    $a = U::ri(4, 12);
                    $b = U::ri(4, 12);
                    $d = U::ri(2, 9);
                    $q = U::ri(2, 12);
                } while ($a * $b <= $q);

                $c = $d * $q;

                return U::num('Berechne die Differenz aus dem Produkt von '.U::b($a).' und '.U::b($b).' und dem Quotienten von '.U::b($c).' und '.U::b($d).'.', $a * $b - $q, "Produkt: $a · $b = ".($a * $b).", Quotient: $c : $d = $q, Differenz: ".($a * $b)." − $q = ".($a * $b - $q));
            },
            static function (): array {
                do {
                    $a = U::ri(20, 90);
                    $b = U::ri(5, $a - 5);
                    $c = U::ri(2, 9);
                    $d = U::ri(1, 9);
                } while (($a - $b) * ($c + $d) > 1000);

                return U::num('Berechne das Produkt aus der Differenz von '.U::b($a).' und '.U::b($b).' und der Summe von '.U::b($c).' und '.U::b($d).'.', ($a - $b) * ($c + $d), "Differenz: $a − $b = ".($a - $b).", Summe: $c + $d = ".($c + $d).', Produkt: '.($a - $b).' · '.($c + $d).' = '.(($a - $b) * ($c + $d)));
            },
            static function (): array {
                do {
                    $a = U::ri(4, 25);
                    $b = U::ri(4, 25);
                    $c = U::ri(3, 9);
                } while (($a * $b) % $c || $a * $b > 1000 || $a * $b / $c < 5);

                return U::num('Berechne den Quotienten aus dem Produkt von '.U::b($a).' und '.U::b($b).' und der Zahl '.U::b($c).'.', $a * $b / $c, "Produkt: $a · $b = ".($a * $b).', Quotient: '.($a * $b)." : $c = ".($a * $b / $c));
            },
            static function (): array {
                $a = U::ri(200, 700);
                $b = U::ri(15, 150);

                return U::num('Um wie viel ist die Summe von '.U::b($a).' und '.U::b($b).' grösser als ihre Differenz?', 2 * $b, 'Summe: '.($a + $b).", Differenz: ".($a - $b).', '.($a + $b).' − '.($a - $b).' = '.(2 * $b));
            },
            static function (): array {
                $d = U::ri(2, 9);
                $q1 = U::ri(5, 60);
                $q2 = U::ri(2, $q1 - 1);

                return U::num('Berechne die Differenz aus dem Quotienten von '.U::b($q1 * $d).' und '.U::b($d).' und dem Quotienten von '.U::b($q2 * $d).' und '.U::b($d).'.', $q1 - $q2, 'Quotienten: '.($q1 * $d)." : $d = $q1, ".($q2 * $d)." : $d = $q2, Differenz: $q1 − $q2 = ".($q1 - $q2));
            },
        ];
    }
}
