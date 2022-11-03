<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\service;

use DateTime;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ResolutionDetails;

interface ServiceInterface
{
    /**
     * @param mixed[]|bool|DateTime|float|int|string|null $defaultValue
     */
    public function resolveValue(string $flagKey, string $flagType, $defaultValue, ?EvaluationContext $context): ResolutionDetails;
}
