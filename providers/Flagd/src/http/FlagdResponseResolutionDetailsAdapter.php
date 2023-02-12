<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

use DateTime;
use OpenFeature\Providers\Flagd\common\ResponseCodeErrorCodeMap;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\ResolutionDetails;

class FlagdResponseResolutionDetailsAdapter
{
    /**
     * @param mixed[]|bool|DateTime|float|int|string|null $defaultValue
     */
    public static function forTypeMismatch(mixed $defaultValue): ResolutionDetails
    {
        return (new ResolutionDetailsBuilder())
            ->withValue($defaultValue)
            ->withError(new ResolutionError(ErrorCode::TYPE_MISMATCH()))
            ->build();
    }

    /**
     * @param string[] $response
     * @param mixed[]|bool|DateTime|float|int|string|null $defaultValue
     */
    public static function forError(array $response, mixed $defaultValue): ResolutionDetails
    {
        $responseCode = $response['code'];
        if ($responseCode && ResponseCodeErrorCodeMap::has($responseCode)) {
            /**
             * @var ErrorCode $responseErrorCode
            */
            $responseErrorCode = ResponseCodeErrorCodeMap::get($responseCode);

            $resolutionError = new ResolutionError(
                $responseErrorCode,
                $response['message'] ?? (string) ErrorCode::GENERAL(),
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
     * @param array{value: mixed[]|bool|DateTime|float|int|string|null, variant: ?string, reason: ?string} $response
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
