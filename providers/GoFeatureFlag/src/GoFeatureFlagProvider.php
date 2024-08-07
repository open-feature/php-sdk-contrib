<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag;

use OpenFeature\implementation\common\Metadata;
use OpenFeature\implementation\provider\AbstractProvider;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenFeature\Providers\GoFeatureFlag\controller\OfrepApi;
use OpenFeature\Providers\GoFeatureFlag\exception\BaseOfrepException;
use OpenFeature\Providers\GoFeatureFlag\exception\InvalidConfigException;
use OpenFeature\Providers\GoFeatureFlag\util\Validator;

class GoFeatureFlagProvider extends AbstractProvider implements Provider
{
    protected static string $CLIENT_NAME = 'GO Feature Flag Provider';
    private OfrepApi $ofrepApi;

    /**
     * @throws InvalidConfigException
     */
    public function __construct($config = null)
    {
        Validator::validateConfig($config);
        if (is_array($config->getCustomHeaders()) && !array_key_exists("Content-Type", $config->getCustomHeaders())) {
            $config->getCustomHeaders()["Content-Type"] = "application/json";
        }
        $this->ofrepApi = new OfrepApi($config);
    }

    public function getMetadata(): Metadata
    {
        return new Metadata(self::$CLIENT_NAME);
    }

    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->evaluate($flagKey, $defaultValue, ['boolean'], $context);
    }

    private function evaluate(string $flagKey, mixed $defaultValue, array $allowedClasses, ?EvaluationContext $evaluationContext = null): ResolutionDetails
    {
        try {
            Validator::validateEvaluationContext($evaluationContext);
            Validator::validateFlagKey($flagKey);
            $apiResp = $this->ofrepApi->evaluate($flagKey, $evaluationContext);

            if ($apiResp->isError()) {
                $err = new ResolutionError($apiResp->getErrorCode(), $apiResp->getErrorDetails());
                return (new ResolutionDetailsBuilder())
                    ->withValue($defaultValue)
                    ->withError($err)
                    ->withReason(Reason::ERROR)
                    ->build();
            }

            if (!$this->isValidType($apiResp->getValue(), $allowedClasses)) {
                return (new ResolutionDetailsBuilder())
                    ->withReason(Reason::ERROR)
                    ->withError(new ResolutionError(
                        ErrorCode::TYPE_MISMATCH(),
                        "Invalid type for $flagKey, got " . gettype($apiResp->getValue()) . " expected " . implode(", ", $allowedClasses)))
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
        } catch (\Exception $e) {
            return (new ResolutionDetailsBuilder())
                ->withValue($defaultValue)
                ->withError(new ResolutionError(ErrorCode::GENERAL(), "An error occurred while evaluating the flag: " . $e->getMessage()))
                ->withReason(Reason::ERROR)
                ->build();
        }
    }

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
