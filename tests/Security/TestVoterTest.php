<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Tests\Security;

use Contao\BackendUser;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Doctrine\DBAL\DriverManager;
use Markocupic\ContaoSchoolTest\Security\ParticipationVoter;
use Markocupic\ContaoSchoolTest\Security\TestOwnership;
use Markocupic\ContaoSchoolTest\Security\TestVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class TestVoterTest extends TestCase
{
    private const OWNER = 5;

    private const OTHER = 6;

    private const ADMIN = 1;

    public function testOnlyOwnerAndAdminsReachATest(): void
    {
        [$tests, $participations] = $this->voters();
        $test = ['id' => 1, 'author' => self::OWNER];

        $this->assertGranted($tests, self::OWNER, new ReadAction('tl_st_test', $test));
        $this->assertGranted($tests, self::ADMIN, new ReadAction('tl_st_test', $test));
        $this->assertDenied($tests, self::OTHER, new ReadAction('tl_st_test', $test));
        $this->assertDenied($tests, self::OTHER, new UpdateAction('tl_st_test', $test, ['title' => 'x']));
        $this->assertDenied($tests, self::OTHER, new DeleteAction('tl_st_test', $test));
        $this->assertGranted($tests, self::OWNER, new UpdateAction('tl_st_test', $test, ['published' => '1']));
        $this->assertGranted($tests, self::OWNER, new DeleteAction('tl_st_test', $test));

        // Record without the author column: read from the database
        $this->assertGranted($tests, self::OWNER, new ReadAction('tl_st_test', ['id' => 1]));
        $this->assertDenied($tests, self::OTHER, new ReadAction('tl_st_test', ['id' => 1]));

        $this->assertGranted($participations, self::OWNER, new ReadAction('tl_st_participation', ['id' => 9, 'pid' => 1]));
        $this->assertGranted($participations, self::ADMIN, new UpdateAction('tl_st_participation', ['id' => 9, 'pid' => 1]));
        $this->assertDenied($participations, self::OTHER, new UpdateAction('tl_st_participation', ['id' => 9, 'pid' => 1]));
    }

    public function testOwnerlessTestsAreForAdminsOnly(): void
    {
        [$tests] = $this->voters();
        $test = ['id' => 2, 'author' => 0];

        $this->assertDenied($tests, self::OWNER, new ReadAction('tl_st_test', $test));
        $this->assertGranted($tests, self::ADMIN, new ReadAction('tl_st_test', $test));
    }

    public function testOnlyAdminsChangeTheOwner(): void
    {
        [$tests] = $this->voters();
        $test = ['id' => 1, 'author' => self::OWNER];

        $this->assertDenied($tests, self::OWNER, new UpdateAction('tl_st_test', $test, ['author' => self::OTHER]));
        $this->assertGranted($tests, self::OWNER, new UpdateAction('tl_st_test', $test, ['author' => self::OWNER]));
        $this->assertGranted($tests, self::ADMIN, new UpdateAction('tl_st_test', $test, ['author' => self::OTHER]));
    }

    public function testCreateAndCopy(): void
    {
        [$tests] = $this->voters();

        $this->assertGranted($tests, self::OTHER, new CreateAction('tl_st_test', ['title' => 'Neu']));
        $this->assertGranted($tests, self::OWNER, new CreateAction('tl_st_test', ['id' => null, 'author' => self::OWNER]));
        $this->assertDenied($tests, self::OTHER, new CreateAction('tl_st_test', ['id' => null, 'author' => self::OWNER]));
    }

    private function voters(): array
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE tl_st_test (id INTEGER PRIMARY KEY, author INT)');
        $connection->insert('tl_st_test', ['id' => 1, 'author' => self::OWNER]);
        $connection->insert('tl_st_test', ['id' => 2, 'author' => 0]);
        $ownership = new TestOwnership($connection);

        return [new TestVoter($ownership), new ParticipationVoter($ownership)];
    }

    private function assertGranted(VoterInterface $voter, int $userId, object $action): void
    {
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote($voter, $userId, $action), 'user '.$userId.' '.$action::class);
    }

    private function assertDenied(VoterInterface $voter, int $userId, object $action): void
    {
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($voter, $userId, $action), 'user '.$userId.' '.$action::class);
    }

    private function vote(VoterInterface $voter, int $userId, object $action): int
    {
        $user = (new \ReflectionClass(BackendUser::class))->newInstanceWithoutConstructor();
        $user->id = $userId;
        $user->admin = self::ADMIN === $userId ? 1 : 0;

        return $voter->vote(new UsernamePasswordToken($user, 'contao_backend'), $action, ['contao_dc.'.$action->getDataSource()]);
    }
}
