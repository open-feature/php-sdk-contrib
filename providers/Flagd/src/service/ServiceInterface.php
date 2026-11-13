<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\service;

use DateTime;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\ResolutionDetails;

interface ServiceInterface
{
    /**
     * @param mixed[]|bool|DateTime|float|int|string|null $defaultValue
     */
    public function resolveValue(string $flagKey, FlagValueType $flagType, mixed $defaultValue, ?EvaluationContext $context): ResolutionDetails;
}
