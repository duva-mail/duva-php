<?php

declare(strict_types=1);

namespace Duva\Exceptions;

/** 403 `domain_not_verified` or `sending_not_allowed`; `code` distinguishes the two. */
class PermissionException extends DuvaException
{
}
