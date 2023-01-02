<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\Validators\Regexp;

use Exception;
use Throwable;

use function sprintf;

class InvalidRegularExpressionException extends Exception
{
    private const ERROR_MESSAGE_TEMPLATE = 'The provided regular expression was invalid: %s';

    public function __construct(string $regexp, int $code = 0, ?Throwable $previous = null)
    {
        $message = sprintf(self::ERROR_MESSAGE_TEMPLATE, $regexp);

        parent::__construct($message, $code, $previous);
    }
}
