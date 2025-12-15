<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\config;

use InvalidArgumentException;

use function trim;

class FlagsmithConfig
{
    public readonly string $apiKey;

    public function __construct(
        string $apiKey,
        public readonly ?string $apiUrl = null,
        public readonly ?object $customHeaders = null,
        public readonly ?int $requestTimeout = null,
        public readonly bool $useBooleanConfigValue = true,
    ) {
        if (trim($apiKey) === '') {
            throw new InvalidArgumentException('API key cannot be empty');
        }
        $this->apiKey = $apiKey;
    }
}
