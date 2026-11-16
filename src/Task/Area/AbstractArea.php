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

namespace Markocupic\ContaoSchoolTest\Task\Area;

use Markocupic\ContaoSchoolTest\Task\Util;

/**
 * One topic ("Bereich") with six levels. Each level is a list of generator
 * closures; every closure returns one task array.
 */
abstract class AbstractArea
{
    public const LEVELS = 6;

    abstract public function getAlias(): string;

    abstract public function getTitle(): string;

    /**
     * Short instruction printed above the section.
     */
    abstract public function getInstruction(): string;

    /**
     * @return list<\Closure(): array>
     */
    abstract protected function generators(int $level): array;

    public function generate(int $level): array
    {
        return (Util::pick($this->generators($this->checkLevel($level))))();
    }

    /**
     * Generate $count different tasks, cycling through all task types of the level.
     *
     * @return list<array>
     */
    public function generateMany(int $level, int $count): array
    {
        $gens = $this->generators($this->checkLevel($level));
        $out = [];
        $seen = [];
        $pool = [];
        $fails = 0;

        while (\count($out) < $count) {
            if (!$pool) {
                $pool = Util::shuffle($gens);
            }

            $gen = array_pop($pool);

            for ($t = 0; $t < 30; ++$t) {
                $task = $gen();
                $key = $task['key'] ?? $task['q'];

                // Accept a duplicate rather than looping forever on tiny task pools
                if (!isset($seen[$key]) || ++$fails > 200) {
                    $seen[$key] = true;
                    $task['area'] = $this->getAlias();
                    $task['level'] = $level;
                    $out[] = $task;
                    break;
                }
            }
        }

        return $out;
    }

    private function checkLevel(int $level): int
    {
        if ($level < 1 || $level > self::LEVELS) {
            throw new \InvalidArgumentException(\sprintf('Level must be between 1 and %d, %d given.', self::LEVELS, $level));
        }

        return $level;
    }
}
