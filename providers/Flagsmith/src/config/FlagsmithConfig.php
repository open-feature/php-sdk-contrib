<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\config;

use InvalidArgumentException;

class FlagsmithConfig
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->validateApiKey($apiKey);
        $this->apiKey = $apiKey;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    private function validateApiKey(string $apiKey): void
    {
        if (trim($apiKey) === '') {
            throw new InvalidArgumentException('API key cannot be empty');
        }
    }
}
