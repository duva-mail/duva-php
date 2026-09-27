<?php

declare(strict_types=1);

namespace Duva;

use InvalidArgumentException;

/** Small formatting helpers matched to the API's own parsing rules (`docs/api.md`). */
final class Address
{
    private function __construct()
    {
    }

    /**
     * `"Name <address>"` (with `Name` quoted if it contains a `"` or `,`), or just `address`
     * without a name.
     */
    public static function format(string $email, ?string $name = null): string
    {
        if ($name === null || $name === '') {
            return $email;
        }
        $escaped = str_replace('"', '\\"', $name);
        $quoted = preg_match('/[",]/', $name) === 1 ? "\"{$escaped}\"" : $name;

        return "{$quoted} <{$email}>";
    }

    /**
     * Builds `List-Unsubscribe` (and `List-Unsubscribe-Post` for one-click) exactly as the API
     * validates them: at most 3 links, `https://` or `mailto:` only. Throws
     * InvalidArgumentException when neither `$httpsUrl` nor `$mailto` is given.
     *
     * `$oneClick` adds `List-Unsubscribe-Post` (RFC 8058). Defaults to `true` when `$httpsUrl` is
     * given, `false` otherwise.
     *
     * @return array<string, string>
     */
    public static function unsubscribeHeaders(?string $httpsUrl = null, ?string $mailto = null, ?bool $oneClick = null): array
    {
        if ($httpsUrl === null && $mailto === null) {
            throw new InvalidArgumentException('Duva: unsubscribeHeaders needs httpsUrl and/or mailto');
        }
        if ($httpsUrl !== null && !str_starts_with($httpsUrl, 'https://')) {
            throw new InvalidArgumentException('Duva: unsubscribeHeaders httpsUrl must be an https:// link');
        }
        $resolvedOneClick = $oneClick ?? ($httpsUrl !== null);

        $links = array_filter([$httpsUrl, $mailto !== null ? "mailto:{$mailto}" : null], static fn (?string $v): bool => $v !== null);
        $headers = ['List-Unsubscribe' => implode(', ', array_map(static fn (string $l): string => "<{$l}>", $links))];
        if ($resolvedOneClick) {
            if ($httpsUrl === null) {
                throw new InvalidArgumentException('Duva: one-click unsubscribe (List-Unsubscribe-Post) needs httpsUrl');
            }
            $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        }

        return $headers;
    }
}
