<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\config;

use InvalidArgumentException;

use function trim;

class FlagsmithConfig
{
    private string $apiKey;
    private ?string $apiUrl;
    private ?object $customHeaders;
    private ?int $requestTimeout;

    public function __construct(
        string $apiKey,
        ?string $apiUrl = null,
        ?object $customHeaders = null,
        ?int $requestTimeout = null,
    ) {
        $this->validateApiKey($apiKey);
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->customHeaders = $customHeaders;
        $this->requestTimeout = $requestTimeout;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getApiUrl(): ?string
    {
        return $this->apiUrl;
    }

    public function getCustomHeaders(): ?object
    {
        return $this->customHeaders;
    }

    public function getRequestTimeout(): ?int
    {
        return $this->requestTimeout;
    }

    private function validateApiKey(string $apiKey): void
    {
        if (trim($apiKey) === '') {
            throw new InvalidArgumentException('API key cannot be empty');
        }
    }
}
