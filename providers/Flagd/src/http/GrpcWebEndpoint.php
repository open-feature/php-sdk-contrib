<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

/**
 * gRPC Web Endpoints
 */
class GrpcWebEndpoint
{
    public const BOOLEAN = 'schema.v1.Service/ResolveBoolean';
    public const STRING = 'schema.v1.Service/ResolveString';
    public const FLOAT = 'schema.v1.Service/ResolveFloat';
    public const INTEGER = 'schema.v1.Service/ResolveInt';
    public const OBJECT = 'schema.v1.Service/ResolveObject';
}
