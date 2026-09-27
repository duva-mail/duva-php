<?php

declare(strict_types=1);

namespace Duva\Models;

use DateTimeImmutable;

/**
 * Hand-written response value objects (Ruby and PHP are the two languages in
 * `docs/bibliotheques-clientes.md` §4/§5 without an idiomatic single-purpose "models from
 * OpenAPI" generator: `openapi-generator`'s PHP output is verbose, mutable
 * getter/setter/ArrayAccess classes -- not the `readonly` types this library targets). Each
 * `fromArray` factory reads only the fields it knows and ignores the rest, and never restricts a
 * string enum-like field (`status`, `type`, `reason`...) to a fixed set: a value the server adds
 * later still comes through as plain text rather than throwing (section 3.7, "tolerance").
 *
 * These typed accessors are the ONE place that trusts the wire shape (our own `openapi.json`):
 * every `fromArray` reads a decoded JSON value (`mixed` to PHPStan) through them instead of a
 * scattered `(string) $data['x']` cast, which PHPStan (level max) refuses on a genuinely
 * `mixed` value.
 *
 * @internal
 */
final class ModelSupport
{
    private function __construct()
    {
    }

    // The four casts below are the deliberate, single trust boundary for decoded JSON (`mixed`
    // to PHPStan by construction): every other call site in this library goes through them
    // instead of casting a `mixed` value itself.

    public static function str(mixed $value): string
    {
        // @phpstan-ignore-next-line cast.string (trust boundary: see class docblock)
        return (string) $value;
    }

    public static function strOrNull(mixed $value): ?string
    {
        return $value !== null ? self::str($value) : null;
    }

    public static function int(mixed $value): int
    {
        // @phpstan-ignore-next-line cast.int (trust boundary: see class docblock)
        return (int) $value;
    }

    public static function intOrNull(mixed $value): ?int
    {
        return $value !== null ? self::int($value) : null;
    }

    public static function time(mixed $value): DateTimeImmutable
    {
        return new DateTimeImmutable(self::str($value));
    }

    public static function timeOrNull(mixed $value): ?DateTimeImmutable
    {
        return $value !== null ? new DateTimeImmutable(self::str($value)) : null;
    }

    /** A JSON array of strings (e.g. `tags`, `events`): reindexed, since a JSON array is
     * inherently int-keyed 0.. and a malformed/sparse input should not leak its own keys.
     *
     * @return array<int, string>
     */
    public static function strList(mixed $value): array
    {
        return is_array($value) ? array_values(array_map(self::str(...), $value)) : [];
    }

    /** @return array<string, string> */
    public static function stringMap(mixed $value): array
    {
        // @phpstan-ignore-next-line return.type (trust boundary: see class docblock)
        return is_array($value) ? array_map(self::str(...), $value) : [];
    }

    /** @return array<string, int> */
    public static function intMap(mixed $value): array
    {
        // @phpstan-ignore-next-line return.type (trust boundary: see class docblock)
        return is_array($value) ? array_map(self::int(...), $value) : [];
    }

    /** @return array<string, mixed> */
    public static function map(mixed $value): array
    {
        // @phpstan-ignore-next-line return.type (trust boundary: see class docblock)
        return is_array($value) ? $value : [];
    }

    /** @return array<int, mixed> */
    public static function list(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    /** A list of JSON objects (e.g. `recipients`, `data`): each item narrowed with {@see self::map}.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function mapList(mixed $value): array
    {
        return array_map(self::map(...), self::list($value));
    }
}
