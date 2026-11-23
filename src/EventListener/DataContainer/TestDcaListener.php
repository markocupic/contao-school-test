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

use Contao\BackendUser;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Markocupic\ContaoSchoolTest\Participation\CodeGenerator;
use Markocupic\ContaoSchoolTest\Participation\ParticipationStatus;
use Markocupic\ContaoSchoolTest\Participation\ParticipationSynchronizer;
use Markocupic\ContaoSchoolTest\TestType\TestTypeRegistry;
use Symfony\Bundle\SecurityBundle\Security;

class TestDcaListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TestTypeRegistry $testTypes,
        private readonly CodeGenerator $codeGenerator,
        private readonly ParticipationSynchronizer $synchronizer,
        private readonly Security $security,
    ) {
    }

    /**
     * One palette per registered test type.
     */
    #[AsHook('loadDataContainer')]
    public function addPalettes(string $table): void
    {
        if ('tl_st_test' !== $table) {
            return;
        }

        foreach ($this->testTypes->all() as $alias => $type) {
            $GLOBALS['TL_DCA']['tl_st_test']['palettes'][$alias] = '{title_legend},title,subject,type;{class_legend},class;'
                .$type->getPaletteFields().',sameTasks'
                .';{grade_legend},gradeStep,pointsForSix;{time_legend},timeLimit;{security_legend},lockOnLeave,resumeCode;{seb_legend},acceptSebBrowserOnly,sebConfigKey;{codesheet_legend},codeSheetNote;{publish_legend},published';
        }
    }

    #[AsCallback(table: 'tl_st_test', target: 'fields.type.options')]
    public function typeOptions(): array
    {
        return array_map(static fn ($type) => $type->getLabel(), $this->testTypes->all());
    }

    #[AsCallback(table: 'tl_st_test', target: 'config.onsubmit')]
    public function onSubmit(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        if ('' === trim((string) ($dc->activeRecord?->resumeCode ?? ''))) {
            $this->connection->update('tl_st_test', ['resumeCode' => $this->codeGenerator->resumeCode()], ['id' => $dc->id]);
        }

        // As long as nobody has started, changed settings take effect: the shared tasks are generated anew
        $started = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_st_participation WHERE pid = ? AND status != ?', [$dc->id, ParticipationStatus::OPEN]);

        if (0 === $started) {
            $this->connection->update('tl_st_test', ['tasks' => null], ['id' => $dc->id]);
        }

        $this->synchronizer->sync((int) $dc->id);
    }

    /**
     * Config Key of the SEB configuration: 64 hexadecimal characters (stored in lower case) or empty.
     */
    #[AsCallback(table: 'tl_st_test', target: 'fields.sebConfigKey.save')]
    public function saveSebConfigKey(mixed $value): string
    {
        $value = strtolower(trim((string) $value));

        if ('' !== $value && !preg_match('/^[0-9a-f]{64}$/', $value)) {
            throw new \RuntimeException('Der Config Key besteht aus 64 Zeichen 0–9 und a–f. Du findest ihn im SEB-Konfigurationswerkzeug unter «Config Key».');
        }

        return $value;
    }

    /**
     * Empty: a code is generated on submit. Otherwise exactly 6 letters or digits (stored in capitals).
     */
    #[AsCallback(table: 'tl_st_test', target: 'fields.resumeCode.save')]
    public function saveResumeCode(mixed $value): string
    {
        if ('' === trim((string) $value)) {
            return '';
        }

        return CodeGenerator::normalizeResumeCode((string) $value)
            ?? throw new \RuntimeException('Der Wiederaufnahme-Code muss genau 6 Zeichen lang sein (Buchstaben und Ziffern). Leer lassen erzeugt automatisch einen Code.');
    }

    /**
     * Non-admins only see their own tests (editing and deleting are checked in Security\TestVoter).
     */
    #[AsCallback(table: 'tl_st_test', target: 'config.onload')]
    public function onLoad(): void
    {
        $user = $this->security->getUser();

        if ($user instanceof BackendUser && !$user->isAdmin) {
            $GLOBALS['TL_DCA']['tl_st_test']['list']['sorting']['filter'][] = ['author=?', (int) $user->id];
        }
    }

    /**
     * A new test belongs to the user who creates it.
     */
    #[AsCallback(table: 'tl_st_test', target: 'config.oncreate')]
    public function onCreate(string $table, int|string $insertId): void
    {
        $this->setAuthor((int) $insertId);
    }

    #[AsCallback(table: 'tl_st_test', target: 'config.oncopy')]
    public function onCopy(int|string $insertId): void
    {
        $this->connection->update('tl_st_test', ['resumeCode' => $this->codeGenerator->resumeCode()], ['id' => (int) $insertId]);

        // The copy belongs to the user who copied it
        $this->setAuthor((int) $insertId);
    }

    #[AsCallback(table: 'tl_st_test', target: 'list.label.label')]
    public function label(array $row, string $label): string
    {
        $class = (string) $this->connection->fetchOne('SELECT title FROM tl_st_class WHERE id = ?', [$row['class']]);
        $counts = $this->connection->fetchAllKeyValue('SELECT status, COUNT(*) FROM tl_st_participation WHERE pid = ? GROUP BY status', [$row['id']]);
        $total = array_sum($counts);

        // Admins see all tests and therefore whose test it is
        $user = $this->security->getUser();
        $owner = '';

        if ($user instanceof BackendUser && $user->isAdmin) {
            $name = (string) $this->connection->fetchOne('SELECT name FROM tl_user WHERE id = ?', [(int) $row['author']]);
            $owner = ' · '.($name ? StringUtil::specialchars(StringUtil::decodeEntities($name)) : 'ohne Besitzer');
        }

        return \sprintf(
            '%s <span class="label-info">[%s · %s · %d/%d abgegeben · Wiederaufnahme-Code: <strong>%s</strong>%s]</span>',
            StringUtil::specialchars($row['title']),
            StringUtil::specialchars($row['subject'] ?: '–'),
            StringUtil::specialchars($class ?: '–'),
            (int) ($counts[ParticipationStatus::FINISHED] ?? 0),
            $total,
            StringUtil::specialchars($row['resumeCode'] ?: '–'),
            $owner,
        );
    }

    private function setAuthor(int $testId): void
    {
        $user = $this->security->getUser();

        if ($user instanceof BackendUser && $testId > 0) {
            $this->connection->update('tl_st_test', ['author' => (int) $user->id], ['id' => $testId]);
        }
    }
}
