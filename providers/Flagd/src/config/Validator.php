<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_null;
use function is_string;
use function preg_match;

class Validator
{
    private const VALID_HOST_REGEXP = '/^(([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])\.)*([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])$/i';
    private const VALID_PORT_RANGE = [1, 65535];
    private const VALID_PROTOCOLS = ['grpc', 'http'];

    public static function validate(mixed $config = null): IConfig
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
        $host = self::validateHost($config['host'] ?? null);
        $port = self::validatePort($config['port'] ?? null);
        $protocol = self::validateProtocol($config['protocol'] ?? null);
        $secure = self::validateSecure($config['secure'] ?? null);
        $httpConfig = self::validateHttpConfig($config['httpConfig'] ?? null);

        return new Config($host, $port, $protocol, $secure, $httpConfig);
    }

    private static function validateConfig(IConfig $config): IConfig
    {
        $host = self::validateHost($config->getHost());
        $port = self::validatePort($config->getPort());
        $protocol = self::validateProtocol($config->getProtocol());
        $secure = self::validateSecure($config->isSecure());
        $httpConfig = self::validateHttpConfig($config->getHttpConfig());

        return new Config($host, $port, $protocol, $secure, $httpConfig);
    }

    private static function validateSecure(mixed $secure): bool
    {
        if (is_bool($secure)) {
            return $secure;
        }

        return Defaults::DEFAULT_SECURE;
    }

    private static function validateHost(mixed $host): string
    {
        if (is_string($host) && preg_match(self::VALID_HOST_REGEXP, $host)) {
            return $host;
        }

        return Defaults::DEFAULT_HOST;
    }

    private static function validatePort(mixed $port): int
    {
        [$minPort, $maxPort] = self::VALID_PORT_RANGE;

        if (is_int($port) && $port >= $minPort && $port <= $maxPort) {
            return $port;
        }

        return Defaults::DEFAULT_PORT;
    }

    private static function validateProtocol(mixed $protocol): string
    {
        if (is_string($protocol) && in_array($protocol, self::VALID_PROTOCOLS)) {
            return $protocol;
        }

        return Defaults::DEFAULT_PROTOCOL;
    }

    private static function validateHttpConfig(mixed $httpConfig): ?IHttpConfig
    {
        if (is_null($httpConfig)) {
            return null;
        }

        if (is_array($httpConfig)) {
            /** @var ClientInterface|mixed $client */
            $client = $httpConfig['client'];
            /** @var RequestFactoryInterface|mixed $requestFactory */
            $requestFactory = $httpConfig['requestFactory'];
            /** @var StreamFactoryInterface|mixed $streamFactory */
            $streamFactory = $httpConfig['streamFactory'];

            if (
                $client instanceof ClientInterface
                && $requestFactory instanceof RequestFactoryInterface
                && $streamFactory instanceof StreamFactoryInterface
            ) {
                return new HttpConfig($client, $requestFactory, $streamFactory);
            }
        }

        if ($httpConfig instanceof IHttpConfig) {
            return $httpConfig;
        }

        return null;
    }
}
