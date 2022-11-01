<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\grpc;

use Google\Protobuf\Struct;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Schema\V1\ResolveBooleanResponse;
use Schema\V1\ResolveFloatResponse;
use Schema\V1\ResolveIntResponse;
use Schema\V1\ResolveObjectResponse;
use Schema\V1\ResolveStringResponse;

use function json_decode;

class ResponseResolutionDetailsAdapter
{
    /**
     * @param string[] $response
     */
    public static function fromArray(array $response): ResolutionDetails
    {
        return (new ResolutionDetailsBuilder())
                    ->withValue($response['value'])
                    ->withReason($response['reason'])
                    ->withVariant($response['variant'])
                    ->build();
    }

    /**
     * @param ResolveBooleanResponse|ResolveFloatResponse|ResolveIntResponse|ResolveObjectResponse|ResolveStringResponse $response
     */
    public static function fromResponse($response): ResolutionDetails
    {
        /** @var bool|int|string|float|Struct $value */
        $value = $response->getValue();
        $reason = $response->getReason();
        $variant = $response->getVariant();

        if ($value instanceof Struct) {
            /** @var mixed[] $value */
            $value = json_decode($value->serializeToJsonString(), true);
        }

        return (new ResolutionDetailsBuilder())
                    ->withValue($value)
                    ->withReason($reason)
                    ->withVariant($variant)
                    ->build();
    }
}
