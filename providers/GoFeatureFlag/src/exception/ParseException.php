<?php

namespace OpenFeature\Providers\GoFeatureFlag\exception;

use OpenFeature\interfaces\provider\ErrorCode;

class ParseException extends BaseOfrepException
{
    public function __construct(string $message, \Exception $previous = null)
    {
        $code = 1005;
        parent::__construct($message, ErrorCode::PARSE_ERROR(), null, $code, $previous);
    }
}
