<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Binomial;

/**
 * Translations of "Produkte von Binomen": German => [fr, it, en]. In French and Italian
 * the decimal point in the placeholders becomes a comma.
 */
final class BinomialTexts
{
    public const TITLES = [
        'Rechteckfiguren: Fläche und Umfang' => ['Figures rectangulaires&nbsp;: aire et périmètre', 'Figure rettangolari: area e perimetro', 'Rectangular figures: area and perimeter'],
        'Geschickt rechnen' => ['Calculer astucieusement', 'Calcolare in modo furbo', 'Clever calculation'],
        'Terme am Rechteck' => ['Termes et rectangles', 'Termini e rettangoli', 'Terms and rectangles'],
        'Ausmultiplizieren' => ['Développer', 'Sviluppare', 'Multiplying out'],
        'Produkt und Summe: faktorisieren' => ['Produit et somme&nbsp;: factoriser', 'Prodotto e somma: scomporre', 'Product and sum: factorising'],
        'Summenterm' => ['Somme', 'Somma', 'Sum'],
        'Produktterm' => ['Produit', 'Prodotto', 'Product'],
    ];

    public const PHRASES = [
        // Instructions of the sections
        'Lies die Masse an der Figur ab. Hochzahlen schreibst du mit ^ oder mit der Taste x², zum Beispiel 4a^2 + 6ab.' => [
            'Lis les mesures sur la figure. Écris les exposants avec ^ ou avec la touche x², par exemple 4a^2 + 6ab.',
            'Leggi le misure sulla figura. Scrivi gli esponenti con ^ o con il tasto x², per esempio 4a^2 + 6ab.',
            'Read the measures on the figure. Write powers with ^ or with the x² key, for example 4a^2 + 6ab.',
        ],
        'Rechne ohne Taschenrechner.' => ['Calcule sans calculatrice.', 'Calcola senza calcolatrice.', 'Calculate without a calculator.'],
        'Hochzahlen schreibst du mit ^ oder mit der Taste x². Mal-Punkte darfst du weglassen, zum Beispiel (x + 3)(x + 5).' => [
            'Écris les exposants avec ^ ou avec la touche x². Tu peux omettre les points de multiplication, par exemple (x + 3)(x + 5).',
            'Scrivi gli esponenti con ^ o con il tasto x². Puoi omettere i punti di moltiplicazione, per esempio (x + 3)(x + 5).',
            'Write powers with ^ or with the x² key. You may leave out multiplication dots, for example (x + 3)(x + 5).',
        ],
        'Hochzahlen schreibst du mit ^ oder mit der Taste x². Mal-Punkte darfst du weglassen, zum Beispiel 9x^2 − 12xy + 4y^2.' => [
            'Écris les exposants avec ^ ou avec la touche x². Tu peux omettre les points de multiplication, par exemple 9x^2 − 12xy + 4y^2.',
            'Scrivi gli esponenti con ^ o con il tasto x². Puoi omettere i punti di moltiplicazione, per esempio 9x^2 − 12xy + 4y^2.',
            'Write powers with ^ or with the x² key. You may leave out multiplication dots, for example 9x^2 − 12xy + 4y^2.',
        ],
        'Hochzahlen schreibst du mit ^ oder mit der Taste x². Mal-Punkte darfst du weglassen, zum Beispiel 2a(3a + b) oder (x + 3)(x − 5).' => [
            'Écris les exposants avec ^ ou avec la touche x². Tu peux omettre les points de multiplication, par exemple 2a(3a + b) ou (x + 3)(x − 5).',
            'Scrivi gli esponenti con ^ o con il tasto x². Puoi omettere i punti di moltiplicazione, per esempio 2a(3a + b) o (x + 3)(x − 5).',
            'Write powers with ^ or with the x² key. You may leave out multiplication dots, for example 2a(3a + b) or (x + 3)(x − 5).',
        ],
        'Hochzahlen schreibst du mit ^ oder mit der Taste x². Mal-Punkte darfst du weglassen.' => [
            'Écris les exposants avec ^ ou avec la touche x². Tu peux omettre les points de multiplication.',
            'Scrivi gli esponenti con ^ o con il tasto x². Puoi omettere i punti di moltiplicazione.',
            'Write powers with ^ or with the x² key. You may leave out multiplication dots.',
        ],

        // Questions
        'Gib den Flächeninhalt A und den Umfang u der blauen Figur als Term an.' => ['Donne l’aire A et le périmètre u de la figure bleue sous forme de terme.', 'Indica l’area A e il perimetro u della figura blu come termine.', 'Give the area A and the perimeter u of the blue figure as a term.'],
        'Berechne den Flächeninhalt A und den Umfang u der blauen Figur für {0} und {1}.' => ['Calcule l’aire A et le périmètre u de la figure bleue pour {0} et {1}.', 'Calcola l’area A e il perimetro u della figura blu per {0} e {1}.', 'Calculate the area A and the perimeter u of the blue figure for {0} and {1}.'],
        'Berechne den Flächeninhalt A und den Umfang u der blauen Figur für {0} und {1}. Gib die Resultate in cm an.' => ['Calcule l’aire A et le périmètre u de la figure bleue pour {0} et {1}. Donne les résultats en cm.', 'Calcola l’area A e il perimetro u della figura blu per {0} e {1}. Indica i risultati in cm.', 'Calculate the area A and the perimeter u of the blue figure for {0} and {1}. Give the results in cm.'],
        'Bestimme x. Der Flächeninhalt A beträgt {0}.' => ['Détermine x. L’aire A vaut {0}.', 'Determina x. L’area A è {0}.', 'Find x. The area A is {0}.'],
        'Bestimme x. Der Umfang u beträgt {0}.' => ['Détermine x. Le périmètre u vaut {0}.', 'Determina x. Il perimetro u è {0}.', 'Find x. The perimeter u is {0}.'],
        'Rechne geschickt ohne Taschenrechner: Benutze (a + b)(a − b) = a² − b².' => ['Calcule astucieusement sans calculatrice&nbsp;: utilise (a + b)(a − b) = a² − b².', 'Calcola in modo furbo senza calcolatrice: usa (a + b)(a − b) = a² − b².', 'Calculate cleverly without a calculator: use (a + b)(a − b) = a² − b².'],
        'Berechne im Kopf. Benutze die binomischen Formeln.' => ['Calcule de tête. Utilise les identités remarquables.', 'Calcola a mente. Usa i prodotti notevoli.', 'Calculate in your head. Use the binomial formulas.'],
        'Ergänze das Malkreuz. Welche Quadratzahl ist dargestellt?' => ['Complète la grille de multiplication. Quel carré est représenté&nbsp;?', 'Completa la griglia di moltiplicazione. Quale quadrato è rappresentato?', 'Complete the multiplication grid. Which square number is shown?'],
        'In den Feldern stehen die Flächeninhalte. Gib den Flächeninhalt des ganzen Rechtecks als Summe und als Produkt an.' => ['Les cases contiennent les aires. Donne l’aire du rectangle entier comme somme et comme produit.', 'Nei campi ci sono le aree. Indica l’area dell’intero rettangolo come somma e come prodotto.', 'The parts show their areas. Give the area of the whole rectangle as a sum and as a product.'],
        'Schreibe ohne Klammern und fasse zusammen.' => ['Écris sans parenthèses et réduis.', 'Scrivi senza parentesi e semplifica.', 'Write without brackets and simplify.'],
        'Klammere so weit wie möglich aus.' => ['Mets en évidence le plus grand facteur possible.', 'Raccogli il più possibile.', 'Factor out as much as possible.'],
        'Schreibe als Produkt. Benutze eine binomische Formel.' => ['Écris sous forme de produit. Utilise une identité remarquable.', 'Scrivi come prodotto. Usa un prodotto notevole.', 'Write as a product. Use a binomial formula.'],
        'Faktorisiere durch Aufspalten: Schreibe als Produkt von zwei Klammern.' => ['Factorise en décomposant&nbsp;: écris sous forme de produit de deux parenthèses.', 'Scomponi spezzando il termine: scrivi come prodotto di due parentesi.', 'Factorise by splitting: write as a product of two brackets.'],
        'Faktorisiere vollständig: zuerst ausklammern, dann eine binomische Formel oder Aufspalten.' => ['Factorise complètement&nbsp;: d’abord mettre en évidence, puis une identité remarquable ou la décomposition.', 'Scomponi completamente: prima raccogli, poi un prodotto notevole o la scomposizione.', 'Factorise completely: first factor out, then a binomial formula or splitting.'],
        'Ist der Term ein Summenterm oder ein Produktterm? Achte darauf, was zuletzt gerechnet wird.' => ['Le terme est-il une somme ou un produit&nbsp;? Regarde quelle opération est faite en dernier.', 'Il termine è una somma o un prodotto? Guarda quale operazione si fa per ultima.', 'Is the term a sum or a product? Look at what is calculated last.'],

        // Solution paths
        'denn {0} und {1}' => ['car {0} et {1}', 'perché {0} e {1}', 'because {0} and {1}'],
        '{0} ist ein Produktterm.' => ['{0} est un produit.', '{0} è un prodotto.', '{0} is a product.'],
        '{0} ist ein Summenterm.' => ['{0} est une somme.', '{0} è una somma.', '{0} is a sum.'],
    ];
}
