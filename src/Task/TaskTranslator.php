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

namespace Markocupic\ContaoSchoolTest\Task;

use Markocupic\ContaoSchoolTest\Task\Angle\AngleTexts;
use Markocupic\ContaoSchoolTest\Task\Binomial\BinomialTexts;
use Markocupic\ContaoSchoolTest\Task\Equation\EquationTexts;
use Markocupic\ContaoSchoolTest\Task\Triangle\TriangleTexts;

/**
 * Translates the German task texts for the frontend (fr, it, en).
 *
 * Tasks are generated and stored in German (the backend and the print sheets are German).
 * The student chooses a language before the test; questions, solution paths, units and
 * section titles are translated on output. Numbers and fraction markup stay untouched.
 */
final class TaskTranslator
{
    public const LANGUAGES = ['de', 'fr', 'it', 'en'];

    public const DEFAULT = 'de';

    /**
     * Bold placeholder {n} in the question templates.
     */
    private const B = '(<b>(?:(?!</b>).)*</b>)';

    /**
     * German question template => [fr, it, en]. {1}, {2} … are the bold parts in German order.
     */
    private const QUESTIONS = [
        // Kopfrechnen
        'Die Summe von {1} und {2} ist:' => ['La somme de {1} et {2} est&nbsp;:', 'La somma di {1} e {2} è:', 'The sum of {1} and {2} is:'],
        'Die Summe von {1}, {2} und {3} ist:' => ['La somme de {1}, {2} et {3} est&nbsp;:', 'La somma di {1}, {2} e {3} è:', 'The sum of {1}, {2} and {3} is:'],
        'Die Differenz von {1} und {2} ist:' => ['La différence entre {1} et {2} est&nbsp;:', 'La differenza tra {1} e {2} è:', 'The difference between {1} and {2} is:'],
        'Das Produkt von {1} und {2} ist:' => ['Le produit de {1} et {2} est&nbsp;:', 'Il prodotto di {1} e {2} è:', 'The product of {1} and {2} is:'],
        'Der Quotient von {1} und {2} ist:' => ['Le quotient de {1} par {2} est&nbsp;:', 'Il quoziente di {1} diviso {2} è:', 'The quotient of {1} and {2} is:'],
        'Das Doppelte von {1} ist:' => ['Le double de {1} est&nbsp;:', 'Il doppio di {1} è:', 'Double {1} is:'],
        'Halbiere {1}!' => ['Calcule la moitié de {1}&nbsp;!', 'Calcola la metà di {1}!', 'Halve {1}!'],
        'Verdopple {1}!' => ['Double {1}&nbsp;!', 'Raddoppia {1}!', 'Double {1}!'],
        'Verdreifache {1}!' => ['Triple {1}&nbsp;!', 'Triplica {1}!', 'Triple {1}!'],
        'Drittle {1}!' => ['Divise {1} par trois&nbsp;!', 'Dividi {1} per tre!', 'Divide {1} by three!'],
        'Addiere {1} zu {2}!' => ['Ajoute {1} à {2}&nbsp;!', 'Aggiungi {1} a {2}!', 'Add {1} to {2}!'],
        'Subtrahiere {1} von {2}!' => ['Soustrais {1} de {2}&nbsp;!', 'Sottrai {1} da {2}!', 'Subtract {1} from {2}!'],
        'Multipliziere {1} mit {2}!' => ['Multiplie {1} par {2}&nbsp;!', 'Moltiplica {1} per {2}!', 'Multiply {1} by {2}!'],
        'Vermindere {1} um {2}!' => ['Diminue {1} de {2}&nbsp;!', 'Diminuisci {1} di {2}!', 'Decrease {1} by {2}!'],
        'Berechne das Vierfache von {1}!' => ['Calcule le quadruple de {1}&nbsp;!', 'Calcola il quadruplo di {1}!', 'Calculate four times {1}!'],
        'Berechne das Fünffache von {1}!' => ['Calcule le quintuple de {1}&nbsp;!', 'Calcola il quintuplo di {1}!', 'Calculate five times {1}!'],
        'Berechne die Differenz von {1} und {2}!' => ['Calcule la différence entre {1} et {2}&nbsp;!', 'Calcola la differenza tra {1} e {2}!', 'Calculate the difference between {1} and {2}!'],
        'Berechne das Produkt der Zahlen {1}, {2} und {3}!' => ['Calcule le produit des nombres {1}, {2} et {3}&nbsp;!', 'Calcola il prodotto dei numeri {1}, {2} e {3}!', 'Calculate the product of the numbers {1}, {2} and {3}!'],
        'Halbiere das Produkt von {1} und {2}!' => ['Calcule la moitié du produit de {1} et {2}&nbsp;!', 'Calcola la metà del prodotto di {1} e {2}!', 'Halve the product of {1} and {2}!'],
        'Um wie viel ist {1} kleiner als {2}?' => ['De combien {1} est-il plus petit que {2}&nbsp;?', 'Di quanto {1} è minore di {2}?', 'By how much is {1} less than {2}?'],
        'Um wie viel ist {1} grösser als {2}?' => ['De combien {1} est-il plus grand que {2}&nbsp;?', 'Di quanto {1} è maggiore di {2}?', 'By how much is {1} greater than {2}?'],
        'Um wie viel ist die Summe von {1} und {2} grösser als ihre Differenz?' => ['De combien la somme de {1} et {2} est-elle plus grande que leur différence&nbsp;?', 'Di quanto la somma di {1} e {2} è maggiore della loro differenza?', 'By how much is the sum of {1} and {2} greater than their difference?'],
        'Berechne die Summe aus dem Produkt von {1} und {2} und der Zahl {3}.' => ['Calcule la somme du produit de {1} et {2} et du nombre {3}.', 'Calcola la somma del prodotto di {1} e {2} e del numero {3}.', 'Calculate the sum of the product of {1} and {2} and the number {3}.'],
        'Berechne die Differenz aus der Zahl {1} und dem Quotienten von {2} und {3}.' => ['Calcule la différence entre le nombre {1} et le quotient de {2} par {3}.', 'Calcola la differenza tra il numero {1} e il quoziente di {2} diviso {3}.', 'Calculate the difference between the number {1} and the quotient of {2} and {3}.'],
        'Berechne das Produkt aus der Summe von {1} und {2} und der Zahl {3}.' => ['Calcule le produit de la somme de {1} et {2} par le nombre {3}.', 'Calcola il prodotto della somma di {1} e {2} per il numero {3}.', 'Calculate the product of the sum of {1} and {2} and the number {3}.'],
        'Berechne den Quotienten aus der Summe von {1} und {2} und der Zahl {3}.' => ['Calcule le quotient de la somme de {1} et {2} par le nombre {3}.', 'Calcola il quoziente della somma di {1} e {2} diviso il numero {3}.', 'Calculate the quotient of the sum of {1} and {2} and the number {3}.'],
        'Berechne das Doppelte der Differenz von {1} und {2}.' => ['Calcule le double de la différence entre {1} et {2}.', 'Calcola il doppio della differenza tra {1} e {2}.', 'Calculate double the difference between {1} and {2}.'],
        'Berechne die Summe aus dem Produkt von {1} und {2} und dem Quotienten von {3} und {4}.' => ['Calcule la somme du produit de {1} et {2} et du quotient de {3} par {4}.', 'Calcola la somma del prodotto di {1} e {2} e del quoziente di {3} diviso {4}.', 'Calculate the sum of the product of {1} and {2} and the quotient of {3} and {4}.'],
        'Berechne die Differenz aus dem Produkt von {1} und {2} und dem Quotienten von {3} und {4}.' => ['Calcule la différence entre le produit de {1} et {2} et le quotient de {3} par {4}.', 'Calcola la differenza tra il prodotto di {1} e {2} e il quoziente di {3} diviso {4}.', 'Calculate the difference between the product of {1} and {2} and the quotient of {3} and {4}.'],
        'Berechne das Produkt aus der Differenz von {1} und {2} und der Summe von {3} und {4}.' => ['Calcule le produit de la différence entre {1} et {2} par la somme de {3} et {4}.', 'Calcola il prodotto della differenza tra {1} e {2} per la somma di {3} e {4}.', 'Calculate the product of the difference between {1} and {2} and the sum of {3} and {4}.'],
        'Berechne den Quotienten aus dem Produkt von {1} und {2} und der Zahl {3}.' => ['Calcule le quotient du produit de {1} et {2} par le nombre {3}.', 'Calcola il quoziente del prodotto di {1} e {2} diviso il numero {3}.', 'Calculate the quotient of the product of {1} and {2} and the number {3}.'],
        'Berechne die Differenz aus dem Quotienten von {1} und {2} und dem Quotienten von {3} und {4}.' => ['Calcule la différence entre le quotient de {1} par {2} et le quotient de {3} par {4}.', 'Calcola la differenza tra il quoziente di {1} diviso {2} e il quoziente di {3} diviso {4}.', 'Calculate the difference between the quotient of {1} and {2} and the quotient of {3} and {4}.'],

        // Bruchrechnen
        'Kürze {1} vollständig.' => ['Simplifie {1} au maximum.', 'Semplifica {1} ai minimi termini.', 'Simplify {1} fully.'],
        'Erweitere {1} mit {2}.' => ['Amplifie {1} par {2}.', 'Amplifica {1} per {2}.', 'Expand {1} by {2}.'],
        'Erweitere {1} auf den Nenner {2}.' => ['Amplifie {1} pour obtenir le dénominateur {2}.', 'Amplifica {1} fino al denominatore {2}.', 'Expand {1} to the denominator {2}.'],
        'Schreibe {1} als gemischte Zahl.' => ['Écris {1} sous forme de nombre mixte.', 'Scrivi {1} come numero misto.', 'Write {1} as a mixed number.'],
        'Schreibe {1} als unechten Bruch.' => ['Écris {1} sous forme de fraction impropre.', 'Scrivi {1} come frazione impropria.', 'Write {1} as an improper fraction.'],
        'Wie viel fehlt von {1} bis zu einem Ganzen?' => ['Combien manque-t-il à {1} pour faire un entier&nbsp;?', 'Quanto manca a {1} per arrivare a un intero?', 'How much is missing from {1} to make one whole?'],
        'Wie viel sind {1} von {2}?' => ['Combien font {1} de {2}&nbsp;?', 'Quanto fanno {1} di {2}?', 'What is {1} of {2}?'],
        'Wie viel sind {1} von {2} von {3}?' => ['Combien font {1} de {2} de {3}&nbsp;?', 'Quanto fanno {1} di {2} di {3}?', 'What is {1} of {2} of {3}?'],
        'Wie viel ist {1} von {2}?' => ['Combien font {1} de {2}&nbsp;?', 'Quanto fa {1} di {2}?', 'What is {1} of {2}?'],
        '{1} einer Zahl sind {2}. Wie heisst die Zahl?' => ['{1} d’un nombre valent {2}. Quel est ce nombre&nbsp;?', '{1} di un numero valgono {2}. Qual è il numero?', '{1} of a number is {2}. What is the number?'],
        '{1} einer Zahl sind {2}. Wie heisst die ganze Zahl?' => ['{1} d’un nombre valent {2}. Quel est ce nombre&nbsp;?', '{1} di un numero valgono {2}. Qual è il numero?', '{1} of a number is {2}. What is the number?'],
        'Wie viele Minuten sind {1} Stunden?' => ['Combien de minutes font {1} heures&nbsp;?', 'Quanti minuti sono {1} ore?', 'How many minutes are {1} hours?'],
        'Wie viele Rappen sind {1} Franken?' => ['Combien de centimes font {1} franc&nbsp;?', 'Quanti centesimi sono {1} franchi?', 'How many centimes are {1} francs?'],
        'Wie viele Gramm sind {1} kg?' => ['Combien de grammes font {1} kg&nbsp;?', 'Quanti grammi sono {1} kg?', 'How many grams are {1} kg?'],
        'Wie viele Gramm sind {1} von {2}?' => ['Combien de grammes font {1} de {2}&nbsp;?', 'Quanti grammi sono {1} di {2}?', 'How many grams are {1} of {2}?'],

        // Bruch · Prozent · Dezimal
        'Schreibe {1} als Prozentzahl.' => ['Écris {1} sous forme de pourcentage.', 'Scrivi {1} come percentuale.', 'Write {1} as a percentage.'],
        'Schreibe {1} als Bruch.' => ['Écris {1} sous forme de fraction.', 'Scrivi {1} come frazione.', 'Write {1} as a fraction.'],
        'Schreibe {1} als Dezimalbruch.' => ['Écris {1} sous forme de nombre décimal.', 'Scrivi {1} come numero decimale.', 'Write {1} as a decimal.'],
        'Schreibe {1} als Prozentzahl. Runde auf eine Stelle nach dem Punkt.' => ['Écris {1} sous forme de pourcentage. Arrondis à une décimale.', 'Scrivi {1} come percentuale. Arrotonda a una cifra decimale.', 'Write {1} as a percentage. Round to one decimal place.'],
        'Schreibe {1} als Dezimalbruch. Runde auf zwei Stellen nach dem Punkt.' => ['Écris {1} sous forme de nombre décimal. Arrondis à deux décimales.', 'Scrivi {1} come numero decimale. Arrotonda a due cifre decimali.', 'Write {1} as a decimal. Round to two decimal places.'],

        // Spiegeln, Drehen, Verschieben
        'Spiegle die Figur {1} an der Achse {2}.' => ['Effectue la symétrie axiale de la figure {1} par rapport à l’axe {2}.', 'Rifletti la figura {1} rispetto all’asse {2}.', 'Reflect the figure {1} in the axis {2}.'],
        'Spiegle die Figur {1} am Punkt {2}.' => ['Effectue la symétrie centrale de la figure {1} par rapport au point {2}.', 'Rifletti la figura {1} rispetto al punto {2}.', 'Reflect the figure {1} in the point {2}.'],
        'Drehe die Figur {1} um den Punkt {2} um {3} im Gegenuhrzeigersinn.' => ['Fais tourner la figure {1} autour du point {2} de {3} dans le sens inverse des aiguilles d’une montre.', 'Ruota la figura {1} attorno al punto {2} di {3} in senso antiorario.', 'Rotate the figure {1} about the point {2} by {3} anticlockwise.'],
        'Drehe die Figur {1} um den Punkt {2} um {3} im Gegenuhrzeigersinn (das ist dasselbe wie 90° im Uhrzeigersinn).' => ['Fais tourner la figure {1} autour du point {2} de {3} dans le sens inverse des aiguilles d’une montre (c’est la même chose que 90° dans le sens des aiguilles d’une montre).', 'Ruota la figura {1} attorno al punto {2} di {3} in senso antiorario (è lo stesso che 90° in senso orario).', 'Rotate the figure {1} about the point {2} by {3} anticlockwise (this is the same as 90° clockwise).'],
        'Verschiebe die Figur {1} um den Pfeil {2}.' => ['Effectue la translation de la figure {1} selon la flèche {2}.', 'Trasla la figura {1} secondo la freccia {2}.', 'Translate the figure {1} by the arrow {2}.'],

        // Prozent-Tabellen
        'Vervollständige die Tabelle.' => ['Complète le tableau.', 'Completa la tabella.', 'Complete the table.'],

        // Schätzen
        'Schätze: {1} von {2}' => ['Estime&nbsp;: {1} de {2}', 'Stima: {1} di {2}', 'Estimate: {1} of {2}'],
        'Wie viel etwa sind 100 %? {1} sind {2}.' => ['Combien valent environ 100&nbsp;%&nbsp;? {1} correspondent à {2}.', 'Quanto vale circa il 100 %? {1} corrispondono al {2}.', 'About how much is 100 %? {1} is {2}.'],
    ];

    /**
     * "Schätze den Anteil in Prozent: <b>a von b</b> NOUN" — nouns.
     */
    private const NOUNS = [
        'Schülerinnen und Schülern' => ['élèves', 'allievi', 'pupils'],
        'Personen' => ['personnes', 'persone', 'people'],
        'Ländern' => ['pays', 'paesi', 'countries'],
        'Kindern' => ['enfants', 'bambini', 'children'],
        'Velos' => ['vélos', 'biciclette', 'bikes'],
        'Bäumen' => ['arbres', 'alberi', 'trees'],
        'Fragen' => ['questions', 'domande', 'questions'],
        'Tagen' => ['jours', 'giorni', 'days'],
        'Besucherinnen und Besuchern' => ['visiteurs', 'visitatori', 'visitors'],
        'Stimmen' => ['voix', 'voti', 'votes'],
        'Losen' => ['billets de loterie', 'biglietti della lotteria', 'lottery tickets'],
        'Einwohnerinnen und Einwohnern' => ['habitants', 'abitanti', 'inhabitants'],
    ];

    /**
     * Section titles, instructions and table names (exact text). Plain text, escaped in the browser:
     * use a real no-break space, not &nbsp;.
     */
    private const TEXTS = [
        'Kopfrechnen bis 1000' => ['Calcul mental jusqu’à 1000', 'Calcolo mentale fino a 1000', 'Mental arithmetic up to 1000'],
        'Bruchrechnen' => ['Fractions', 'Frazioni', 'Fractions'],
        'Bruch · Prozent · Dezimal' => ['Fraction · pourcentage · décimal', 'Frazione · percentuale · decimale', 'Fraction · percentage · decimal'],
        'Prozent-Tabellen' => ['Tableaux de pourcentages', 'Tabelle delle percentuali', 'Percentage tables'],
        'Schätzaufgaben' => ['Estimations', 'Stime', 'Estimation'],
        'Rechne im Kopf und schreibe nur das Ergebnis auf.' => ['Calcule de tête et écris seulement le résultat.', 'Calcola a mente e scrivi solo il risultato.', 'Work it out in your head and write down only the result.'],
        'Gib Brüche vollständig gekürzt an. Gemischte Zahlen sind erlaubt.' => ['Simplifie les fractions au maximum. Les nombres mixtes sont permis.', 'Scrivi le frazioni ai minimi termini. I numeri misti sono ammessi.', 'Give fractions in lowest terms. Mixed numbers are allowed.'],
        'Gib Brüche vollständig gekürzt an.' => ['Simplifie les fractions au maximum.', 'Scrivi le frazioni ai minimi termini.', 'Give fractions in lowest terms.'],
        'Vervollständige die Tabelle. Die grau hinterlegte Spalte ist der Bezug.' => ['Complète le tableau. La colonne grise sert de référence.', 'Completa la tabella. La colonna grigia è il riferimento.', 'Complete the table. The grey column is the reference.'],
        'Schätze. Runde die Zahlen geschickt und rechne im Kopf. Höchstens 10 % Abweichung sind erlaubt.' => ['Estime. Arrondis les nombres habilement et calcule de tête. Un écart de 10 % au plus est admis.', 'Stima. Arrotonda i numeri in modo furbo e calcola a mente. È ammesso uno scarto massimo del 10 %.', 'Estimate. Round the numbers cleverly and work it out in your head. At most 10 % deviation is allowed.'],
        'Achsenspiegelung' => ['Symétrie axiale', 'Simmetria assiale', 'Reflection in a line'],
        'Punktspiegelung' => ['Symétrie centrale', 'Simmetria centrale', 'Point reflection'],
        'Drehung' => ['Rotation', 'Rotazione', 'Rotation'],
        'Verschiebung' => ['Translation', 'Traslazione', 'Translation'],
        'Gemischt' => ['Mélangé', 'Misto', 'Mixed'],
        'Achsenspiegelung mit GeoGebra' => ['Symétrie axiale avec GeoGebra', 'Simmetria assiale con GeoGebra', 'Reflection in a line with GeoGebra'],
        'Punktspiegelung mit GeoGebra' => ['Symétrie centrale avec GeoGebra', 'Simmetria centrale con GeoGebra', 'Point reflection with GeoGebra'],
        'Setze die Bildpunkte auf die Gitterpunkte. Ein Klick auf einen gesetzten Punkt entfernt ihn.' => ['Place les points images sur les points du quadrillage. Un clic sur un point placé le supprime.', 'Posiziona i punti immagine sui punti della griglia. Un clic su un punto già posizionato lo elimina.', 'Place the image points on the grid points. Clicking a placed point removes it.'],
        'Gewicht' => ['Poids', 'Peso', 'Weight'],
        'Länge' => ['Longueur', 'Lunghezza', 'Length'],
        'Preis' => ['Prix', 'Prezzo', 'Price'],
        'Volumen' => ['Volume', 'Volume', 'Volume'],
    ];

    /**
     * Instruction of the GeoGebra constructions (fr, it, en), %s: tolerance in mm.
     */
    private const INSTRUCTION_GGB = [
        'Construis les points images avec les outils de GeoGebra. Choisis d’abord le point image (A′, B′, …), puis clique dans le dessin à l’endroit voulu, de préférence sur un point d’intersection. Un écart jusqu’à %s mm est accepté. Pour finir, relie les points images par des segments pour obtenir la figure image&nbsp;: cela donne un point de plus.',
        'Costruisci i punti immagine con gli strumenti di GeoGebra. Scegli prima il punto immagine (A′, B′, …) e poi clicca nel disegno nel punto giusto, possibilmente su un punto di intersezione. Uno scarto fino a %s mm è accettato. Alla fine collega i punti immagine con dei segmenti per ottenere la figura immagine: vale un punto in più.',
        'Construct the image points with the GeoGebra tools. First choose the image point (A′, B′, …), then click on the spot in the drawing, ideally on an intersection point. A deviation of up to %s mm counts as correct. Finally, join the image points with segments to form the image figure; this gives one more point.',
    ];

    /**
     * Phrases of the solution paths (regex => [fr, it, en]), applied in this order.
     */
    private const WAY = [
        // Spiegeln, Drehen, Verschieben (whole sentences first)
        '/Zeichne mit «Senkrechte Gerade» die Senkrechte zur Achse s durch den Punkt\. Zeichne mit «Kreis mit Mittelpunkt durch Punkt» einen Kreis um den Schnittpunkt der Senkrechten mit s durch den Punkt\. Der Kreis schneidet die Senkrechte auf der anderen Seite im Bildpunkt\./u' => ['Avec «&nbsp;Droite perpendiculaire&nbsp;», trace la perpendiculaire à l’axe s passant par le point. Avec «&nbsp;Cercle (centre-point)&nbsp;», trace un cercle centré au point d’intersection de la perpendiculaire avec s et passant par le point. Le cercle coupe la perpendiculaire de l’autre côté au point image.', 'Con «Retta perpendicolare» traccia la perpendicolare all’asse s che passa per il punto. Con «Circonferenza dati il centro e un punto» traccia un cerchio con centro nel punto di intersezione della perpendicolare con s che passa per il punto. Il cerchio interseca la perpendicolare dall’altra parte nel punto immagine.', 'With “Perpendicular Line”, draw the perpendicular to the axis s through the point. With “Circle with Center through Point”, draw a circle around the intersection of the perpendicular and s through the point. The circle cuts the perpendicular on the other side at the image point.'],
        '/Verbinde zum Schluss die Bildpunkte mit «Strecke» zur Bildfigur\./u' => ['Pour finir, relie les points images avec «&nbsp;Segment&nbsp;» pour obtenir la figure image.', 'Alla fine collega i punti immagine con «Segmento» per ottenere la figura immagine.', 'Finally, join the image points with “Segment” to form the image figure.'],
        '/Zeichne mit «Gerade» die Gerade durch den Punkt und Z\. Zeichne mit «Kreis mit Mittelpunkt durch Punkt» den Kreis um Z durch den Punkt\. Er schneidet die Gerade auf der anderen Seite im Bildpunkt\./u' => ['Avec «&nbsp;Droite&nbsp;», trace la droite passant par le point et Z. Avec «&nbsp;Cercle (centre-point)&nbsp;», trace le cercle de centre Z passant par le point. Il coupe la droite de l’autre côté au point image.', 'Con «Retta» traccia la retta che passa per il punto e per Z. Con «Circonferenza dati il centro e un punto» traccia il cerchio con centro Z che passa per il punto. Interseca la retta dall’altra parte nel punto immagine.', 'With “Line”, draw the line through the point and Z. With “Circle with Center through Point”, draw the circle around Z through the point. It cuts the line on the other side at the image point.'],
        '/Die Achse ist schräg\. Gehe vom Punkt diagonal durch die Häuschen-Ecken bis zur Achse und auf der anderen Seite gleich weit weiter\./u' => ['L’axe est oblique. Pars du point en diagonale à travers les coins des carreaux jusqu’à l’axe, puis continue de la même distance de l’autre côté.', 'L’asse è obliquo. Dal punto vai in diagonale attraverso gli angoli dei quadretti fino all’asse e poi prosegui della stessa distanza dall’altra parte.', 'The axis is slanted. Go diagonally from the point through the corners of the squares to the axis and the same distance on the other side.'],
        '/Gehe vom Punkt rechtwinklig zur Achse und zähle die Häuschen\. Auf der anderen Seite gehst du gleich weit\./u' => ['Pars du point perpendiculairement à l’axe et compte les carreaux. De l’autre côté, va aussi loin.', 'Dal punto vai perpendicolarmente all’asse e conta i quadretti. Dall’altra parte vai altrettanto lontano.', 'Go from the point at right angles to the axis and count the squares. On the other side, go the same distance.'],
        '/Achtung: Die Achse liegt in der Mitte zwischen zwei Gitterlinien\./u' => ['Attention&nbsp;: l’axe se trouve au milieu entre deux lignes du quadrillage.', 'Attenzione: l’asse si trova a metà tra due linee della griglia.', 'Careful: the axis lies halfway between two grid lines.'],
        '/Gehe vom Punkt zu Z und zähle dabei die Häuschen nach links oder rechts und nach oben oder unten\. Von Z aus gehst du genau gleich weit in dieselbe Richtung weiter\./u' => ['Va du point jusqu’à Z en comptant les carreaux vers la gauche ou la droite et vers le haut ou le bas. À partir de Z, continue exactement de la même distance dans la même direction.', 'Vai dal punto a Z contando i quadretti verso sinistra o destra e verso l’alto o il basso. Da Z prosegui esattamente della stessa distanza nella stessa direzione.', 'Go from the point to Z and count the squares left or right and up or down. From Z, go exactly the same distance in the same direction.'],
        '/Eine Drehung um 180° ist dasselbe wie eine Punktspiegelung an Z\./u' => ['Une rotation de 180° est la même chose qu’une symétrie centrale par rapport à Z.', 'Una rotazione di 180° è uguale a una simmetria centrale rispetto a Z.', 'A rotation by 180° is the same as a point reflection in Z.'],
        '/Zähle den Weg von Z zum Punkt: a Häuschen nach rechts, b nach oben\. Nach der Drehung um 90° im Gegenuhrzeigersinn wird daraus: b nach links und a nach oben\./u' => ['Compte le chemin de Z au point&nbsp;: a carreaux vers la droite, b vers le haut. Après la rotation de 90° dans le sens inverse des aiguilles d’une montre, cela devient&nbsp;: b vers la gauche et a vers le haut.', 'Conta il percorso da Z al punto: a quadretti verso destra, b verso l’alto. Dopo la rotazione di 90° in senso antiorario diventa: b verso sinistra e a verso l’alto.', 'Count the way from Z to the point: a squares to the right, b up. After the rotation by 90° anticlockwise this becomes: b to the left and a up.'],
        '/Zähle den Weg von Z zum Punkt: a Häuschen nach rechts, b nach oben\. Nach der Drehung um 270° im Gegenuhrzeigersinn wird daraus: b nach rechts und a nach unten\./u' => ['Compte le chemin de Z au point&nbsp;: a carreaux vers la droite, b vers le haut. Après la rotation de 270° dans le sens inverse des aiguilles d’une montre, cela devient&nbsp;: b vers la droite et a vers le bas.', 'Conta il percorso da Z al punto: a quadretti verso destra, b verso l’alto. Dopo la rotazione di 270° in senso antiorario diventa: b verso destra e a verso il basso.', 'Count the way from Z to the point: a squares to the right, b up. After the rotation by 270° anticlockwise this becomes: b to the right and a down.'],
        '/Für «nach links» oder «nach unten» gilt dieselbe Regel mit umgekehrter Richtung\./u' => ['Pour «&nbsp;vers la gauche&nbsp;» ou «&nbsp;vers le bas&nbsp;», la même règle s’applique dans le sens inverse.', 'Per «verso sinistra» o «verso il basso» vale la stessa regola nella direzione opposta.', 'For “to the left” or “down” the same rule applies in the opposite direction.'],
        '/Zähle die Häuschen des Pfeils:/u' => ['Compte les carreaux de la flèche&nbsp;:', 'Conta i quadretti della freccia:', 'Count the squares of the arrow:'],
        '/(\d+) nach rechts/u' => ['$1 vers la droite', '$1 verso destra', '$1 to the right'],
        '/(\d+) nach links/u' => ['$1 vers la gauche', '$1 verso sinistra', '$1 to the left'],
        '/(\d+) nach oben/u' => ['$1 vers le haut', '$1 verso l’alto', '$1 up'],
        '/(\d+) nach unten/u' => ['$1 vers le bas', '$1 verso il basso', '$1 down'],
        '/Jeder Punkt wandert genau gleich\./u' => ['Chaque point se déplace exactement de la même façon.', 'Ogni punto si sposta esattamente allo stesso modo.', 'Every point moves in exactly the same way.'],

        '/Ein Ganzes in (\d+)tel umwandeln:/u' => ['Transformer un entier en fractions de dénominateur $1&nbsp;:', 'Trasformare un intero in frazioni con denominatore $1:', 'Turn one whole into fractions with denominator $1:'],
        '/also mit (\d+) erweitern:/u' => ['donc amplifier par $1&nbsp;:', 'quindi amplificare per $1:', 'so expand by $1:'],
        '/Teile Zähler und Nenner durch (\d+):/u' => ['Diviser le numérateur et le dénominateur par $1&nbsp;:', 'Dividere numeratore e denominatore per $1:', 'Divide numerator and denominator by $1:'],
        '/Multipliziere Zähler und Nenner mit (\d+):/u' => ['Multiplier le numérateur et le dénominateur par $1&nbsp;:', 'Moltiplicare numeratore e denominatore per $1:', 'Multiply numerator and denominator by $1:'],
        '/Zähler mal Zähler, Nenner mal Nenner:/u' => ['Numérateur fois numérateur, dénominateur fois dénominateur&nbsp;:', 'Numeratore per numeratore, denominatore per denominatore:', 'Numerator times numerator, denominator times denominator:'],
        '/Zähler addieren, Nenner bleibt:/u' => ['Additionner les numérateurs, le dénominateur reste&nbsp;:', 'Sommare i numeratori, il denominatore resta:', 'Add the numerators, the denominator stays:'],
        '/Zähler subtrahieren, Nenner bleibt:/u' => ['Soustraire les numérateurs, le dénominateur reste&nbsp;:', 'Sottrarre i numeratori, il denominatore resta:', 'Subtract the numerators, the denominator stays:'],
        '/Zähler mal (\d+):/u' => ['Numérateur fois $1&nbsp;:', 'Numeratore per $1:', 'Numerator times $1:'],
        '/Nenner mal (\d+):/u' => ['Dénominateur fois $1&nbsp;:', 'Denominatore per $1:', 'Denominator times $1:'],
        '/Zähler:/u' => ['Numérateurs&nbsp;:', 'Numeratori:', 'Numerators:'],
        '/Gemeinsamer Nenner/u' => ['Dénominateur commun', 'Denominatore comune', 'Common denominator'],
        '/Mit dem Kehrwert multiplizieren:/u' => ['Multiplier par l’inverse&nbsp;:', 'Moltiplicare per il reciproco:', 'Multiply by the reciprocal:'],
        '/Vor dem Multiplizieren kürzen:/u' => ['Simplifier avant de multiplier&nbsp;:', 'Semplificare prima di moltiplicare:', 'Simplify before multiplying:'],
        '/«von» heisst mal:/u' => ['«&nbsp;de&nbsp;» signifie «&nbsp;fois&nbsp;»&nbsp;:', '«di» significa «per»:', '“of” means “times”:'],
        '/Punkt vor Strich:/u' => ['Multiplication et division d’abord&nbsp;:', 'Prima moltiplicazioni e divisioni:', 'Multiplication and division first:'],
        '/Umwandeln:/u' => ['Convertir&nbsp;:', 'Convertire:', 'Convert:'],
        '/Gesuchte Prozente: Wert :/u' => ['Pourcentages cherchés&nbsp;: valeur :', 'Percentuali cercate: valore :', 'Percentages wanted: value :'],
        '/Achte auf die Einheiten:/u' => ['Attention aux unités&nbsp;:', 'Attenzione alle unità:', 'Watch the units:'],
        '/Genau wären es/u' => ['Valeur exacte&nbsp;:', 'Valore esatto:', 'Exact value:'],
        '/Wenn (.+?) sind, dann sind /u' => ['Si $1, alors ', 'Se $1, allora ', 'If $1, then '],
        '/Runde:/u' => ['Arrondis&nbsp;:', 'Arrotonda:', 'Round:'],
        '/\(das ist /u' => ['(c’est ', '(cioè ', '(that is '],
        '/ggT von (\d+) und (\d+) ist/u' => ['PGDC de $1 et $2&nbsp;:', 'MCD di $1 e $2:', 'GCD of $1 and $2 is'],
        '/(\d) Rest (\d)/u' => ['$1 reste $2', '$1 resto $2', '$1 remainder $2'],
        '/Produkt:/u' => ['Produit&nbsp;:', 'Prodotto:', 'Product:'],
        '/Quotienten:/u' => ['Quotients&nbsp;:', 'Quozienti:', 'Quotients:'],
        '/Quotient:/u' => ['Quotient&nbsp;:', 'Quoziente:', 'Quotient:'],
        '/Summe:/u' => ['Somme&nbsp;:', 'Somma:', 'Sum:'],
        '/Differenz:/u' => ['Différence&nbsp;:', 'Differenza:', 'Difference:'],
        '/Doppeltes:/u' => ['Double&nbsp;:', 'Doppio:', 'Double:'],
        '/(\d) Ganze\b/u' => ['$1 entiers', '$1 interi', '$1 wholes'],
        '/ Minuten\b/u' => [' minutes', ' minuti', ' minutes'],
        '/% von /u' => ['% de ', '% di ', '% of '],
        "/(\\d[\\d']*) von (\\d[\\d']*)/u" => ['$1 sur $2', '$1 su $2', '$1 out of $2'],
        '/, davon /u' => [', puis de cela ', ', poi di questo ', ', then of that '],
        '/\bvon\b/u' => ['de', 'di', 'of'],
        '/\bzusammen\b/u' => ['en tout', 'in totale', 'together'],
        '/\bDann\b/u' => ['Puis', 'Poi', 'Then'],
        '/\bdann\b/u' => ['puis', 'poi', 'then'],
        '/\balso\b/u' => ['donc', 'quindi', 'so'],
        '/\bund\b/u' => ['et', 'e', 'and'],
    ];

    /**
     * Units after a number (regex => [fr, it, en]).
     */
    private const UNITS = [
        // "40 Fr. Genau …": the dot also ends the sentence, keep it in English
        '/(\d|&nbsp;|\s|^)Fr\.(?=\s+[A-ZÀ-Ý])/u' => ['$1fr.', '$1fr.', '$1CHF.'],
        '/(\d|&nbsp;|\s|^)Fr\.(?![A-Za-zÀ-ÿ])/u' => ['$1fr.', '$1fr.', '$1CHF'],
        '/(\d|&nbsp;|\s|^)Rp\.(?![A-Za-zÀ-ÿ])/u' => ['$1ct.', '$1ct.', '$1ct.'],
        '/(\d|&nbsp;|\s|^)Liter\b/u' => ['$1litres', '$1litri', '$1litres'],
    ];

    /**
     * @var array<string, string>|null German question regex => template, built once
     */
    private array|null $patterns = null;

    public static function normalize(mixed $lang): string
    {
        $lang = strtolower(substr(trim((string) $lang), 0, 2));

        return \in_array($lang, self::LANGUAGES, true) ? $lang : self::DEFAULT;
    }

    public function question(string $html, string $lang): string
    {
        $i = $this->index($lang);

        if (null === $i) {
            return $html;
        }

        // Whole sentences with placeholders (angles, equations)
        $phrase = $this->phrase($html, $i);

        if (null !== $phrase) {
            return $phrase;
        }

        // "Schätze den Anteil in Prozent: <b>a von b</b> NOUN"
        if (preg_match('#^Schätze den Anteil in Prozent: <b>(.+?) von (.+?)</b> (.+)$#u', $html, $m)) {
            $noun = self::NOUNS[$m[3]][$i] ?? $m[3];
            $tpl = ['Estime la part en pourcentage&nbsp;: <b>%s sur %s</b> %s', 'Stima la percentuale: <b>%s su %s</b> %s', 'Estimate the percentage: <b>%s out of %s</b> %s'][$i];

            return \sprintf($tpl, $m[1], $m[2], $noun);
        }

        foreach ($this->patterns() as $regex => $translations) {
            if (preg_match($regex, $html, $m)) {
                $out = $translations[$i];

                for ($n = \count($m) - 1; $n >= 1; --$n) {
                    $out = str_replace('{'.$n.'}', $m[$n], $out);
                }

                return $this->units($out, $lang);
            }
        }

        // Pure calculations ("3/4 + 1/6 =") need no translation
        return $this->units($html, $lang);
    }

    public function way(string $html, string $lang): string
    {
        $i = $this->index($lang);

        if (null === $i) {
            return $html;
        }

        // Wrapped phrases (angles, equations) are translated as a whole, the word rules only apply outside
        $parts = preg_split('#(<span class="st-t">.*?</span>)#su', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $k => $part) {
            if (1 === $k % 2) {
                $inner = substr($part, \strlen('<span class="st-t">'), -\strlen('</span>'));
                $parts[$k] = '<span class="st-t">'.($this->phrase($inner, $i) ?? $inner).'</span>';

                continue;
            }

            foreach (self::WAY as $regex => $translations) {
                $part = preg_replace($regex, $translations[$i], $part);
            }

            $parts[$k] = $this->units($part, $lang);
        }

        return implode('', $parts);
    }

    /**
     * Section title, instruction or table name.
     */
    public function text(string $text, string $lang): string
    {
        // Construction with GeoGebra: the tolerance in mm is part of the instruction
        if (preg_match('/^Konstruiere die Bildpunkte mit den Werkzeugen von GeoGebra\. Wähle zuerst den Bildpunkt \(A′, B′, …\) und klicke dann in der Zeichnung auf die Stelle, am besten auf einen Schnittpunkt\. Bis ([\d.]+) mm Abweichung gilt als richtig\. Verbinde zum Schluss die Bildpunkte mit Strecken zur Bildfigur, das gibt einen Punkt mehr\.$/u', $text, $m) && null !== ($i = $this->index($lang))) {
            return sprintf(self::INSTRUCTION_GGB[$i], 'en' === $lang ? $m[1] : str_replace('.', ',', $m[1]));
        }

        $i = $this->index($lang);

        if (null === $i) {
            return $text;
        }

        return self::TEXTS[$text][$i] ?? AngleTexts::TITLES[$text][$i] ?? EquationTexts::TITLES[$text][$i] ?? TriangleTexts::TITLES[$text][$i] ?? BinomialTexts::TITLES[$text][$i] ?? $this->phrase($text, $i) ?? $text;
    }

    /**
     * Units in a suffix, a table cell or a solution ("12&nbsp;Fr.").
     */
    /**
     * Decimal comma in French and Italian, only in the text between the HTML tags.
     */
    public function decimals(string $html, string $lang): string
    {
        if ('fr' !== $lang && 'it' !== $lang) {
            return $html;
        }

        return preg_replace_callback('/(?<=^|>)([^<]+)/', static fn (array $m): string => preg_replace('/(\d)\.(\d)/', '$1,$2', $m[1]) ?? $m[1], $html) ?? $html;
    }

    public function units(string $html, string $lang): string
    {
        $i = $this->index($lang);

        if (null === $i) {
            return $html;
        }

        foreach (self::UNITS as $regex => $translations) {
            $html = preg_replace($regex, $translations[$i], $html);
        }

        return $html;
    }

    /**
     * Index into the translation arrays, null for German.
     */
    private function index(string $lang): int|null
    {
        $pos = array_search(self::normalize($lang), self::LANGUAGES, true);

        return $pos > 0 ? $pos - 1 : null;
    }

    /**
     * Translate a complete German phrase with placeholders, null if no template matches.
     */
    private function phrase(string $text, int $i): string|null
    {
        foreach (self::phrases() as $regex => [$replacements, $groups, $decimal]) {
            if (preg_match($regex, $text, $m)) {
                $out = $replacements[$i];

                foreach ($groups as $placeholder => $group) {
                    // Triangles: decimal comma in French and Italian
                    $value = $decimal && $i < 2 ? preg_replace('/(\d)\.(\d)/', '$1,$2', $m[$group]) : $m[$group];
                    $out = str_replace('{'.$placeholder.'}', $value, $out);
                }

                return $out;
            }
        }

        return null;
    }

    /**
     * German templates of the angle and equation tasks compiled to anchored regexes.
     * A placeholder used twice must have the same value both times.
     *
     * @return array<string, array{0: array<int, string>, 1: array<int, int>}>
     */
    private static function phrases(): array
    {
        static $compiled = null;

        if (null !== $compiled) {
            return $compiled;
        }

        $compiled = [];
        $templates = AngleTexts::PHRASES + EquationTexts::PHRASES + TriangleTexts::PHRASES + BinomialTexts::PHRASES;

        // Most literal text first: "bei {1} und {2}" before "bei {1}"
        uksort($templates, static fn (string $a, string $b): int => mb_strlen(preg_replace('/\{\d+\}/', '', $b)) <=> mb_strlen(preg_replace('/\{\d+\}/', '', $a)));

        foreach ($templates as $tpl => $translations) {
            $parts = preg_split('/\{(\d+)\}/', $tpl, -1, PREG_SPLIT_DELIM_CAPTURE);
            $regex = '';
            $groups = [];

            foreach ($parts as $k => $part) {
                if (0 === $k % 2) {
                    $regex .= preg_quote($part, '#');
                } elseif (isset($groups[$part])) {
                    $regex .= '\\'.$groups[$part];
                } else {
                    $groups[$part] = \count($groups) + 1;
                    $regex .= '(.+?)';
                }
            }

            $compiled['#^'.$regex.'$#su'] = [$translations, $groups, isset(TriangleTexts::PHRASES[$tpl]) || isset(BinomialTexts::PHRASES[$tpl])];
        }

        return $compiled;
    }

    private function patterns(): array
    {
        if (null === $this->patterns) {
            $this->patterns = [];

            foreach (self::QUESTIONS as $german => $translations) {
                $regex = preg_quote($german, '#');
                $regex = preg_replace('/\\\\\{\d+\\\\\}/', self::B, $regex);
                $this->patterns['#^'.$regex.'$#u'] = $translations;
            }
        }

        return $this->patterns;
    }
}
