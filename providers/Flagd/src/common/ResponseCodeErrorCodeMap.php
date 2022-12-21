<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\common;

use OpenFeature\interfaces\provider\ErrorCode;

use function array_key_exists;

class ResponseCodeErrorCodeMap
{
    /** @var ErrorCode[] $keys */
    private static array $keys;
    private static bool $initialized = false;

    public static function has(string $value): bool
    {
        self::init();

        return array_key_exists($value, self::$keys);
    }

    public static function get(string $value): ?ErrorCode
    {
        if (self::has($value)) {
            return self::$keys[$value];
        }

        return null;
    }

    private static function init(): void
    {
        if (!self::$initialized) {
            self::$keys = [
                'not_found' => ErrorCode::FLAG_NOT_FOUND(),
                'invalid_argument' => ErrorCode::TYPE_MISMATCH(),
            ];

            self::$initialized = true;
        }
    }
}
