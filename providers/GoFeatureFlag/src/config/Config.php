<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\config;

class Config
{
    private string $endpoint;
    private array $customHeaders = [];

    public function __construct(string $endpoint, ?string $apiKey = '', ?array $custom_headers = [])
    {
        $this->endpoint = $endpoint;
        $this->customHeaders = $custom_headers;
        if ($apiKey !== null && $apiKey !== '') {
            $this->customHeaders['Authorization'] = 'Bearer ' . $apiKey;
        }
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @return array
     */
    public function getCustomHeaders(): array
    {
        return $this->customHeaders;
    }
}
