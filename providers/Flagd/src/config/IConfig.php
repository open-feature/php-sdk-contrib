<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

interface IConfig
{
    public function getHost(): string;

    public function getPort(): int;

    public function getProtocol(): string;

    public function isSecure(): bool;

    public function getHttpConfig(): ?IHttpConfig;
}
