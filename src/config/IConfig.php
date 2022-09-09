<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

interface IConfig
{
    public function getService(): string;

    public function getHost(): string;

    public function getPort(): int;

    public function getProtocol(): string;
}
