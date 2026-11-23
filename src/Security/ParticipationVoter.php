<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Security;

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * tl_st_participation: only the owner of the test (or an admin) sees the participations.
 */
class ParticipationVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly TestOwnership $ownership)
    {
    }

    protected function getTable(): string
    {
        return 'tl_st_participation';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        $record = ($action instanceof CreateAction ? $action->getNew() : $action->getCurrent()) ?? [];

        return TestOwnership::mayAccess($token->getUser(), $this->ownership->authorOfId((int) ($record['pid'] ?? 0)));
    }
}
