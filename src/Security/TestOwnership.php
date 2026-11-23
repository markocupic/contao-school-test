<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Security;

use Contao\BackendUser;
use Doctrine\DBAL\Connection;

/**
 * Every test belongs to the backend user who created it (tl_st_test.author). Other users do
 * not see, edit or delete it; administrators see all tests and may change the owner.
 * Tests without an owner (author = 0, e.g. created before this feature) are for admins only.
 */
class TestOwnership
{
    /**
     * @var array<int, int>
     */
    private array $authors = [];

    public function __construct(private readonly Connection $connection)
    {
    }

    public static function mayAccess(mixed $user, int $author): bool
    {
        if (!$user instanceof BackendUser) {
            return false;
        }

        if ($user->isAdmin) {
            return true;
        }

        return $author > 0 && $author === (int) $user->id;
    }

    /**
     * Owner of a test record, read from the record or (if the column is missing) from the database.
     */
    public function authorOf(array $test): int
    {
        if (\array_key_exists('author', $test)) {
            return (int) $test['author'];
        }

        return $this->authorOfId((int) ($test['id'] ?? 0));
    }

    public function authorOfId(int $testId): int
    {
        if ($testId <= 0) {
            return 0;
        }

        return $this->authors[$testId] ??= (int) $this->connection->fetchOne('SELECT author FROM tl_st_test WHERE id = ?', [$testId]);
    }
}
