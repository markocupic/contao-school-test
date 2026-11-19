<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Task\Geometry;

/**
 * Translations of the symmetry cards: German => [fr, it, en]. {0} is a number.
 */
final class SymmetryTexts
{
    public const TITLE = 'Symmetrie: Achsen und Zentrum einzeichnen';

    public const INSTRUCTION = 'Ist das Bild achsensymmetrisch, punktsymmetrisch, beides oder keines von beidem? Zeichne jede Symmetrieachse ein und setze das Symmetriezentrum Z. Hat das Bild keine Symmetrie, klicke auf «Keine Symmetrie». Die Achsen geben einen Punkt, das Zentrum einen Punkt.';

    public const TEXTS = [
        self::TITLE => ['Symétrie&nbsp;: tracer les axes et le centre', 'Simmetria: disegna gli assi e il centro', 'Symmetry: draw the axes and the centre'],
        self::INSTRUCTION => [
            'L’image a-t-elle une symétrie axiale, une symétrie centrale, les deux ou aucune&nbsp;? Trace chaque axe de symétrie et place le centre de symétrie Z. Si l’image n’a aucune symétrie, clique sur «&nbsp;Aucune symétrie&nbsp;». Les axes donnent un point, le centre un point.',
            'L’immagine ha una simmetria assiale, una simmetria centrale, entrambe o nessuna delle due? Disegna ogni asse di simmetria e posiziona il centro di simmetria Z. Se l’immagine non ha simmetria, clicca su «Nessuna simmetria». Gli assi danno un punto, il centro un punto.',
            'Is the picture symmetric about a line, about a point, both or neither? Draw every axis of symmetry and set the centre of symmetry Z. If the picture has no symmetry, click “No symmetry”. The axes give one point, the centre one point.',
        ],
    ];

    public const PHRASES = [
        'Zeichne alle Symmetrieachsen und das Symmetriezentrum Z ein.' => ['Trace tous les axes de symétrie et le centre de symétrie Z.', 'Disegna tutti gli assi di simmetria e il centro di simmetria Z.', 'Draw all axes of symmetry and the centre of symmetry Z.'],
        'Das Bild ist <b>nicht achsensymmetrisch</b>: Es gibt keine Gerade, an der gespiegelt es mit sich selbst zusammenfällt.' => ['L’image <b>n’a pas de symétrie axiale</b>&nbsp;: aucune droite ne la ramène sur elle-même par symétrie.', 'L’immagine <b>non ha simmetria assiale</b>: non esiste una retta rispetto alla quale, riflessa, coincida con sé stessa.', 'The picture is <b>not symmetric about a line</b>: there is no line in which its reflection coincides with itself.'],
        'Das Bild ist <b>achsensymmetrisch</b> mit einer Symmetrieachse (grün gestrichelt): An ihr gespiegelt fällt es mit sich selbst zusammen.' => ['L’image a une <b>symétrie axiale</b> avec un axe (en pointillé vert)&nbsp;: par symétrie par rapport à cet axe, elle se superpose à elle-même.', 'L’immagine ha una <b>simmetria assiale</b> con un asse (tratteggiato in verde): riflessa rispetto a esso coincide con sé stessa.', 'The picture is <b>symmetric about a line</b> with one axis (dashed in green): reflected in it, it coincides with itself.'],
        'Das Bild ist <b>achsensymmetrisch</b> mit {0} Symmetrieachsen (grün gestrichelt): An jeder von ihnen gespiegelt fällt es mit sich selbst zusammen.' => ['L’image a une <b>symétrie axiale</b> avec {0} axes (en pointillé vert)&nbsp;: par symétrie par rapport à chacun d’eux, elle se superpose à elle-même.', 'L’immagine ha una <b>simmetria assiale</b> con {0} assi (tratteggiati in verde): riflessa rispetto a ciascuno di essi coincide con sé stessa.', 'The picture is <b>symmetric about a line</b> with {0} axes (dashed in green): reflected in each of them, it coincides with itself.'],
        'Das Bild ist <b>punktsymmetrisch</b>: Nach einer halben Drehung (180°) um das Zentrum Z fällt es mit sich selbst zusammen.' => ['L’image a une <b>symétrie centrale</b>&nbsp;: après un demi-tour (180°) autour du centre Z, elle se superpose à elle-même.', 'L’immagine ha una <b>simmetria centrale</b>: dopo mezzo giro (180°) attorno al centro Z coincide con sé stessa.', 'The picture is <b>symmetric about a point</b>: after a half turn (180°) about the centre Z it coincides with itself.'],
        'Das Bild ist <b>nicht punktsymmetrisch</b>: Nach einer halben Drehung um einen Punkt fällt es nicht mit sich selbst zusammen.' => ['L’image <b>n’a pas de symétrie centrale</b>&nbsp;: après un demi-tour autour d’un point, elle ne se superpose pas à elle-même.', 'L’immagine <b>non ha simmetria centrale</b>: dopo mezzo giro attorno a un punto non coincide con sé stessa.', 'The picture is <b>not symmetric about a point</b>: after a half turn about any point it does not coincide with itself.'],
        'Tipp: Eine Symmetrieachse ist die <b>Mittelsenkrechte</b> zweier Punkte, die einander entsprechen.' => ['Astuce&nbsp;: un axe de symétrie est la <b>médiatrice</b> de deux points qui se correspondent.', 'Suggerimento: un asse di simmetria è l’<b>asse del segmento</b> che unisce due punti corrispondenti.', 'Tip: an axis of symmetry is the <b>perpendicular bisector</b> of two corresponding points.'],
        'Tipp: Das Zentrum Z ist die <b>Mitte</b> zwischen zwei Punkten, die einander entsprechen.' => ['Astuce&nbsp;: le centre Z est le <b>milieu</b> de deux points qui se correspondent.', 'Suggerimento: il centro Z è il <b>punto medio</b> tra due punti corrispondenti.', 'Tip: the centre Z is the <b>midpoint</b> of two corresponding points.'],
        'Hier ist «Keine Symmetrie» richtig.' => ['Ici, «&nbsp;Aucune symétrie&nbsp;» est juste.', 'Qui «Nessuna simmetria» è giusto.', 'Here “No symmetry” is right.'],
    ];
}
