<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

interface IHttpConfig
{
    public function getClient(): ClientInterface;

    public function getRequestFactory(): RequestFactoryInterface;
}
