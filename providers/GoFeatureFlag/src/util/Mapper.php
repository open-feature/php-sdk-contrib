<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\util;

use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Reason;

class Mapper
{
    public static function errorCode(string $errorCode): ErrorCode
    {
        return match ($errorCode) {
            'PROVIDER_NOT_READY' => ErrorCode::PROVIDER_NOT_READY(),
            'FLAG_NOT_FOUND' => ErrorCode::FLAG_NOT_FOUND(),
            'PARSE_ERROR' => ErrorCode::PARSE_ERROR(),
            'TYPE_MISMATCH' => ErrorCode::TYPE_MISMATCH(),
            'TARGETING_KEY_MISSING' => ErrorCode::TARGETING_KEY_MISSING(),
            'INVALID_CONTEXT' => ErrorCode::INVALID_CONTEXT(),
            default => ErrorCode::GENERAL()
        };
    }

    public static function reason(string $reason): string
    {
        return match ($reason) {
            'ERROR' => Reason::ERROR,
            'DEFAULT' => Reason::DEFAULT,
            'TARGETING_MATCH' => Reason::TARGETING_MATCH,
            'SPLIT' => Reason::SPLIT,
            'DISABLED' => Reason::DISABLED,
            default => Reason::UNKNOWN
        };
    }
}
