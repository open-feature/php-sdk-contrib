<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\DDTrace;

use DDTrace\Contracts\Span;
use DDTrace\GlobalTracer;
use DDTrace\Tag;
use DateTimeImmutable;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\hooks\Hook;
use OpenFeature\interfaces\hooks\HookContext;
use OpenFeature\interfaces\hooks\HookHints;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Throwable;

use function class_exists;

/**
 * Creates a DDTrace hook for OpenFeature. This emits a structured log event
 * containing information about the feature flag for the current trace.
 *
 * This generally follows the structure of the semantic convention of feature
 * flags in OpenTelemetry, as OpenTracing (which DDTrace is based upon) is
 * now an archived CNCF project.
 *
 * @see https://opentelemetry.io/docs/reference/specification/trace/semantic_conventions/feature-flags/
 * @see https://www.cncf.io/blog/2022/01/31/cncf-archives-the-opentracing-project/
 */
class DDTraceHook implements Hook
{
    private const FLAG_KEY = 'feature_flag.key';
    private const FLAG_PROVIDER_NAME = 'feature_flag.provider_name';
    private const FLAG_VARIANT = 'feature_flag.variant';

    /**
     * Registering the DDTraceHook can only be done against the global OpenFeatureAPI
     * instance. Thus, the only exposed functionality of the hook for instantiation is this
     * static invocation which will directly register itself at most once to the global API
     * hooks.
     */
    public static function register(): void
    {
        if (!self::$instance) {
            self::$instance = new DDTraceHook();
        }

        if (self::$registeredHook && self::isRegisteredInHooks()) {
            return;
        }

        OpenFeatureAPI::getInstance()->addHooks(self::$instance);
        self::$registeredHook = true;
    }

    private static ?DDTraceHook $instance = null;
    private static bool $registeredHook = false;

    public function before(HookContext $context, HookHints $hints): ?EvaluationContext
    {
        return null;
    }

    public function after(HookContext $context, ResolutionDetails $details, HookHints $hints): void
    {
        $span = self::getCurrentSpan();
        if (!$span) {
            return;
        }

        $span->log([
            Tag::LOG_MESSAGE => [
                self::FLAG_KEY => $context->getFlagKey(),
                self::FLAG_PROVIDER_NAME => OpenFeatureAPI::getInstance()->getProvider()->getMetadata()->getName(),
                self::FLAG_VARIANT => $details->getVariant(),
            ],
        ], new DateTimeImmutable('now'));
    }

    public function error(HookContext $context, Throwable $error, HookHints $hints): void
    {
        $span = self::getCurrentSpan();
        if (!$span) {
            return;
        }

        $span->log([
            Tag::LOG_ERROR_OBJECT => [
                self::FLAG_KEY => $context->getFlagKey(),
                self::FLAG_PROVIDER_NAME => OpenFeatureAPI::getInstance()->getProvider()->getMetadata()->getName(),
            ],
        ], new DateTimeImmutable('now'));
    }

    public function finally(HookContext $context, HookHints $hints): void
    {
        // no-op
    }

    public function supportsFlagValueType(string $flagValueType): bool
    {
        return true;
    }

    /**
     * Hooks can be cleared by other means so we can't simply memoize whether a registration has occurred
     *
     * However if no registration has yet happened then we can absolutely determine that the hook will
     * not be registered yet.
     */
    private static function isRegisteredInHooks(): bool
    {
        foreach (OpenFeatureAPI::getInstance()->getHooks() as $hook) {
            if ($hook instanceof DDTraceHook) {
                return true;
            }
        }

        return false;
    }

    private static function getCurrentSpan(): ?Span
    {
        if (!class_exists(GlobalTracer::class)) {
            return null;
        }

        return GlobalTracer::get()->getActiveSpan();
    }
}
