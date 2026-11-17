<?php

declare(strict_types=1);

/*
 * This file is part of Contao School Test.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 */

namespace Markocupic\ContaoSchoolTest\Participation;

use Symfony\Component\HttpFoundation\Request;

/**
 * Is the request coming from the Safe Exam Browser (SEB)?
 *
 * With a Config Key (from the SEB configuration tool) the check is cryptographic: SEB sends
 * SHA256(absolute URL without fragment + Config Key) in the header X-SafeExamBrowser-ConfigKeyHash.
 * SEB with the modern WebView (macOS, iOS) cannot send headers; the page script then reads the
 * same value for the page URL from the SEB JavaScript API (SafeExamBrowser.security.configKey)
 * and sends it along with the URL.
 *
 * Without a Config Key only signs of SEB are checked (header, JavaScript API, user agent).
 * A browser can fake these, so a Config Key is recommended.
 */
final class SebVerifier
{
    public const HEADER = 'X-SafeExamBrowser-ConfigKeyHash';

    /**
     * @param array{ck?: mixed, url?: mixed, v?: mixed}|null $js values from the SEB JavaScript API
     */
    public function isSeb(Request $request, string $configKey, array|null $js = null): bool
    {
        $configKey = strtolower(trim($configKey));
        $header = strtolower(trim((string) $request->headers->get(self::HEADER, '')));
        $jsHash = strtolower(trim(\is_string($js['ck'] ?? null) ? $js['ck'] : ''));
        $jsUrl = \is_string($js['url'] ?? null) ? $js['url'] : '';

        if ('' === $configKey) {
            return '' !== $header
                || 1 === preg_match('/^[0-9a-f]{64}$/', $jsHash)
                || (\is_string($js['v'] ?? null) && str_starts_with($js['v'], 'SEB'))
                || 1 === preg_match('/\bSEB\/\d/', (string) $request->headers->get('User-Agent', ''));
        }

        // Header: hash of the URL of this request
        if ('' !== $header && hash_equals(self::hash($request->getSchemeAndHttpHost().$request->getRequestUri(), $configKey), $header)) {
            return true;
        }

        // JavaScript API: hash of the page URL, which must be on this host
        if ('' !== $jsHash && '' !== $jsUrl && self::sameHost($jsUrl, $request)) {
            return hash_equals(self::hash($jsUrl, $configKey), $jsHash);
        }

        return false;
    }

    /**
     * SHA256 (Base16) of the URL without fragment followed by the Config Key.
     */
    public static function hash(string $url, string $configKey): string
    {
        return hash('sha256', explode('#', $url, 2)[0].strtolower($configKey));
    }

    private static function sameHost(string $url, Request $request): bool
    {
        $parts = parse_url($url);

        return \is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === strtolower($request->getScheme())
            && strtolower((string) ($parts['host'] ?? '')) === strtolower($request->getHost());
    }
}
