<?php

declare(strict_types = 1);

namespace OpenFeature\Providers\Flagd\http;

use DateTime;
use OpenFeature\interfaces\provider\ResolutionDetails;

class FlagdResponseResolutionAllAdapter
{
    /**
     * @param array{flags: array{array{value: mixed[]|bool|DateTime|float|int|string|null, variant: ?string, reason: ?string}}} $response
     * @return ResolutionDetails[]
     */
    public static function forSuccess(array $response): array
    {
        return array_map(
            fn($flagDetails) => FlagdResponseResolutionDetailsAdapter::forSuccess($flagDetails), $response['flags']
        );
    }
}
