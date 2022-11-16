<?php

declare(strict_types=1);

namespace OpenFeature\Providers\CloudBees;

use Exception;
use OpenFeature\Providers\CloudBees\context\ContextAdapter;
use OpenFeature\Providers\CloudBees\transformers\IdentityTransformer;
use OpenFeature\Providers\CloudBees\transformers\JsonTransformer;
use OpenFeature\implementation\provider\AbstractProvider;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenFeature\interfaces\provider\ThrowableWithResolutionError;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Rox\Server\Rox;
use Rox\Server\RoxOptions;
use Throwable;

use function call_user_func;
use function is_bool;
use function is_null;
use function json_encode;

class CloudBeesProvider extends AbstractProvider implements Provider
{
    protected const NAME = 'CloudBeesProvider';

    private static ?CloudBeesProvider $instance = null;

    public static function setup(string $apiKey, ?RoxOptions $options = null): CloudBeesProvider
    {
        Rox::setup($apiKey, $options);

        if (is_null(self::$instance)) {
            self::$instance = new CloudBeesProvider();
        }

        return self::$instance;
    }

    public static function shutdown(): void
    {
        Rox::shutdown();
    }

    private function __construct()
    {
    }

    public function setLogger(LoggerInterface $logger)
    {
        // no-op
    }

    public function getLogger(): LoggerInterface
    {
        // no-op
        return new NullLogger();
    }

    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve(
            $defaultValue,
            fn () => Rox::dynamicApi()->isEnabled($flagKey, $defaultValue, ContextAdapter::adapt($context)),
        );
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve(
            $defaultValue,
            fn () => Rox::dynamicApi()->getValue($flagKey, $defaultValue, [/* TODO: Variations */], ContextAdapter::adapt($context)),
        );
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve(
            $defaultValue,
            fn () => Rox::dynamicApi()->getInt($flagKey, $defaultValue, [/* TODO: Variations */], ContextAdapter::adapt($context)),
        );
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolve(
            $defaultValue,
            fn () => Rox::dynamicApi()->getDouble($flagKey, $defaultValue, [/* TODO: Variations */], ContextAdapter::adapt($context)),
        );
    }

    /**
     * @param mixed[] $defaultValue
     */
    public function resolveObjectValue(string $flagKey, $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        $stringifiedDefault = json_encode($defaultValue);

        if (is_bool($stringifiedDefault)) {
            throw new Exception('Invalid default value');
        }

        return $this->resolve(
            $defaultValue,
            fn () => Rox::dynamicApi()->getValue($flagKey, $stringifiedDefault, [/* TODO: Variations */], ContextAdapter::adapt($context)),
            new JsonTransformer(),
        );
    }

    /**
     * @param bool|string|int|float|mixed[] $defaultValue
     */
    private function resolve($defaultValue, callable $fn, ?callable $transformer = null): ResolutionDetails
    {
        if (is_null($transformer)) {
            $transformer = new IdentityTransformer();
        }

        try {
            /** @var bool|string|int|float $value */
            $value = call_user_func($fn);

            /** @var bool|string|int|float|mixed[] $transformed */
            $transformed = call_user_func($transformer, $value);

            return (new ResolutionDetailsBuilder())
                        ->withValue($transformed)
                        ->build();
        } catch (Throwable $err) {
            $detailsBuilder = new ResolutionDetailsBuilder();

            $detailsBuilder->withValue($defaultValue);

            if ($err instanceof ThrowableWithResolutionError) {
                $detailsBuilder->withError($err->getResolutionError());
            } else {
                $detailsBuilder->withError(new ResolutionError(ErrorCode::GENERAL(), $err->getMessage()));
            }

            return $detailsBuilder->build();
        }
    }
}
