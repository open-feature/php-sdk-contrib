<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\exception;

use OpenFeature\interfaces\provider\ErrorCode;
use Throwable;

class ParseException extends BaseOfrepException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        $code = 1005;
        parent::__construct($message, ErrorCode::PARSE_ERROR(), null, $code, $previous);
    }
}
