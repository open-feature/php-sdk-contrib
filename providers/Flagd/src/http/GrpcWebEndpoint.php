<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

/**
 * gRPC Web Endpoints
 *
 * The RPC names are identical across evaluation APIs; only the service path differs.
 */
class GrpcWebEndpoint
{
    public const BOOLEAN = 'schema.v1.Service/ResolveBoolean';
    public const STRING = 'schema.v1.Service/ResolveString';
    public const FLOAT = 'schema.v1.Service/ResolveFloat';
    public const INTEGER = 'schema.v1.Service/ResolveInt';
    public const OBJECT = 'schema.v1.Service/ResolveObject';

    /**
     * `flagd.evaluation.v2.Service`, available from flagd v0.14.0.
     */
    public const BOOLEAN_V2 = 'flagd.evaluation.v2.Service/ResolveBoolean';
    public const STRING_V2 = 'flagd.evaluation.v2.Service/ResolveString';
    public const FLOAT_V2 = 'flagd.evaluation.v2.Service/ResolveFloat';
    public const INTEGER_V2 = 'flagd.evaluation.v2.Service/ResolveInt';
    public const OBJECT_V2 = 'flagd.evaluation.v2.Service/ResolveObject';
}
