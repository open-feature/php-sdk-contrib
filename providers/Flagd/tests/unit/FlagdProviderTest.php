<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\Test\unit;

use OpenFeature\Providers\Flagd\FlagdProvider;
use OpenFeature\Providers\Flagd\Test\TestCase;
use OpenFeature\Providers\Flagd\config\ConfigFactory;
use OpenFeature\Providers\Flagd\config\HttpConfig;
use OpenFeature\interfaces\provider\Provider;
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
        $mockResponse->shouldReceive('getBody->__toString')->andReturn(
            "{
                \"value\":\"{$expectedValue}\",
                \"variant\":\"{$expectedVariant}\",
                \"reason\":\"{$expectedReason}\"
            }",
            "{
                \"flags\":{
                    \"any-key\":
                        {\"reason\":\"{$expectedReason}\", 
                        \"variant\":\"{$expectedVariant}\", 
                        \"doubleValue\":\"{$expectedValue}\"
                    }
                }
            }"
        );

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

        $actualFlagsDetails = $provider->resolveAllValues(null);

        // Then
        $this->assertEquals($expectedValue, $actualFlagsDetails['any-key']->getValue());
        $this->assertEquals($expectedVariant, $actualFlagsDetails['any-key']->getVariant());
        $this->assertEquals($expectedReason, $actualFlagsDetails['any-key']->getReason());
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
        $mockResponse->shouldReceive('getBody->__toString')->andReturn(
            "{
                \"value\":\"{$expectedValue}\",
                \"variant\":\"{$expectedVariant}\",
                \"reason\":\"{$expectedReason}\"
            }",
            "{
                \"flags\":{
                    \"any-key\":
                        {\"reason\":\"{$expectedReason}\", 
                        \"variant\":\"{$expectedVariant}\", 
                        \"doubleValue\":\"{$expectedValue}\"
                    }
                }
            }"
        );
        
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

        $actualFlagsDetails = $provider->resolveAllValues(null);

        // Then
        $this->assertEquals($expectedValue, $actualFlagsDetails['any-key']->getValue());
        $this->assertEquals($expectedVariant, $actualFlagsDetails['any-key']->getVariant());
        $this->assertEquals($expectedReason, $actualFlagsDetails['any-key']->getReason());
    }
}
