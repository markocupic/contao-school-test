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

namespace Markocupic\ContaoSchoolTest\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\StringUtil;

class StudentDcaListener
{
    #[AsCallback(table: 'tl_st_student', target: 'list.sorting.child_record')]
    public function childRecord(array $row): string
    {
        return '<div>'.StringUtil::specialchars(trim($row['lastname'].' '.$row['firstname'])).'</div>';
    }
}
