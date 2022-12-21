<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

class Defaults
{
    public const DEFAULT_SECURE = false;
    public const DEFAULT_HOST = 'localhost';
    public const DEFAULT_PORT = 8013;
    public const DEFAULT_PROTOCOL = Protocols::HTTP;
}
