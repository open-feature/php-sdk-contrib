<?php

namespace OpenFeature\Providers\GoFeatureFlag\exception;

use OpenFeature\interfaces\provider\ErrorCode;
use Psr\Http\Message\ResponseInterface;

class UnknownOfrepException extends BaseOfrepException
{
    public function __construct(?ResponseInterface $response, \Exception $previous = null)
    {
        $message = "Unknown error occurred";
        $code = 1004;
        parent::__construct($message, ErrorCode::GENERAL(), $response, $code, $previous);
    }
}
