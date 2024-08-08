<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\exception;

use OpenFeature\interfaces\provider\ErrorCode;
use Psr\Http\Message\ResponseInterface;

class UnauthorizedException extends BaseOfrepException
{
    public function __construct(ResponseInterface $response)
    {
        $message = 'Unauthorized access to the API';
        $code = 1001;
        parent::__construct($message, ErrorCode::GENERAL(), $response, $code);
    }
}
