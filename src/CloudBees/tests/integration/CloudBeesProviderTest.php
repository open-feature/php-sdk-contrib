<?php

declare(strict_types=1);

namespace OpenFeature\Providers\CloudBees\Test\integration;

use OpenFeature\Providers\CloudBees\CloudBeesProvider;
use OpenFeature\Providers\CloudBees\Test\TestCase;
use OpenFeature\interfaces\provider\Provider;
use Rox\Core\Consts\Environment;
use Rox\Server\RoxOptions;
use Rox\Server\RoxOptionsBuilder;

use function putenv;

class CloudBeesProviderTest extends TestCase
{
    protected function setUp(): void
    {
        putenv(Environment::ENV_VAR_NAME . '=' . Environment::LOCAL);
    }

    public function testCanBeInstantiated(): void
    {
        // Given
        $apiKey = '012345678901234567890123';

        // When
        $instance = CloudBeesProvider::setup(
            $apiKey,
            new RoxOptions((new RoxOptionsBuilder())
                ->setRoxyURL('http://localhost:4444/')),
        );

        // Then
        $this->assertNotNull($instance);
        $this->assertInstanceOf(Provider::class, $instance);
    }
}
