<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\Test\e2e\bootstrap;

use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
use Testcontainers\Container\GenericContainer;
use Testcontainers\Container\StartedGenericContainer;
use Throwable;

use function file_get_contents;
use function rtrim;
use function sprintf;
use function usleep;

/**
 * Boots the flagd testbed image and drives its launchpad so flagd is serving the
 * default flag set before tests run. Only the RPC/HTTP evaluation port (8013) is
 * needed; the PHP provider is HTTP-only, so no gRPC or sync ports are wired up.
 */
final class FlagdTestbedContainer
{
    private const IMAGE = 'ghcr.io/open-feature/flagd-testbed';
    private const EVALUATION_PORT = 8013;
    private const HEALTH_PORT = 8014;
    private const LAUNCHPAD_PORT = 8080;

    private StartedGenericContainer $container;
    private string $host;
    private int $evaluationPort;

    private function __construct(StartedGenericContainer $container, string $host, int $evaluationPort)
    {
        $this->container = $container;
        $this->host = $host;
        $this->evaluationPort = $evaluationPort;
    }

    public static function start(): self
    {
        $image = sprintf('%s:v%s', self::IMAGE, self::resolveVersion());

        $started = (new GenericContainer($image))
            ->withExposedPorts(self::EVALUATION_PORT, self::HEALTH_PORT, self::LAUNCHPAD_PORT)
            ->start();

        $host = $started->getHost();
        $launchpad = sprintf('http://%s:%d', $host, $started->getMappedPort(self::LAUNCHPAD_PORT));
        $healthUrl = sprintf('http://%s:%d/healthz', $host, $started->getMappedPort(self::HEALTH_PORT));

        $client = new GuzzleClient(['http_errors' => false, 'timeout' => 5]);

        self::poll(fn (): bool => $client->post($launchpad . '/start')->getStatusCode() === 200);
        self::poll(fn (): bool => $client->get($healthUrl)->getStatusCode() === 200);

        return new self($started, $host, $started->getMappedPort(self::EVALUATION_PORT));
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->evaluationPort;
    }

    public function stop(): void
    {
        $this->container->stop();
    }

    /**
     * @param callable(): bool $probe
     */
    private static function poll(callable $probe, int $attempts = 60, int $intervalMs = 500): void
    {
        for ($i = 0; $i < $attempts; $i++) {
            try {
                if ($probe()) {
                    return;
                }
            } catch (Throwable $e) {
                // swallow until the deadline; flagd may not be listening yet
            }

            usleep($intervalMs * 1000);
        }

        throw new RuntimeException('flagd testbed did not become ready in time');
    }

    private static function resolveVersion(): string
    {
        $version = @file_get_contents(__DIR__ . '/../../../test-harness/version.txt');

        if ($version === false) {
            throw new RuntimeException('Unable to read test-harness/version.txt; is the submodule initialized?');
        }

        return rtrim($version);
    }
}
