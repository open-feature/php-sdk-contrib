<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\exception;

use OpenFeature\interfaces\provider\ErrorCode;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class UnknownOfrepException extends BaseOfrepException
{
    public function __construct(?ResponseInterface $response, ?Throwable $previous = null)
    {
        $message = 'Unknown error occurred';
        $code = 1004;
        parent::__construct($message, ErrorCode::GENERAL(), $response, $code, $previous);
    }
}
