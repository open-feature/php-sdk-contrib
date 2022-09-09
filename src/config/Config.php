<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

class Config implements IConfig
{
    private string $service;
    private string $host;
    private int $port;
    private string $protocol;

    public function __construct(?string $service = null, ?string $host = null, ?int $port = null, ?string $protocol = null)
    {
        $this->service = $service ?? Defaults::DEFAULT_SERVICE;
        $this->host = $host ?? Defaults::DEFAULT_HOST;
        $this->port = $port ?? Defaults::DEFAULT_PORT;
        $this->protocol = $protocol ?? Defaults::DEFAULT_PROTOCOL;
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }
}
