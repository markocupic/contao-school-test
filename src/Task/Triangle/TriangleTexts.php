<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Triangle;

/**
 * Translations of the triangle constructions: German template => [fr, it, en].
 * {0}, {1}, … are placeholders (points, measures such as "a = 5.2 cm"). In French and
 * Italian the decimal point in the placeholders becomes a comma.
 */
final class TriangleTexts
{
    public const TITLES = [
        'SSS: drei Seiten' => ['CCC&nbsp;: trois côtés', 'LLL: tre lati', 'SSS: three sides'],
        'SWS: zwei Seiten und der Zwischenwinkel' => ['CAC&nbsp;: deux côtés et l’angle compris', 'LAL: due lati e l’angolo compreso', 'SAS: two sides and the included angle'],
        'WSW: eine Seite und die anliegenden Winkel' => ['ACA&nbsp;: un côté et les deux angles adjacents', 'ALA: un lato e i due angoli adiacenti', 'ASA: one side and the two adjacent angles'],
        'SsW: zwei Seiten und der Gegenwinkel der grösseren Seite' => ['CCA&nbsp;: deux côtés et l’angle opposé au plus grand', 'LLA: due lati e l’angolo opposto al lato maggiore', 'SSA: two sides and the angle opposite the longer side'],
        'Mit Höhe' => ['Avec une hauteur', 'Con un’altezza', 'With a height'],
        'Mit Winkelhalbierender' => ['Avec une bissectrice', 'Con una bisettrice', 'With an angle bisector'],
        'Mit Schwerelinie' => ['Avec une médiane', 'Con una mediana', 'With a median'],
    ];

    public const PHRASES = [
        // Instruction (tolerance in mm and degrees)
        'Konstruiere das Dreieck mit den Werkzeugen von GeoGebra. Die Ecken heissen A, B und C; mit den Knöpfen A, B, C gibst du einem anderen Punkt diesen Namen. Zeichne zum Schluss die drei Seiten als Strecken, das gibt einen Punkt mehr. Bis {0} mm und {1}° Abweichung gilt als richtig.' => [
            'Construis le triangle avec les outils de GeoGebra. Les sommets s’appellent A, B et C&nbsp;; avec les boutons A, B, C, tu donnes ce nom à un autre point. Pour finir, trace les trois côtés comme segments&nbsp;: cela donne un point de plus. Un écart jusqu’à {0} mm et {1}° est accepté.',
            'Costruisci il triangolo con gli strumenti di GeoGebra. I vertici si chiamano A, B e C; con i pulsanti A, B, C dai questo nome a un altro punto. Alla fine traccia i tre lati come segmenti: vale un punto in più. Uno scarto fino a {0} mm e {1}° è accettato.',
            'Construct the triangle with the GeoGebra tools. The vertices are called A, B and C; use the buttons A, B, C to give this name to another point. Finally draw the three sides as segments; this gives one more point. A deviation of up to {0} mm and {1}° counts as correct.',
        ],

        // Question
        'Konstruiere das Dreieck ABC mit {0}, {1} und {2}.' => ['Construis le triangle ABC avec {0}, {1} et {2}.', 'Costruisci il triangolo ABC con {0}, {1} e {2}.', 'Construct the triangle ABC with {0}, {1} and {2}.'],

        // Construction steps
        'Zeichne die Strecke {0} mit {1}.' => ['Trace le segment {0} avec {1}.', 'Disegna il segmento {0} con {1}.', 'Draw the segment {0} with {1}.'],
        'Zeichne um {0} einen Kreis mit dem Radius {1}.' => ['Trace un cercle de centre {0} et de rayon {1}.', 'Traccia una circonferenza di centro {0} e raggio {1}.', 'Draw a circle around {0} with radius {1}.'],
        'Die Kreise schneiden sich in {0}.' => ['Les cercles se coupent en {0}.', 'Le circonferenze si intersecano in {0}.', 'The circles meet at {0}.'],
        'Trage in {0} den Winkel {1} an.' => ['Reporte en {0} l’angle {1}.', 'Riporta in {0} l’angolo {1}.', 'Mark off the angle {1} at {0}.'],
        'Trage auf dem freien Schenkel von {0} aus {1} ab, das gibt {2}.' => ['Sur le côté libre, reporte {1} à partir de {0}&nbsp;: tu obtiens {2}.', 'Sul lato libero riporta {1} a partire da {0}: ottieni {2}.', 'On the free arm, mark off {1} from {0}: this gives {2}.'],
        'Die freien Schenkel schneiden sich in {0}.' => ['Les côtés libres se coupent en {0}.', 'I lati liberi si intersecano in {0}.', 'The free arms meet at {0}.'],
        'Der Kreis schneidet den freien Schenkel in {0}.' => ['Le cercle coupe le côté libre en {0}.', 'La circonferenza interseca il lato libero in {0}.', 'The circle cuts the free arm at {0}.'],
        'Zeichne im Abstand {0} eine Parallele zu {1} auf der Seite, wo {2} liegen soll.' => ['Trace une parallèle à {1} à la distance {0}, du côté où doit se trouver {2}.', 'Traccia una parallela a {1} alla distanza {0}, dalla parte in cui deve trovarsi {2}.', 'Draw a parallel to {1} at the distance {0}, on the side where {2} should lie.'],
        'Der freie Schenkel schneidet die Parallele in {0}.' => ['Le côté libre coupe la parallèle en {0}.', 'Il lato libero interseca la parallela in {0}.', 'The free arm cuts the parallel at {0}.'],
        'Der Kreis schneidet die Parallele in {0}.' => ['Le cercle coupe la parallèle en {0}.', 'La circonferenza interseca la parallela in {0}.', 'The circle cuts the parallel at {0}.'],
        'Zeichne eine Gerade g und im Abstand {0} eine Parallele dazu.' => ['Trace une droite g et une parallèle à g à la distance {0}.', 'Traccia una retta g e una sua parallela alla distanza {0}.', 'Draw a line g and a parallel to it at the distance {0}.'],
        'Wähle auf g den Punkt {0} und trage dort den Winkel {1} an.' => ['Choisis le point {0} sur g et reporte-y l’angle {1}.', 'Scegli il punto {0} su g e riporta lì l’angolo {1}.', 'Choose the point {0} on g and mark off the angle {1} there.'],
        'Trage in {0} an der Parallelen den Winkel {1} an (Wechselwinkel). Der Schenkel schneidet g in {2}.' => ['Reporte en {0}, contre la parallèle, l’angle {1} (angles alternes-internes). Ce côté coupe g en {2}.', 'Riporta in {0}, sulla parallela, l’angolo {1} (angoli alterni interni). Il lato interseca g in {2}.', 'At {0}, mark off the angle {1} against the parallel (alternate angles). The arm cuts g at {2}.'],
        'Wähle auf der Parallelen den Punkt {0}.' => ['Choisis le point {0} sur la parallèle.', 'Scegli il punto {0} sulla parallela.', 'Choose the point {0} on the parallel.'],
        'Zeichne um {0} einen Kreis mit dem Radius {1}. Er schneidet g in {2}.' => ['Trace un cercle de centre {0} et de rayon {1}. Il coupe g en {2}.', 'Traccia una circonferenza di centro {0} e raggio {1}. Interseca g in {2}.', 'Draw a circle around {0} with radius {1}. It cuts g at {2}.'],
        'Zeichne um {0} einen Kreis mit dem Radius {1}. Er schneidet g auf der anderen Seite in {2}.' => ['Trace un cercle de centre {0} et de rayon {1}. Il coupe g de l’autre côté en {2}.', 'Traccia una circonferenza di centro {0} e raggio {1}. Interseca g dall’altra parte in {2}.', 'Draw a circle around {0} with radius {1}. It cuts g on the other side at {2}.'],
        'Zeichne die Winkelhalbierende von {0} und trage darauf von {1} aus {2} ab, das gibt {3}.' => ['Trace la bissectrice de {0} et reporte {2} sur celle-ci à partir de {1}&nbsp;: tu obtiens {3}.', 'Traccia la bisettrice di {0} e riporta su di essa {2} a partire da {1}: ottieni {3}.', 'Draw the bisector of {0} and mark off {2} on it from {1}: this gives {3}.'],
        'Die Gerade durch {0} und {1} schneidet den freien Schenkel in {2}.' => ['La droite passant par {0} et {1} coupe le côté libre en {2}.', 'La retta per {0} e {1} interseca il lato libero in {2}.', 'The line through {0} and {1} cuts the free arm at {2}.'],
        'Bestimme den Mittelpunkt {0} der Strecke {1}.' => ['Détermine le milieu {0} du segment {1}.', 'Determina il punto medio {0} del segmento {1}.', 'Find the midpoint {0} of the segment {1}.'],
        'Zeichne um {0} einen Kreis mit dem Radius {1} (halb so lang wie {2}).' => ['Trace un cercle de centre {0} et de rayon {1} (la moitié de {2}).', 'Traccia una circonferenza di centro {0} e raggio {1} (la metà di {2}).', 'Draw a circle around {0} with radius {1} (half of {2}).'],
        'Die Kreise schneiden sich im Mittelpunkt {0} der Seite {1}.' => ['Les cercles se coupent au milieu {0} du côté {1}.', 'Le circonferenze si intersecano nel punto medio {0} del lato {1}.', 'The circles meet at the midpoint {0} of the side {1}.'],
        'Verlängere {0} über {1} hinaus auf die doppelte Länge, das gibt {2}.' => ['Prolonge {0} au-delà de {1} jusqu’au double de sa longueur&nbsp;: tu obtiens {2}.', 'Prolunga {0} oltre {1} fino al doppio della lunghezza: ottieni {2}.', 'Extend {0} beyond {1} to twice its length: this gives {2}.'],
        'Verbinde A, B und C mit Strecken zum Dreieck.' => ['Relie A, B et C par des segments pour former le triangle.', 'Collega A, B e C con dei segmenti per formare il triangolo.', 'Join A, B and C with segments to form the triangle.'],
    ];
}
