<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd;

use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\config\Validator;
use OpenFeature\implementation\common\Metadata;
use OpenFeature\implementation\provider\ResolutionDetailsFactory;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\hooks\Hook;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Psr\Log\LoggerAwareTrait;

class FlagdProvider implements Provider
{
    use LoggerAwareTrait;

    /** @var Hook[] $hooks */
    private array $hooks = [];

    private IConfig $config;

    /**
     * @param mixed|IConfig|mixed[] $config
     */
    public function __construct($config = null)
    {
        $this->config = Validator::validate($config);
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
        // TODO: Implement
        return ResolutionDetailsFactory::fromSuccess($defaultValue);
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        // TODO: Implement
        return ResolutionDetailsFactory::fromSuccess($defaultValue);
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        // TODO: Implement
        return ResolutionDetailsFactory::fromSuccess($defaultValue);
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        // TODO: Implement
        return ResolutionDetailsFactory::fromSuccess($defaultValue);
    }

    /**
     * @param mixed[] $defaultValue
     */
    public function resolveObjectValue(string $flagKey, $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        // TODO: Implement
        return ResolutionDetailsFactory::fromSuccess($defaultValue);
    }
}
