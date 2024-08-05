<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd;

use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\config\Validator;
use OpenFeature\Providers\Flagd\service\ServiceFactory;
use OpenFeature\Providers\Flagd\service\ServiceInterface;
use OpenFeature\implementation\provider\AbstractProvider;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;

class FlagdProvider extends AbstractProvider implements Provider
{
    protected static string $NAME = 'FlagdProvider';

    private IConfig $config;

    private ServiceInterface $service;

    public function __construct(mixed $config = null)
    {
        $this->config = Validator::validate($config);

        $this->service = ServiceFactory::fromConfig($this->config);
    }

    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->service->resolveValue($flagKey, FlagValueType::BOOLEAN, $defaultValue, $context);
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->service->resolveValue($flagKey, FlagValueType::STRING, $defaultValue, $context);
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->service->resolveValue($flagKey, FlagValueType::INTEGER, $defaultValue, $context);
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->service->resolveValue($flagKey, FlagValueType::FLOAT, $defaultValue, $context);
    }

    public function resolveObjectValue(string $flagKey, mixed $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->service->resolveValue($flagKey, FlagValueType::OBJECT, $defaultValue, $context);
    }
}
