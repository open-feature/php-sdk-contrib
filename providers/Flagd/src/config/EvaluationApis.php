<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

/**
 * Supported flagd flag evaluation APIs.
 *
 * V1 targets the legacy `schema.v1.Service`, which is supported by every flagd release.
 *
 * V2 targets `flagd.evaluation.v2.Service`, available from flagd v0.14.0. Its response
 * messages declare `value` and `variant` as `optional`, so an absent value is represented
 * explicitly on the wire rather than being zero-filled. From flagd v0.16.0 this lets the
 * provider detect a disabled flag by field presence instead of inferring it from the
 * reason and an empty variant.
 */
class EvaluationApis
{
    public const V1 = 'v1';
    public const V2 = 'v2';
}
