<?php

declare(strict_types=1);

namespace Duva\Exceptions;

/** A 5xx, or a response whose body was not the documented error envelope. */
class ServerException extends DuvaException
{
}
