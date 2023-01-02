<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\Validators\Exceptions;

use Exception;
use Throwable;

class ValidationException extends Exception
{
    private const ERROR_MESSAGE = 'Validation hook failed to validate the provided value';

    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(self::ERROR_MESSAGE, $code, $previous);
    }
}
