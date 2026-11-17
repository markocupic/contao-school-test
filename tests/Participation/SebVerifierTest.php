<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Tests\Participation;

use Markocupic\ContaoSchoolTest\Participation\SebVerifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class SebVerifierTest extends TestCase
{
    private const KEY = '0f3a9c6b1e2d4a5f8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1a0b9c8d7e6f5a';

    private const API = 'https://example.org/_school_test/start';

    private const PAGE = 'https://example.org/test.html?x=1';

    public function testHashIsSha256OfUrlWithoutFragmentAndKey(): void
    {
        $this->assertSame(hash('sha256', self::PAGE.self::KEY), SebVerifier::hash(self::PAGE.'#top', self::KEY));
    }

    public function testHeaderWithMatchingConfigKey(): void
    {
        $seb = new SebVerifier();

        $this->assertTrue($seb->isSeb($this->request(SebVerifier::hash(self::API, self::KEY)), self::KEY));
        $this->assertTrue($seb->isSeb($this->request(strtoupper(SebVerifier::hash(self::API, self::KEY))), strtoupper(self::KEY)));
        // Other configuration or other URL
        $this->assertFalse($seb->isSeb($this->request(SebVerifier::hash(self::API, str_repeat('a', 64))), self::KEY));
        $this->assertFalse($seb->isSeb($this->request(SebVerifier::hash(self::PAGE, self::KEY)), self::KEY));
        $this->assertFalse($seb->isSeb($this->request(), self::KEY));
    }

    public function testJavaScriptApiWithMatchingConfigKey(): void
    {
        $seb = new SebVerifier();
        $ck = SebVerifier::hash(self::PAGE, self::KEY);

        $this->assertTrue($seb->isSeb($this->request(), self::KEY, ['ck' => $ck, 'url' => self::PAGE, 'v' => 'SEB_macOS_3.4']));
        // The hash must belong to the URL sent along
        $this->assertFalse($seb->isSeb($this->request(), self::KEY, ['ck' => $ck, 'url' => 'https://example.org/other.html']));
        // A page on another host does not count
        $this->assertFalse($seb->isSeb($this->request(), self::KEY, ['ck' => SebVerifier::hash('https://example.net/', self::KEY), 'url' => 'https://example.net/']));
        // The version alone is not enough when a Config Key is set
        $this->assertFalse($seb->isSeb($this->request(), self::KEY, ['ck' => '', 'url' => self::PAGE, 'v' => 'SEB_Windows_3.8']));
    }

    public function testWithoutConfigKeyAnySignOfSebIsAccepted(): void
    {
        $seb = new SebVerifier();

        $this->assertTrue($seb->isSeb($this->request('abc'), ''));
        $this->assertTrue($seb->isSeb($this->request(), '', ['ck' => str_repeat('f', 64), 'url' => self::PAGE]));
        $this->assertTrue($seb->isSeb($this->request(), '', ['v' => 'SEB_iOS_3.4']));
        $this->assertTrue($seb->isSeb($this->request(null, 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120 Safari/537.36 SEB/3.8.0'), ''));
        $this->assertFalse($seb->isSeb($this->request(null, 'Mozilla/5.0 (Windows NT 10.0) Chrome/120 Safari/537.36'), ''));
        $this->assertFalse($seb->isSeb($this->request(), '', ['ck' => 'nope', 'v' => 'Firefox']));
    }

    private function request(string|null $header = null, string $userAgent = 'Mozilla/5.0'): Request
    {
        $server = ['HTTP_USER_AGENT' => $userAgent];

        if (null !== $header) {
            $server['HTTP_X_SAFEEXAMBROWSER_CONFIGKEYHASH'] = $header;
        }

        return Request::create(self::API, 'POST', [], [], [], $server);
    }
}
