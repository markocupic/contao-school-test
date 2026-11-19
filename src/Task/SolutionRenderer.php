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

use Contao\StringUtil;

/**
 * HTML of the correct solution of a task (evaluation sheet and practice mode).
 */
final class SolutionRenderer
{
    public function suffix(array $a): string
    {
        if (empty($a['suffix'])) {
            return '';
        }

        return '%' === $a['suffix'] ? '&nbsp;%' : '&nbsp;'.StringUtil::specialchars($a['suffix']);
    }

    /**
     * Solution of a number or fraction task. Tables: see tableSolutions().
     */
    public function solution(array $a): string
    {
        return match ($a['type']) {
            'num' => ($a['show'] ?? Util::fmt($a['v'])).$this->suffix($a),
            'frac' => \in_array($a['mode'], ['exact', 'improper'], true) ? Util::f($a['n'], $a['d']) : Util::m($a['n'], $a['d']),
            'rat' => '<i>x</i> = '.(new Equation\Fraction((int) $a['n'], (int) $a['d']))->html(),
            'angles' => implode(', ', array_map(static fn (array $f, int|float $v): string => $f['label'].' = '.Util::fmt($v).$f['unit'], $a['fields'], $a['v'])),
            'terms' => implode(', ', array_map(static fn (array $f, array $v): string => trim(($f['prompt'] ?? '').' '.$v['show']), $a['fields'], $a['v'])),
            'triangle' => implode(', ', array_map(static fn (array $g): string => (new Triangle\TriangleGenerator())->measure($g['k'], (float) $g['v']), $a['given'])),
            'points' => implode(', ', array_map(static fn (array $p, int $i): string => \chr(65 + $i).'′ ('.Util::fmt($p[0]).'|'.Util::fmt($p[1]).')', $a['img'], array_keys($a['img']))),
            'symmetry' => match (true) {
                $a['axes'] && null !== $a['center'] => 'achsen- und punktsymmetrisch, '.\count($a['axes']).' '.(1 === \count($a['axes']) ? 'Achse' : 'Achsen'),
                (bool) $a['axes'] => 'nur achsensymmetrisch, '.\count($a['axes']).' '.(1 === \count($a['axes']) ? 'Achse' : 'Achsen'),
                null !== $a['center'] => 'nur punktsymmetrisch',
                default => 'weder achsen- noch punktsymmetrisch',
            },
            default => '',
        };
    }

    /**
     * Column index => solution HTML of every cell that is asked for.
     *
     * @return array<int, string>
     */
    public function tableSolutions(array $a): array
    {
        $out = [];

        // Terms: the solution of every field
        if ('terms' === $a['type']) {
            return array_map(static fn (array $v): string => $v['show'], $a['v']);
        }

        // Angles: one field per unknown
        if ('angles' === $a['type']) {
            foreach ($a['v'] as $i => $v) {
                $out[$i] = Util::fmt($v).$a['fields'][$i]['unit'];
            }

            return $out;
        }

        foreach ($a['cols'] ?? [] as $i => $c) {
            if (isset($c['ask'])) {
                $out[$i] = $c['show'];
            }
        }

        return $out;
    }
}
