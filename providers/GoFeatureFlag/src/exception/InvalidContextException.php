<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\exception;

use OpenFeature\interfaces\provider\ErrorCode;

class InvalidContextException extends BaseOfrepException
{
    public function __construct(string $message)
    {
        $code = 1006;
        parent::__construct($message, ErrorCode::INVALID_CONTEXT(), null, $code);
    }
}
