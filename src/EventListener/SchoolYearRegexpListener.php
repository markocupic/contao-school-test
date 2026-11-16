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

namespace Markocupic\ContaoSchoolTest\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Widget;

/**
 * rgxp "st_school_year": school year in the form 2026/27 (second year = first year + 1).
 */
#[AsHook('addCustomRegexp')]
class SchoolYearRegexpListener
{
    public const RGXP = 'st_school_year';

    public function __invoke(string $regexp, mixed $input, Widget $widget): bool
    {
        if (self::RGXP !== $regexp) {
            return false;
        }

        if ('' !== (string) $input && !self::isValid((string) $input)) {
            $widget->addError('Bitte das Schuljahr in der Form 2026/27 eingeben (zweites Jahr = erstes Jahr + 1).');
        }

        return true;
    }

    public static function isValid(string $value): bool
    {
        if (!preg_match('#^(\d{4})/(\d{2})$#', $value, $m)) {
            return false;
        }

        return ((int) $m[1] + 1) % 100 === (int) $m[2];
    }
}
