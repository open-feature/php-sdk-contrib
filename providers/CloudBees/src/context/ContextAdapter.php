<?php

declare(strict_types=1);

namespace OpenFeature\Providers\CloudBees\context;

use OpenFeature\interfaces\flags\EvaluationContext;
use Rox\Core\Context\ContextImp;
use Rox\Core\Context\ContextInterface;

use function array_filter;
use function array_merge;
use function is_null;

class ContextAdapter
{
    public static function adapt(?EvaluationContext $context): ?ContextInterface
    {
        return is_null($context)
            ? null
            : new ContextImp(
                array_filter(
                    array_merge(
                        $context->getAttributes()->toArray(),
                        ['targetingKey' => $context->getTargetingKey()],
                    ),
                    /**
                     * @param mixed $value
                     */
                    fn ($value) => !is_null($value),
                ),
            );
    }
}
