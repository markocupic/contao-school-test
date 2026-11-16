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

namespace Markocupic\ContaoSchoolTest\TestType;

final class TestTypeRegistry
{
    /**
     * @var array<string, TestTypeInterface>
     */
    private array $types = [];

    /**
     * @param iterable<TestTypeInterface> $types
     */
    public function __construct(iterable $types)
    {
        foreach ($types as $type) {
            $this->types[$type->getAlias()] = $type;
        }
    }

    public function get(string $alias): TestTypeInterface
    {
        return $this->types[$alias] ?? throw new \InvalidArgumentException(\sprintf('Unknown test type "%s".', $alias));
    }

    public function has(string $alias): bool
    {
        return isset($this->types[$alias]);
    }

    /**
     * @return array<string, TestTypeInterface>
     */
    public function all(): array
    {
        return $this->types;
    }
}
