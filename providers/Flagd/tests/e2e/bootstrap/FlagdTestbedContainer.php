<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\Test\e2e\bootstrap;

use GuzzleHttp\Client as GuzzleClient;
use RuntimeException;
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

        $started = (new ExposingGenericContainer($image))
            ->withExposedPorts(self::EVALUATION_PORT, self::HEALTH_PORT, self::LAUNCHPAD_PORT)
            ->start();

        $host = $started->getHost();
        $id = $started->getId();

        $launchpadPort = self::mappedPort($started, $id, self::LAUNCHPAD_PORT);
        $healthPort = self::mappedPort($started, $id, self::HEALTH_PORT);
        $evaluationPort = self::mappedPort($started, $id, self::EVALUATION_PORT);

        $launchpad = sprintf('http://%s:%d', $host, $launchpadPort);
        $healthUrl = sprintf('http://%s:%d/healthz', $host, $healthPort);

        $client = new GuzzleClient(['http_errors' => false, 'timeout' => 5]);

        self::poll(fn (): bool => $client->post($launchpad . '/start')->getStatusCode() === 200);
        self::poll(fn (): bool => $client->get($healthUrl)->getStatusCode() === 200);

        return new self($started, $host, $evaluationPort);
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
     * Resolves a container's published host port for an exposed port. Docker can take a moment
     * after start to publish the bindings, and StartedGenericContainer caches its inspect, so a
     * fresh instance is used on each attempt to force an uncached lookup (see
     * testcontainers/testcontainers-php#50). On timeout the container logs are surfaced to aid
     * diagnosis on CI runners.
     */
    private static function mappedPort(StartedGenericContainer $started, string $id, int $port, int $attempts = 40, int $intervalMs = 250): int
    {
        for ($i = 0; $i < $attempts; $i++) {
            try {
                return (new StartedGenericContainer($id))->getMappedPort($port);
            } catch (Throwable $e) {
                // bindings not published yet; retry until the deadline
            }

            usleep($intervalMs * 1000);
        }

        throw new RuntimeException(sprintf(
            "flagd testbed did not publish port %d in time.\n--- container logs ---\n%s",
            $port,
            self::safeLogs($started),
        ));
    }

    private static function safeLogs(StartedGenericContainer $started): string
    {
        try {
            return $started->logs();
        } catch (Throwable $e) {
            return '(unable to read container logs: ' . $e->getMessage() . ')';
        }
    }

    /**
     * @param callable(): bool $probe
     */
    private static function poll(callable $probe, int $attempts = 20, int $intervalMs = 500): void
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
