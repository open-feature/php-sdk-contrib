<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\Test\unit;

use OpenFeature\Providers\Split\SplitProvider;
use OpenFeature\Providers\Split\Test\TestCase;
use OpenFeature\Providers\Split\config\ConfigFactory;
use OpenFeature\Providers\Split\config\HttpConfig;
use OpenFeature\interfaces\provider\Provider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class SplitProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        // Given
        $apiKey = 'localhost';
        $config = [];

        // When
        $instance = new SplitProvider($apiKey, $config);

        // Then
        $this->assertNotNull($instance);
        $this->assertInstanceOf(Provider::class, $instance);
    }

    public function testCanInstantiateHttpWithConfigObject(): void
    {
        // Given
        $expectedValue = 3.14;
        $expectedVariant = 'Pi time';
        $expectedReason = 'Success';

        $mockRequest = $this->mockery(RequestInterface::class);
        $mockRequest->shouldReceive('withHeader')->andReturn($mockRequest);
        $mockRequest->shouldReceive('withBody')->andReturn($mockRequest);

        $mockRequestFactory = $this->mockery(RequestFactoryInterface::class);
        $mockRequestFactory->shouldReceive('createRequest')->andReturn($mockRequest);

        $mockStream = $this->mockery(StreamInterface::class);

        $mockStreamFactory = $this->mockery(StreamFactoryInterface::class);
        $mockStreamFactory->shouldReceive('createStream')->andReturn($mockStream);

        $mockResponse = $this->mockery(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')->andReturn("{
            \"value\":\"{$expectedValue}\",
            \"variant\":\"{$expectedVariant}\",
            \"reason\":\"{$expectedReason}\"
        }");

        $mockClient = $this->mockery(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')->with($mockRequest)->andReturn($mockResponse);

        /** @var ClientInterface $client */
        $client = $mockClient;
        /** @var RequestFactoryInterface $requestFactory */
        $requestFactory = $mockRequestFactory;
        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $mockStreamFactory;

        $config = ConfigFactory::fromOptions(
            'localhost',
            8013,
            'http',
            true,
            new HttpConfig($client, $requestFactory, $streamFactory),
        );

        // When
        $provider = new SplitProvider($config);
        $actualDetails = $provider->resolveFloatValue('any-key', 1.0, null);

        // Then
        $this->assertEquals($expectedValue, $actualDetails->getValue());
        $this->assertEquals($expectedVariant, $actualDetails->getVariant());
        $this->assertEquals($expectedReason, $actualDetails->getReason());
    }

    public function testCanInstantiateHttpWithConfigArray(): void
    {
        // Given
        $expectedValue = 3.14;
        $expectedVariant = 'Pi time';
        $expectedReason = 'Success';

        $mockRequest = $this->mockery(RequestInterface::class);
        $mockRequest->shouldReceive('withHeader')->andReturn($mockRequest);
        $mockRequest->shouldReceive('withBody')->andReturn($mockRequest);

        $mockRequestFactory = $this->mockery(RequestFactoryInterface::class);
        $mockRequestFactory->shouldReceive('createRequest')->andReturn($mockRequest);

        $mockStream = $this->mockery(StreamInterface::class);

        $mockStreamFactory = $this->mockery(StreamFactoryInterface::class);
        $mockStreamFactory->shouldReceive('createStream')->andReturn($mockStream);

        $mockResponse = $this->mockery(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')->andReturn("{
            \"value\":\"{$expectedValue}\",
            \"variant\":\"{$expectedVariant}\",
            \"reason\":\"{$expectedReason}\"
        }");

        $mockClient = $this->mockery(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')->with($mockRequest)->andReturn($mockResponse);

        /** @var ClientInterface $client */
        $client = $mockClient;
        /** @var RequestFactoryInterface $requestFactory */
        $requestFactory = $mockRequestFactory;
        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $mockStreamFactory;

        $config = [
            'host' => 'localhost',
            'port' => 8013,
            'protocol' => 'http',
            'secure' => true,
            'httpConfig' => [
                'client' => $client,
                'requestFactory' => $requestFactory,
                'streamFactory' => $streamFactory,
            ],
        ];

        // When
        $provider = new SplitProvider($config);
        $actualDetails = $provider->resolveFloatValue('any-key', 1.0, null);

        // Then
        $this->assertEquals($expectedValue, $actualDetails->getValue());
        $this->assertEquals($expectedVariant, $actualDetails->getVariant());
        $this->assertEquals($expectedReason, $actualDetails->getReason());
    }
}
