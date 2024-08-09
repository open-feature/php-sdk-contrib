<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\config;

class Config
{
    private string $endpoint;
    /**
     * @var array<string, string>
     */
    private array $customHeaders = [];

    public function __construct(string $endpoint, ?string $apiKey = '', ?array $customHeaders = [])
    {
        $this->endpoint = $endpoint;
        $this->customHeaders = $customHeaders;
        if ($apiKey !== null && $apiKey !== '') {
            $this->customHeaders['Authorization'] = 'Bearer ' . $apiKey;
        }
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @return array<string, string>
     */
    public function getCustomHeaders(): array
    {
        return $this->customHeaders;
    }
}
