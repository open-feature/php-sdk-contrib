<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

use function array_key_exists;
use function is_array;
use function is_null;

class FlagdResponseValidator
{
    /**
     * @param mixed[] $response
     */
    public static function isTypeMismatch(?array $response): bool
    {
        return is_null($response);
    }

    /**
     * @param mixed[] $response
     */
    public static function isErrorResponse(?array $response): bool
    {
        return !isset($response['value']);
    }

    /**
     * On the v2 evaluation API an absent `value` is a valid resolution rather than an error,
     * so error detection keys on the Connect error envelope instead.
     *
     * @param mixed[] $response
     */
    public static function isErrorResponseV2(?array $response): bool
    {
        return isset($response['code']);
    }

    /**
     * v2 declares `value` as an `optional` protobuf field, so it is omitted from the payload
     * entirely when the flag resolves without one (a disabled flag, or a flag with no default
     * variant). v1 cannot express this and zero-fills the field instead.
     *
     * @param mixed[] $response
     */
    public static function hasNoValue(?array $response): bool
    {
        return !is_array($response) || !array_key_exists('value', $response);
    }
}
