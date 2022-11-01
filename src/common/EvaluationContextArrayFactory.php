<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\common;

use OpenFeature\interfaces\flags\EvaluationContext;

use function array_merge;
use function is_null;

class EvaluationContextArrayFactory
{
    /**
     * @return mixed[]|null
     */
    public static function build(?EvaluationContext $context): ?array
    {
        if (is_null($context)) {
            $contextArray = null;
        } else {
            $contextArray = array_merge(
                [],
                $context->getAttributes()->toArray(),
                ['targetingKey' => $context->getTargetingKey()],
            );
        }

        return $contextArray;
    }
}
