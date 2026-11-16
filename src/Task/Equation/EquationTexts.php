<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Equation;

/**
 * Translations of the equation tasks: German => [fr, it, en].
 */
final class EquationTexts
{
    public const TITLES = [
        'x auf beiden Seiten' => ['x des deux côtés', 'x da entrambe le parti', 'x on both sides'],
        'Lösung als Bruch' => ['Solution sous forme de fraction', 'Soluzione come frazione', 'Solution as a fraction'],
        'Klammern auflösen' => ['Supprimer les parenthèses', 'Eliminare le parentesi', 'Expanding brackets'],
        'Brüche als Koeffizienten' => ['Fractions comme coefficients', 'Frazioni come coefficienti', 'Fractions as coefficients'],
        'Bruchterme' => ['Termes fractionnaires', 'Termini frazionari', 'Fraction terms'],
        'Profi-Mix' => ['Mélange expert', 'Mix per esperti', 'Expert mix'],
        'Binome (x² fällt weg)' => ['Binômes (x² disparaît)', 'Binomi (x² si elimina)', 'Binomials (x² cancels out)'],
        'Binome mit Koeffizienten' => ['Binômes avec coefficients', 'Binomi con coefficienti', 'Binomials with coefficients'],
        'Gleichungen mit Brüchen' => ['Équations avec fractions', 'Equazioni con frazioni', 'Equations with fractions'],
        'Binome' => ['Binômes', 'Binomi', 'Binomials'],
        'Grundform' => ['Forme de base', 'Forma di base', 'Basic form'],
        'Gemischt' => ['Mélangé', 'Misto', 'Mixed'],
        'Löse nach x auf. Schreibe Brüche als 3/4 und negative Zahlen mit Minus, zum Beispiel -3/4.' => ['Résous pour x. Écris les fractions sous la forme 3/4 et les nombres négatifs avec un moins, par exemple -3/4.', 'Risolvi rispetto a x. Scrivi le frazioni come 3/4 e i numeri negativi con il meno, per esempio -3/4.', 'Solve for x. Write fractions as 3/4 and negative numbers with a minus sign, for example -3/4.'],
    ];

    public const PHRASES = [
        // Operation "simplify" in the solution steps (German: TU = Terme umformen)
        'TU' => ['simplifier', 'semplificare', 'simplify'],
    ];
}
