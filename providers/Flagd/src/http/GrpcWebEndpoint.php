<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

/**
 * gRPC Web Endpoints for flagd's `flagd.evaluation.v2.Service` (requires flagd v0.16.0+).
 */
class GrpcWebEndpoint
{
    public const BOOLEAN = 'flagd.evaluation.v2.Service/ResolveBoolean';
    public const STRING = 'flagd.evaluation.v2.Service/ResolveString';
    public const FLOAT = 'flagd.evaluation.v2.Service/ResolveFloat';
    public const INTEGER = 'flagd.evaluation.v2.Service/ResolveInt';
    public const OBJECT = 'flagd.evaluation.v2.Service/ResolveObject';
}
