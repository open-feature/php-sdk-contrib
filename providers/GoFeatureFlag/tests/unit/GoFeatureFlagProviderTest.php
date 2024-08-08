<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\Test\unit;

use GuzzleHttp\Psr7\Response;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\Providers\GoFeatureFlag\GoFeatureFlagProvider;
use OpenFeature\Providers\GoFeatureFlag\Test\TestCase;
use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\exception\InvalidConfigException;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\MutableEvaluationContext;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Reason;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;
use ReflectionClass;
use ReflectionException;

use function PHPUnit\Framework\assertEquals;
use function json_encode;

class GoFeatureFlagProviderTest extends TestCase
{
    private EvaluationContext $defaultEvaluationContext;

    public function testShouldThrowIfInvalidEndpoint(): void
    {
        $this->expectException(InvalidConfigException::class);
        new GoFeatureFlagProvider(
            new Config('invalid'),
        );
    }

    // Configuration validation tests

    public function testShouldNotThrowIfValidEndpoint(): void
    {
        $provider = new GoFeatureFlagProvider(
            new Config('https://gofeatureflag.org'),
        );
        $this->assertInstanceOf(GoFeatureFlagProvider::class, $provider);
    }

    public function testShouldRaiseIfEndpointIsNotHttp(): void
    {
        $this->expectException(InvalidConfigException::class);
        $provider = new GoFeatureFlagProvider(
            new Config('gofeatureflag.org'),
        );
        $this->assertInstanceOf(GoFeatureFlagProvider::class, $provider);
    }

    public function testEmptyEndpointShouldThrow(): void
    {
        $this->expectException(InvalidConfigException::class);
        new GoFeatureFlagProvider(
            new Config(''),
        );
    }

    public function testMetadataNameIsDefined(): void
    {
        $config = new Config('http://localhost:1031');
        $provider = new GoFeatureFlagProvider($config);
        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        assertEquals('GO Feature Flag Provider', $api->getProviderMetadata()->getName());
    }

    // Metadata tests

    public function testShouldReturnTheValueOfTheFlagAsInt(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'integer_key',
            'value' => 42,
            'reason' => 'TARGETING_MATCH',
            'variant' => 'default',
        ]));

        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getIntegerDetails('integer_key', 1, $this->defaultEvaluationContext);
        assertEquals(42, $got->getValue());
        assertEquals(Reason::TARGETING_MATCH, $got->getReason());
        assertEquals('default', $got->getVariant());
        assertEquals(null, $got->getError());
        assertEquals('integer_key', $got->getFlagKey());
    }

    /**
     * @throws ReflectionException
     */
    private function mockHttpClient(GoFeatureFlagProvider $provider, MockObject $mockClient): void
    {
        $providerReflection = new ReflectionClass($provider);
        $ofrepApiProperty = $providerReflection->getProperty('ofrepApi');
        $ofrepApiProperty->setAccessible(true);
        $ofrepApi = $ofrepApiProperty->getValue($provider);

        $ofrepApiReflection = new ReflectionClass($ofrepApi);
        $clientProperty = $ofrepApiReflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($ofrepApi, $mockClient);
    }

    public function testShouldReturnTheValueOfTheFlagAsFloat(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'flag-key',
            'value' => 42.2,
            'reason' => 'TARGETING_MATCH',
            'variant' => 'default',
        ]));

        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getFloatDetails('flag-key', 1.0, $this->defaultEvaluationContext);
        assertEquals(42.2, $got->getValue());
        assertEquals(Reason::TARGETING_MATCH, $got->getReason());
        assertEquals('default', $got->getVariant());
        assertEquals(null, $got->getError());
        assertEquals('flag-key', $got->getFlagKey());
    }

    public function testShouldReturnTheValueOfTheFlagAsString(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'flag-key',
            'value' => 'value as string',
            'reason' => 'TARGETING_MATCH',
            'variant' => 'default',
        ]));

        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getStringDetails('flag-key', 'default', $this->defaultEvaluationContext);
        assertEquals('value as string', $got->getValue());
        assertEquals(Reason::TARGETING_MATCH, $got->getReason());
        assertEquals('default', $got->getVariant());
        assertEquals(null, $got->getError());
        assertEquals('flag-key', $got->getFlagKey());
    }

    public function testShouldReturnTheValueOfTheFlagAsBool(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'flag-key',
            'value' => true,
            'reason' => 'TARGETING_MATCH',
            'variant' => 'default',
        ]));

        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getBooleanDetails('flag-key', false, $this->defaultEvaluationContext);
        assertEquals(true, $got->getValue());
        assertEquals(Reason::TARGETING_MATCH, $got->getReason());
        assertEquals('default', $got->getVariant());
        assertEquals(null, $got->getError());
        assertEquals('flag-key', $got->getFlagKey());
    }

    public function testShouldReturnTheValueOfTheFlagAsObject(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'flag-key',
            'value' => ['value' => 'value as object'],
            'reason' => 'TARGETING_MATCH',
            'variant' => 'default',
        ]));

        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getObjectDetails('flag-key', ['default' => true], $this->defaultEvaluationContext);
        assertEquals(['value' => 'value as object'], $got->getValue());
        assertEquals(Reason::TARGETING_MATCH, $got->getReason());
        assertEquals('default', $got->getVariant());
        assertEquals(null, $got->getError());
        assertEquals('flag-key', $got->getFlagKey());
    }

    public function testShouldReturnTheDefaultValueIfFlagIsNotTheRightType(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'integer_key',
            'value' => 42,
            'reason' => 'TARGETING_MATCH',
            'variant' => 'default',
        ]));

        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getBooleanDetails('integer_key', false, $this->defaultEvaluationContext);
        assertEquals(false, $got->getValue());
        assertEquals(Reason::ERROR, $got->getReason());
        assertEquals(null, $got->getVariant());
        assertEquals(ErrorCode::TYPE_MISMATCH(), $got->getError()->getResolutionErrorCode());
        assertEquals('Invalid type for integer_key, got integer expected boolean', $got->getError()->getResolutionErrorMessage());
        assertEquals('integer_key', $got->getFlagKey());
    }

    public function testShouldReturnTheDefaultValueOfTheFlagIfErrorSendByTheAPIHttpCode403(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(403, [], json_encode([]));

        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getBooleanDetails('boolean_key', false, $this->defaultEvaluationContext);
        assertEquals(false, $got->getValue());
        assertEquals(Reason::ERROR, $got->getReason());
        assertEquals(null, $got->getVariant());
        assertEquals(ErrorCode::GENERAL(), $got->getError()->getResolutionErrorCode());
        assertEquals('Unauthorized access to the API', $got->getError()->getResolutionErrorMessage());
        assertEquals('boolean_key', $got->getFlagKey());
    }

    public function testShouldReturnTheDefaultValueOfTheFlagIfErrorSendByTheAPIHttpCode400(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(400, [], json_encode([
            'key' => 'integer_key',
            'reason' => 'ERROR',
            'errorCode' => 'INVALID_CONTEXT',
            'errorDetails' => 'Error Details for invalid context',
        ]));

        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getBooleanDetails('boolean_key', false, $this->defaultEvaluationContext);
        assertEquals(false, $got->getValue());
        assertEquals(Reason::ERROR, $got->getReason());
        assertEquals(null, $got->getVariant());
        assertEquals(ErrorCode::INVALID_CONTEXT(), $got->getError()->getResolutionErrorCode());
        assertEquals('Error Details for invalid context', $got->getError()->getResolutionErrorMessage());
        assertEquals('boolean_key', $got->getFlagKey());
    }

    public function testShouldReturnDefaultValueIfNoEvaluationContext(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'integer_key',
            'value' => 42,
            'reason' => 'TARGETING_MATCH',
            'variant' => 'default',
        ]));

        $mockClient->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getBooleanDetails('boolean_key', false);
        assertEquals(false, $got->getValue());
        assertEquals(Reason::ERROR, $got->getReason());
        assertEquals(null, $got->getVariant());
        assertEquals(ErrorCode::INVALID_CONTEXT(), $got->getError()->getResolutionErrorCode());
        assertEquals('Missing targetingKey in evaluation context', $got->getError()->getResolutionErrorMessage());
        assertEquals('boolean_key', $got->getFlagKey());
    }

    public function testShouldReturnDefaultValueIfEvaluationContextHasEmptyStringTargetingKey(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'integer_key',
            'value' => 42,
            'reason' => 'TARGETING_MATCH',
            'variant' => 'default',
        ]));

        $mockClient->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getBooleanDetails('boolean_key', false, new MutableEvaluationContext(''));
        assertEquals(false, $got->getValue());
        assertEquals(Reason::ERROR, $got->getReason());
        assertEquals(null, $got->getVariant());
        assertEquals(ErrorCode::INVALID_CONTEXT(), $got->getError()->getResolutionErrorCode());
        assertEquals('Missing targetingKey in evaluation context', $got->getError()->getResolutionErrorMessage());
        assertEquals('boolean_key', $got->getFlagKey());
    }

    public function testShouldReturnDefaultValueIfEvaluationContextHasNullTargetingKey(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'integer_key',
            'value' => 42,
            'reason' => 'TARGETING_MATCH',
            'variant' => 'default',
        ]));

        $mockClient->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getBooleanDetails('boolean_key', false, new MutableEvaluationContext(null));
        assertEquals(false, $got->getValue());
        assertEquals(Reason::ERROR, $got->getReason());
        assertEquals(null, $got->getVariant());
        assertEquals(ErrorCode::INVALID_CONTEXT(), $got->getError()->getResolutionErrorCode());
        assertEquals('Missing targetingKey in evaluation context', $got->getError()->getResolutionErrorMessage());
        assertEquals('boolean_key', $got->getFlagKey());
    }

    public function testShouldReturnDefaultValueIfFlagKeyEmptyString(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'integer_key',
            'value' => 42,
            'reason' => 'TARGETING_MATCH',
            'variant' => 'default',
        ]));

        $mockClient->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getBooleanDetails('', false, $this->defaultEvaluationContext);
        assertEquals(false, $got->getValue());
        assertEquals(Reason::ERROR, $got->getReason());
        assertEquals(null, $got->getVariant());
        assertEquals(ErrorCode::GENERAL(), $got->getError()->getResolutionErrorCode());
        assertEquals('An error occurred while evaluating the flag: Flag key is null or empty', $got->getError()->getResolutionErrorMessage());
        assertEquals('', $got->getFlagKey());
    }

    public function testReturnAnErrorAPIResponseIf500(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(500, [], json_encode([]));

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $config = new Config('http://gofeatureflag.org');
        $provider = new GoFeatureFlagProvider($config);

        $this->mockHttpClient($provider, $mockClient);

        $api = OpenFeatureAPI::getInstance();
        $api->setProvider($provider);
        $client = $api->getClient();
        $got = $client->getBooleanDetails('boolean_flag', false, $this->defaultEvaluationContext);
        assertEquals(false, $got->getValue());
        assertEquals(Reason::ERROR, $got->getReason());
        assertEquals(null, $got->getVariant());
        assertEquals(ErrorCode::GENERAL(), $got->getError()->getResolutionErrorCode());
        assertEquals('Unknown error occurred', $got->getError()->getResolutionErrorMessage());
        assertEquals('boolean_flag', $got->getFlagKey());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->defaultEvaluationContext = new MutableEvaluationContext('214b796a-807b-4697-b3a3-42de0ec10a37', new Attributes(['email' => 'contact@gofeatureflag.org']));
    }
}
