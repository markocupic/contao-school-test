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

namespace Markocupic\ContaoSchoolTest;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarkocupicContaoSchoolTest extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
