# Contao School Test

Online-Tests für die Schule in Contao 5.7 und 6: Klassen und Schüler erfassen, Tests erstellen, Codeblatt drucken, Test im Frontend lösen lassen, Auswertungsblatt mit Note drucken.

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
| Achsen-/Punktspiegelung mit GeoGebra | Konstruktion ohne Häuschen in GeoGebra, Toleranz 4 / 3 / 1.5 mm je nach Schwierigkeit; einen Punkt mehr, wenn die Bildpunkte mit Strecken zur Bildfigur verbunden sind |
| Symmetrie: Achsen und Zentrum einzeichnen | Karte mit grauen Figuren oder Kacheln: nur achsensymmetrisch, nur punktsymmetrisch, beides oder keines. Auf einem einfachen Zeichenfeld (ohne GeoGebra) jede Symmetrieachse mit zwei Klicks einzeichnen und das Zentrum Z mit einem Klick setzen, sonst «Keine Symmetrie»; «Rückgängig» und «Alles löschen». 1 Punkt für die Achsen (alle, keine zu viel), 1 Punkt für das Zentrum. Ungefähr genügt: Toleranz Leicht 0.6 / 8°, Mittel 0.5 / 6°, Anspruchsvoller 0.4 / 5° (Kartenbreite 10). Leicht: Bild in der Kartenmitte, Mittel: verschoben, Anspruchsvoller: verschoben und gedreht, auch vier Achsen |

Im Backend wählst du unter «Aufgaben pro Bereich» die Abbildungsarten mit Anzahl Aufgaben und Schwierigkeit. Die Kinder setzen die Bildpunkte A′, B′, … mit einem Klick auf die Gitterpunkte, ein Klick auf einen gesetzten Punkt entfernt ihn. Jeder richtige Bildpunkt gibt einen Punkt. Auf dem Auswertungsblatt erscheint jede Aufgabe als Zeichnung: Lösung grün gestrichelt, gesetzte Punkte grün oder rot.

### GeoGebra

Die Varianten «mit GeoGebra» laden GeoGebra beim ersten Anzeigen der Aufgabe von `https://www.geogebra.org/apps/deployggb.js`. In einem neuen Test stehen sie deshalb nicht in der Liste «Aufgaben pro Bereich». Die Werkzeugleiste ist eingeschränkt (Bewegen, Punkt, Schneiden, Gerade, Strecke, Halbgerade, Senkrechte, Parallele, Kreise, Zirkel, Löschen), Eingabezeile, Algebra-Ansicht, Kontextmenü und die Spiegeln-Werkzeuge fehlen. Das Kind wählt A′, B′, … und klickt dann auf die Stelle in der Zeichnung; der Punkt heisst danach in GeoGebra A′. Bewertet wird über die Koordinaten der Bildpunkte. Dazu kommt ein Punkt, wenn die Bildpunkte mit Strecken (Werkzeug Strecke oder Vieleck) genau zur Bildfigur verbunden sind: alle Seiten vorhanden, keine Diagonale zu viel. Gespeichert werden die Bildpunkte und diese Strecken, andere Hilfslinien nicht.

GeoGebra ist für die nicht-kommerzielle Nutzung (Schule, Unterricht) frei, siehe [geogebra.org/license](https://www.geogebra.org/license). Für eine selbst gehostete Kopie des Math Apps Bundle vor dem Script setzen:

```html
<script>window.SchoolTestGeoGebra = {deploy: '/files/geogebra/deployggb.js', codebase: '/files/geogebra/HTML5/5.0/web3d/'};</script>
```

Dritter Testtyp: **Winkel** mit Skizze zu jeder Aufgabe

| Bereich | Aufgabe |
|---|---|
| Scheitel- und Nebenwinkel | zwei oder drei Geraden durch einen Punkt |
| Stufenwinkel an Parallelen | zwei oder drei Parallelen und eine Schnittgerade |
| Parallelen und zwei Geraden | zwei oder drei Parallelen, geschnitten von zwei Geraden, die sich in S schneiden: gegeben ein Winkel an einer Parallele und einer bei S, gesucht drei Winkel (Dreieck PQS, Stufen-, Neben-, Scheitelwinkel) |
| Dreieck | allgemein, gleichschenklig, rechtwinklig |
| Viereck | Winkelsumme, Parallelogramm |
| Vieleck | Winkelsumme, fehlender Winkel, regelmässiges n-Eck, Anzahl Ecken |
| Figuren mit Diagonalen und Höhen | Trapez mit Diagonale, regelmässiges Fünfeck mit zwei Diagonalen (auch Winkel im Schnittpunkt), Parallelogramm mit Diagonale und Höhe, gleichschenkliges Dreieck mit Höhe |
| Winkel im Kreis (Peripherie- und Zentriwinkel) | vier Punkte auf dem Kreis, drei oder vier gesuchte Winkel ω₁ … ω₄: zwei sich kreuzende Sehnen (Peripheriewinkel über derselben Sehne, Winkelsumme, Nebenwinkel), Sehnenviereck mit Diagonale, zwei Dreiecke über dem Durchmesser (Thales), Zentriwinkel mit Peripheriewinkeln und gleichschenkligem Dreieck aus zwei Radien; die Skizzen sind massstabsgetreu, pro Runde jede Figur nur einmal |
| Kombiniert | mehrere gesuchte Winkel pro Aufgabe (Aussenwinkel, Trapez, Parallelen) |
| Profi | gleichschenklige und gleichseitige Dreiecke mit Parallelen |

Jeder gesuchte Winkel gibt einen Punkt. Beim Üben und auf dem Auswertungsblatt erscheint die Skizze mit der Lösung, dazu der Lösungsweg.

Vierter Testtyp: **Lineare Gleichungen** in zehn Stufen, von «x + a = b» bis zu Binomen mit Koeffizienten. Die Kinder geben x als ganze Zahl oder Bruch ein (z. B. `-3/4`, ungekürzt gilt auch). Punkte: 1 (Stufe 1–3), 2 (Stufe 4–7), 3 (Stufe 8–10). Schwierigkeit: Leicht = nur ganzzahlige Lösungen, Mittel = auch Brüche, Anspruchsvoller = meistens Brüche. Beim Üben zeigt der Lösungsweg jede Umformung («| − 3x», «| : 4»).

Sechster Testtyp: **Produkte von Binomen** (Algebra)

| Bereich | Aufgaben |
|---|---|
| Rechteckfiguren: Fläche und Umfang | blaue Figur mit Massen a, b oder Zahlen: A und u berechnen oder als Term angeben, Seitenlänge x aus A oder u |
| Geschickt rechnen | 19 · 21 = 400 − 1, 29² = (30 − 1)², Malkreuz für (p + q)² |
| Terme am Rechteck | Rechteck mit Teilflächen: Fläche als Summe und als Produkt |
| Ausmultiplizieren | binomische Formeln, auch mit Dezimalzahlen und Brüchen |
| Produkt und Summe: faktorisieren | Summen- oder Produktterm erkennen, ausklammern, binomische Formeln rückwärts, Aufspalten, vollständig faktorisieren |

Leicht = Grundanforderungen, Anspruchsvoller = Zusatzanforderungen, Mittel mischt Aufgabe für Aufgabe beides. Jedes Feld gibt einen Punkt. Terme werden auf dem Server gelesen (`Task/Term/TermParser`): Hochzahlen mit ^ oder ², Mal-Punkte dürfen fehlen, Dezimalkomma geht auch. Gewertet wird, ob die Eingabe gleichwertig ist und die verlangte Form hat (ohne Klammern und zusammengefasst, als Produkt, vollständig faktorisiert). Ist sie gleichwertig, aber in der falschen Form, sagt die Rückmeldung beim Üben das.


Fünfter Testtyp: **Dreieckskonstruktionen** mit GeoGebra

| Bereich | Gegeben |
|---|---|
| SSS | drei Seiten |
| SWS | zwei Seiten und der Zwischenwinkel |
| WSW | eine Seite und die beiden anliegenden Winkel |
| SsW | zwei Seiten und der Gegenwinkel der grösseren Seite |
| Mit Höhe | Grundseite, Höhe und ein Winkel oder eine Seite; Anspruchsvoller auch Höhe mit zwei Winkeln oder zwei Seiten |
| Mit Winkelhalbierender | eine Seite, ein anliegender Winkel und dessen Winkelhalbierende |
| Mit Schwerelinie | Grundseite, Schwerelinie dazu und eine Seite oder ein Winkel; Anspruchsvoller auch die Schwerelinie auf eine gegebene Seite |

Das Kind konstruiert das Dreieck irgendwo auf dem Blatt (1 Einheit = 1 cm). Die ersten drei Punkte heissen in GeoGebra A, B und C, mit den Knöpfen A, B, C gibt es einem anderen Punkt den Namen. Bewertet werden nicht Koordinaten, sondern die gegebenen Masse: Jedes Mass, das das Dreieck ABC innerhalb der Toleranz hat, gibt einen Punkt (Lage, Drehung und Spiegelung egal), einen weiteren gibt es für die drei Seiten als Strecken. Toleranz: Leicht 2 mm und 2°, Mittel 1 mm und 1°, Anspruchsvoller 0.5 mm und 0.5°. Leicht baut immer auf der Seite c auf, Werte auf 0.5 cm und 5°. Beim Üben zeigt die Rückmeldung jedes Mass mit dem Wert im Dreieck des Kindes, die Lösung liegt grün gestrichelt auf seiner Seite AB.


Siebter Testtyp: **Flächenberechnung** (Geometrie) mit farbigen, beschrifteten Skizzen, je drei Niveaus:

| Bereich | Leicht | Mittel | Anspruchsvoller |
|---|---|---|---|
| Dreieck | Grundseite und Höhe, rechtwinklig | Höhe aus A, stumpfwinklig (Höhe aussen), Dezimalzahlen | zwei Höhen (h₂ aus A und b), gemischte Einheiten |
| Rechteck und Quadrat | A und U, auch auf Häuschenpapier | Seite aus A oder U | gemischte Einheiten, Seitenverhältnis, Quadrat aus A |
| Parallelogramm und Raute | g · h (schräge Seite als Ablenkung), Raute aus Diagonalen | h aus A, Raute mit Höhe, Diagonale aus A | zwei Höhen, Raute in dm² |
| Trapez | a, c, h | h oder c aus A | gemischte Einheiten, a und c aus Unterschied, Mittellinie |
| Kreis | A und U aus r | aus d, Halb- und Viertelkreis, r aus U | Kreisring, Sektor, r aus A |
| Zusammengesetzte Flächen | L- und T-Form (auch Häuschen), Haus, Treppe, Pfeil | Rahmen, U-Form, Kerbe, Stern, Vieleck auf Häuschen, Hallenwand mit Tor, Werkstück (Rechteck und Trapez), Rechteck mit Halbkreis, Quadrat mit zwei Halbkreisen | Quadrat minus Viertelkreis, Laufbahn, Löcher, Kreis im Quadrat, Quadrat im Kreis, Blatt aus zwei Viertelkreisen, Halbkreis-Ausschnitt |
| Flächen mit fehlenden Längen (im Backend mit Hinweis «Satz des Pythagoras», für die Kinder ohne) | rechtwinkliges Dreieck | Rechteck mit Diagonale, gleichschenkliges Dreieck, Raute, Dreieck und Rechteck (h aus Pythagoras) | gleichschenkliges und rechtwinkliges Trapez, gleichseitiges Dreieck, Werkstück aus zwei Dreiecken, Rechteck mit Halbkreis-Ausschnitt und Turm |
| Im Koordinatensystem | Rechteck, Dreieck, rechtwinkliges Dreieck, Parallelogramm aus Eckpunkten A(x\|y) | Trapez, L-Form, Haus, Figur im Gitter ablesen | Rahmen-Methode (Viereck, Dreieck, Rechteck mit abgeschnittenen Ecken), Figur mit Viertelkreis im Gitter |

Innerhalb eines Abschnitts kommt keine Aufgabenart doppelt vor (pro Bereich und Niveau gibt es mindestens drei Arten, Leicht auch mit Figuren auf Häuschenpapier). Jede vierte Aufgabe eines Bereichs ist eine **Textaufgabe** (Segel, Parkett, Platten, Grundstück mit Preis, Pizza, Wand mit Farbe, Bildschirm, Giebel …), der Bereich «Textaufgaben» hat nur solche. Textaufgaben zeigen keine Skizze, die Lösung schon. Jeder gesuchte Wert gibt einen Punkt. Werte ohne π und Wurzeln müssen genau stimmen; Ergebnisse mit π oder Wurzeln werden auf eine Dezimale gerundet, π ≈ 3.14 gilt auch, ebenso Folgefehler aus einem gerundeten Zwischenresultat. Beim Üben zeigt die Lösung die Skizze mit den Werten (zusammengesetzte Flächen mit der Zerlegung A₁, A₂) und den Lösungsweg.


## Ablauf

1. **Klassen** (Backend › Schultests › Klassen): Klasse anlegen, Schülerinnen und Schüler erfassen.
2. **Tests** (Backend › Schultests › Tests): Test anlegen, Testtyp und Klasse wählen, «Aufgaben pro Bereich» (mit Schwierigkeit pro Bereich, beim Kopfrechnen Leicht = Level 1–2, Mittel = 3–4, Anspruchsvoller = 5–6), Zeit in Minuten, Notenrundung. Beim Speichern erhält jede Schülerin und jeder Schüler einen **fünfstelligen Code**, der Test einen **Wiederaufnahme-Code** für die Lehrperson.

   **Aufgaben pro Bereich** (Feld `taskCounts`, Row Wizard ab Contao 5.7): pro Zeile ein Bereich (Kopfrechnen, Abbildungsart, Winkelart, Flächenart, Stufe …, je nach Testtyp), die Anzahl Aufgaben und die Schwierigkeit (Leicht, Mittel, Anspruchsvoller), zum Beispiel Rechteck 3 Leicht, Kreis 4 Anspruchsvoller, Textaufgaben 1 Mittel. Die Winkel haben keine Schwierigkeit (sie steckt in den Bereichen wie «Kombiniert» oder «Profi»), dort fehlt die Spalte; `TaskKindsInterface::hasTaskDifficulty()`. Ein gemeinsames Feld «Schwierigkeit» für den ganzen Test gibt es nicht mehr. Jede Zeile ergibt einen Abschnitt, in der Reihenfolge der Zeilen (verschiebbar). Ein Bereich darf mehrmals vorkommen, zum Beispiel Achsenspiegelung 2 × Mittel und 1 × Anspruchsvoller: zwei Abschnitte mit je eigener Anweisung und Toleranz (gleicher Bereich mit gleicher Schwierigkeit wird zusammengezählt). Mit 0 Aufgaben bleibt die Zeile stehen, der Bereich fehlt aber im Test (mindestens ein Bereich braucht mehr als 0). Ein neuer Test (oder ein anderer Testtyp) startet mit den üblichen Zeilen des Typs, nicht benötigte Zeilen löschst du einfach. Höchstens 10 Aufgaben pro Bereich, Kopfrechnungstest 20, Prozent-Tabellen 3 Tabellen. Eigene Testtypen liefern ihre Bereiche über `TestType\TaskKindsInterface` und lesen das Feld mit `TestType\TaskCounts::rows()`. Die früheren Felder (`difficulty`, `tasksPerArea`, `tasksPerKind`, `areaKinds`, `angleKinds`, …) gibt es nicht mehr; bestehende Tests zeigen nach dem Update die üblichen Zeilen ihres Typs (Schwierigkeit Mittel).
3. **Codeblatt** (Symbol in der Testliste): Karten mit Name und Code zum Ausschneiden.
   **Ausprobieren** (Play-Symbol in der Testliste): öffnet den Test in einem neuen Tab, so wie ihn die Schülerinnen und Schüler sehen, aber ohne Code und ohne eigenen Fake-Schüler. Der Safe Exam Browser und die Sperre beim Verlassen sind dabei ausgeschaltet, auch wenn der Test sie verlangt; der Test muss nicht freigegeben sein. Sprache wählen, lösen, abgeben: Punkte und Note erscheinen sofort, «Auswertung mit Lösungen ansehen» zeigt das Auswertungsblatt dieses Versuchs, «Nochmals lösen» beginnt neu. Mit «Gleiche Aufgaben für alle» und bereits gestarteten Teilnahmen bekommt man genau die Aufgaben der Klasse, sonst neue Zufallsaufgaben (sie werden nicht beim Test gespeichert). Der Versuch liegt nur in der Backend-Session (`Participation\PreviewSession`, Routen `/contao/school-test/preview/{id}`, Zugriff mit dem Recht auf das Modul «Tests»): keine Teilnahme, nichts in der Klassenübersicht.
4. **Test freigeben** (Häkchen «Test freigeben»).
5. **Frontend**: Inhaltselement «Online-Test (Schule)» auf einer Seite einfügen. Die Schüler geben ihren Code ein und lösen den Test. Antworten werden laufend gespeichert.
6. **Üben** (optional): Inhaltselement «Online-Test üben» einfügen, ohne Einstellungen. Die Schülerinnen und Schüler wählen ohne Code zuerst, was sie üben möchten (den Testtyp), dann einen Bereich (Kopfrechnen, Bruchrechnen, Bruch · Prozent · Dezimal, Prozent-Tabellen, Schätzaufgaben) oder «Alle Bereiche» und die Schwierigkeit Leicht (Level 1–2), Mittel (3–4) oder Anspruchsvoller (5–6). Immer neue Zufallsaufgaben. Mit «Prüfen» bei einer Aufgabe sieht das Kind, welche Felder stimmen (grün/rot), aber nicht die Lösung, und kann beliebig oft korrigieren und nochmals prüfen. Erst «Übung auswerten» ganz unten zeigt bei jeder Aufgabe Lösung und Lösungsweg; danach sind alle Eingaben und Zeichnungen gesperrt. «Andere Auswahl» steht oberhalb und unterhalb der Aufgaben. Beim Neuladen bleibt die Übung erhalten (Aufgaben, Eingaben, Prüfergebnisse, GeoGebra-Konstruktionen, Uhr, auch eine ausgewertete Übung) – gespeichert nur im Tab (`sessionStorage`). Mehrere Tabs können gleichzeitig verschiedene Übungen offen haben (bis 8 Runden pro Inhaltselement und Session). Auf dem Server wird nichts dauerhaft gespeichert.

   Das Übungselement hat ein eigenes, kindgerechtes Aussehen «Mathe-Mission» (Weltraum, farbige Kacheln für Arithmetik, Algebra und Geometrie, Raketen-Fortschrittsbalken). Für jede ganz richtige Aufgabe gibt es nach «Übung auswerten» einen Stern; die Sterne zählt nur der Browser (`localStorage`, Schlüssel `st-stars`), nichts geht an den Server. Es werden keine Web-Fonts geladen. Die Testseite hat dasselbe Aussehen (Code-Eingabe, Ergebnis und Abgabeleiste im Weltraum-Look), aber keine Sterne und keinen Fortschrittsbalken.

   Läuft das Übungselement allein auf einer Seite, gibt es dafür ein Seiten-Stylesheet (Weltraum-Hintergrund über die ganze Seite, Planet und Mond, App zentriert und auf grossen Bildschirmen grösser). Einschalten im Inhaltselement (Übung und Test) mit «Ganze Seite gestalten (eigene Seite)» (Feld `tl_content.schoolTestPageStyle`, nach dem Update `contao:migrate`). Oder von Hand im Seitenlayout unter «Zusätzliche <head>-Tags»: `<link rel="stylesheet" href="/bundles/markocupiccontaoschooltest/page/mathe-mission.css">`
7. **Arbeitsblatt drucken** (Symbol in der Testliste): der Test auf Papier zum Hineinschreiben, A4. Kopf mit Name, Datum, Punkte und Note; pro Abschnitt die Aufgaben mit Kästchen für die Ergebnisse, Brüche mit Zähler und Nenner, Prozent-Tabellen mit leeren Feldern, Winkel- und Flächenskizzen, Malkreuz, Ankreuzfelder Summen-/Produktterm. Spiegeln und Drehen auf Häuschenpapier (1 Häuschen = 5 mm), Konstruktionen ohne Häuschen im Originalmassstab, Dreieckskonstruktionen in einem leeren Feld 16 × 11 cm (zum Messen in echter Grösse beim Drucken «Tatsächliche Grösse» bzw. 100 % wählen). Die Anweisungen sind fürs Papier umgeschrieben (Geodreieck und Zirkel statt GeoGebra, keine Hinweise auf Tasten). «Lösungsblatt mitdrucken» in der Leiste hängt ein Lösungsblatt an. Mit «Gleiche Aufgaben für alle» sind es dieselben Aufgaben wie online (hat noch niemand gestartet, werden sie jetzt erzeugt und gespeichert), sonst bei jedem Öffnen neue Zufallsaufgaben. Als PDF: im Druckdialog «Als PDF speichern» (`Task\WorksheetRenderer`, `PrintController::worksheet()`).
8. **Auswertung** (Symbol in der Testliste): Klassenübersicht und pro Schülerin und Schüler ein Blatt mit allen Aufgaben, Antworten, Lösungen, Punkten und Note.

## Rechte im Backend

Zugriff auf «Schultests» haben nur Backend-Benutzer, für deren Benutzergruppe die Module freigeschaltet sind. Jeder Test gehört der Person, die ihn angelegt (oder kopiert) hat (Feld `author`, «Besitzer»). Andere Benutzer sehen den Test nicht in der Liste und können ihn weder öffnen, bearbeiten, kopieren, löschen noch die Teilnahmen, das Codeblatt, die Auswertung oder die Vorschau aufrufen (`Security\TestVoter`, `Security\ParticipationVoter`). Administratoren sehen alle Tests, in der Liste mit dem Namen des Besitzers, und können den Besitzer im Test unter «Besitzer» (unterste Gruppe im Formular) ändern. Tests ohne Besitzer (angelegt vor diesem Update) sehen nur Administratoren, bis sie einen Besitzer zuweisen. Nach dem Update `contao:migrate` ausführen (neue Spalte `tl_st_test.author`).

## Regeln im Frontend

- **Gleiche Aufgaben für alle** (Standard): Die Aufgaben werden beim ersten Start einmal pro Test erzeugt. Solange niemand gestartet hat, erzeugt jedes Speichern des Tests neue Aufgaben. Ohne Häkchen erhält jedes Kind eigene Zufallsaufgaben.
- Lösungen werden nur auf dem Server gespeichert und korrigiert, sie gelangen nie in den Browser.
- **Sperre**: Wechselt ein Kind im Browser die Seite (anderer Tab, Fenster minimiert, neu laden, wegnavigieren), wird der Test gesperrt. Ein Klick in die Adressleiste oder in ein anderes Programm sperrt nicht. Weiter geht es nur mit Schülercode + Wiederaufnahme-Code der Lehrperson. Die Antworten bleiben erhalten. Die Anzahl Sperren steht auf der Auswertung. Abschaltbar pro Test.
- **Seite neu laden**: Der Browser merkt sich im Tab (`sessionStorage`, weg beim Schliessen des Tabs) Schülercode, Sitzungsschlüssel und die GeoGebra-Konstruktionen samt Hilfslinien. Ohne Sperre geht der Test nach dem Neuladen direkt weiter (Aktion `continue`, der Server prüft den Sitzungsschlüssel). Mit Sperre erscheint direkt die Eingabe des Wiederaufnahme-Codes, danach sind Antworten und Konstruktionen wieder da.
- **Zeit**: Optional. Die Uhr läuft nur, solange der Test offen ist (nicht während einer Sperre). Ist die Zeit um, wird automatisch abgegeben.
- **Abgeschlossene Tests** können nicht nochmals gestartet werden. Das Kind sieht nach der Abgabe seine Punktzahl (nicht die Note).
- Ein Test kann in der Teilnahmen-Liste auf «Offen» zurückgesetzt werden: Aufgaben und Antworten werden gelöscht, der Test beginnt neu.
- Nach 15 falschen Codes pro IP-Adresse wird 15 Minuten gesperrt.

## Safe Exam Browser

Pro Test: **Nur mit dem Safe Exam Browser** (bei neuen Tests eingeschaltet, bestehende Tests bleiben nach dem Update ausgeschaltet). Der Server prüft dann bei jedem Schritt (Starten, Fortsetzen, Speichern, Sperren, Abgeben), ob die Anfrage aus dem SEB kommt. In jedem anderen Browser erscheint nach der Code-Eingabe: «Dieser Test kann nur mit dem Safe Exam Browser gelöst werden.» (übersetzt in fr/it/en). Die Übungen sind nicht betroffen.

- **Mit Config Key** (empfohlen): Im SEB-Konfigurationswerkzeug den *Config Key* kopieren und im Test eintragen. SEB schickt bei jeder Anfrage den Header `X-SafeExamBrowser-ConfigKeyHash` = SHA256(URL + Config Key). SEB für macOS und iOS kann keine Header senden; dann liest das Script denselben Wert über die SEB-JavaScript-API (`SafeExamBrowser.security.configKey`) und schickt ihn mit. Beides lässt sich ohne die passende Konfiguration nicht fälschen. In der Konfiguration «Send Browser Exam Key / Config Key» aktivieren. Wird die Konfiguration geändert, ändert sich der Config Key.
- **Ohne Config Key**: Es wird nur geprüft, ob sich der Browser als SEB ausgibt (Header, JavaScript-API oder User-Agent `SEB/…`). Das genügt gegen versehentliches Öffnen im normalen Browser, lässt sich aber fälschen.

Nach dem Update `vendor/bin/contao-console contao:migrate` ausführen (neue Felder `acceptSebBrowserOnly`, `sebConfigKey`).

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

