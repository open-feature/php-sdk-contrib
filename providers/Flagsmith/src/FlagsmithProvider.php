<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith;

use Flagsmith\Flagsmith;
use OpenFeature\implementation\common\Metadata;
use OpenFeature\implementation\provider\AbstractProvider;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenFeature\Providers\Flagsmith\config\FlagsmithConfig;
use OpenFeature\Providers\Flagsmith\service\ContextMapper;
use OpenFeature\Providers\Flagsmith\service\FlagEvaluator;
use Psr\Log\LoggerInterface;

class FlagsmithProvider extends AbstractProvider
{
    private Flagsmith $flagsmithClient;
    private ContextMapper $contextMapper;
    private FlagEvaluator $evaluator;
    private array $hooks = [];

    public function __construct(
        FlagsmithConfig $config,
        ?Flagsmith $flagsmithClient = null,
        ?ContextMapper $contextMapper = null,
        ?FlagEvaluator $evaluator = null
    ) {
        // Initialize Flagsmith client from config (or use injected one for testing)
        $this->flagsmithClient = $flagsmithClient ?? new Flagsmith(
            $config->getApiKey(),
            $config->getApiUrl(),
            $config->getCustomHeaders(),
            $config->getRequestTimeout()
        );

        // Initialize services (or use injected ones for testing)
        $this->contextMapper = $contextMapper ?? new ContextMapper();
        $this->evaluator = $evaluator ?? new FlagEvaluator($this->flagsmithClient);
    }

    public function getMetadata(): Metadata
    {
        return new Metadata('FlagsmithProvider');
    }

    public function resolveBooleanValue(
        string $flagKey,
        bool $defaultValue,
        ?EvaluationContext $context = null
    ): ResolutionDetails {
        $mapped = $this->contextMapper->map($context);
        return $this->evaluator->evaluateBoolean(
            $flagKey,
            $defaultValue,
            $mapped['identifier'],
            $mapped['traits']
        );
    }

    public function resolveStringValue(
        string $flagKey,
        string $defaultValue,
        ?EvaluationContext $context = null
    ): ResolutionDetails {
        $mapped = $this->contextMapper->map($context);
        return $this->evaluator->evaluateString(
            $flagKey,
            $defaultValue,
            $mapped['identifier'],
            $mapped['traits']
        );
    }

    public function resolveIntegerValue(
        string $flagKey,
        int $defaultValue,
        ?EvaluationContext $context = null
    ): ResolutionDetails {
        $mapped = $this->contextMapper->map($context);
        return $this->evaluator->evaluateInteger(
            $flagKey,
            $defaultValue,
            $mapped['identifier'],
            $mapped['traits']
        );
    }

    public function resolveFloatValue(
        string $flagKey,
        float $defaultValue,
        ?EvaluationContext $context = null
    ): ResolutionDetails {
        $mapped = $this->contextMapper->map($context);
        return $this->evaluator->evaluateFloat(
            $flagKey,
            $defaultValue,
            $mapped['identifier'],
            $mapped['traits']
        );
    }

    public function resolveObjectValue(
        string $flagKey,
        array $defaultValue,
        ?EvaluationContext $context = null
    ): ResolutionDetails {
        $mapped = $this->contextMapper->map($context);
        return $this->evaluator->evaluateObject(
            $flagKey,
            $defaultValue,
            $mapped['identifier'],
            $mapped['traits']
        );
    }

    public function getHooks(): array
    {
        return $this->hooks;
    }

    public function setHooks(array $hooks): void
    {
        $this->hooks = $hooks;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        // Logger support can be added in the future
    }
}
