<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

interface IHttpConfig
{
    public function getClient(): ClientInterface;

    // TODO: Support async client
    // public function getAsyncClient(): ?HttpAsyncClient;

    public function getRequestFactory(): RequestFactoryInterface;

    public function getStreamFactory(): StreamFactoryInterface;
}
