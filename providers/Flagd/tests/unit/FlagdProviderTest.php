<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\Test\unit;

use OpenFeature\Providers\Flagd\FlagdProvider;
use OpenFeature\Providers\Flagd\Test\TestCase;
use OpenFeature\Providers\Flagd\config\ConfigFactory;
use OpenFeature\Providers\Flagd\config\HttpConfig;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\Reason;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class FlagdProviderTest extends TestCase
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

        // When
        $instance = new FlagdProvider($config);

        // Then
        $this->assertNotNull($instance);
        $this->assertInstanceOf(Provider::class, $instance);
        $this->assertEquals('FlagdProvider', $instance->getMetadata()->getName());
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
        $mockResponse->shouldReceive('getBody->__toString')->andReturn("{
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
        $provider = new FlagdProvider($config);
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
        $mockResponse->shouldReceive('getBody->__toString')->andReturn("{
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
        $provider = new FlagdProvider($config);
        $actualDetails = $provider->resolveFloatValue('any-key', 1.0, null);

        // Then
        $this->assertEquals($expectedValue, $actualDetails->getValue());
        $this->assertEquals($expectedVariant, $actualDetails->getVariant());
        $this->assertEquals($expectedReason, $actualDetails->getReason());
    }

    public function testDisabledBooleanFlagFallsBackToCallerDefault(): void
    {
        // Given
        $provider = $this->providerWithResponseBody('{"value":false,"reason":"DISABLED","variant":"","metadata":{}}');

        // When
        $actualDetails = $provider->resolveBooleanValue('any-key', true, null);

        // Then
        $this->assertTrue($actualDetails->getValue());
        $this->assertNull($actualDetails->getVariant());
        $this->assertEquals(Reason::DISABLED, $actualDetails->getReason());
    }

    public function testDisabledStringFlagFallsBackToCallerDefault(): void
    {
        // Given
        $provider = $this->providerWithResponseBody('{"value":"","reason":"DISABLED","variant":"","metadata":{}}');

        // When
        $actualDetails = $provider->resolveStringValue('any-key', 'fallback', null);

        // Then
        $this->assertEquals('fallback', $actualDetails->getValue());
        $this->assertNull($actualDetails->getVariant());
        $this->assertEquals(Reason::DISABLED, $actualDetails->getReason());
    }

    public function testDisabledIntegerFlagFallsBackToCallerDefault(): void
    {
        // Given
        $provider = $this->providerWithResponseBody('{"value":"0","reason":"DISABLED","variant":"","metadata":{}}');

        // When
        $actualDetails = $provider->resolveIntegerValue('any-key', 42, null);

        // Then
        $this->assertEquals(42, $actualDetails->getValue());
        $this->assertNull($actualDetails->getVariant());
        $this->assertEquals(Reason::DISABLED, $actualDetails->getReason());
    }

    public function testDisabledFloatFlagFallsBackToCallerDefault(): void
    {
        // Given
        $provider = $this->providerWithResponseBody('{"value":0.0,"reason":"DISABLED","variant":"","metadata":{}}');

        // When
        $actualDetails = $provider->resolveFloatValue('any-key', 1.5, null);

        // Then
        $this->assertEquals(1.5, $actualDetails->getValue());
        $this->assertNull($actualDetails->getVariant());
        $this->assertEquals(Reason::DISABLED, $actualDetails->getReason());
    }

    public function testDisabledObjectFlagFallsBackToCallerDefault(): void
    {
        // Given
        $provider = $this->providerWithResponseBody('{"value":{},"reason":"DISABLED","variant":"","metadata":{}}');
        $default = ['a' => 1];

        // When
        $actualDetails = $provider->resolveObjectValue('any-key', $default, null);

        // Then
        $this->assertEquals($default, $actualDetails->getValue());
        $this->assertNull($actualDetails->getVariant());
        $this->assertEquals(Reason::DISABLED, $actualDetails->getReason());
    }

    private function providerWithResponseBody(string $body): FlagdProvider
    {
        $mockRequest = $this->mockery(RequestInterface::class);
        $mockRequest->shouldReceive('withHeader')->andReturn($mockRequest);
        $mockRequest->shouldReceive('withBody')->andReturn($mockRequest);

        $mockRequestFactory = $this->mockery(RequestFactoryInterface::class);
        $mockRequestFactory->shouldReceive('createRequest')->andReturn($mockRequest);

        $mockStream = $this->mockery(StreamInterface::class);

        $mockStreamFactory = $this->mockery(StreamFactoryInterface::class);
        $mockStreamFactory->shouldReceive('createStream')->andReturn($mockStream);

        $mockResponse = $this->mockery(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody->__toString')->andReturn($body);

        $mockClient = $this->mockery(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')->with($mockRequest)->andReturn($mockResponse);

        /** @var ClientInterface $client */
        $client = $mockClient;
        /** @var RequestFactoryInterface $requestFactory */
        $requestFactory = $mockRequestFactory;
        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $mockStreamFactory;

        return new FlagdProvider([
            'httpConfig' => [
                'client' => $client,
                'requestFactory' => $requestFactory,
                'streamFactory' => $streamFactory,
            ],
        ]);
    }
}
