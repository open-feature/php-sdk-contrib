<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\service;

use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ResolutionDetails;

interface ServiceInterface
{
    /**
     * @param mixed $defaultValue
     */
    public function resolveValue(string $flagKey, string $flagType, $defaultValue, ?EvaluationContext $context): ResolutionDetails;
}
