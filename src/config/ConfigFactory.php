<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

class ConfigFactory
{
    public static function fromOptions(?string $host = null, ?int $port = null, ?string $protocol = null, ?bool $secure = null, ?IHttpConfig $httpConfig): IConfig
    {
        return Validator::validate(new Config($host, $port, $protocol, $secure, $httpConfig));
    }

    /**
     * @param mixed[] $options
     */
    public static function fromArray(array $options): IConfig
    {
        return Validator::validate($options);
    }
}
