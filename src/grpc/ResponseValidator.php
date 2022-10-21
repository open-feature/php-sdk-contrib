<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\grpc;

use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Schema\V1\ResolveBooleanResponse;
use Schema\V1\ResolveFloatResponse;
use Schema\V1\ResolveIntResponse;
use Schema\V1\ResolveObjectResponse;
use Schema\V1\ResolveStringResponse;

class ResponseValidator
{
    /**
     * @param ResolveBooleanResponse|ResolveFloatResponse|ResolveIntResponse|ResolveObjectResponse|ResolveStringResponse|mixed $response
     */
    public static function isResponse($response): bool
    {
        return ($response instanceof ResolveBooleanResponse ||
            $response instanceof ResolveFloatResponse ||
            $response instanceof ResolveIntResponse ||
            $response instanceof ResolveObjectResponse ||
            $response instanceof ResolveStringResponse
        );
    }

    /**
     * @param ResolveBooleanResponse|ResolveFloatResponse|ResolveIntResponse|ResolveObjectResponse|ResolveStringResponse $response
     */
    public static function isCorrectType($response, string $expectedType): bool
    {
        $value = $response->getValue();

        $actualType = self::determineType($value);

        return ($expectedType !== $actualType);
    }

    private static function determineType($value): string
    {
        if (is_bool($value)) {
            return FlagValueType::BOOLEAN;
        }

        if (is_float($value)) {
            return FlagValueType::FLOAT;
        }

        if (is_int($value)) {
            return FlagValueType::INTEGER;
        }

        if (is_null($value) || is_array($value)) {
            return FlagValueType::OBJECT;
        }

        if (is_string($value)) {
            return FlagValueType::STRING;
        }

        return null;
    }
}
