<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\exception;

use OpenFeature\interfaces\provider\ErrorCode;
use Psr\Http\Message\ResponseInterface;

class FlagNotFoundException extends BaseOfrepException
{
    private string $flagKey;

    public function __construct(string $flagKey, ResponseInterface $response)
    {
        $this->flagKey = $flagKey;
        $message = "Flag with key $flagKey not found";
        $code = 1002;
        parent::__construct($message, ErrorCode::FLAG_NOT_FOUND(), $response, $code);
    }

    public function getFlagKey(): string
    {
        return $this->flagKey;
    }
}
