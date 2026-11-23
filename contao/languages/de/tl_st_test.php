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

$GLOBALS['TL_LANG']['tl_st_test']['title_legend'] = 'Test';
$GLOBALS['TL_LANG']['tl_st_test']['class_legend'] = 'Klasse';
$GLOBALS['TL_LANG']['tl_st_test']['config_legend'] = 'Aufgaben';
$GLOBALS['TL_LANG']['tl_st_test']['grade_legend'] = 'Note';
$GLOBALS['TL_LANG']['tl_st_test']['time_legend'] = 'Zeit';
$GLOBALS['TL_LANG']['tl_st_test']['security_legend'] = 'Sperre und Wiederaufnahme';
$GLOBALS['TL_LANG']['tl_st_test']['seb_legend'] = 'Safe Exam Browser';
$GLOBALS['TL_LANG']['tl_st_test']['codesheet_legend'] = 'Codeblatt';
$GLOBALS['TL_LANG']['tl_st_test']['publish_legend'] = 'Freigabe';
$GLOBALS['TL_LANG']['tl_st_test']['owner_legend'] = 'Besitzer';

$GLOBALS['TL_LANG']['tl_st_test']['title'] = ['Titel', 'Zum Beispiel «Kopfrechnen Quartal 2».'];
$GLOBALS['TL_LANG']['tl_st_test']['subject'] = ['Fach', 'Erscheint auf Codeblatt und Auswertung.'];
$GLOBALS['TL_LANG']['tl_st_test']['type'] = ['Testtyp', 'Welche Art von Aufgaben der Test enthält.'];
$GLOBALS['TL_LANG']['tl_st_test']['class'] = ['Klasse', 'Für jede Schülerin und jeden Schüler dieser Klasse wird beim Speichern ein Code erzeugt.'];
$GLOBALS['TL_LANG']['tl_st_test']['difficulty'] = ['Schwierigkeit', 'Kopfrechnungstest: Leicht = Level 1 und 2, Mittel = Level 3 und 4, Anspruchsvoller = Level 5 und 6 jedes Bereichs. Spiegeln und Drehen: grössere Figuren, schräge Achsen, Drehung um 180°. Gleichungen: Leicht = nur ganzzahlige Lösungen, Mittel = auch Brüche, Anspruchsvoller = meistens Brüche. Produkte von Binomen: Leicht = Grundanforderungen, Anspruchsvoller = Zusatzanforderungen, Mittel = Aufgabe für Aufgabe gemischt. Dreiecke: Leicht = Grundseite c, Werte auf 0.5 cm und 5°, Toleranz 2 mm und 2°; Mittel = beliebige Seite, Werte auf 1 mm und 1°, Toleranz 1 mm und 1°; Anspruchsvoller = zusätzlich Höhe ohne Grundseite, Toleranz 0.5 mm und 0.5°.'];
$GLOBALS['TL_LANG']['tl_st_test']['difficultyOptions'] = ['easy' => 'Leicht', 'medium' => 'Mittel', 'hard' => 'Anspruchsvoller'];
$GLOBALS['TL_LANG']['tl_st_test']['taskCounts'] = ['Aufgaben pro Bereich', 'Jede Zeile ergibt einen Abschnitt im Test, in dieser Reihenfolge (Zeilen lassen sich verschieben). Jeder Bereich darf nur einmal vorkommen. Punkte: Kopfrechnen 1 pro Aufgabe, Prozent-Tabellen 1 pro Feld (6–7 pro Tabelle); Spiegeln und Drehen 1 pro richtig gesetztem Bildpunkt (3 bis 5 pro Aufgabe); Winkel und Flächen 1 pro gesuchter Grösse; Gleichungen 1 (Stufe 1–3), 2 (Stufe 4–7) oder 3 Punkte (Stufe 8–10); Dreiecke 1 pro gegebenem Mass und 1 für die gezeichneten Seiten.'];
$GLOBALS['TL_LANG']['tl_st_test']['taskCountsKind'] = ['Bereich', ''];
$GLOBALS['TL_LANG']['tl_st_test']['taskCountsCount'] = ['Aufgaben', ''];
$GLOBALS['TL_LANG']['tl_st_test']['sameTasks'] = ['Gleiche Aufgaben für alle', 'Alle Schülerinnen und Schüler lösen dieselben Aufgaben. Sie werden beim ersten Start erzeugt. Solange niemand gestartet hat, erzeugt jedes Speichern neue Aufgaben. Ohne Häkchen erhält jedes Kind eigene Zufallsaufgaben.'];
$GLOBALS['TL_LANG']['tl_st_test']['transformKindsOptions'] = ['achse' => 'Achsenspiegelung', 'punkt' => 'Punktspiegelung', 'drehung' => 'Drehung', 'verschiebung' => 'Verschiebung', 'achse_ggb' => 'Achsenspiegelung mit GeoGebra (Konstruktion ohne Häuschen, Toleranz: Leicht 4 mm, Mittel 3 mm, Anspruchsvoller 1.5 mm, 1 Punkt mehr für die mit Strecken verbundene Bildfigur, lädt GeoGebra von geogebra.org)', 'punkt_ggb' => 'Punktspiegelung mit GeoGebra (Konstruktion ohne Häuschen, Toleranz: Leicht 4 mm, Mittel 3 mm, Anspruchsvoller 1.5 mm, 1 Punkt mehr für die mit Strecken verbundene Bildfigur, lädt GeoGebra von geogebra.org)', 'symmetrie' => 'Symmetrie: Achsen und Zentrum einzeichnen (Karten nur achsen-, nur punktsymmetrisch, beides oder keines; Achsen mit zwei Klicks, Zentrum mit einem Klick, ungefähr genügt; je 1 Punkt)'];
$GLOBALS['TL_LANG']['tl_st_test']['angleKindsOptions'] = ['scheitel' => 'Scheitel- und Nebenwinkel', 'parallel' => 'Stufenwinkel an Parallelen', 'transversalen' => 'Parallelen und zwei Geraden (Schnittpunkt S, Dreieck)', 'dreieck' => 'Dreieck', 'viereck' => 'Viereck', 'vieleck' => 'Vieleck', 'figuren' => 'Figuren mit Diagonalen und Höhen (Trapez, regelmässiges Fünfeck, Parallelogramm, gleichschenkliges Dreieck)', 'kreis' => 'Winkel im Kreis (Peripherie- und Zentriwinkel)', 'kombi' => 'Kombiniert (mehrere Winkel pro Aufgabe)', 'profi' => 'Profi (gleichschenklige und gleichseitige Dreiecke)'];
$GLOBALS['TL_LANG']['tl_st_test']['areaKindsOptions'] = ['dreieck' => 'Dreieck', 'rechteck' => 'Rechteck und Quadrat', 'parallelogramm' => 'Parallelogramm und Raute', 'trapez' => 'Trapez', 'kreis' => 'Kreis (Halb-, Viertelkreis, Kreisring, Sektor)', 'zusammengesetzt' => 'Zusammengesetzte Flächen', 'pythagoras' => 'Flächen mit fehlenden Längen (Satz des Pythagoras)', 'koordinaten' => 'Im Koordinatensystem (Eckpunkte, Rahmen-Methode)', 'text' => 'Nur Textaufgaben (alle Figuren)'];
$GLOBALS['TL_LANG']['tl_st_test']['triangleKindsOptions'] = ['sss' => 'SSS: drei Seiten', 'sws' => 'SWS: zwei Seiten und der Zwischenwinkel', 'wsw' => 'WSW: eine Seite und die anliegenden Winkel', 'ssw' => 'SsW: zwei Seiten und der Gegenwinkel der grösseren Seite', 'hoehe' => 'Mit Höhe (Anspruchsvoller auch ohne gegebene Grundseite)', 'winkelhalbierende' => 'Mit Winkelhalbierender (Seite, anliegender Winkel und dessen Winkelhalbierende)', 'schwerelinie' => 'Mit Schwerelinie (Seite mit Seite oder Winkel; Anspruchsvoller auch Schwerelinie auf eine gegebene Seite)'];
$GLOBALS['TL_LANG']['tl_st_test']['binomialTopicsOptions'] = ['rechteckfiguren' => 'Rechteckfiguren: Fläche und Umfang (mit Zahlen, mit Termen, Seitenlänge x)', 'geschickt' => 'Geschickt rechnen (19 · 21, 29², Malkreuz)', 'rechteckmodell' => 'Terme am Rechteck (Summe und Produkt)', 'ausmultiplizieren' => 'Ausmultiplizieren (binomische Formeln)', 'faktorisieren' => 'Produkt und Summe: Summen- oder Produktterm, ausklammern, binomische Formeln, Aufspalten'];
$GLOBALS['TL_LANG']['tl_st_test']['equationLevelsOptions'] = [1 => 'Stufe 1: x + a = b', 2 => 'Stufe 2: ax + b = c', 3 => 'Stufe 3: x auf beiden Seiten', 4 => 'Stufe 4: Lösung als Bruch', 5 => 'Stufe 5: Klammern auflösen', 6 => 'Stufe 6: Brüche als Koeffizienten', 7 => 'Stufe 7: Bruchterme', 8 => 'Stufe 8: Profi-Mix', 9 => 'Stufe 9: Binome (x² fällt weg)', 10 => 'Stufe 10: Binome mit Koeffizienten'];
$GLOBALS['TL_LANG']['tl_st_test']['gradeStep'] = ['Rundung der Note', 'Note = 1 + 5 · Punkte / Punkte für Note 6, gerundet.'];
$GLOBALS['TL_LANG']['tl_st_test']['gradeStepOptions'] = ['0.1' => 'auf Zehntel (4.3)', '0.25' => 'auf Viertel (4.25)', '0.5' => 'auf Halbe (4.5)'];
$GLOBALS['TL_LANG']['tl_st_test']['pointsForSix'] = ['Punkte für Note 6', '0 = alle Punkte. Die Punktzahl hängt von «Aufgaben pro Bereich» ab und steht nach dem ersten Test auf dem Auswertungsblatt.'];
$GLOBALS['TL_LANG']['tl_st_test']['timeLimit'] = ['Zeit in Minuten', '0 = unbegrenzt. Die Zeit läuft ab dem Start. Solange der Test gesperrt ist, steht die Uhr still. Bei 0 wird automatisch abgegeben.'];
$GLOBALS['TL_LANG']['tl_st_test']['acceptSebBrowserOnly'] = ['Nur mit dem Safe Exam Browser', 'Der Test lässt sich nur im Safe Exam Browser (SEB) starten, speichern und abgeben. In einem anderen Browser erscheint: «Dieser Test kann nur mit dem Safe Exam Browser gelöst werden.»'];
$GLOBALS['TL_LANG']['tl_st_test']['sebConfigKey'] = ['Config Key', 'Empfohlen: der Config Key der SEB-Konfiguration (64 Zeichen, im SEB-Konfigurationswerkzeug unter «Config Key»). Damit prüft der Server, dass genau diese Konfiguration verwendet wird; die Prüfung lässt sich nicht fälschen. Ohne Config Key wird nur geprüft, ob der Browser sich als SEB ausgibt. In der Konfiguration muss «Send Config Key» bzw. die SEB-JavaScript-API erlaubt sein.'];
$GLOBALS['TL_LANG']['tl_st_test']['lockOnLeave'] = ['Bei Verlassen sperren', 'Wechselt eine Schülerin oder ein Schüler im Browser die Seite (anderer Tab, Fenster minimiert, neu laden, wegnavigieren), wird der Test gesperrt. Weiter geht es nur mit dem Wiederaufnahme-Code.'];
$GLOBALS['TL_LANG']['tl_st_test']['resumeCode'] = ['Wiederaufnahme-Code', 'Genau 6 Zeichen (Buchstaben und Ziffern), nur für die Lehrperson. Leer lassen erzeugt automatisch einen Code.'];
$GLOBALS['TL_LANG']['tl_st_test']['codeSheetNote'] = ['Hinweis auf dem Codeblatt', 'Zum Beispiel die Adresse der Testseite: «www.schule-ettiswil.ch/test».'];
$GLOBALS['TL_LANG']['tl_st_test']['published'] = ['Test freigeben', 'Nur freigegebene Tests können im Frontend gestartet werden.'];

$GLOBALS['TL_LANG']['tl_st_test']['children'] = 'Teilnahmen des Tests ID %s anzeigen';
$GLOBALS['TL_LANG']['tl_st_test']['codesheet'] = 'Codeblatt drucken';
$GLOBALS['TL_LANG']['tl_st_test']['evaluation'] = 'Auswertungsblätter drucken';
$GLOBALS['TL_LANG']['tl_st_test']['preview'] = ['Ausprobieren', 'Den Test selbst lösen wie eine Schülerin oder ein Schüler: ohne Code, ohne SEB, nichts wird gespeichert'];
$GLOBALS['TL_LANG']['tl_st_test']['author'] = ['Besitzer', 'Nur dieser Backend-Benutzer sieht, bearbeitet und löscht den Test (Administratoren sehen alle Tests). Neue Tests gehören automatisch der Person, die sie anlegt oder kopiert.'];
