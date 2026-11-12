<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\service;

use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\http\HttpService;

class ServiceFactory
{
    public static function fromConfig(IConfig $config): ServiceInterface
    {
        // @phan-suppress-next-line PhanNoopSwitchCases -- only HTTP is implemented; gRPC will add a case here
        switch ($config->getProtocol()) {
            default:
                return HttpService::fromConfig($config);
        }
    }
}
