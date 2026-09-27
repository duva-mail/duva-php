<?php

declare(strict_types=1);

namespace Duva\Exceptions;

use RuntimeException;

/**
 * Base of every exception thrown for a request Duva actually answered (as opposed to a network
 * failure: see {@see ConnectionException} and {@see TimeoutException}). Mapped from `error.code`
 * (the contract) rather than from the HTTP status or the wording of `error.message`, which can
 * change between languages and over time. See `docs/bibliotheques-clientes.md` section 3.6 in
 * the `duva` repository for the source table.
 */
class DuvaException extends RuntimeException
{
    /** @var array<int, array{field: string, message: string}>|null `error.fields`, when the error is a validation error (`code == "invalid_request"`). */
    public readonly ?array $fields;

    /** The raw response body, bounded to 4 KB: never includes your API key. */
    public readonly string $rawBody;

    /**
     * @param array<int, array{field: string, message: string}>|null $fields
     */
    public function __construct(
        /** The HTTP status Duva answered with. */
        public readonly int $status,
        // Named `errorCode`, not `code`: `\Exception::$code` already exists (a plain, non-readonly
        // int) and PHP forbids redeclaring it as readonly in a subclass.
        /** `error.code`: the contract. Rely on this, never on the exception message. */
        public readonly string $errorCode,
        string $message,
        string $rawBody,
        ?array $fields = null,
    ) {
        parent::__construct($message);
        $this->fields = $fields;
        $this->rawBody = substr($rawBody, 0, 4096);
    }
}
