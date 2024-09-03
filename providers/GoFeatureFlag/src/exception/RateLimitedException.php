<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\exception;

use OpenFeature\interfaces\provider\ErrorCode;
use Psr\Http\Message\ResponseInterface;

class RateLimitedException extends BaseOfrepException
{
    public function __construct(?ResponseInterface $response = null)
    {
        $message = 'Rate limit exceeded';
        $code = 1003;
        parent::__construct($message, ErrorCode::GENERAL(), $response, $code);
    }
}
