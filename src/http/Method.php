<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

use MyCLabs\Enum\Enum;

/**
 * Http Method
 *
 * @see https://github.com/open-feature/spec/blob/main/specification/types.md#error-code
 *
 * @method static Method | string GET()
 * @method static Method | string HEAD()
 * @method static Method | string POST()
 * @method static Method | string PUT()
 * @method static Method | string DELETE()
 * @method static Method | string CONNECT()
 * @method static Method | string OPTIONS()
 * @method static Method | string TRACE()
 * @method static Method | string PATCH()
 *
 * @extends Enum<string>
 *
 * @psalm-immutable
 */
final class Method extends Enum
{
    private const GET = "GET";
    private const HEAD = "HEAD";
    private const POST = "POST";
    private const PUT = "PUT";
    private const DELETE = "DELETE";
    private const CONNECT = "CONNECT";
    private const OPTIONS = "OPTIONS";
    private const TRACE = "TRACE";
    private const PATCH = "PATCH";
}
