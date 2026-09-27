<?php

declare(strict_types=1);

namespace Duva\Exceptions;

/** 409 `idempotency_conflict` or `limit_reached`; `code` distinguishes the two. */
class ConflictException extends DuvaException
{
}
