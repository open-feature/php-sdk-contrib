<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

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
     * An absent `value` is a valid resolution rather than an error, so error detection keys on
     * the Connect error envelope instead.
     *
     * @param mixed[] $response
     */
    public static function isErrorResponse(?array $response): bool
    {
        return isset($response['code']);
    }
}
