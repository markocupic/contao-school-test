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

use Doctrine\DBAL\Connection;

final class CodeGenerator
{
    /**
     * No 0/O, 1/I/L: easy to read aloud and to type.
     */
    private const RESUME_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const RESUME_LENGTH = 6;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Five digit student code, unique across all tests (the code alone identifies the participation).
     */
    public function studentCode(): string
    {
        for ($i = 0; $i < 1000; ++$i) {
            $code = (string) random_int(10000, 99999);

            if (!$this->connection->fetchOne('SELECT id FROM tl_st_participation WHERE code = ?', [$code])) {
                return $code;
            }
        }

        throw new \RuntimeException('Could not find a free student code. Delete old tests to free codes.');
    }

    /**
     * Normalizes a resume code typed by the teacher. Returns null if it is not exactly 6 letters or digits.
     */
    public static function normalizeResumeCode(string $code): string|null
    {
        $code = strtoupper(preg_replace('/\s+/', '', $code));

        return preg_match('/^[A-Z0-9]{'.self::RESUME_LENGTH.'}$/', $code) ? $code : null;
    }

    /**
     * Six character resume code for the teacher.
     */
    public function resumeCode(): string
    {
        $code = '';

        for ($i = 0; $i < self::RESUME_LENGTH; ++$i) {
            $code .= self::RESUME_ALPHABET[random_int(0, \strlen(self::RESUME_ALPHABET) - 1)];
        }

        return $code;
    }
}
