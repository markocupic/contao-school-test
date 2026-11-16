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

namespace Markocupic\ContaoSchoolTest\Grading;

/**
 * Swiss grade (1–6), linear: grade = 1 + 5 · points / pointsForSix, rounded to the given step.
 */
final class GradeCalculator
{
    public const STEPS = ['0.1', '0.25', '0.5'];

    /**
     * @param int   $pointsForSix points needed for grade 6; 0 or less means the maximum
     * @param float $step         rounding step, e.g. 0.5
     */
    public function calculate(int $points, int $maxPoints, int $pointsForSix = 0, float $step = 0.5): float
    {
        $full = $pointsForSix > 0 ? $pointsForSix : $maxPoints;

        if ($full <= 0) {
            return 1.0;
        }

        $grade = 1 + 5 * min($points, $full) / $full;
        $step = $step > 0 ? $step : 0.1;
        $grade = round(round($grade / $step) * $step, 2);

        return max(1.0, min(6.0, $grade));
    }

    public static function format(float|string|null $grade): string
    {
        if (null === $grade || '' === $grade) {
            return '–';
        }

        return rtrim(rtrim(number_format((float) $grade, 2, '.', ''), '0'), '.');
    }
}
