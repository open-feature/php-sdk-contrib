<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\grpc;

use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\interfaces\provider\ResolutionDetails;

class ResponseResolutionDetailsAdapter
{
    public static function fromResponse(array $response): ResolutionDetails
    {
        return (new ResolutionDetailsBuilder())
                    ->withValue($response['value'])
                    ->withReason($response['reason'])
                    ->withVariant($response['variant'])
                    ->build();
    }
}
