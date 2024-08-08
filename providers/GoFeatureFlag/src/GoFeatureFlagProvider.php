<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag;

use DateTime;
use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\controller\OfrepApi;
use OpenFeature\Providers\GoFeatureFlag\exception\BaseOfrepException;
use OpenFeature\Providers\GoFeatureFlag\exception\InvalidConfigException;
use OpenFeature\Providers\GoFeatureFlag\exception\InvalidContextException;
use OpenFeature\Providers\GoFeatureFlag\model\OfrepApiErrorResponse;
use OpenFeature\Providers\GoFeatureFlag\util\Validator;
use OpenFeature\implementation\common\Metadata;
use OpenFeature\implementation\provider\AbstractProvider;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Throwable;

use function array_key_exists;
use function gettype;
use function implode;

class GoFeatureFlagProvider extends AbstractProvider implements Provider
{
    protected static string $NAME = 'GO Feature Flag Provider';
    private OfrepApi $ofrepApi;

    /**
     * @throws InvalidConfigException
     */
    public function __construct(Config $config)
    {
        Validator::validateConfig($config);
        if (!array_key_exists('Content-Type', $config->getCustomHeaders())) {
            $config->addCustomHeader('Content-Type', 'application/json');
        }
        $this->ofrepApi = new OfrepApi($config);
    }

    public function getMetadata(): Metadata
    {
        return new Metadata(static::$NAME);
    }

    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->evaluate($flagKey, $defaultValue, ['boolean'], $context);
    }

    /**
     * @param array<mixed>|array<string, mixed>|bool|DateTime|float|int|string|null $defaultValue
     * @param array<string> $allowedClasses
     */
    private function evaluate(string $flagKey, array | string | bool | DateTime | float | int | null $defaultValue, array $allowedClasses, ?EvaluationContext $evaluationContext = null): ResolutionDetails
    {
        try {
            Validator::validateFlagKey($flagKey);

            if ($evaluationContext === null) {
                throw new InvalidContextException('Evaluation context is null');
            }
            if ($evaluationContext->getTargetingKey() === null || $evaluationContext->getTargetingKey() === '') {
                throw new InvalidContextException('Missing targetingKey in evaluation context');
            }

            $apiResp = $this->ofrepApi->evaluate($flagKey, $evaluationContext);

            if ($apiResp instanceof OfrepApiErrorResponse) {
                $err = new ResolutionError(
                    $apiResp->getErrorCode(),
                    $apiResp->getErrorDetails(),
                );

                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError($err)
                    ->withReason($apiResp->getReason())
                    ->build();
            }

            if (!$this->isValidType($apiResp->getValue(), $allowedClasses)) {
                return (new ResolutionDetailsBuilder())
                    ->withReason(Reason::ERROR)
                    ->withError(new ResolutionError(
                        ErrorCode::TYPE_MISMATCH(),
                        "Invalid type for $flagKey, got " . gettype($apiResp->getValue()) . ' expected ' . implode(', ', $allowedClasses),
                    ))
                    ->withValue($defaultValue)
                    ->build();
            }

            return (new ResolutionDetailsBuilder())
                ->withValue($apiResp->getValue())
                ->withReason($apiResp->getReason())
                ->withVariant($apiResp->getVariant())
                ->build();
        } catch (BaseOfrepException $e) {
            $err = new ResolutionError($e->getErrorCode(), $e->getMessage());

            return (new ResolutionDetailsBuilder())
                ->withValue($defaultValue)
                ->withError($err)
                ->withReason(Reason::ERROR)
                ->build();
        } catch (Throwable $e) {
            return (new ResolutionDetailsBuilder())
                ->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::GENERAL(), 'An error occurred while evaluating the flag: ' . $e->getMessage()))
                ->withReason(Reason::ERROR)
                ->build();
        }
    }

    /**
     * @param array<string> $allowedClasses
     */
    private function isValidType(mixed $value, array $allowedClasses): bool
    {
        foreach ($allowedClasses as $class) {
            if ($value instanceof $class || gettype($value) === $class) {
                return true;
            }
        }

        return false;
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->evaluate($flagKey, $defaultValue, ['string'], $context);
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->evaluate($flagKey, $defaultValue, ['integer'], $context);
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->evaluate($flagKey, $defaultValue, ['double'], $context);
    }

    public function resolveObjectValue(string $flagKey, mixed $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->evaluate($flagKey, $defaultValue, ['array'], $context);
    }
}
