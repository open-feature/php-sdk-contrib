<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\OpenTelemetry;

use OpenTelemetry\API\Common\Instrumentation\Globals;
use OpenTelemetry\API\Trace\TracerInterface;
use Psr\Container\ContainerInterface;

class TracerHelper
{
    /**
     * Determines a non-null Tracer to return based on a given input, allowing
     * multiple types including a PSR-11 container, Tracer, or null
     *
     * @param ContainerInterface|TracerInterface|null $maybeTracerOrContainer
     */
    public static function determineTracer($maybeTracerOrContainer = null): TracerInterface
    {
        if ($maybeTracerOrContainer instanceof TracerInterface) {
            return $maybeTracerOrContainer;
        }

        if ($maybeTracerOrContainer instanceof ContainerInterface) {
            $container = $maybeTracerOrContainer;

            if ($container->has(TracerInterface::class)) {
                /** @var TracerInterface|null $maybeTracer */
                $maybeTracer = $container->get(TracerInterface::class);

                if ($maybeTracer instanceof TracerInterface) {
                    return $maybeTracer;
                }
            }
        }

        return Globals::tracerProvider()->getTracer('open-feature/otel-hook', '0.0.1');
    }
}
