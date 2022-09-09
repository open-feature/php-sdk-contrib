<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

use function in_array;
use function is_array;
use function preg_match;

class Validator
{
    private const VALID_SERVICES = ['http', 'grpc'];
    private const VALID_HOST_REGEXP = '/^(([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])\.)*([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])$/i';
    private const VALID_PORT_RANGE = [1, 65535];
    private const VALID_PROTOCOLS = ['http', 'https'];

    /**
     * @param mixed[] $config
     */
    public static function validate($config = null): IConfig
    {
        if ($config instanceof IConfig) {
            return self::validateConfig($config);
        }

        if (is_array($config)) {
            return self::validateArray($config);
        }

        return self::validateArray();
    }

    /**
     * @param mixed[] $config
     */
    private static function validateArray(array $config = []): IConfig
    {
        $service = self::validateService($config['service'] ?? null);
        $host = self::validateHost($config['host'] ?? null);
        $port = self::validatePort($config['port'] ?? null);
        $protocol = self::validateProtocol($config['protocol'] ?? null);

        return new Config($service, $host, $port, $protocol);
    }

    private static function validateConfig(IConfig $config): IConfig
    {
        $service = self::validateService($config->getService());
        $host = self::validateHost($config->getHost());
        $port = self::validatePort($config->getPort());
        $protocol = self::validateProtocol($config->getProtocol());

        return new Config($service, $host, $port, $protocol);
    }

    /**
     * @param mixed $service
     */
    private static function validateService($service): string
    {
        if (in_array($service, self::VALID_SERVICES)) {
            return $service;
        }

        return Defaults::DEFAULT_SERVICE;
    }

    /**
     * @param mixed $host
     */
    private static function validateHost($host): string
    {
        if (preg_match(self::VALID_HOST_REGEXP, $host)) {
            return $host;
        }

        return Defaults::DEFAULT_HOST;
    }

    /**
     * @param mixed $port
     */
    private static function validatePort($port): int
    {
        [$minPort, $maxPort] = self::VALID_PORT_RANGE;

        if ($port >= $minPort && $port <= $maxPort) {
            return $port;
        }

        return Defaults::DEFAULT_PORT;
    }

    /**
     * @param mixed $protocol
     */
    private static function validateProtocol($protocol): string
    {
        if (in_array($protocol, self::VALID_PROTOCOLS)) {
            return $protocol;
        }

        return Defaults::DEFAULT_PROTOCOL;
    }
}
