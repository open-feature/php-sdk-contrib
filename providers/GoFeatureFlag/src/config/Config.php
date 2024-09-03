<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\config;

use Psr\Http\Client\ClientInterface;

class Config
{
    private string $endpoint;
    /**
     * @var array<string, string>
     */
    private array $customHeaders = [];

    /**
     * @var ClientInterface|null - The HTTP Client to use (if you want to use a custom one)
     */
    private ?ClientInterface $httpclient;

    /**
     * @param string $endpoint - The endpoint to your GO Feature Flag Instance
     * @param string|null $apiKey - API Key to use to connect to GO Feature Flag
     * @param array<string, string>|null $customHeaders - Custom headers you want to send
     * @param ClientInterface|null $httpclient - The HTTP Client to use (if you want to use a custom one)
     */
    public function __construct(string $endpoint, ?string $apiKey = '', ?array $customHeaders = [], ?ClientInterface $httpclient = null)
    {
        $this->httpclient = $httpclient;
        $this->endpoint = $endpoint;
        $this->customHeaders = $customHeaders ?? [];
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

    public function addCustomHeader(string $key, string $value): void
    {
        $this->customHeaders[$key] = $value;
    }

    public function getHttpClient(): ?ClientInterface
    {
        return $this->httpclient;
    }
}
