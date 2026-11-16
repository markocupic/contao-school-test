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

namespace Markocupic\ContaoSchoolTest\Participation;

final class ParticipationStatus
{
    /**
     * Code handed out, test not started yet.
     */
    public const OPEN = 'open';

    /**
     * Test in progress on exactly one device (the one holding the token).
     */
    public const RUNNING = 'running';

    /**
     * Student left the test (tab change, reload, crash). Resume needs the teacher's resume code.
     */
    public const LOCKED = 'locked';

    /**
     * Handed in and graded. Cannot be started again.
     */
    public const FINISHED = 'finished';

    public const ALL = [self::OPEN, self::RUNNING, self::LOCKED, self::FINISHED];
}
