<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\OpenTelemetry;

use OpenFeature\OpenFeatureAPI;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\hooks\Hook;
use OpenFeature\interfaces\hooks\HookContext;
use OpenFeature\interfaces\hooks\HookHints;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenTelemetry\API\Trace\Span;
use Throwable;

/**
 * Creates an OpenTelemetry hook for OpenFeature that follows the semantic
 * convention for feature flags. This emits a structured event containing
 * information about the feature flag for the current span.
 *
 * See the referenced
 * documentation for more information on the structure of this event.
 *
 * @see https://opentelemetry.io/docs/reference/specification/trace/semantic_conventions/feature-flags/
 */
class OpenTelemetryHook implements Hook
{
    private const EVENT_NAME = 'feature_flag';
    private const FLAG_KEY = 'feature_flag.key';
    private const FLAG_PROVIDER_NAME = 'feature_flag.provider_name';
    private const FLAG_VARIANT = 'feature_flag.variant';

    /**
     * Registering the OpenTelemetryHook can only be done against the global OpenFeatureAPI
     * instance. Thus, the only exposed functionality of the hook for instantiation is this
     * static invocation which will directly register itself at most once to the global API
     * hooks.
     */
    public static function register(): void
    {
        if (!self::$instance) {
            self::$instance = new OpenTelemetryHook();
        }

        if (self::$registeredHook) {
            return;
        }

        OpenFeatureAPI::getInstance()->addHooks(self::$instance);
        self::$registeredHook = true;
    }

    private static ?OpenTelemetryHook $instance = null;
    private static bool $registeredHook = false;

    public function before(HookContext $context, HookHints $hints): ?EvaluationContext
    {
        return null;
    }

    public function after(HookContext $context, ResolutionDetails $details, HookHints $hints): void
    {
        $span = Span::getCurrent();

        $span->addEvent(self::EVENT_NAME, [
            self::FLAG_KEY => $context->getFlagKey(),
            self::FLAG_PROVIDER_NAME => OpenFeatureAPI::getInstance()->getProvider()->getMetadata()->getName(),
            self::FLAG_VARIANT => $details->getVariant(),
        ]);
    }

    public function error(HookContext $context, Throwable $error, HookHints $hints): void
    {
        $span = Span::getCurrent();

        $span->recordException($error, [
            self::FLAG_KEY => $context->getFlagKey(),
            self::FLAG_PROVIDER_NAME => OpenFeatureAPI::getInstance()->getProvider()->getMetadata()->getName(),
        ]);
    }

    public function finally(HookContext $context, HookHints $hints): void
    {
        // no-op
    }

    public function supportsFlagValueType(string $flagValueType): bool
    {
        return true;
    }
}
