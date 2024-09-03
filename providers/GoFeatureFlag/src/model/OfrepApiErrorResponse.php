<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\model;

use OpenFeature\Providers\GoFeatureFlag\exception\ParseException;
use OpenFeature\Providers\GoFeatureFlag\util\Mapper;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Reason;

use function is_string;

class OfrepApiErrorResponse
{
    private ErrorCode $errorCode;
    private string $errorDetails;
    private string $reason;

    /**
     * @param array<string, mixed> $apiData
     *
     * @throws ParseException
     */
    public function __construct(array $apiData)
    {
        $this->reason = Reason::ERROR;
        $this->errorCode = Mapper::errorCode(is_string($apiData['errorCode']) ? $apiData['errorCode'] : '');
        $this->errorDetails = is_string($apiData['errorDetails']) ? $apiData['errorDetails'] : '';
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getErrorCode(): ErrorCode
    {
        return $this->errorCode;
    }

    public function getErrorDetails(): string
    {
        return $this->errorDetails;
    }
}
