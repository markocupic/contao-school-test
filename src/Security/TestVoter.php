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
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * tl_st_test: a non-admin reads, edits, copies and deletes only own tests and cannot hand them
 * to another user. Creating is allowed (the owner is set in TestDcaListener::onCreate).
 */
class TestVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly TestOwnership $ownership)
    {
    }

    protected function getTable(): string
    {
        return 'tl_st_test';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        $user = $token->getUser();

        if ($action instanceof CreateAction) {
            $author = (int) (($action->getNew() ?? [])['author'] ?? 0);

            // New record (owner still empty) or a copy of an own test
            return $user instanceof BackendUser && (0 === $author || TestOwnership::mayAccess($user, $author));
        }

        if (!TestOwnership::mayAccess($user, $this->ownership->authorOf($action->getCurrent() ?? []))) {
            return false;
        }

        // Only admins may change the owner
        if ($action instanceof UpdateAction && \is_array($action->getNew()) && \array_key_exists('author', $action->getNew())) {
            return TestOwnership::mayAccess($user, (int) $action->getNew()['author']);
        }

        return true;
    }
}
