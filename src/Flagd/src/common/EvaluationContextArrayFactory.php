<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\common;

use OpenFeature\interfaces\flags\EvaluationContext;
use stdClass;

use function array_merge;
use function is_null;
use function sizeof;

class EvaluationContextArrayFactory
{
    /**
     * @return mixed[]|stdClass|null
     */
    public static function build(?EvaluationContext $context)
    {
        if (is_null($context)) {
            $contextArray = null;
        } else {
            $contextArray = array_merge(
                [],
                $context->getAttributes()->toArray(),
                $context->getTargetingKey() ? ['targetingKey' => $context->getTargetingKey()] : [],
            );

            if (sizeof($contextArray) === 0) {
                return new stdClass();
            }
        }

        return $contextArray;
    }
}
