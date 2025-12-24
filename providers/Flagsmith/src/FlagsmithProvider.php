<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith;

use DateTime;
use Flagsmith\Exceptions\FlagsmithThrowable;
use Flagsmith\Flagsmith;
use Flagsmith\Models\Flags;
use InvalidArgumentException;
use JsonException;
use OpenFeature\implementation\provider\AbstractProvider;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionDetailsFactory;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\interfaces\provider\ResolutionDetails;

use function array_is_list;
use function is_array;
use function is_null;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

class FlagsmithProvider extends AbstractProvider implements Provider
{
    protected static string $NAME = 'Flagsmith';

    public function __construct(
        private Flagsmith $flagsmith,
    ) {
    }

    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        try {
            return ResolutionDetailsFactory::fromSuccess(
                $this->contextualFlagStore($context)->getFlag($flagKey)->getEnabled(),
            );
        } catch (FlagsmithThrowable $throwable) {
            return (new ResolutionDetailsBuilder())
                ->withValue($defaultValue)
                ->withError(
                    new ResolutionError(ErrorCode::GENERAL(), $throwable->getMessage()),
                )->build();
        }
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve($flagKey, $defaultValue, $context);
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve($flagKey, $defaultValue, $context);
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve($flagKey, $defaultValue, $context);
    }

    public function resolveObjectValue(string $flagKey, mixed $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        $builder = new ResolutionDetailsBuilder();

        try {
            $value = $this->resolve($flagKey, $defaultValue, $context)->getValue();

            if ($value !== $defaultValue && is_string($value)) {
                /** @var array<array-key, mixed>|bool|DateTime|float|int|string|null $value */
                $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
            }

            // Valid JSON document might not be an array, so error in this case.
            if (!is_array($value) || array_is_list($value)) {
                throw new InvalidArgumentException("Flag [$flagKey] value must be a JSON encoded array");
            }

            $builder->withValue($value);
        } catch (JsonException | InvalidArgumentException $exception) {
            $builder
                ->withValue($defaultValue)
                ->withError(
                    new ResolutionError(ErrorCode::PARSE_ERROR(), $exception->getMessage()),
                );
        }

        return $builder->build();
    }

    /**
     * @param array<array-key, mixed>|bool|DateTime|float|int|string|null $defaultValue
     */
    protected function resolve(
        string $flagKey,
        array | bool | DateTime | float | int | string | null $defaultValue,
        ?EvaluationContext $context = null,
    ): ResolutionDetails {
        $builder = new ResolutionDetailsBuilder();

        try {
            $flag = $this->contextualFlagStore($context)->getFlag($flagKey);

            if ($flag->getEnabled()) {
                /** @var array<array-key, mixed>|bool|DateTime|float|int|string|null $value */
                $value = $flag->getValue();

                $builder->withValue($value);
            } else {
                $builder
                    ->withValue($defaultValue)
                    ->withReason(Reason::DISABLED);
            }
        } catch (FlagsmithThrowable $throwable) {
            $builder
                ->withValue($defaultValue)
                ->withReason(Reason::ERROR)
                ->withError(
                    new ResolutionError(ErrorCode::GENERAL(), $throwable->getMessage()),
                );
        }

        return $builder->build();
    }

    /**
     * @throws FlagsmithThrowable
     */
    private function contextualFlagStore(?EvaluationContext $context = null): Flags
    {
        if ($context && !is_null($identifier = $context->getTargetingKey())) {
            return $this->flagsmith->getIdentityFlags($identifier, (object) $context->getAttributes()->toArray());
        }

        return $this->flagsmith->getEnvironmentFlags();
    }
}
