<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\Test\Unit;

use Flagsmith\Flagsmith;
use OpenFeature\Providers\Flagsmith\Config\ConfigFactory;
use OpenFeature\Providers\Flagsmith\Config\HttpConfig;
use OpenFeature\Providers\Flagsmith\FlagsmithProvider;
use OpenFeature\Providers\Flagsmith\Test\TestCase;
use OpenFeature\interfaces\provider\Provider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class FlagsmithProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        // Given
        $config = [
            'httpConfig' => [
                'client' => $this->mockery(ClientInterface::class),
                'requestFactory' => $this->mockery(RequestFactoryInterface::class),
                'streamFactory' => $this->mockery(StreamFactoryInterface::class),
            ],
        ];
        $flagsmith = new Flagsmith('dummy-key');

        // When
        $instance = new FlagsmithProvider($flagsmith);

        // Then
        $this->assertNotNull($instance);
        $this->assertInstanceOf(Provider::class, $instance);
    }
}
