<?php

namespace OpenFeature\Providers\GoFeatureFlag\model;

use OpenFeature\interfaces\common\Metadata;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\Providers\GoFeatureFlag\exception\ParseException;
use OpenFeature\Providers\GoFeatureFlag\util\Validator;

class OfrepApiResponse
{
    private mixed $value;
    private string $key;
    private string $reason;
    private ?string $variant;
    private ?ErrorCode $errorCode;
    private ?string $errorDetails;
    /** @var Metadata[] */
    private ?array $metadata;

    private function __construct(
        $value, string $key, string $reason, ?string $variant, ?ErrorCode $errorCode,
        ?string $errorDetails, array $metadata = [])
    {
        $this->value = $value;
        $this->key = $key;
        $this->reason = $reason;
        $this->variant = $variant;
        $this->errorCode = $errorCode;
        $this->errorDetails = $errorDetails;
        $this->metadata = $metadata;
    }

    /**
     * @throws ParseException
     */
    public static function createErrorResponse(array $apiData): OfrepApiResponse
    {
        Validator::validateErrorApiResponse($apiData);
        return new OfrepApiResponse(
            null,
            $apiData["key"],
            Reason::ERROR,
            null,
            OfrepApiResponse::errorCodeMapper($apiData["errorCode"]),
            $apiData["errorDetails"],
            []
        );
    }

    private static function errorCodeMapper(string $errorCode): ErrorCode
    {
        return match ($errorCode) {
            'PROVIDER_NOT_READY' => ErrorCode::PROVIDER_NOT_READY(),
            'FLAG_NOT_FOUND' => ErrorCode::FLAG_NOT_FOUND(),
            'PARSE_ERROR' => ErrorCode::PARSE_ERROR(),
            'TYPE_MISMATCH' => ErrorCode::TYPE_MISMATCH(),
            'TARGETING_KEY_MISSING' => ErrorCode::TARGETING_KEY_MISSING(),
            'INVALID_CONTEXT' => ErrorCode::INVALID_CONTEXT(),
            default => ErrorCode::GENERAL()
        };
    }

    /**
     * @throws ParseException
     */
    public static function createSuccessResponse(array $apiData): OfrepApiResponse
    {
        Validator::validateSuccessApiResponse($apiData);
        $value = $apiData['value'];
        $key = $apiData['key'];
        $variant = $apiData['variant'];
        $reason = OfrepApiResponse::reasonMapper($apiData['reason']);
        $metadata = $apiData['metadata'] ?? [];
        return new OfrepApiResponse($value, $key, $reason, $variant, null, null, $metadata);
    }

    private static function reasonMapper(string $reason): string
    {
        return match ($reason) {
            'ERROR' => Reason::ERROR,
            'DEFAULT' => Reason::DEFAULT,
            'TARGETING_MATCH' => Reason::TARGETING_MATCH,
            'SPLIT' => Reason::SPLIT,
            'DISABLED' => Reason::DISABLED,
            default => Reason::UNKNOWN
        };
    }

    public function isError(): bool
    {
        return $this->errorCode !== null;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @return ?string
     */
    public function getVariant(): ?string
    {
        return $this->variant;
    }

    /**
     * @return ?ErrorCode
     */
    public function getErrorCode(): ?ErrorCode
    {
        return $this->errorCode;
    }

    /**
     * @return ?string
     */
    public function getErrorDetails(): ?string
    {
        return $this->errorDetails;
    }

    /**
     * @return ?array
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
}
