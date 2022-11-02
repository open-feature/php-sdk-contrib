<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenFeature\Providers\Flagd\common\ResponseCodeErrorCodeMap;

class FlagdResponseResolutionDetailsAdapter
{
    public static function forTypeMismatch($defaultValue): ResolutionDetails
    {
        return (new ResolutionDetailsBuilder())
            ->withValue($defaultValue)
            ->withError(new ResolutionError(ErrorCode::TYPE_MISMATCH()))
            ->build();
    }

    /**
     * @param mixed[] $response
     * @param mixed $defaultValue
     */
    public static function forError(array $response, $defaultValue): ResolutionDetails
    {
        $responseCode = $response['code'];
        if ($responseCode && ResponseCodeErrorCodeMap::has($responseCode)) {
            $resolutionError = new ResolutionError(
                ResponseCodeErrorCodeMap::get($responseCode),
                $response['message'] ?? (string) ErrorCode::GENERAL()
            );
        } else {
            $resolutionError = new ResolutionError(ErrorCode::GENERAL(), (string) ErrorCode::GENERAL());
        }

        return (new ResolutionDetailsBuilder())
            ->withValue($defaultValue)
            ->withError($resolutionError)
            ->build();
    }

    /**
     * @param mixed[] $response
     */
    public static function forSuccess(array $response): ResolutionDetails
    {
        $builder = new ResolutionDetailsBuilder();

        $builder->withValue($response['value']);

        if (isset($response['variant'])) {
            $builder->withVariant($response['variant']);
        }

        if (isset($response['reason'])) {
            $builder->withReason($response['reason']);
        }

        return $builder->build();
    }
}
