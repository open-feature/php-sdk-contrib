<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\grpc;

use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Schema\V1\ResolveBooleanResponse;
use Schema\V1\ResolveFloatResponse;
use Schema\V1\ResolveIntResponse;
use Schema\V1\ResolveObjectResponse;
use Schema\V1\ResolveStringResponse;

class ResponseResolutionDetailsAdapter
{
    /**
     * @param ResolveBooleanResponse|ResolveFloatResponse|ResolveIntResponse|ResolveObjectResponse|ResolveStringResponse $response
     */
    public static function fromResponse($response): ResolutionDetails
    {
        return (new ResolutionDetailsBuilder())
                    ->withValue($response->getValue())
                    ->withReason($response->getReason())
                    ->withVariant($response->getVariant())
                    ->build();
    }
}
