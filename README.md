# Contao School Test

Online-Tests für die Schule in Contao 5.3 und 6: Klassen und Schüler erfassen, Tests erstellen, Codeblatt drucken, Test im Frontend lösen lassen, Auswertungsblatt mit Note drucken.

Zwei Inhaltselemente: **Online-Test (Schule)** mit Code-Anmeldung und **Online-Test üben** ohne Anmeldung und unabhängig von Tests.

Erster Testtyp: **Kopfrechnungstest** mit fünf Bereichen

| | Bereich | Beispiel |
|---|---|---|
| A | Kopfrechnen bis 1000 | «Um wie viel ist 7 · 9 kleiner als 8 · 8?» |
| B | Bruchrechnen | kürzen, erweitern, addieren, multiplizieren, dividieren |
| C | Bruch · Prozent · Dezimal | «Schreibe 37.5 % als Bruch.» |
| D | Prozent-Tabellen | Proportionalität, 6–7 Felder pro Tabelle |
| E | Schätzaufgaben | «Schätze: 9.8 % von 755 kg» (max. 10 % Abweichung) |

Zweiter Testtyp: **Spiegeln, Drehen, Verschieben** auf Häuschenpapier (20 × 14 Häuschen)

| Bereich | Aufgabe |
|---|---|
| Achsenspiegelung | an einer senkrechten, waagrechten oder schrägen Achse, ab «Mittel» auch zwischen zwei Gitterlinien |
| Punktspiegelung | am Punkt Z |
| Drehung | um Z, 90°, 180° oder 270° im Gegenuhrzeigersinn |
| Verschiebung | um den Pfeil v |

Im Backend wählst du Schwierigkeit, Abbildungsarten und Aufgaben pro Art. Die Kinder setzen die Bildpunkte A′, B′, … mit einem Klick auf die Gitterpunkte, ein Klick auf einen gesetzten Punkt entfernt ihn. Jeder richtige Bildpunkt gibt einen Punkt. Auf dem Auswertungsblatt erscheint jede Aufgabe als Zeichnung: Lösung grün gestrichelt, gesetzte Punkte grün oder rot.

Dritter Testtyp: **Winkel** mit Skizze zu jeder Aufgabe

| Bereich | Aufgabe |
|---|---|
| Scheitel- und Nebenwinkel | zwei oder drei Geraden durch einen Punkt |
| Stufenwinkel an Parallelen | zwei oder drei Parallelen und eine Schnittgerade |
| Dreieck | allgemein, gleichschenklig, rechtwinklig |
| Viereck | Winkelsumme, Parallelogramm |
| Vieleck | Winkelsumme, fehlender Winkel, regelmässiges n-Eck, Anzahl Ecken |
| Kombiniert | mehrere gesuchte Winkel pro Aufgabe (Aussenwinkel, Trapez, Parallelen) |
| Profi | gleichschenklige und gleichseitige Dreiecke mit Parallelen |

Jeder gesuchte Winkel gibt einen Punkt. Beim Üben und auf dem Auswertungsblatt erscheint die Skizze mit der Lösung, dazu der Lösungsweg.

Vierter Testtyp: **Lineare Gleichungen** in zehn Stufen, von «x + a = b» bis zu Binomen mit Koeffizienten. Die Kinder geben x als ganze Zahl oder Bruch ein (z. B. `-3/4`, ungekürzt gilt auch). Punkte: 1 (Stufe 1–3), 2 (Stufe 4–7), 3 (Stufe 8–10). Schwierigkeit: Leicht = nur ganzzahlige Lösungen, Mittel = auch Brüche, Anspruchsvoller = meistens Brüche. Beim Üben zeigt der Lösungsweg jede Umformung («| − 3x», «| : 4»).

## Ablauf

1. **Klassen** (Backend › Schultests › Klassen): Klasse anlegen, Schülerinnen und Schüler erfassen.
2. **Tests** (Backend › Schultests › Tests): Test anlegen, Testtyp und Klasse wählen, Schwierigkeit (Leicht = Level 1–2, Mittel = 3–4, Anspruchsvoller = 5–6), Aufgaben pro Bereich, Zeit in Minuten, Notenrundung. Beim Speichern erhält jede Schülerin und jeder Schüler einen **fünfstelligen Code**, der Test einen **Wiederaufnahme-Code** für die Lehrperson.
3. **Codeblatt** (Symbol in der Testliste): Karten mit Name und Code zum Ausschneiden.
4. **Test freigeben** (Häkchen «Test freigeben»).
5. **Frontend**: Inhaltselement «Online-Test (Schule)» auf einer Seite einfügen. Die Schüler geben ihren Code ein und lösen den Test. Antworten werden laufend gespeichert.
6. **Üben** (optional): Inhaltselement «Online-Test üben» einfügen, ohne Einstellungen. Die Schülerinnen und Schüler wählen ohne Code zuerst, was sie üben möchten (den Testtyp), dann einen Bereich (Kopfrechnen, Bruchrechnen, Bruch · Prozent · Dezimal, Prozent-Tabellen, Schätzaufgaben) oder «Alle Bereiche» und die Schwierigkeit Leicht (Level 1–2), Mittel (3–4) oder Anspruchsvoller (5–6). Immer neue Zufallsaufgaben; nach «Auswerten» stehen bei jeder Aufgabe Lösung und Lösungsweg. Nichts wird gespeichert.
7. **Auswertung** (Symbol in der Testliste): Klassenübersicht und pro Schülerin und Schüler ein Blatt mit allen Aufgaben, Antworten, Lösungen, Punkten und Note.

## Regeln im Frontend

- **Gleiche Aufgaben für alle** (Standard): Die Aufgaben werden beim ersten Start einmal pro Test erzeugt. Solange niemand gestartet hat, erzeugt jedes Speichern des Tests neue Aufgaben. Ohne Häkchen erhält jedes Kind eigene Zufallsaufgaben.
- Lösungen werden nur auf dem Server gespeichert und korrigiert, sie gelangen nie in den Browser.
- **Sperre**: Wechselt ein Kind im Browser die Seite (anderer Tab, Fenster minimiert, neu laden, wegnavigieren), wird der Test gesperrt. Ein Klick in die Adressleiste oder in ein anderes Programm sperrt nicht. Weiter geht es nur mit Schülercode + Wiederaufnahme-Code der Lehrperson. Die Antworten bleiben erhalten. Die Anzahl Sperren steht auf der Auswertung. Abschaltbar pro Test.
- **Zeit**: Optional. Die Uhr läuft nur, solange der Test offen ist (nicht während einer Sperre). Ist die Zeit um, wird automatisch abgegeben.
- **Abgeschlossene Tests** können nicht nochmals gestartet werden. Das Kind sieht nach der Abgabe seine Punktzahl (nicht die Note).
- Ein Test kann in der Teilnahmen-Liste auf «Offen» zurückgesetzt werden: Aufgaben und Antworten werden gelöscht, der Test beginnt neu.
- Nach 15 falschen Codes pro IP-Adresse wird 15 Minuten gesperrt.

## Sprachen

Im Frontend wählen die Schülerinnen und Schüler vor dem Start die Sprache: Deutsch (Standard), Français, Italiano oder English. Aufgaben, Lösungswege, Einheiten (Fr. → fr./CHF, Rp. → ct.) und alle Texte der Testseite und des Übungsmodus erscheinen in dieser Sprache. Die Aufgaben werden deutsch erzeugt und gespeichert und erst bei der Ausgabe übersetzt (`Task\TaskTranslator`), «Gleiche Aufgaben für alle» gilt also über alle Sprachen. Backend, Codeblatt und Auswertungsblatt bleiben deutsch; die gewählte Sprache steht bei der Teilnahme und auf dem Auswertungsblatt.

Neue Satzmuster in einem Generator brauchen einen Eintrag in `TaskTranslator::QUESTIONS`; der Test `TaskTranslatorTest` schlägt sonst fehl.

## Note

Note = 1 + 5 · Punkte / Punkte für Note 6, gerundet auf 0.1, 0.25 oder 0.5. «Punkte für Note 6» = 0 bedeutet alle Punkte.

## Weitere Testtypen

Einen neuen Testtyp als Service registrieren, der `Markocupic\ContaoSchoolTest\TestType\TestTypeInterface` implementiert. Er erscheint automatisch in der Auswahl, mit eigener Palette aus `getPaletteFields()`. `generate()` liefert Abschnitte mit Aufgaben im Format von `Task\Util::num()`, `Task\Util::frac()`, Tabellen (siehe `Task\Area\PercentTableArea`) oder Zeichnungen mit Gitterpunkten (Antworttyp `points`, siehe `Task\Geometry\TransformGenerator`). Korrektur, Speicherung, Frontend und Auswertung funktionieren ohne weitere Anpassung.

Soll der Typ auch im Übungsmodus erscheinen, implementiert er zusätzlich `PracticeTestTypeInterface`: Beschriftung in den vier Sprachen (`getPracticeLabels()`), wählbare Bereiche (`getPracticeTopics()`) und die Aufgaben einer Übungsrunde (`generatePractice()`). Im Frontend wählen die Kinder dann zuerst den Typ, danach Bereich und Schwierigkeit.

## Installation

```bash
composer require markocupic/contao-school-test
php vendor/bin/contao-console contao:migrate
```

## Tests

```bash
composer install
composer unit-tests
```

## Assets bauen (Webpack Encore)

Die Quellen liegen in `assets/` (`assets/js/school-test.js`, `assets/styles/school-test.css`, `assets/icons/`). Webpack Encore kopiert sie mit Hash in den Dateinamen nach `public/` und schreibt `public/manifest.json`. Die Templates binden sie über das Asset-Paket des Bundles ein:

```twig
{{ asset('js/school-test.js', 'markocupic_contao_school_test') }}
{{ asset('css/school-test.css', 'markocupic_contao_school_test') }}
```

```bash
cd vendor/markocupic/contao-school-test
npm update
npm run build
```

Nach einer Änderung in `assets/` immer neu bauen, der Inhalt von `public/` wird dabei ersetzt.

