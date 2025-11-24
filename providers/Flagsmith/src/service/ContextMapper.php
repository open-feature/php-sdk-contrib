<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\service;

use OpenFeature\interfaces\flags\EvaluationContext;

class ContextMapper
{
    /**
     * Maps OpenFeature EvaluationContext to Flagsmith format.
     *
     * @return array{identifier: ?string, traits: ?object}
     */
    public function map(?EvaluationContext $context): array
    {
        if ($context === null) {
            return [
                'identifier' => null,
                'traits' => null,
            ];
        }

        $identifier = $context->getTargetingKey();
        $attributes = $context->getAttributes();

        $traits = null;
        if ($attributes !== null && count($attributes->toArray()) > 0) {
            $traits = (object) $attributes->toArray();
        }

        return [
            'identifier' => $identifier,
            'traits' => $traits,
        ];
    }
}
