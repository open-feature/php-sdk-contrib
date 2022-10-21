<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd;

use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\config\Validator;
use OpenFeature\implementation\common\Metadata;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\hooks\Hook;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenFeature\Providers\Flagd\service\ServiceFactory;
use OpenFeature\Providers\Flagd\service\ServiceInterface;
use Psr\Log\LoggerAwareTrait;

class FlagdProvider implements Provider
{
    use LoggerAwareTrait;

    /** @var Hook[] $hooks */
    private array $hooks = [];

    private IConfig $config;

    private ServiceInterface $service;

    /**
     * @param mixed|IConfig|mixed[] $config
     */
    public function __construct($config = null)
    {
        $this->config = Validator::validate($config);

        $this->service = ServiceFactory::fromConfig($config);
    }

    /**
     * @inheritdoc
     */
    public function getHooks(): array
    {
        return $this->hooks;
    }

    public function getMetadata(): Metadata
    {
        return new Metadata('flagd');
    }

    public function getEvaluationContext(): ?EvaluationContext
    {
        return $this->evaluationContext;
    }

    public function setEvaluationContext(EvaluationContext $context): void
    {
        $this->evaluationContext = $context;
    }

    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->service->resolvevalue($flagKey, FlagValueType::BOOLEAN, $defaultValue, $context);
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->service->resolvevalue($flagKey, FlagValueType::STRING, $defaultValue, $context);
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->service->resolvevalue($flagKey, FlagValueType::INTEGER, $defaultValue, $context);
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->service->resolvevalue($flagKey, FlagValueType::FLOAT, $defaultValue, $context);
    }

    /**
     * @param mixed[] $defaultValue
     */
    public function resolveObjectValue(string $flagKey, $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->service->resolvevalue($flagKey, FlagValueType::OBJECT, $defaultValue, $context);
    }
}
