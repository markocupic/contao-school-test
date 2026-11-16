/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 *
 * Frontend of the online test: code login, test form with autosave,
 * lock when the student leaves the page (tab change, reload, navigation), resume with the teacher's code, submit.
 * Solutions never reach the browser, grading happens on the server.
 */
(function () {
    'use strict';


    /* ---------- Languages: the student chooses before the test, German is the default ---------- */

    const LANGS = {de: 'Deutsch', fr: 'Français', it: 'Italiano', en: 'English'};
    const I18N = {
        de: {
            chooseType: 'Was möchtest du üben?',
            chooseArea: 'Bereich', chooseLevel: 'Schwierigkeit', easy: 'Leicht', medium: 'Mittel', hard: 'Anspruchsvoller',
            practiceHead: 'Üben', practiceIntro2: 'Wähle aus, was du üben möchtest, und die Schwierigkeit. Bei jedem Start gibt es neue Aufgaben. Nichts wird gespeichert und es gibt keine Note. Nach dem Auswerten siehst du zu jeder Aufgabe die Lösung und den Lösungsweg.', otherChoice: 'Andere Auswahl',
            language: 'Sprache', startTitle: 'Test starten', enterCode: 'Gib deinen fünfstelligen Code ein.', code: 'Code', start: 'Starten',
            codeFive: 'Der Code hat fünf Ziffern.', wait: 'Einen Moment …', noConnection: 'Keine Verbindung zum Server. Versuche es nochmals.',
            interrupted: 'Dein Test wurde unterbrochen.', left: 'Du hast den Test verlassen. Er ist jetzt gesperrt.', lockedTitle: 'Test gesperrt',
            lockedText: 'Deine Antworten sind gespeichert. Melde dich bei deiner Lehrperson. Sie gibt den Wiederaufnahme-Code ein.',
            resumeCode: 'Wiederaufnahme-Code', continue: 'Weiter', otherCode: 'Anderen Code eingeben', enterResume: 'Bitte den Wiederaufnahme-Code eingeben.',
            alreadyDone: 'Diesen Test hast du bereits abgegeben', handedIn: 'Test abgegeben', timeUpAuto: 'Die Zeit ist abgelaufen. Dein Test wurde automatisch abgegeben.',
            score: '<span>{0}</span> von {1} Punkten', gradeFromTeacher: 'Die Note erhältst du von deiner Lehrperson.', points: '{0} Punkte', minutes: '{0} Minuten', pts: '{0} P.',
            saved: 'Gespeichert', saving: 'Wird gespeichert …', retry: 'Keine Verbindung, neuer Versuch …',
            stayHere: 'Bleibe auf dieser Seite. Wechselst du den Tab oder lädst du die Seite neu, wird der Test gesperrt.',
            handIn: 'Test abgeben', confirm: '<strong>Willst du den Test abgeben?</strong> Danach kannst du nichts mehr ändern.',
            oneOpen: 'Eine Aufgabe ist noch nicht beantwortet.', nOpen: '{0} Aufgaben sind noch nicht beantwortet.', yesHandIn: 'Ja, abgeben', keepGoing: 'Weiter rechnen',
            handingIn: 'Test wird abgegeben …', noConnKeep: 'Keine Verbindung zum Server. Deine Antworten sind noch da, versuche es nochmals.',
            timeUpSubmitting: 'Die Zeit ist abgelaufen. Dein Test wird abgegeben …', remaining: 'Noch {0}',
            whole: 'Ganze', numerator: 'Zähler', denominator: 'Nenner', result: 'Ergebnis', share: 'Anteil in %', column: 'Spalte {0}',
            practiceTitle: 'Üben: {0}', practiceIntro: 'Du bekommst Aufgaben wie im Test, bei jedem Start neue. Nichts wird gespeichert und es gibt keine Note. Nach dem Auswerten siehst du zu jeder Aufgabe die Lösung und den Lösungsweg.',
            practiceTime: 'Zeit wie im Test: {0} Minuten.', practiceStart: 'Übung starten', generating: 'Aufgaben werden erzeugt …', practiceError: 'Die Übung kann nicht gestartet werden.',
            check: 'Auswerten', again: 'Neue Übung', timeUp: 'Die Zeit ist abgelaufen.', checkError: 'Die Auswertung hat nicht geklappt.',
            fieldsRight: '{0} von {1} Feldern richtig.', right: 'Richtig.', solution: 'Lösung', scoreSmall: '{0} / {1} Punkte',
            practiceResult: '<strong>{0} von {1} Punkten ({2} %).</strong> Schau dir die Lösungswege der falschen Aufgaben an und starte dann eine neue Übung.',
            err_invalid_code: 'Diesen Code gibt es nicht. Prüfe deine Eingabe.', err_invalid_resume_code: 'Der Wiederaufnahme-Code stimmt nicht.',
            err_too_many_attempts: 'Zu viele falsche Versuche. Warte 15 Minuten oder melde dich bei deiner Lehrperson.',
            err_unknown_type: 'Dieser Test kann nicht gestartet werden. Melde dich bei deiner Lehrperson.',
            tool_point: 'Punkt', tool_line: 'Gerade', tool_circle: 'Zirkel', undo: 'Rückgängig', clearLines: 'Hilfslinien löschen', tool_select: 'Auswählen', delSel: 'Auswahl löschen', toolsHint: 'Gerade: von einem Punkt zum anderen ziehen oder auf eine Kante des Geodreiecks klicken. Zirkel: beim Mittelpunkt drücken und bis zum Radius ziehen. Auswählen: Hilfslinie anklicken und verschieben, mit der Taste Entf löschen. Punkt: Bildpunkt setzen, er rastet auf Schnittpunkten ein; zum Korrigieren ziehen.',
            geoOff: 'Bei schweren Aufgaben ist das Geodreieck nicht erlaubt.',
            geoShow: 'Geodreieck einblenden', geoHide: 'Geodreieck ausblenden', geoLeft: 'Geodreieck nach links drehen', geoRight: 'Geodreieck nach rechts drehen', geoHint: 'Am Geodreieck ziehen verschiebt es, am runden Griff drehst du es.',
            imagePoints: 'Bildpunkte', board: 'Zeichenfläche: Klicke auf einen Gitterpunkt, um den gewählten Bildpunkt zu setzen.', clearPoints: 'Alle Punkte entfernen', pointsRight: '{0} von {1} Bildpunkten richtig.', solutionDashed: 'Die Lösung ist grün gestrichelt eingezeichnet.', wrongDir: 'Du hast in die falsche Richtung gedreht.',
            err_expired: 'Die Übung ist abgelaufen. Starte eine neue Übung.', err_unavailable: 'Diese Übung ist nicht verfügbar.',
        },
        fr: {
            chooseType: 'Que veux-tu travailler ?',
            chooseArea: 'Domaine', chooseLevel: 'Difficulté', easy: 'Facile', medium: 'Moyen', hard: 'Exigeant',
            practiceHead: 'S’entraîner', practiceIntro2: 'Choisis ce que tu veux travailler et la difficulté. À chaque départ, tu reçois de nouvelles tâches. Rien n’est enregistré et il n’y a pas de note. Après la correction, tu vois la solution et la démarche de chaque tâche.', otherChoice: 'Autre choix',
            language: 'Langue', startTitle: 'Commencer le test', enterCode: 'Saisis ton code à cinq chiffres.', code: 'Code', start: 'Commencer',
            codeFive: 'Le code comporte cinq chiffres.', wait: 'Un instant …', noConnection: 'Pas de connexion au serveur. Réessaie.',
            interrupted: 'Ton test a été interrompu.', left: 'Tu as quitté le test. Il est maintenant bloqué.', lockedTitle: 'Test bloqué',
            lockedText: 'Tes réponses sont enregistrées. Adresse-toi à ton enseignant·e, qui saisira le code de reprise.',
            resumeCode: 'Code de reprise', continue: 'Continuer', otherCode: 'Saisir un autre code', enterResume: 'Saisis le code de reprise.',
            alreadyDone: 'Tu as déjà rendu ce test', handedIn: 'Test rendu', timeUpAuto: 'Le temps est écoulé. Ton test a été rendu automatiquement.',
            score: '<span>{0}</span> points sur {1}', gradeFromTeacher: 'Ton enseignant·e te donnera la note.', points: '{0} points', minutes: '{0} minutes', pts: '{0} pts',
            saved: 'Enregistré', saving: 'Enregistrement …', retry: 'Pas de connexion, nouvel essai …',
            stayHere: 'Reste sur cette page. Si tu changes d’onglet ou recharges la page, le test sera bloqué.',
            handIn: 'Rendre le test', confirm: '<strong>Veux-tu rendre le test&nbsp;?</strong> Ensuite, tu ne pourras plus rien modifier.',
            oneOpen: 'Une tâche est encore sans réponse.', nOpen: '{0} tâches sont encore sans réponse.', yesHandIn: 'Oui, rendre', keepGoing: 'Continuer à calculer',
            handingIn: 'Le test est en train d’être rendu …', noConnKeep: 'Pas de connexion au serveur. Tes réponses sont conservées, réessaie.',
            timeUpSubmitting: 'Le temps est écoulé. Ton test est rendu …', remaining: 'Encore {0}',
            whole: 'Entiers', numerator: 'Numérateur', denominator: 'Dénominateur', result: 'Résultat', share: 'Part en %', column: 'Colonne {0}',
            practiceTitle: 'S’entraîner&nbsp;: {0}', practiceIntro: 'Tu reçois des tâches comme au test, de nouvelles à chaque fois. Rien n’est enregistré et il n’y a pas de note. Après la correction, tu vois la solution et la démarche de chaque tâche.',
            practiceTime: 'Temps comme au test&nbsp;: {0} minutes.', practiceStart: 'Commencer l’entraînement', generating: 'Création des tâches …', practiceError: 'L’entraînement ne peut pas commencer.',
            check: 'Corriger', again: 'Nouvel entraînement', timeUp: 'Le temps est écoulé.', checkError: 'La correction n’a pas fonctionné.',
            fieldsRight: '{0} cases justes sur {1}.', right: 'Juste.', solution: 'Solution', scoreSmall: '{0} / {1} points',
            practiceResult: '<strong>{0} points sur {1} ({2}&nbsp;%).</strong> Regarde la démarche des tâches fausses, puis commence un nouvel entraînement.',
            err_invalid_code: 'Ce code n’existe pas. Vérifie ta saisie.', err_invalid_resume_code: 'Le code de reprise est incorrect.',
            err_too_many_attempts: 'Trop d’essais erronés. Attends 15 minutes ou adresse-toi à ton enseignant·e.',
            err_unknown_type: 'Ce test ne peut pas commencer. Adresse-toi à ton enseignant·e.',
            tool_point: 'Point', tool_line: 'Droite', tool_circle: 'Compas', undo: 'Annuler', clearLines: 'Effacer les traits de construction', tool_select: 'Sélectionner', delSel: 'Supprimer la sélection', toolsHint: 'Droite&nbsp;: fais glisser d’un point à l’autre ou clique sur un bord de l’équerre. Compas&nbsp;: appuie au centre et fais glisser jusqu’au rayon. Sélectionner&nbsp;: clique sur un trait de construction pour le déplacer, la touche Suppr l’efface. Point&nbsp;: place un point image, il s’accroche aux intersections&nbsp;; fais-le glisser pour le corriger.',
            geoOff: 'L’équerre n’est pas autorisée pour les tâches difficiles.',
            geoShow: 'Afficher l’équerre', geoHide: 'Masquer l’équerre', geoLeft: 'Tourner l’équerre vers la gauche', geoRight: 'Tourner l’équerre vers la droite', geoHint: 'Fais glisser l’équerre pour la déplacer, utilise la poignée ronde pour la tourner.',
            imagePoints: 'Points images', board: 'Surface de dessin&nbsp;: clique sur un point du quadrillage pour placer le point image choisi.', clearPoints: 'Supprimer tous les points', pointsRight: '{0} points images justes sur {1}.', solutionDashed: 'La solution est dessinée en vert pointillé.', wrongDir: 'Tu as tourné dans le mauvais sens.',
            err_expired: 'L’entraînement a expiré. Commence un nouvel entraînement.', err_unavailable: 'Cet entraînement n’est pas disponible.',
        },
        it: {
            chooseType: 'Cosa vuoi esercitare?',
            chooseArea: 'Campo', chooseLevel: 'Difficoltà', easy: 'Facile', medium: 'Medio', hard: 'Impegnativo',
            practiceHead: 'Esercitarsi', practiceIntro2: 'Scegli cosa vuoi esercitare e la difficoltà. A ogni avvio ricevi nuovi esercizi. Non viene salvato nulla e non c’è nessun voto. Dopo la correzione vedi la soluzione e il procedimento di ogni esercizio.', otherChoice: 'Altra scelta',
            language: 'Lingua', startTitle: 'Iniziare il test', enterCode: 'Inserisci il tuo codice di cinque cifre.', code: 'Codice', start: 'Iniziare',
            codeFive: 'Il codice ha cinque cifre.', wait: 'Un momento …', noConnection: 'Nessuna connessione al server. Riprova.',
            interrupted: 'Il tuo test è stato interrotto.', left: 'Hai lasciato il test. Ora è bloccato.', lockedTitle: 'Test bloccato',
            lockedText: 'Le tue risposte sono salvate. Rivolgiti al tuo docente: inserirà il codice di ripresa.',
            resumeCode: 'Codice di ripresa', continue: 'Continua', otherCode: 'Inserire un altro codice', enterResume: 'Inserisci il codice di ripresa.',
            alreadyDone: 'Hai già consegnato questo test', handedIn: 'Test consegnato', timeUpAuto: 'Il tempo è scaduto. Il tuo test è stato consegnato automaticamente.',
            score: '<span>{0}</span> punti su {1}', gradeFromTeacher: 'Il voto te lo darà il tuo docente.', points: '{0} punti', minutes: '{0} minuti', pts: '{0} pt.',
            saved: 'Salvato', saving: 'Salvataggio …', retry: 'Nessuna connessione, nuovo tentativo …',
            stayHere: 'Resta su questa pagina. Se cambi scheda o ricarichi la pagina, il test viene bloccato.',
            handIn: 'Consegnare il test', confirm: '<strong>Vuoi consegnare il test?</strong> Dopo non potrai più modificare nulla.',
            oneOpen: 'Un esercizio non ha ancora una risposta.', nOpen: '{0} esercizi non hanno ancora una risposta.', yesHandIn: 'Sì, consegnare', keepGoing: 'Continuare a calcolare',
            handingIn: 'Il test viene consegnato …', noConnKeep: 'Nessuna connessione al server. Le tue risposte sono ancora qui, riprova.',
            timeUpSubmitting: 'Il tempo è scaduto. Il tuo test viene consegnato …', remaining: 'Ancora {0}',
            whole: 'Interi', numerator: 'Numeratore', denominator: 'Denominatore', result: 'Risultato', share: 'Percentuale', column: 'Colonna {0}',
            practiceTitle: 'Esercitarsi: {0}', practiceIntro: 'Ricevi esercizi come nel test, nuovi a ogni avvio. Non viene salvato nulla e non c’è nessun voto. Dopo la correzione vedi la soluzione e il procedimento di ogni esercizio.',
            practiceTime: 'Tempo come nel test: {0} minuti.', practiceStart: 'Iniziare l’esercizio', generating: 'Creazione degli esercizi …', practiceError: 'L’esercizio non può essere avviato.',
            check: 'Correggere', again: 'Nuovo esercizio', timeUp: 'Il tempo è scaduto.', checkError: 'La correzione non è riuscita.',
            fieldsRight: '{0} caselle giuste su {1}.', right: 'Giusto.', solution: 'Soluzione', scoreSmall: '{0} / {1} punti',
            practiceResult: '<strong>{0} punti su {1} ({2} %).</strong> Guarda il procedimento degli esercizi sbagliati e poi inizia un nuovo esercizio.',
            err_invalid_code: 'Questo codice non esiste. Controlla l’inserimento.', err_invalid_resume_code: 'Il codice di ripresa non è corretto.',
            err_too_many_attempts: 'Troppi tentativi errati. Aspetta 15 minuti o rivolgiti al tuo docente.',
            err_unknown_type: 'Questo test non può essere avviato. Rivolgiti al tuo docente.',
            tool_point: 'Punto', tool_line: 'Retta', tool_circle: 'Compasso', undo: 'Annulla', clearLines: 'Cancella le linee di costruzione', tool_select: 'Seleziona', delSel: 'Elimina selezione', toolsHint: 'Retta: trascina da un punto all’altro o clicca su un bordo della squadra. Compasso: premi sul centro e trascina fino al raggio. Seleziona: clicca su una linea di costruzione per spostarla, il tasto Canc la elimina. Punto: posiziona un punto immagine, si aggancia alle intersezioni; trascinalo per correggerlo.',
            geoOff: 'Per i compiti difficili la squadra non è permessa.',
            geoShow: 'Mostra la squadra', geoHide: 'Nascondi la squadra', geoLeft: 'Ruota la squadra a sinistra', geoRight: 'Ruota la squadra a destra', geoHint: 'Trascina la squadra per spostarla, usa la maniglia rotonda per ruotarla.',
            imagePoints: 'Punti immagine', board: 'Area di disegno: clicca su un punto della griglia per posizionare il punto immagine scelto.', clearPoints: 'Eliminare tutti i punti', pointsRight: '{0} punti immagine giusti su {1}.', solutionDashed: 'La soluzione è disegnata in verde tratteggiato.', wrongDir: 'Hai ruotato nella direzione sbagliata.',
            err_expired: 'L’esercizio è scaduto. Inizia un nuovo esercizio.', err_unavailable: 'Questo esercizio non è disponibile.',
        },
        en: {
            chooseType: 'What do you want to practise?',
            chooseArea: 'Area', chooseLevel: 'Difficulty', easy: 'Easy', medium: 'Medium', hard: 'Challenging',
            practiceHead: 'Practice', practiceIntro2: 'Choose what you want to practise and the difficulty. Every start gives you new tasks. Nothing is saved and there is no grade. After checking you see the solution and the steps for every task.', otherChoice: 'Change selection',
            language: 'Language', startTitle: 'Start the test', enterCode: 'Enter your five-digit code.', code: 'Code', start: 'Start',
            codeFive: 'The code has five digits.', wait: 'One moment …', noConnection: 'No connection to the server. Please try again.',
            interrupted: 'Your test was interrupted.', left: 'You left the test. It is now locked.', lockedTitle: 'Test locked',
            lockedText: 'Your answers are saved. Go to your teacher, who will enter the resume code.',
            resumeCode: 'Resume code', continue: 'Continue', otherCode: 'Enter a different code', enterResume: 'Please enter the resume code.',
            alreadyDone: 'You have already handed in this test', handedIn: 'Test handed in', timeUpAuto: 'Time is up. Your test was handed in automatically.',
            score: '<span>{0}</span> of {1} points', gradeFromTeacher: 'Your teacher will give you the grade.', points: '{0} points', minutes: '{0} minutes', pts: '{0} pts',
            saved: 'Saved', saving: 'Saving …', retry: 'No connection, retrying …',
            stayHere: 'Stay on this page. If you switch tabs or reload the page, the test will be locked.',
            handIn: 'Hand in the test', confirm: '<strong>Do you want to hand in the test?</strong> You cannot change anything afterwards.',
            oneOpen: 'One task has not been answered yet.', nOpen: '{0} tasks have not been answered yet.', yesHandIn: 'Yes, hand in', keepGoing: 'Keep working',
            handingIn: 'Handing in the test …', noConnKeep: 'No connection to the server. Your answers are still here, please try again.',
            timeUpSubmitting: 'Time is up. Your test is being handed in …', remaining: '{0} left',
            whole: 'Whole', numerator: 'Numerator', denominator: 'Denominator', result: 'Result', share: 'Share in %', column: 'Column {0}',
            practiceTitle: 'Practice: {0}', practiceIntro: 'You get tasks like in the test, new ones every time. Nothing is saved and there is no grade. After checking you see the solution and the steps for every task.',
            practiceTime: 'Time as in the test: {0} minutes.', practiceStart: 'Start practice', generating: 'Creating tasks …', practiceError: 'The practice cannot be started.',
            check: 'Check', again: 'New practice', timeUp: 'Time is up.', checkError: 'The check did not work.',
            fieldsRight: '{0} of {1} fields correct.', right: 'Correct.', solution: 'Solution', scoreSmall: '{0} / {1} points',
            practiceResult: '<strong>{0} of {1} points ({2} %).</strong> Look at the steps of the wrong tasks, then start a new practice.',
            err_invalid_code: 'This code does not exist. Check your entry.', err_invalid_resume_code: 'The resume code is wrong.',
            err_too_many_attempts: 'Too many wrong attempts. Wait 15 minutes or ask your teacher.',
            err_unknown_type: 'This test cannot be started. Ask your teacher.',
            tool_point: 'Point', tool_line: 'Line', tool_circle: 'Compass', undo: 'Undo', clearLines: 'Clear construction lines', tool_select: 'Select', delSel: 'Delete selection', toolsHint: 'Line: drag from one point to another or click an edge of the set square. Compass: press at the centre and drag to the radius. Select: click a construction line to move it, the Delete key removes it. Point: place an image point, it snaps to intersections; drag it to correct it.',
            geoOff: 'The set square is not allowed for hard tasks.',
            geoShow: 'Show the set square', geoHide: 'Hide the set square', geoLeft: 'Rotate the set square to the left', geoRight: 'Rotate the set square to the right', geoHint: 'Drag the set square to move it, use the round handle to rotate it.',
            imagePoints: 'Image points', board: 'Drawing area: click a grid point to place the selected image point.', clearPoints: 'Remove all points', pointsRight: '{0} of {1} image points correct.', solutionDashed: 'The solution is drawn as a dashed green line.', wrongDir: 'You rotated in the wrong direction.',
            err_expired: 'The practice has expired. Start a new practice.', err_unavailable: 'This practice is not available.',
        },
    };

    // Not remembered on purpose: on a shared computer every student starts in German
    let lang = 'de';

    const tx = (key, ...args) => {
        let s = I18N[lang][key] ?? I18N.de[key] ?? key;
        args.forEach((v, i) => {
            s = s.split('{' + i + '}').join(v);
        });

        return s;
    };

    const errorText = (res, fallbackKey) => (res && res.error && I18N.de['err_' + res.error] ? tx('err_' + res.error) : tx(fallbackKey));

    function langPicker() {
        return `<div class="st-lang" role="group" aria-label="${tx('language')}">${Object.entries(LANGS).map(([k, name]) => `<button type="button" class="st-langbtn${k === lang ? ' on' : ''}" data-lang="${k}" lang="${k}" aria-pressed="${k === lang}">${name}</button>`).join('')}</div>`;
    }

    function bindPicker(root, rerender) {
        root.querySelectorAll('[data-lang]').forEach((b) => b.addEventListener('click', () => {
            lang = b.dataset.lang;
            rerender();
        }));
    }

    const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

    // Shared by exam and practice. t.q is generated by the server (numbers and fraction markup only).
    function taskHtml(t, ti) {
        const a = t.a;
        let input = '';

        if ('num' === a.type) {
            const suffix = a.suffix ? `<span class="st-suffix">${esc(a.suffix)}</span>` : '';
            input = `<span class="st-num">${a.est ? '≈ ' : ''}<input data-k="${t.id}" inputmode="decimal" autocomplete="off" aria-label="${tx('result')}">${suffix}</span>`;
        } else if ('frac' === a.type) {
            const whole = a.noWhole ? '' : `<input class="st-w" data-k="${t.id}" data-part="w" inputmode="numeric" autocomplete="off" aria-label="${tx('whole')}">`;
            input = `<span class="st-frac">${whole}<span class="st-stack"><input data-k="${t.id}" data-part="n" inputmode="numeric" autocomplete="off" aria-label="${tx('numerator')}"><span class="st-bar"></span><input data-k="${t.id}" data-part="d" inputmode="numeric" autocomplete="off" aria-label="${tx('denominator')}"></span></span>`;
        } else if ('rat' === a.type) {
            input = `<span class="st-num st-rat"><i>x</i>&nbsp;= <input data-k="${t.id}" inputmode="text" autocomplete="off" autocapitalize="off" spellcheck="false" aria-label="x"></span>`;
        } else if ('angles' === a.type) {
            // a.svg is generated by the server (sketch without solution)
            const fields = a.fields.map((f, i) => `<label class="st-angin"><span>${esc(f.label)}&nbsp;=</span><input data-k="${t.id}" data-col="${i}" inputmode="decimal" autocomplete="off" aria-label="${esc(f.label)}"><span>${esc(f.unit)}</span></label>`).join('');

            return `<li class="st-task st-wide st-ang" data-task="${t.id}"><span class="st-nr">${ti + 1}</span><div class="st-body"><span class="st-q">${t.q}</span><div class="st-angfig">${a.svg}</div><div class="st-angins">${fields}</div></div></li>`;
        } else if ('points' === a.type) {
            return `<li class="st-task st-wide st-geo" data-task="${t.id}"><span class="st-nr">${ti + 1}</span><div class="st-body"><span class="st-q">${t.q}</span>${boardHtml(t)}</div></li>`;
        } else if ('table' === a.type) {
            const cell = (c, i, row) => {
                if (c.ref) {
                    return `<td class="st-ref">${c[row]}</td>`;
                }

                if (c.ask !== row) {
                    return `<td>${c[row]}</td>`;
                }

                return `<td class="st-in"><input data-k="${t.id}" data-col="${i}" inputmode="decimal" autocomplete="off" aria-label="${esc(row === 'top' ? a.name : tx('share'))}, ${tx('column', i + 1)}"><span class="st-unit">${esc(c.unit)}</span></td>`;
            };

            return `<li class="st-task st-wide" data-task="${t.id}"><span class="st-nr">${ti + 1}</span><div class="st-body"><div class="st-tblwrap"><table class="st-table"><tr><th>${esc(a.name)}</th>${a.cols.map((c, i) => cell(c, i, 'top')).join('')}</tr><tr><th>${tx('share')}</th>${a.cols.map((c, i) => cell(c, i, 'bot')).join('')}</tr></table></div></div></li>`;
        }

        // t.q is generated by the server and contains only numbers and fraction markup
        return `<li class="st-task" data-task="${t.id}"><span class="st-nr">${ti + 1}</span><div class="st-body"><span class="st-q">${t.q}</span> ${input}</div></li>`;
    }

    /* ---------- Drawing board for reflections, rotations and translations (answer type "points") ---------- */

    const LETTERS = 'ABCDEFGH';
    const PRIME = '′';

    function boardHtml(t) {
        const n = t.a.draw.n;
        const chips = Array.from({length: n}, (_, i) => `<button type="button" class="st-chip" data-chip="${i}">${LETTERS[i]}${PRIME}</button>`).join('');

        // Geodreieck for reflections (axis or point, no rotation angle)
        const geo = hasGeo(t.a.draw) && t.a.draw.noGeo
            ? `<div class="st-geotools"><button type="button" class="st-opt st-geo-btn" disabled aria-disabled="true" aria-label="${esc(tx('geoOff'))}" title="${esc(tx('geoOff'))}">${GEO_ICON}</button><span class="st-geo-hint">${tx('geoOff')}</span></div>`
            : hasGeo(t.a.draw)
            ? `<div class="st-geotools"><button type="button" class="st-opt st-geo-btn" data-geo aria-pressed="false" aria-label="${esc(tx('geoShow'))}" title="${esc(tx('geoShow'))}">${GEO_ICON}</button><span class="st-geo-rot" hidden><button type="button" class="st-opt" data-geo-rot="-45" aria-label="${tx('geoLeft')}">↺</button><button type="button" class="st-opt" data-geo-rot="45" aria-label="${tx('geoRight')}">↻</button><span class="st-geo-hint">${tx('geoHint')}</span></span></div>`
            : '';

        // Without squares: tools for the construction
        const tools = t.a.draw.free
            ? `<div class="st-tools" role="group">${['select', 'point', 'line', 'circle'].map((k) => `<button type="button" class="st-opt st-tool${'point' === k ? ' on' : ''}" data-tool="${k}" aria-pressed="${'point' === k}" aria-label="${esc(tx('tool_' + k))}" title="${esc(tx('tool_' + k))}">${TOOL_ICONS[k]}</button>`).join('')}<button type="button" class="st-link" data-del-sel hidden>${tx('delSel')}</button><button type="button" class="st-link" data-undo>${tx('undo')}</button><button type="button" class="st-link" data-clear-cons>${tx('clearLines')}</button></div><div class="st-geo-hint">${tx('toolsHint')}</div>`
            : '';

        return `<div class="st-board${t.a.draw.free ? ' st-free' : ''}" data-draw="${esc(JSON.stringify(t.a.draw))}">
            <div class="st-chips" role="group" aria-label="${tx('imagePoints')}">${chips}<button type="button" class="st-link st-clear" data-clear>${tx('clearPoints')}</button></div>${tools}${geo}
            <svg class="st-svg" role="img" aria-label="${esc(tx('board').replace(/&nbsp;/g, ' '))}"></svg>
            <input type="hidden" data-k="${t.id}" data-pts="${n}">
        </div>`;
    }

    // Board used last: the Delete key removes its selected construction line
    let lastBoard = null;

    document.addEventListener('keydown', (e) => {
        if (('Delete' === e.key || 'Backspace' === e.key) && lastBoard && lastBoard.isConnected && !(e.target instanceof Element && e.target.closest('input, textarea, select, [contenteditable]'))) {
            if (lastBoard.stDelete?.()) {
                e.preventDefault();
            }
        }
    });

    // Tool icons: select (arrow), point (cross), line through two points, compass
    const toolIcon = (inner) => `<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${inner}</svg>`;
    const TOOL_ICONS = {
        select: toolIcon('<path d="M6 3.5l12 8.2-5.6 1.2-2.9 5.6z" fill="currentColor" fill-opacity=".18"/><path d="M12.4 12.9l4.2 6.1"/>'),
        point: toolIcon('<path d="M7 7l10 10M17 7L7 17"/>'),
        line: toolIcon('<path d="M3 20L21 4"/><circle cx="8.2" cy="15.4" r="1.9" fill="currentColor" stroke="none"/><circle cx="15.8" cy="8.6" r="1.9" fill="currentColor" stroke="none"/>'),
        circle: toolIcon('<circle cx="12" cy="4" r="1.8"/><path d="M11.2 5.6L6 19.5M12.8 5.6L18 19.5M8.2 13.5h7.6"/><path d="M3.5 21.2q8.5 2.6 17 0" stroke-dasharray="2 2.2"/>'),
    };

    // Button icon: Geodreieck with scale and centre line
    const GEO_ICON = '<svg viewBox="0 0 34 19" width="34" height="19" aria-hidden="true" focusable="false"><path d="M2 2.5h30L17 17.5z" fill="currentColor" fill-opacity=".15" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M17 2.5v12.5" fill="none" stroke="currentColor" stroke-width="1.1" stroke-dasharray="2 1.5"/><path d="M7 2.5v2.6M12 2.5v2.6M22 2.5v2.6M27 2.5v2.6M9.5 2.5v1.5M14.5 2.5v1.5M19.5 2.5v1.5M24.5 2.5v1.5" fill="none" stroke="currentColor" stroke-width="1"/></svg>';

    const hasGeo = (d) => !!d.seg || (!!d.center && !d.ang && !d.vec);

    /**
     * Geodreieck (hypotenuse 16 cm) in its own coordinates: zero point at the origin,
     * hypotenuse along x, right angle on +y. mm = pixels per millimetre.
     */
    function geoTriangle(mm) {
        const r = (v) => +(v * mm).toFixed(2);
        const pol = (rad, deg) => [r(rad * Math.cos(deg * Math.PI / 180)), r(rad * Math.sin(deg * Math.PI / 180))];
        let lines = '';
        let fine = '';
        let text = '';

        // Millimetre scale with centimetre numbers
        for (let k = -70; k <= 70; ++k) {
            const len = 0 === k % 10 ? 3.2 : (0 === k % 5 ? 2.5 : 1.5);
            if (0 === k % 5) {
                lines += `M${r(k)} 0V${r(len)}`;
            } else {
                fine += `M${r(k)} 0V${r(len)}`;
            }

            if (0 === k % 10) {
                text += `<text x="${r(k)}" y="${r(6.6)}">${Math.abs(k / 10)}</text>`;
            }
        }

        // Parallels every 5 mm
        for (let y = 5; y <= 35; y += 5) {
            fine += `M${r(-(80 - y) + 1)} ${r(y)}H${r(80 - y - 1)}`;
        }

        // Centre line
        lines += `M0 ${r(8)}V${r(80)}`;

        // Degree scale around the zero point
        for (let a = 1; a < 180; ++a) {
            const len = 0 === a % 10 ? 3.5 : (0 === a % 5 ? 3 : 2);
            const [x1, y1] = pol(44, a);
            const [x2, y2] = pol(44 + len, a);
            if (0 === a % 5) {
                lines += `M${x1} ${y1}L${x2} ${y2}`;
            } else {
                fine += `M${x1} ${y1}L${x2} ${y2}`;
            }

            if (0 === a % 10 && a > 10 && a < 170 && 90 !== a) {
                const [ix, iy] = pol(51, a);
                const [ox, oy] = pol(55.5, a);
                text += `<text x="${ix}" y="${iy}" transform="rotate(${a - 90} ${ix} ${iy})">${a}</text><text class="st-gdt-o" x="${ox}" y="${oy}" transform="rotate(${a - 90} ${ox} ${oy})">${180 - a}</text>`;
            }
        }

        const [nx, ny] = pol(51, 90);

        return `<polygon class="st-gdt-body" points="${r(-80)},0 ${r(80)},0 0,${r(80)}"/>`
            + `<path class="st-gdt-fine" d="${fine}"/><path class="st-gdt-line" d="${lines}"/>`
            + `<text x="${nx}" y="${ny}">90</text>${text}`
            // Handles close to the zero point, so they stay on the board
            + `<circle class="st-gdt-move" cx="0" cy="${r(18)}" r="9"/><path class="st-gdt-rotsym" d="M-5 ${r(18)}h10M0 ${r(18) - 5}v10"/>`
            + `<circle class="st-gdt-rot" cx="0" cy="${r(34)}" r="9"/><path class="st-gdt-rotsym" d="M-4 ${r(34) - 2}a4.5 4.5 0 1 1 1.5 5"/>`;
    }

    const parsePoints = (value, n) => {
        let v = [];

        try {
            v = JSON.parse(value || '[]');
        } catch (e) {
            v = [];
        }

        return Array.from({length: n}, (_, i) => (Array.isArray(v) && Array.isArray(v[i]) && 2 === v[i].length ? [+v[i][0], +v[i][1]] : null));
    };

    function mountBoards(root) {
        root.querySelectorAll('.st-board:not([data-mounted])').forEach((el) => {
            el.dataset.mounted = '1';
            el.stBoard = createBoard(el);
        });
    }

    function createBoard(el) {
        const d = JSON.parse(el.dataset.draw);
        const [W, H] = d.grid;
        const S = 30;
        const P = 20;
        // One square is 5 mm, as on squared paper: the scales of the Geodreieck match the squares
        const MM = S / 5;
        const X = (x) => P + x * S;
        const Y = (y) => P + (H - y) * S;
        const svg = el.querySelector('svg.st-svg');
        const input = el.querySelector('input[data-pts]');
        const chips = [...el.querySelectorAll('[data-chip]')];
        const n = d.n;
        let pts = parsePoints(input.value, n);
        let active = Math.max(0, pts.findIndex((p) => !p));
        let chosen = false;
        let result = null;
        let hover = null;
        // Geodreieck: position of the zero point (board pixels) and rotation in degrees
        let geo = null;
        let geoDrag = null;
        let suppressClick = false;
        // Without squares: tool and construction lines (board pixels, not saved)
        const free = !!d.free;
        const tol = free ? (d.tol || 0.6) : 0;
        let tool = 'point';
        let cons = [];
        let consDrag = null;
        // Selected construction line (index) and its move
        let sel = -1;
        let consMove = null;
        const box = [P, P, P + W * S, P + H * S];
        const inBox = (q) => q[0] >= box[0] - 0.5 && q[0] <= box[2] + 0.5 && q[1] >= box[1] - 0.5 && q[1] <= box[3] + 0.5;

        // Infinite line through a and b, cut at the paper border
        const clipLine = (a, b) => {
            const dx = b[0] - a[0];
            const dy = b[1] - a[1];
            let t0 = -1e9;
            let t1 = 1e9;

            for (const [p0, dp, lo, hi] of [[a[0], dx, box[0], box[2]], [a[1], dy, box[1], box[3]]]) {
                if (Math.abs(dp) < 1e-9) {
                    if (p0 < lo || p0 > hi) {
                        return null;
                    }

                    continue;
                }

                let u0 = (lo - p0) / dp;
                let u1 = (hi - p0) / dp;

                if (u0 > u1) {
                    [u0, u1] = [u1, u0];
                }

                t0 = Math.max(t0, u0);
                t1 = Math.min(t1, u1);
            }

            return t0 < t1 ? [[a[0] + t0 * dx, a[1] + t0 * dy], [a[0] + t1 * dx, a[1] + t1 * dy]] : null;
        };

        const intersect = (u, v) => {
            if ('circle' === u.t && 'line' === v.t) {
                [u, v] = [v, u];
            }

            if ('line' === u.t && 'line' === v.t) {
                const [x1, y1] = u.a;
                const [x2, y2] = u.b;
                const [x3, y3] = v.a;
                const [x4, y4] = v.b;
                const den = (x1 - x2) * (y3 - y4) - (y1 - y2) * (x3 - x4);

                if (Math.abs(den) < 1e-9) {
                    return [];
                }

                const t = ((x1 - x3) * (y3 - y4) - (y1 - y3) * (x3 - x4)) / den;

                return [[x1 + t * (x2 - x1), y1 + t * (y2 - y1)]];
            }

            if ('line' === u.t) {
                const dx = u.b[0] - u.a[0];
                const dy = u.b[1] - u.a[1];
                const fx = u.a[0] - v.c[0];
                const fy = u.a[1] - v.c[1];
                const A = dx * dx + dy * dy;
                const B = 2 * (fx * dx + fy * dy);
                const C = fx * fx + fy * fy - v.r * v.r;
                const disc = B * B - 4 * A * C;

                if (disc < 0) {
                    return [];
                }

                const sq = Math.sqrt(disc);

                return [(-B - sq) / (2 * A), (-B + sq) / (2 * A)].map((t) => [u.a[0] + t * dx, u.a[1] + t * dy]);
            }

            const dx = v.c[0] - u.c[0];
            const dy = v.c[1] - u.c[1];
            const dist = Math.hypot(dx, dy);

            if (dist < 1e-9 || dist > u.r + v.r || dist < Math.abs(u.r - v.r)) {
                return [];
            }

            const a = (u.r * u.r - v.r * v.r + dist * dist) / (2 * dist);
            const h = Math.sqrt(Math.max(0, u.r * u.r - a * a));
            const mx = u.c[0] + a * dx / dist;
            const my = u.c[1] + a * dy / dist;

            return [[mx + h * dy / dist, my - h * dx / dist], [mx - h * dy / dist, my + h * dx / dist]];
        };

        const axisLine = () => (d.seg ? {t: 'line', a: [X(d.seg[0][0]), Y(d.seg[0][1])], b: [X(d.seg[1][0]), Y(d.seg[1][1])]} : null);

        // Snap targets: intersections of the construction (with the axis), optionally the given points
        const snapTargets = (withPoints) => {
            const items = [...cons];
            const ax = axisLine();
            const out = [];

            if (ax) {
                items.push(ax);
            }

            for (let i = 0; i < items.length; ++i) {
                for (let j = i + 1; j < items.length; ++j) {
                    out.push(...intersect(items[i], items[j]));
                }
            }

            if (withPoints) {
                out.push(...d.pts.map((q) => [X(q[0]), Y(q[1])]), ...pts.filter(Boolean).map((q) => [X(q[0]), Y(q[1])]));

                if (d.center) {
                    out.push([X(d.center[0]), Y(d.center[1])]);
                }
            }

            return out.filter(inBox);
        };

        const snapTo = (q, withPoints, radius = 8) => {
            let best = null;
            let bd = radius;

            for (const c of snapTargets(withPoints)) {
                const dd = Math.hypot(c[0] - q[0], c[1] - q[1]);

                if (dd < bd) {
                    bd = dd;
                    best = c;
                }
            }

            return best ? [best[0], best[1]] : [q[0], q[1]];
        };

        const consSvg = (c, cls) => {
            if ('circle' === c.t) {
                return `<circle class="${cls}" cx="${c.c[0].toFixed(2)}" cy="${c.c[1].toFixed(2)}" r="${c.r.toFixed(2)}"/>`;
            }

            const seg = clipLine(c.a, c.b);

            return seg ? `<line class="${cls}" x1="${seg[0][0].toFixed(2)}" y1="${seg[0][1].toFixed(2)}" x2="${seg[1][0].toFixed(2)}" y2="${seg[1][1].toFixed(2)}"/>` : '';
        };

        // Line along an edge of the Geodreieck (long edge or centre line) near q
        const geoEdgeAt = (q) => {
            if (!geo) {
                return null;
            }

            const rad = geo.a * Math.PI / 180;
            const cos = Math.cos(rad);
            const sin = Math.sin(rad);
            const lx = (q[0] - geo.x) * cos + (q[1] - geo.y) * sin;
            const ly = -(q[0] - geo.x) * sin + (q[1] - geo.y) * cos;
            const len = 80 * MM;

            if (Math.abs(ly) < 10 && Math.abs(lx) <= len) {
                return {t: 'line', a: [geo.x, geo.y], b: [geo.x + cos * 100, geo.y + sin * 100]};
            }

            if (Math.abs(lx) < 10 && ly > 0 && ly <= len) {
                return {t: 'line', a: [geo.x, geo.y], b: [geo.x - sin * 100, geo.y + cos * 100]};
            }

            return null;
        };

        // Construction line or circle under q (within 7 px of the stroke)
        const hitCons = (q) => {
            let best = -1;
            let bd = 7;

            cons.forEach((c, i) => {
                let dd;

                if ('circle' === c.t) {
                    dd = Math.abs(Math.hypot(q[0] - c.c[0], q[1] - c.c[1]) - c.r);
                } else {
                    const dx = c.b[0] - c.a[0];
                    const dy = c.b[1] - c.a[1];
                    dd = Math.abs((q[0] - c.a[0]) * dy - (q[1] - c.a[1]) * dx) / Math.hypot(dx, dy);
                }

                if (dd < bd) {
                    bd = dd;
                    best = i;
                }
            });

            return best;
        };

        // Moved line or circle: a line snaps onto a point it passes close by, a circle centre onto a point
        const movedCons = (orig, dx, dy) => {
            const keep = cons;
            cons = cons.filter((c, i) => i !== consMove.i);
            const targets = snapTargets(true);
            cons = keep;

            if ('circle' === orig.t) {
                const c = [orig.c[0] + dx, orig.c[1] + dy];
                const near = targets.find((t) => Math.hypot(t[0] - c[0], t[1] - c[1]) < 8);

                return {t: 'circle', c: near ? [near[0], near[1]] : c, r: orig.r};
            }

            let a = [orig.a[0] + dx, orig.a[1] + dy];
            let b = [orig.b[0] + dx, orig.b[1] + dy];
            const ux = b[0] - a[0];
            const uy = b[1] - a[1];
            const len = Math.hypot(ux, uy);

            for (const t of targets) {
                const dist = ((t[0] - a[0]) * uy - (t[1] - a[1]) * ux) / len;

                if (Math.abs(dist) < 6) {
                    const sx = uy / len * dist;
                    const sy = -ux / len * dist;
                    a = [a[0] + sx, a[1] + sy];
                    b = [b[0] + sx, b[1] + sy];
                    break;
                }
            }

            return {t: 'line', a, b};
        };

        // Image point: onto an intersection (generous), otherwise onto a line or circle nearby
        const snapPlace = (q) => {
            const hit = snapTo(q, false, 14);

            if (hit[0] !== q[0] || hit[1] !== q[1]) {
                return hit;
            }

            let best = null;
            let bd = 8;

            for (const c of cons) {
                let f;

                if ('circle' === c.t) {
                    const len = Math.hypot(q[0] - c.c[0], q[1] - c.c[1]) || 1;
                    f = [c.c[0] + (q[0] - c.c[0]) / len * c.r, c.c[1] + (q[1] - c.c[1]) / len * c.r];
                } else {
                    const ux = c.b[0] - c.a[0];
                    const uy = c.b[1] - c.a[1];
                    const t = ((q[0] - c.a[0]) * ux + (q[1] - c.a[1]) * uy) / (ux * ux + uy * uy);
                    f = [c.a[0] + t * ux, c.a[1] + t * uy];
                }

                const dd = Math.hypot(f[0] - q[0], f[1] - q[1]);

                if (dd < bd) {
                    bd = dd;
                    best = f;
                }
            }

            return best || q;
        };

        const toCell = (q) => [+((q[0] - P) / S).toFixed(3), +(H - (q[1] - P) / S).toFixed(3)];

        // Dragging a placed image point (free mode)
        let pointDrag = null;

        const deleteSelection = () => {
            if (sel < 0) {
                return false;
            }

            cons.splice(sel, 1);
            sel = -1;
            render();

            return true;
        };

        // Delete key: only for the board used last, never while typing in a field
        el.stDelete = deleteSelection;

        const previewSvg = () => {
            if (!consDrag) {
                return '';
            }

            const [a, b] = [consDrag.start, consDrag.cur];

            if ('circle' === tool) {
                const r = Math.hypot(b[0] - a[0], b[1] - a[1]);

                return consSvg({t: 'circle', c: a, r}, 'st-cons st-prev') + `<line class="st-cons st-prev" x1="${a[0]}" y1="${a[1]}" x2="${b[0]}" y2="${b[1]}"/><text class="st-radius" x="${b[0] + 10}" y="${b[1] - 10}">r = ${(r / MM).toFixed(0)} mm</text>`;
            }

            if (consDrag.edge && !consDrag.moved) {
                return consSvg(consDrag.edge, 'st-cons st-prev');
            }

            return consDrag.moved ? consSvg({t: 'line', a, b}, 'st-cons st-prev') : '';
        };
        const geoMarkup = hasGeo(d) ? geoTriangle(MM) : '';

        svg.setAttribute('viewBox', `0 0 ${W * S + 2 * P} ${H * S + 2 * P}`);

        const poly = (list, cls) => {
            const xy = list.map((p) => `${X(p[0])},${Y(p[1])}`).join(' ');

            return list.length > 2 ? `<polygon class="${cls}" points="${xy}"/>` : `<polyline class="${cls}" points="${xy}"/>`;
        };

        // Vertex labels sit outside the figure, away from its centre
        const labels = (list, suffix, cls) => {
            const cx = list.reduce((s, p) => s + p[0], 0) / list.length;
            const cy = list.reduce((s, p) => s + p[1], 0) / list.length;

            return list.map((p, i) => {
                if (!p) {
                    return '';
                }

                let dx = p[0] - cx;
                let dy = p[1] - cy;
                const len = Math.hypot(dx, dy) || 1;
                dx /= len;
                dy /= len;

                return `<text class="${cls}" x="${X(p[0]) + dx * 13}" y="${Y(p[1]) - dy * 13 + 5}" text-anchor="middle">${LETTERS[i]}${suffix}</text>`;
            }).join('');
        };

        function render() {
            let g = '<defs><marker id="st-arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" class="st-arrowhead"/></marker></defs>';

            if (free) {
                g += `<rect class="st-paper" x="${box[0]}" y="${box[1]}" width="${W * S}" height="${H * S}"/>` + cons.map((c, i) => consSvg(c, i === sel ? 'st-cons st-sel' : 'st-cons')).join('');
            } else {
                for (let x = 0; x <= W; ++x) {
                    g += `<line class="st-gl" x1="${X(x)}" y1="${Y(0)}" x2="${X(x)}" y2="${Y(H)}"/>`;
                }

                for (let y = 0; y <= H; ++y) {
                    g += `<line class="st-gl" x1="${X(0)}" y1="${Y(y)}" x2="${X(W)}" y2="${Y(y)}"/>`;
                }
            }

            if (d.seg) {
                const [a, b] = d.seg;
                g += `<line class="st-axis" x1="${X(a[0])}" y1="${Y(a[1])}" x2="${X(b[0])}" y2="${Y(b[1])}"/>`;
                const lx = a[0] + (b[0] - a[0]) * 0.86;
                const ly = a[1] + (b[1] - a[1]) * 0.86;
                g += `<text class="st-mark" x="${X(lx) + 9}" y="${Y(ly) - 6}">s</text>`;
            }

            if (d.vec && d.vecStart) {
                const [sx, sy] = d.vecStart;
                const ex = sx + d.vec[0];
                const ey = sy + d.vec[1];
                g += `<line class="st-vec" x1="${X(sx)}" y1="${Y(sy)}" x2="${X(ex)}" y2="${Y(ey)}" marker-end="url(#st-arrow)"/>`;
                g += `<text class="st-mark" x="${(X(sx) + X(ex)) / 2 + 8}" y="${(Y(sy) + Y(ey)) / 2 - 8}">v</text>`;
            }

            if (d.center) {
                const [zx, zy] = d.center;

                // Small arc arrow: direction of the rotation (counterclockwise)
                if (d.ang) {
                    const r = 17;
                    const a0 = 20 * Math.PI / 180;
                    const a1 = (20 + d.ang) * Math.PI / 180;
                    g += `<path class="st-turn" d="M${X(zx) + r * Math.cos(a0)} ${Y(zy) - r * Math.sin(a0)} A${r} ${r} 0 ${d.ang > 180 ? 1 : 0} 0 ${X(zx) + r * Math.cos(a1)} ${Y(zy) - r * Math.sin(a1)}" marker-end="url(#st-arrow)"/>`;
                }

                g += `<circle class="st-z" cx="${X(zx)}" cy="${Y(zy)}" r="4.5"/><text class="st-mark" x="${X(zx) + 8}" y="${Y(zy) + 17}">Z</text>`;
            }

            g += poly(d.pts, 'st-fig') + d.pts.map((p) => `<circle class="st-figpt" cx="${X(p[0])}" cy="${Y(p[1])}" r="3.5"/>`).join('') + labels(d.pts, '', 'st-figlbl');

            if (result) {
                g += poly(result.img, 'st-ghost');

                if (free) {
                    g += result.img.map((q) => `<circle class="st-tol" cx="${X(q[0])}" cy="${Y(q[1])}" r="${tol * S}"/>`).join('');
                }
            }

            if (pts.every(Boolean)) {
                g += poly(pts, 'st-img');
            }

            g += pts.map((p, i) => {
                if (!p) {
                    return '';
                }

                const cls = result ? (result.correct[i] ? ' ok' : ' bad') : (i === active && chosen ? ' on' : '');

                return `<circle class="st-imgpt${cls}" cx="${X(p[0])}" cy="${Y(p[1])}" r="${free ? 4.5 : 6}"/>`;
            }).join('');

            if (pts.some(Boolean)) {
                const ref = pts.map((p, i) => p || d.pts[i]);
                const cx = ref.reduce((s, p) => s + p[0], 0) / n;
                const cy = ref.reduce((s, p) => s + p[1], 0) / n;
                g += pts.map((p, i) => {
                    if (!p) {
                        return '';
                    }

                    let dx = p[0] - cx;
                    let dy = p[1] - cy;
                    const len = Math.hypot(dx, dy) || 1;

                    return `<text class="st-imglbl" x="${X(p[0]) + dx / len * 15}" y="${Y(p[1]) - dy / len * 15 + 5}" text-anchor="middle">${LETTERS[i]}${PRIME}</text>`;
                }).join('');
            }

            g += '<circle class="st-hover" r="7" cx="-50" cy="-50"/><g class="st-preview"></g>';

            if (geo) {
                g += `<g class="st-gdt" transform="${geoTransform()}">${geoMarkup}</g>`;
            }

            svg.innerHTML = g;
            hover = svg.querySelector('.st-hover');
            const del = el.querySelector('[data-del-sel]');

            if (del) {
                del.hidden = sel < 0;
            }

            el.querySelectorAll('[data-tool]').forEach((b) => {
                b.classList.toggle('on', b.dataset.tool === tool);
                b.setAttribute('aria-pressed', String(b.dataset.tool === tool));
            });

            chips.forEach((c, i) => {
                c.classList.toggle('on', i === active && !result && (!pts[i] || chosen));
                c.classList.toggle('set', !!pts[i]);
                c.setAttribute('aria-pressed', String(i === active));
            });
        }

        const gridPoint = (e) => {
            const m = svg.getScreenCTM();

            if (!m) {
                return null;
            }

            const pt = new DOMPoint(e.clientX, e.clientY).matrixTransform(m.inverse());
            const x = Math.round((pt.x - P) / S);
            const y = H - Math.round((pt.y - P) / S);

            return x < 0 || x > W || y < 0 || y > H ? null : [x, y];
        };

        const toSvg = (e) => {
            const m = svg.getScreenCTM();

            return m ? new DOMPoint(e.clientX, e.clientY).matrixTransform(m.inverse()) : null;
        };

        const geoTransform = () => `translate(${geo.x.toFixed(2)} ${geo.y.toFixed(2)}) rotate(${geo.a.toFixed(2)})`;

        const updateGeo = () => svg.querySelector('.st-gdt')?.setAttribute('transform', geoTransform());

        // Zero point snaps to grid points, half grid points and the axis
        const snapPoint = (x, y) => {
            if (free) {
                const [sx, sy] = snapTo([x, y], true);

                if (sx !== x || sy !== y) {
                    return [sx, sy];
                }

                const ax = axisLine();

                if (ax) {
                    const ux = ax.b[0] - ax.a[0];
                    const uy = ax.b[1] - ax.a[1];
                    const t = ((x - ax.a[0]) * ux + (y - ax.a[1]) * uy) / (ux * ux + uy * uy);
                    const px = ax.a[0] + t * ux;
                    const py = ax.a[1] + t * uy;

                    if (Math.hypot(px - x, py - y) < 8) {
                        return [px, py];
                    }
                }

                return [x, y];
            }

            const hx = Math.round((x - P) / (S / 2)) * (S / 2) + P;
            const hy = Math.round((y - P) / (S / 2)) * (S / 2) + P;

            if (Math.hypot(hx - x, hy - y) < 8) {
                return [hx, hy];
            }

            if (d.seg) {
                const ax = X(d.seg[0][0]);
                const ay = Y(d.seg[0][1]);
                const ux = X(d.seg[1][0]) - ax;
                const uy = Y(d.seg[1][1]) - ay;
                const t = ((x - ax) * ux + (y - ay) * uy) / (ux * ux + uy * uy);
                const px = ax + t * ux;
                const py = ay + t * uy;

                if (Math.hypot(px - x, py - y) < 8) {
                    return [px, py];
                }
            }

            return [x, y];
        };

        // Rotation snaps to multiples of 15° (45° more strongly)
        const snapAngle = (a) => {
            // Without squares: only parallel or perpendicular to the axis
            if (free) {
                const ax = axisLine();

                if (!ax) {
                    return a;
                }

                const base = Math.atan2(ax.b[1] - ax.a[1], ax.b[0] - ax.a[0]) * 180 / Math.PI;
                const k = Math.round((a - base) / 90) * 90 + base;

                return Math.abs(k - a) < 3 ? k : a;
            }

            const n45 = Math.round(a / 45) * 45;

            if (Math.abs(n45 - a) < 5) {
                return n45;
            }

            const n15 = Math.round(a / 15) * 15;

            return Math.abs(n15 - a) < 2.5 ? n15 : a;
        };

        // First position: on the axis next to the figure, centre line along the axis; for a point reflection on Z
        const placeGeo = () => {
            const cx = d.pts.reduce((sum, p) => sum + X(p[0]), 0) / d.pts.length;
            const cy = d.pts.reduce((sum, p) => sum + Y(p[1]), 0) / d.pts.length;

            if (d.seg) {
                const ax = X(d.seg[0][0]);
                const ay = Y(d.seg[0][1]);
                const ux = X(d.seg[1][0]) - ax;
                const uy = Y(d.seg[1][1]) - ay;
                const t = ((cx - ax) * ux + (cy - ay) * uy) / (ux * ux + uy * uy);

                return towardsRoom({x: ax + t * ux, y: ay + t * uy, a: Math.atan2(uy, ux) * 180 / Math.PI - 90});
            }

            return towardsRoom({x: X(d.center[0]), y: Y(d.center[1]), a: 0});
        };

        // Turn the Geodreieck by 180° if its handles would end up outside the board
        const towardsRoom = (g) => {
            const room = (a) => {
                const rad = a * Math.PI / 180;
                const hx = g.x - Math.sin(rad) * 34 * MM;
                const hy = g.y + Math.cos(rad) * 34 * MM;

                return Math.min(hx - box[0], box[2] - hx, hy - box[1], box[3] - hy);
            };

            return room(g.a + 180) > room(g.a) ? {...g, a: g.a + 180} : g;
        };

        const geoBtn = el.querySelector('[data-geo]');

        geoBtn?.addEventListener('click', () => {
            geo = geo ? null : placeGeo();
            geoBtn.setAttribute('aria-label', tx(geo ? 'geoHide' : 'geoShow'));
            geoBtn.title = tx(geo ? 'geoHide' : 'geoShow');
            geoBtn.setAttribute('aria-pressed', String(!!geo));
            el.querySelector('.st-geo-rot').hidden = !geo;
            render();
        });

        el.querySelectorAll('[data-geo-rot]').forEach((b) => b.addEventListener('click', () => {
            if (geo) {
                geo.a = free ? geo.a + +b.dataset.geoRot : Math.round((geo.a + +b.dataset.geoRot) / 45) * 45;
                updateGeo();
            }
        }));

        svg.addEventListener('pointerdown', (e) => {
            const p = toSvg(e);

            if (!p) {
                return;
            }

            const handle = e.target.closest('.st-gdt-rot, .st-gdt-move');
            lastBoard = el;

            // Drag a placed image point onto an intersection
            if (free && 'point' === tool && !handle && !result) {
                const i = pts.findIndex((q) => q && Math.hypot(X(q[0]) - p.x, Y(q[1]) - p.y) < 10);

                if (i >= 0) {
                    pointDrag = {i, start: [p.x, p.y], moved: false};

                    try {
                        svg.setPointerCapture(e.pointerId);
                    } catch (err) {
                        // Pointer already released
                    }

                    e.preventDefault();

                    return;
                }
            }

            // Select and move a construction line
            if (free && 'select' === tool && !handle) {
                const q = [p.x, p.y];
                sel = hitCons(q);

                if (sel >= 0) {
                    consMove = {i: sel, start: q, orig: JSON.parse(JSON.stringify(cons[sel])), moved: false};

                    try {
                        svg.setPointerCapture(e.pointerId);
                    } catch (err) {
                        // Pointer already released
                    }

                    e.preventDefault();
                    render();

                    return;
                }

                render();
            }

            const onGeo = geo && e.target.closest('.st-gdt') && (handle || 'point' === tool || 'select' === tool);

            // Construction: line or circle
            if (free && ('line' === tool || 'circle' === tool) && !onGeo) {
                const q = [p.x, p.y];
                const edge = 'line' === tool ? geoEdgeAt(q) : null;
                const start = edge ? q : snapTo(q, true);
                consDrag = {start, cur: start, edge, moved: false};

                try {
                    svg.setPointerCapture(e.pointerId);
                } catch (err) {
                    // Pointer already released
                }

                svg.querySelector('.st-preview').innerHTML = previewSvg();
                e.preventDefault();

                return;
            }

            if (!onGeo) {
                return;
            }

            geoDrag = {rot: !!e.target.closest('.st-gdt-rot'), sx: p.x, sy: p.y, gx: geo.x, gy: geo.y, moved: false};

            try {
                svg.setPointerCapture(e.pointerId);
            } catch (err) {
                // Pointer already released: moving still works while the pointer stays on the board
            }

            e.preventDefault();
        });

        svg.addEventListener('pointerup', () => {
            // A real drag moved the point; a tap stays a click (which removes the point)
            if (pointDrag) {
                if (pointDrag.moved) {
                    suppressClick = true;
                    commit();
                }

                pointDrag = null;
            }

            if (consMove) {
                consMove = null;
                suppressClick = true;
            }

            if (geoDrag) {
                suppressClick = geoDrag.moved || geoDrag.rot;
                geoDrag = null;
            }

            if (consDrag) {
                const [a, b] = [consDrag.start, consDrag.cur];
                const dist = Math.hypot(b[0] - a[0], b[1] - a[1]);

                if ('circle' === tool && dist > 4) {
                    cons.push({t: 'circle', c: a, r: dist});
                } else if ('line' === tool && consDrag.edge && !consDrag.moved) {
                    cons.push(consDrag.edge);
                } else if ('line' === tool && dist > 6) {
                    cons.push({t: 'line', a, b});
                }

                consDrag = null;
                suppressClick = true;
                render();
            }
        });

        svg.addEventListener('pointercancel', () => {
            geoDrag = null;
            consDrag = null;
            consMove = null;
            pointDrag = null;
        });

        el.querySelectorAll('[data-tool]').forEach((b) => b.addEventListener('click', () => {
            tool = b.dataset.tool;
            sel = -1;
            lastBoard = el;
            render();
        }));

        el.querySelector('[data-del-sel]')?.addEventListener('click', deleteSelection);

        el.querySelector('[data-undo]')?.addEventListener('click', () => {
            cons.pop();
            sel = -1;
            render();
        });

        el.querySelector('[data-clear-cons]')?.addEventListener('click', () => {
            cons = [];
            sel = -1;
            render();
        });

        const commit = () => {
            input.value = JSON.stringify(pts);
            input.dispatchEvent(new Event('input', {bubbles: true}));
            render();
        };

        svg.addEventListener('click', (e) => {
            // The click ended a move of the Geodreieck: no point
            if (suppressClick) {
                suppressClick = false;

                return;
            }

            if (result || (free && 'point' !== tool)) {
                return;
            }

            let g;
            let hit;

            if (free) {
                const raw = toSvg(e);

                if (!raw || !inBox([raw.x, raw.y])) {
                    return;
                }

                hit = pts.findIndex((p) => p && Math.hypot(X(p[0]) - raw.x, Y(p[1]) - raw.y) < 9);
                g = toCell(snapPlace([raw.x, raw.y]));
            } else {
                g = gridPoint(e);

                if (!g) {
                    return;
                }

                hit = pts.findIndex((p) => p && p[0] === g[0] && p[1] === g[1]);
            }

            // A click on a placed point removes it, its letter is next
            if (hit >= 0) {
                pts[hit] = null;
                active = hit;
                chosen = false;
                commit();

                return;
            }

            // All points set: only move one after its letter was chosen
            if (pts[active] && !chosen) {
                return;
            }

            pts[active] = g;
            chosen = false;
            const next = pts.findIndex((p, i) => !p && i > active);
            const open = next >= 0 ? next : pts.findIndex((p) => !p);
            active = open >= 0 ? open : active;
            commit();
        });

        svg.addEventListener('pointermove', (e) => {
            if (geoDrag) {
                const p = toSvg(e);

                if (!p) {
                    return;
                }

                if (Math.hypot(p.x - geoDrag.sx, p.y - geoDrag.sy) > 4) {
                    geoDrag.moved = true;
                }

                if (geoDrag.rot) {
                    geo.a = snapAngle(Math.atan2(p.y - geo.y, p.x - geo.x) * 180 / Math.PI - 90);
                } else if (geoDrag.moved) {
                    [geo.x, geo.y] = snapPoint(geoDrag.gx + p.x - geoDrag.sx, geoDrag.gy + p.y - geoDrag.sy);
                }

                updateGeo();

                return;
            }

            if (pointDrag) {
                const p = toSvg(e);

                if (!p) {
                    return;
                }

                if (pointDrag.moved || Math.hypot(p.x - pointDrag.start[0], p.y - pointDrag.start[1]) > 3) {
                    pointDrag.moved = true;
                    const q = snapPlace([Math.min(box[2], Math.max(box[0], p.x)), Math.min(box[3], Math.max(box[1], p.y))]);
                    pts[pointDrag.i] = toCell(q);
                    render();
                }

                return;
            }

            if (consMove) {
                const p = toSvg(e);

                if (!p) {
                    return;
                }

                const dx = p.x - consMove.start[0];
                const dy = p.y - consMove.start[1];

                if (consMove.moved || Math.hypot(dx, dy) > 3) {
                    consMove.moved = true;
                    cons[consMove.i] = movedCons(consMove.orig, dx, dy);
                    render();
                }

                return;
            }

            if (consDrag) {
                const p = toSvg(e);

                if (!p) {
                    return;
                }

                const q = [p.x, p.y];

                if (Math.hypot(q[0] - consDrag.start[0], q[1] - consDrag.start[1]) > 4) {
                    consDrag.moved = true;
                }

                consDrag.cur = snapTo(q, true);
                svg.querySelector('.st-preview').innerHTML = previewSvg();

                return;
            }

            if ('mouse' !== e.pointerType || !hover) {
                return;
            }

            if (free) {
                const p = toSvg(e);

                if (p && inBox([p.x, p.y])) {
                    const [hx, hy] = 'point' === tool ? snapPlace([p.x, p.y]) : snapTo([p.x, p.y], true, 8);
                    const snapped = hx !== p.x || hy !== p.y;
                    hover.setAttribute('cx', snapped ? hx : -50);
                    hover.setAttribute('cy', snapped ? hy : -50);
                } else {
                    hover.setAttribute('cx', -50);
                }

                return;
            }

            if (result) {
                return;
            }

            const g = gridPoint(e);
            hover.setAttribute('cx', g ? X(g[0]) : -50);
            hover.setAttribute('cy', g ? Y(g[1]) : -50);
        });

        svg.addEventListener('pointerleave', () => {
            hover?.setAttribute('cx', -50);
        });

        chips.forEach((c, i) => c.addEventListener('click', () => {
            if (result) {
                return;
            }

            active = i;
            chosen = true;
            render();
        }));

        el.querySelector('[data-clear]').addEventListener('click', () => {
            if (result) {
                return;
            }

            pts = pts.map(() => null);
            active = 0;
            chosen = false;
            commit();
        });

        render();

        return {
            // Practice: show the solution and mark the student's points
            showResult(img, correct) {
                result = {img, correct};
                el.classList.add('st-board-done');
                el.querySelector('[data-clear]').hidden = true;
                render();
            },
        };
    }

    // Filled means: a value in the input, for drawings at least one point
    const isFilled = (inp) => (inp.dataset.pts ? parsePoints(inp.value, +inp.dataset.pts).some(Boolean) : '' !== inp.value.trim());

    // Value of one input for the answers object
    const inputValue = (inp) => (inp.dataset.pts ? parsePoints(inp.value, +inp.dataset.pts) : inp.value);


    document.querySelectorAll('[data-school-test]').forEach((root) => init(root));
    document.querySelectorAll('[data-school-test-practice]').forEach((root) => initPractice(root));

    function init(root) {
        let api;

        try {
            api = JSON.parse(root.dataset.schoolTest);
        } catch (e) {
            return;
        }

        const st = {
            code: '',
            token: '',
            student: '',
            test: '',
            sections: [],
            answers: {},
            lockOnLeave: true,
            armed: false,
            dirty: false,
            saveTimer: null,
            deadline: 0,
            clock: null,
        };

        /* ---------- Server ---------- */

        async function post(action, data) {
            const res = await fetch(api[action], {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                credentials: 'same-origin',
                body: JSON.stringify(data),
            });

            return res.json();
        }

        // Survives page unload (tab closed, reload)
        function beacon(action, data) {
            const body = JSON.stringify(data);

            if (navigator.sendBeacon && navigator.sendBeacon(api[action], new Blob([body], {type: 'application/json'}))) {
                return;
            }

            fetch(api[action], {method: 'POST', headers: {'Content-Type': 'application/json'}, body, keepalive: true, credentials: 'same-origin'}).catch(() => {});
        }

        function handle(res) {
            switch (res.state) {
                case 'running':
                    return showTest(res);
                case 'resume_required':
                    st.student = res.student;
                    st.test = res.test;
                    return showResume(tx('interrupted'));
                case 'locked':
                    return showResume(tx('left'));
                case 'finished':
                    return showFinished(res);
                default:
                    return null;
            }
        }

        /* ---------- Screens ---------- */

        function message(cls, text) {
            const m = root.querySelector('.st-msg');

            if (m) {
                m.className = 'st-msg ' + cls;
                m.innerHTML = text;
            }
        }

        function stopClock() {
            clearInterval(st.clock);
            st.clock = null;
        }

        function fmtTime(sec) {
            sec = Math.max(0, Math.ceil(sec));

            return `${Math.floor(sec / 60)}:${String(sec % 60).padStart(2, '0')}`;
        }

        function tick() {
            const el = root.querySelector('.st-clock');
            const left = (st.deadline - Date.now()) / 1000;

            if (el) {
                el.textContent = tx('remaining', fmtTime(left));
                el.className = 'st-clock' + (left <= 60 ? ' bad' : left <= 300 ? ' warn' : '');
            }

            if (left <= 0) {
                stopClock();
                message('warn', tx('timeUpSubmitting'));
                submit();
            }
        }

        function showLogin() {
            stopClock();
            disarm();
            root.innerHTML = `
                <div class="st-card st-login">
                    ${langPicker()}
                    <h2>${tx('startTitle')}</h2>
                    <p>${tx('enterCode')}</p>
                    <form class="st-row" novalidate>
                        <label class="st-sr" for="st-code">${tx('code')}</label>
                        <input id="st-code" class="st-code" inputmode="numeric" autocomplete="off" maxlength="5" pattern="[0-9]{5}" placeholder="12345">
                        <button type="submit" class="st-btn">${tx('start')}</button>
                    </form>
                    <div class="st-msg" aria-live="polite"></div>
                </div>`;
            root.lang = lang;
            bindPicker(root, showLogin);
            const input = root.querySelector('#st-code');
            // Digits only, at most five
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, 5);
            });
            input.focus();
            root.querySelector('form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const code = input.value.replace(/\D/g, '');

                if (5 !== code.length) {
                    message('warn', tx('codeFive'));

                    return;
                }

                st.code = code;
                message('', tx('wait'));

                try {
                    const res = await post('start', {code, lang});

                    if ('error' === res.state) {
                        message('bad', esc(errorText(res, 'noConnection')));

                        return;
                    }

                    handle(res);
                } catch (err) {
                    message('bad', tx('noConnection'));
                }
            });
        }

        function showResume(reason) {
            stopClock();
            disarm();
            root.innerHTML = `
                <div class="st-card st-locked">
                    <h2>${tx('lockedTitle')}</h2>
                    <p><strong>${esc(st.student)}</strong>${st.test ? ' · ' + esc(st.test) : ''}</p>
                    <p>${esc(reason)} ${tx('lockedText')}</p>
                    <form class="st-row" novalidate>
                        <label class="st-sr" for="st-resume">${tx('resumeCode')}</label>
                        <input id="st-resume" type="text" class="st-code st-resume" style="-webkit-text-security: disc; text-security: disc;" autocomplete="off" data-lpignore="true" data-1p-ignore autocapitalize="characters" spellcheck="false" maxlength="6" placeholder="••••••">
                        <button type="submit" class="st-btn">${tx('continue')}</button>
                    </form>
                    <div class="st-msg" aria-live="polite"></div>
                    <p><button type="button" class="st-link" data-act="other">${tx('otherCode')}</button></p>
                </div>`;
            root.querySelector('[data-act="other"]').addEventListener('click', showLogin);
            // Letters and digits only, at most six, in capitals
            const resumeInput = root.querySelector('#st-resume');
            resumeInput.addEventListener('input', () => {
                resumeInput.value = resumeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
            });
            resumeInput.focus();
            root.querySelector('form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const resumeInput = root.querySelector('#st-resume');
                const resumeCode = resumeInput.value.trim();
                resumeInput.value = '';

                if (!resumeCode) {
                    message('warn', tx('enterResume'));

                    return;
                }

                try {
                    const res = await post('resume', {code: st.code, resumeCode, lang});

                    if ('error' === res.state) {
                        message('bad', esc(errorText(res, 'noConnection')));

                        return;
                    }

                    handle(res);
                } catch (err) {
                    message('bad', tx('noConnection'));
                }
            });
        }

        function showFinished(res) {
            stopClock();
            disarm();
            st.token = '';
            root.innerHTML = `
                <div class="st-card st-finished">
                    <h2>${res.already ? tx('alreadyDone') : tx('handedIn')}</h2>
                    ${res.timeUp ? `<p>${tx('timeUpAuto')}</p>` : ''}
                    <p><strong>${esc(res.student)}</strong> · ${esc(res.test)}</p>
                    <p class="st-score">${tx('score', res.points, res.max)}</p>
                    <p>${tx('gradeFromTeacher')}</p>
                </div>`;
        }

        function showTest(res) {
            st.token = res.token;
            st.student = res.student;
            st.test = res.test;
            st.sections = res.sections;
            st.lockOnLeave = !!res.lockOnLeave;
            st.answers = res.answers && 'object' === typeof res.answers ? res.answers : {};
            const timed = null !== res.remaining && undefined !== res.remaining;

            let total = 0;
            const sections = st.sections.map((sec, si) => {
                const pts = sec.tasks.reduce((s, t) => s + t.points, 0);
                total += pts;
                const one = sec.tasks.some((t) => ['table', 'points', 'angles'].includes(t.a.type));

                return `
                    <section class="st-section">
                        <h3><span class="st-letter">${String.fromCharCode(65 + si)}</span>${esc(sec.title)}<span class="st-pts">${tx('pts', pts)}</span></h3>
                        <p class="st-instr">${esc(sec.instruction)}</p>
                        <ol class="st-tasks${one ? ' st-one' : ''}">${sec.tasks.map((t, ti) => taskHtml(t, ti)).join('')}</ol>
                    </section>`;
            }).join('');

            root.innerHTML = `
                <div class="st-test">
                    <div class="st-head">
                        <div><h2>${esc(st.test)}</h2><div>${esc(st.student)} · ${tx('points', total)}${timed ? ` · ${tx('minutes', res.timeLimit)}` : ''}</div></div>
                        <div class="st-status">${timed ? '<div class="st-clock" role="timer"></div>' : ''}<div class="st-saved" aria-live="polite">${tx('saved')}</div></div>
                    </div>
                    ${st.lockOnLeave ? `<p class="st-warnbox">${tx('stayHere')}</p>` : ''}
                    ${sections}
                    <div class="st-submit">
                        <button type="button" class="st-btn" data-act="submit">${tx('handIn')}</button>
                        <div class="st-confirm" hidden></div>
                    </div>
                    <div class="st-msg" aria-live="polite"></div>
                </div>`;

            fillAnswers();
            mountBoards(root);
            bindInputs();
            stopClock();

            if (timed) {
                st.deadline = Date.now() + res.remaining * 1000;
                tick();
                st.clock = setInterval(tick, 1000);
            }

            root.querySelector('[data-act="submit"]').addEventListener('click', askSubmit);
            const first = root.querySelector('.st-test input:not([type="hidden"])');

            if (first) {
                first.focus({preventScroll: true});
            }

            // Arm the lock a moment later, so focus changes during rendering do not count
            setTimeout(() => {
                st.armed = true;
            }, 1500);
        }

        function fillAnswers() {
            root.querySelectorAll('.st-test input[data-k]').forEach((inp) => {
                const v = st.answers[inp.dataset.k];

                if (undefined === v || null === v) {
                    return;
                }

                if (inp.dataset.pts) {
                    inp.value = JSON.stringify(v);
                } else if (inp.dataset.part) {
                    inp.value = v[inp.dataset.part] ?? '';
                } else if (undefined !== inp.dataset.col) {
                    inp.value = v[inp.dataset.col] ?? '';
                } else {
                    inp.value = 'object' === typeof v ? '' : v;
                }
            });
        }

        function bindInputs() {
            root.querySelectorAll('.st-test input[data-k]').forEach((inp) => {
                inp.addEventListener('input', () => {
                    const k = inp.dataset.k;

                    if (inp.dataset.part) {
                        st.answers[k] = Object.assign({}, st.answers[k] || {}, {[inp.dataset.part]: inp.value});
                    } else if (undefined !== inp.dataset.col) {
                        st.answers[k] = Object.assign({}, st.answers[k] || {}, {[inp.dataset.col]: inp.value});
                    } else {
                        st.answers[k] = inputValue(inp);
                    }

                    scheduleSave();
                });

                inp.addEventListener('keydown', (e) => {
                    if ('Enter' === e.key) {
                        e.preventDefault();
                        focusNext(inp);
                    }

                    // "/" in the numerator jumps to the denominator
                    if ('/' === e.key && 'n' === inp.dataset.part) {
                        e.preventDefault();
                        const d = inp.parentNode.querySelector('[data-part="d"]');

                        if (d) {
                            d.focus();
                        }
                    }
                });
            });
        }

        function focusNext(inp) {
            const all = [...root.querySelectorAll('.st-test input[data-k]:not([type="hidden"])')];
            const next = all[all.indexOf(inp) + 1];

            if (next) {
                next.focus();
            }
        }

        function setSaved(text, cls) {
            const s = root.querySelector('.st-saved');

            if (s) {
                s.textContent = text;
                s.className = 'st-saved ' + (cls || '');
            }
        }

        function scheduleSave() {
            st.dirty = true;
            setSaved(tx('saving'), 'busy');
            clearTimeout(st.saveTimer);
            st.saveTimer = setTimeout(save, 1200);
        }

        async function save() {
            if (!st.token) {
                return;
            }

            st.dirty = false;

            try {
                const res = await post('save', {code: st.code, token: st.token, answers: st.answers});

                if ('saved' === res.state) {
                    setSaved(tx('saved'), '');

                    // The server clock is the reference
                    if (null !== res.remaining && undefined !== res.remaining && st.clock) {
                        st.deadline = Date.now() + res.remaining * 1000;
                    }
                } else {
                    handle(res);
                }
            } catch (err) {
                st.dirty = true;
                setSaved(tx('retry'), 'bad');
                clearTimeout(st.saveTimer);
                st.saveTimer = setTimeout(save, 4000);
            }
        }

        function askSubmit() {
            const box = root.querySelector('.st-confirm');
            // A task counts as answered as soon as one of its fields is filled (fractions: whole number alone is fine).
            // Table cells are counted one by one.
            const groups = {};

            root.querySelectorAll('.st-test input[data-k]').forEach((i) => {
                const key = undefined !== i.dataset.col ? i.dataset.k + '/' + i.dataset.col : i.dataset.k;
                groups[key] = groups[key] || isFilled(i);
            });
            const empty = Object.values(groups).filter((filled) => !filled).length;
            box.hidden = false;
            box.innerHTML = `
                <p>${tx('confirm')}${empty ? ' ' + (1 === empty ? tx('oneOpen') : tx('nOpen', empty)) : ''}</p>
                <div class="st-row"><button type="button" class="st-btn" data-act="yes">${tx('yesHandIn')}</button><button type="button" class="st-btn st-btn2" data-act="no">${tx('keepGoing')}</button></div>`;
            box.querySelector('[data-act="no"]').addEventListener('click', () => {
                box.hidden = true;
            });
            box.querySelector('[data-act="yes"]').addEventListener('click', submit);
            box.querySelector('[data-act="yes"]').focus();
        }

        async function submit() {
            stopClock();
            disarm();
            clearTimeout(st.saveTimer);
            message('', tx('handingIn'));

            try {
                const res = await post('submit', {code: st.code, token: st.token, answers: st.answers});
                handle(res);
            } catch (err) {
                st.armed = true;
                message('bad', tx('noConnKeep'));
            }
        }

        /* ---------- Leaving the page ---------- */

        function disarm() {
            st.armed = false;
        }

        function leave(reason) {
            if (!st.armed || !st.token) {
                return;
            }

            clearTimeout(st.saveTimer);
            beacon('lock', {code: st.code, token: st.token, reason, answers: st.answers});

            if (st.lockOnLeave) {
                st.armed = false;
                st.token = '';
                showResume(tx('left'));
            }
        }

        // Lock only when the page itself is left: other tab, minimized window, reload,
        // navigation or closing. A click into the address bar or another program
        // (window blur) does not count.
        document.addEventListener('visibilitychange', () => {
            if ('hidden' === document.visibilityState) {
                leave('hidden');
            }
        });
        window.addEventListener('pagehide', () => leave('pagehide'));

        showLogin();
    }

    function sectionsHtml(sections) {
        return sections.map((sec, si) => {
            const pts = sec.tasks.reduce((s, t) => s + t.points, 0);
            const one = sec.tasks.some((t) => ['table', 'points', 'angles'].includes(t.a.type));

            return `
                <section class="st-section">
                    <h3><span class="st-letter">${String.fromCharCode(65 + si)}</span>${esc(sec.title)}<span class="st-pts" data-sec="${si}">${tx('pts', pts)}</span></h3>
                    <p class="st-instr">${esc(sec.instruction)}</p>
                    <ol class="st-tasks${one ? ' st-one' : ''}">${sec.tasks.map((t, ti) => taskHtml(t, ti)).join('')}</ol>
                </section>`;
        }).join('');
    }

    // Read all answers from the inputs
    function collectAnswers(root) {
        const answers = {};

        root.querySelectorAll('input[data-k]').forEach((inp) => {
            const k = inp.dataset.k;

            if (inp.dataset.part) {
                answers[k] = Object.assign({}, answers[k] || {}, {[inp.dataset.part]: inp.value});
            } else if (undefined !== inp.dataset.col) {
                answers[k] = Object.assign({}, answers[k] || {}, {[inp.dataset.col]: inp.value});
            } else {
                answers[k] = inputValue(inp);
            }
        });

        return answers;
    }

    /* ---------- Practice mode: no code, nothing stored, explained solutions ---------- */

    function initPractice(root) {
        let cfg;

        try {
            cfg = JSON.parse(root.dataset.schoolTestPractice);
        } catch (e) {
            return;
        }

        let clock = null;
        let deadline = 0;
        let busy = false;

        const post = async (action, data) => {
            const res = await fetch(cfg.urls[action], {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                credentials: 'same-origin',
                body: JSON.stringify(Object.assign({element: cfg.element}, data)),
            });

            return res.json();
        };

        const msg = (cls, text) => {
            const m = root.querySelector('.st-msg');

            if (m) {
                m.className = 'st-msg ' + cls;
                m.innerHTML = text;
            }
        };

        const stop = () => {
            clearInterval(clock);
            clock = null;
        };

        // Test types that can be practised, with their topics (labels in all languages, from the server)
        const types = Array.isArray(cfg.types) ? cfg.types : [];
        const DIFFS = ['easy', 'medium', 'hard'];
        const L = (labels) => (labels && (labels[lang] || labels.de)) || '';
        const typeOf = (alias) => types.find((x) => x.alias === alias);

        // Current choice, kept while the page is open. With one type it is preselected.
        const choice = {type: 1 === types.length ? types[0].alias : '', topic: '', difficulty: 'medium'};

        const topicsOf = (type) => Object.entries((type && type.topics) || {});

        const label = () => {
            const type = typeOf(choice.type);
            const topic = type && type.topics ? type.topics[choice.topic] : null;

            return [topic ? L(topic) : L(type && type.labels), type && false === type.difficulty ? '' : tx(choice.difficulty)].filter(Boolean).join(' · ');
        };

        const optionButtons = (items, attr, current) => items.map(([value, text]) => `<button type="button" class="st-opt${value === current ? ' on' : ''}" data-${attr}="${esc(value)}" aria-pressed="${value === current}">${esc(text)}</button>`).join('');

        function showStart() {
            stop();
            const type = typeOf(choice.type);
            const topics = topicsOf(type);

            if (type && topics.length && !type.topics[choice.topic]) {
                choice.topic = topics[0][0];
            }

            root.innerHTML = `
                <div class="st-card st-practice">
                    ${langPicker()}
                    <h2>${tx('practiceHead')}</h2>
                    <p>${tx('practiceIntro2')}</p>
                    <div class="st-choose">
                        <div class="st-chooselabel">${tx('chooseType')}</div>
                        <div class="st-options" role="group" aria-label="${tx('chooseType')}">${optionButtons(types.map((x) => [x.alias, L(x.labels)]), 'type', choice.type)}</div>
                        ${type && topics.length ? `
                        <div class="st-chooselabel">${tx('chooseArea')}</div>
                        <div class="st-options" role="group" aria-label="${tx('chooseArea')}">${optionButtons(topics.map(([k, v]) => [k, L(v)]), 'topic', choice.topic)}</div>` : ''}
                        ${type && false !== type.difficulty ? `
                        <div class="st-chooselabel">${tx('chooseLevel')}</div>
                        <div class="st-options" role="group" aria-label="${tx('chooseLevel')}">${optionButtons(DIFFS.map((d) => [d, tx(d)]), 'diff', choice.difficulty)}</div>` : ''}
                    </div>
                    ${type ? `<div class="st-row"><button type="button" class="st-btn" data-act="new">${tx('practiceStart')}</button></div>` : ''}
                    <div class="st-msg" aria-live="polite"></div>
                </div>`;
            root.lang = lang;
            bindPicker(root, showStart);
            root.querySelectorAll('[data-type]').forEach((b) => b.addEventListener('click', () => {
                choice.type = b.dataset.type;
                choice.topic = '';
                showStart();
            }));
            root.querySelectorAll('[data-topic]').forEach((b) => b.addEventListener('click', () => {
                choice.topic = b.dataset.topic;
                showStart();
            }));
            root.querySelectorAll('[data-diff]').forEach((b) => b.addEventListener('click', () => {
                choice.difficulty = b.dataset.diff;
                showStart();
            }));
            root.querySelector('[data-act="new"]')?.addEventListener('click', start);
        }

        async function start() {
            msg('', tx('generating'));

            try {
                const res = await post('new', {lang, type: choice.type, topic: choice.topic, difficulty: choice.difficulty});

                if ('running' !== res.state) {
                    msg('bad', esc(errorText(res, 'practiceError')));

                    return;
                }

                showTasks(res);
            } catch (e) {
                msg('bad', tx('noConnection'));
            }
        }

        function showTasks(res) {
            const total = res.sections.reduce((s, sec) => s + sec.tasks.reduce((a, t) => a + t.points, 0), 0);
            const timed = null !== res.remaining && undefined !== res.remaining;
            root.innerHTML = `
                <div class="st-test st-practice">
                    <div class="st-head">
                        <div><h2>${tx('practiceTitle', esc(label()))}</h2><div>${tx('points', total)}${timed ? ` · ${tx('minutes', res.timeLimit)}` : ''}</div></div>
                        <div class="st-status">${timed ? '<div class="st-clock" role="timer"></div>' : ''}<div class="st-result"></div></div>
                    </div>
                    ${sectionsHtml(res.sections)}
                    <div class="st-submit">
                        <div class="st-row">
                            <button type="button" class="st-btn" data-act="check">${tx('check')}</button>
                            <button type="button" class="st-btn st-btn2" data-act="again" hidden>${tx('again')}</button>
                            <button type="button" class="st-link" data-act="choose">${tx('otherChoice')}</button>
                        </div>
                    </div>
                    <div class="st-msg" aria-live="polite"></div>
                </div>`;
            mountBoards(root);
            root.querySelectorAll('input[data-k]').forEach((inp) => inp.addEventListener('keydown', (e) => {
                if ('Enter' === e.key) {
                    e.preventDefault();
                    const all = [...root.querySelectorAll('input[data-k]:not([type="hidden"])')];
                    const next = all[all.indexOf(inp) + 1];

                    if (next) {
                        next.focus();
                    }
                }

                if ('/' === e.key && 'n' === inp.dataset.part) {
                    e.preventDefault();
                    inp.parentNode.querySelector('[data-part="d"]')?.focus();
                }
            }));
            root.querySelector('[data-act="check"]').addEventListener('click', check);
            root.querySelector('[data-act="again"]').addEventListener('click', start);
            root.querySelector('[data-act="choose"]').addEventListener('click', showStart);
            root.querySelector('input[data-k]:not([type="hidden"])')?.focus({preventScroll: true});
            stop();

            if (timed) {
                deadline = Date.now() + res.remaining * 1000;
                const tick = () => {
                    const left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                    const el = root.querySelector('.st-clock');

                    if (el) {
                        el.textContent = tx('remaining', `${Math.floor(left / 60)}:${String(left % 60).padStart(2, '0')}`);
                        el.className = 'st-clock' + (left <= 60 ? ' bad' : left <= 300 ? ' warn' : '');
                    }

                    if (left <= 0) {
                        stop();
                        msg('warn', tx('timeUp'));
                        check();
                    }
                };
                tick();
                clock = setInterval(tick, 1000);
            }
        }

        async function check() {
            if (busy) {
                return;
            }

            busy = true;
            stop();

            try {
                const res = await post('check', {answers: collectAnswers(root), lang});

                if ('checked' !== res.state) {
                    msg('bad', esc(errorText(res, 'checkError')));

                    return;
                }

                showResult(res);
            } catch (e) {
                msg('bad', tx('noConnection'));
            } finally {
                busy = false;
            }
        }

        function showResult(res) {
            root.querySelectorAll('input[data-k]').forEach((inp) => {
                inp.disabled = true;
            });

            Object.entries(res.tasks).forEach(([k, r]) => {
                const li = root.querySelector(`[data-task="${k}"]`);

                if (!li) {
                    return;
                }

                const ok = r.points === r.max;
                li.classList.add(ok ? 'st-ok' : 'st-bad');

                // Table: mark each cell, show the solution under wrong ones
                Object.entries(li.classList.contains('st-ang') ? {} : r.cells || {}).forEach(([col, show]) => {
                    const inp = li.querySelector(`input[data-col="${col}"]`);

                    if (!inp) {
                        return;
                    }

                    const td = inp.closest('td');
                    td.classList.add(r.correct[col] ? 'st-cell-ok' : 'st-cell-bad');

                    if (!r.correct[col]) {
                        td.insertAdjacentHTML('beforeend', `<div class="st-sol">${show}</div>`);
                    }
                });

                let head;

                if (li.classList.contains('st-ang')) {
                    // Sketch with the solution, every field marked, solution next to the wrong ones
                    const fig = li.querySelector('.st-angfig');

                    if (fig && r.svg) {
                        fig.innerHTML = r.svg;
                    }

                    li.querySelectorAll('input[data-col]').forEach((inp) => {
                        const col = inp.dataset.col;
                        const label = inp.closest('label');
                        label.classList.add(r.correct[col] ? 'st-cell-ok' : 'st-cell-bad');

                        if (!r.correct[col] && r.cells && r.cells[col]) {
                            label.insertAdjacentHTML('beforeend', `<span class="st-sol">${r.cells[col]}</span>`);
                        }
                    });
                    head = ok ? tx('right') : tx('fieldsRight', r.points, r.max);
                } else if (li.classList.contains('st-geo')) {
                    li.querySelector('.st-board')?.stBoard?.showResult(r.img || [], r.correct);
                    head = ok ? tx('right') : `${tx('pointsRight', r.points, r.max)}${r.wrongDir ? ' ' + tx('wrongDir') : ''} ${tx('solutionDashed')}`;
                } else if (li.classList.contains('st-wide')) {
                    head = tx('fieldsRight', r.points, r.max);
                } else {
                    head = ok ? tx('right') : `${tx('solution')}: <b>${r.solution}</b>`;
                }
                li.querySelector('.st-body').insertAdjacentHTML('beforeend', `<div class="st-feedback"><span class="st-fhead">${head}</span> <span class="st-way">${r.way}</span></div>`);
            });

            const pct = res.max ? Math.round(res.points / res.max * 100) : 0;
            root.querySelector('.st-result').innerHTML = `<span class="st-score-small">${tx('scoreSmall', res.points, res.max)}</span>`;
            root.querySelector('[data-act="check"]').hidden = true;
            root.querySelector('[data-act="again"]').hidden = false;
            msg(pct >= 80 ? 'ok' : pct >= 50 ? 'warn' : 'bad', tx('practiceResult', res.points, res.max, pct));
            root.querySelector('.st-msg').scrollIntoView({behavior: 'smooth', block: 'center'});
        }

        showStart();
    }
})();
