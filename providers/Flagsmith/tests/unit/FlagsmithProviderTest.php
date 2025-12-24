<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\Test\unit;

use Flagsmith\Flagsmith;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use OpenFeature\Providers\Flagsmith\FlagsmithProvider;
use OpenFeature\Providers\Flagsmith\config\FlagsmithConfig;
use OpenFeature\Providers\Flagsmith\service\ContextMapper;
use OpenFeature\Providers\Flagsmith\service\FlagEvaluator;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\interfaces\common\Metadata;
use OpenFeature\interfaces\hooks\Hook;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FlagsmithProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private FlagsmithProvider $provider;
    private FlagsmithConfig $config;
    private Flagsmith $mockFlagsmithClient;
    private ContextMapper $mockContextMapper;
    private FlagEvaluator $mockEvaluator;

    protected function setUp(): void
    {
        $this->config = new FlagsmithConfig('test-environment-key');

        // Mock dependencies to avoid real Flagsmith client instantiation
        $this->mockFlagsmithClient = Mockery::mock(Flagsmith::class);
        $this->mockContextMapper = Mockery::mock(ContextMapper::class);
        $this->mockEvaluator = Mockery::mock(FlagEvaluator::class);

        $this->provider = new FlagsmithProvider(
            $this->config,
            $this->mockFlagsmithClient,
            $this->mockContextMapper,
            $this->mockEvaluator,
        );
    }

    public function testGetMetadataReturnsProviderName(): void
    {
        $metadata = $this->provider->getMetadata();

        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertEquals('FlagsmithProvider', $metadata->getName());
    }

    public function testResolveBooleanValueWithoutContext(): void
    {
        $expectedResult = (new ResolutionDetailsBuilder())
            ->withValue(true)
            ->withReason('STATIC')
            ->build();

        $this->mockContextMapper->shouldReceive('map')
            ->with(null)
            ->once()
            ->andReturn(['identifier' => null, 'traits' => null]);

        $this->mockEvaluator->shouldReceive('evaluateBoolean')
            ->with('test_flag', false, null, null)
            ->once()
            ->andReturn($expectedResult);

        $result = $this->provider->resolveBooleanValue('test_flag', false, null);

        $this->assertSame($expectedResult, $result);
        $this->assertTrue($result->getValue());
    }

    public function testResolveStringValueWithoutContext(): void
    {
        $expectedResult = (new ResolutionDetailsBuilder())
            ->withValue('test_value')
            ->withReason('STATIC')
            ->build();

        $this->mockContextMapper->shouldReceive('map')
            ->with(null)
            ->once()
            ->andReturn(['identifier' => null, 'traits' => null]);

        $this->mockEvaluator->shouldReceive('evaluateString')
            ->with('test_flag', 'default', null, null)
            ->once()
            ->andReturn($expectedResult);

        $result = $this->provider->resolveStringValue('test_flag', 'default', null);

        $this->assertSame($expectedResult, $result);
    }

    public function testResolveIntegerValueWithoutContext(): void
    {
        $expectedResult = (new ResolutionDetailsBuilder())
            ->withValue(42)
            ->withReason('STATIC')
            ->build();

        $this->mockContextMapper->shouldReceive('map')
            ->with(null)
            ->once()
            ->andReturn(['identifier' => null, 'traits' => null]);

        $this->mockEvaluator->shouldReceive('evaluateInteger')
            ->with('test_flag', 42, null, null)
            ->once()
            ->andReturn($expectedResult);

        $result = $this->provider->resolveIntegerValue('test_flag', 42, null);

        $this->assertSame($expectedResult, $result);
    }

    public function testResolveFloatValueWithoutContext(): void
    {
        $expectedResult = (new ResolutionDetailsBuilder())
            ->withValue(3.14)
            ->withReason('STATIC')
            ->build();

        $this->mockContextMapper->shouldReceive('map')
            ->with(null)
            ->once()
            ->andReturn(['identifier' => null, 'traits' => null]);

        $this->mockEvaluator->shouldReceive('evaluateFloat')
            ->with('test_flag', 3.14, null, null)
            ->once()
            ->andReturn($expectedResult);

        $result = $this->provider->resolveFloatValue('test_flag', 3.14, null);

        $this->assertSame($expectedResult, $result);
    }

    public function testResolveObjectValueWithoutContext(): void
    {
        $expectedResult = (new ResolutionDetailsBuilder())
            ->withValue(['key' => 'value'])
            ->withReason('STATIC')
            ->build();

        $this->mockContextMapper->shouldReceive('map')
            ->with(null)
            ->once()
            ->andReturn(['identifier' => null, 'traits' => null]);

        $this->mockEvaluator->shouldReceive('evaluateObject')
            ->with('test_flag', ['default' => 'value'], null, null)
            ->once()
            ->andReturn($expectedResult);

        $result = $this->provider->resolveObjectValue('test_flag', ['default' => 'value'], null);

        $this->assertSame($expectedResult, $result);
    }

    public function testResolveBooleanValueWithContext(): void
    {
        $context = new EvaluationContext(
            'user-123',
            new Attributes(['plan' => 'premium']),
        );

        $traits = (object) ['plan' => 'premium'];
        $expectedResult = (new ResolutionDetailsBuilder())
            ->withValue(true)
            ->withReason('TARGETING_MATCH')
            ->build();

        $this->mockContextMapper->shouldReceive('map')
            ->with($context)
            ->once()
            ->andReturn(['identifier' => 'user-123', 'traits' => $traits]);

        $this->mockEvaluator->shouldReceive('evaluateBoolean')
            ->with('test_flag', false, 'user-123', $traits)
            ->once()
            ->andReturn($expectedResult);

        $result = $this->provider->resolveBooleanValue('test_flag', false, $context);

        $this->assertSame($expectedResult, $result);
    }

    public function testGetHooksReturnsEmptyArrayByDefault(): void
    {
        $hooks = $this->provider->getHooks();

        $this->assertIsArray($hooks);
        $this->assertEmpty($hooks);
    }

    public function testSetHooksStoresHooks(): void
    {
        $hook = Mockery::mock(Hook::class);
        $hooks = [$hook];
        $this->provider->setHooks($hooks);

        $this->assertEquals($hooks, $this->provider->getHooks());
    }

    public function testSetLoggerPassesLoggerToEvaluator(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $this->mockEvaluator->logger = null;

        $this->provider->setLogger($logger);

        // Verify logger was passed to evaluator
        $this->assertSame($logger, $this->mockEvaluator->logger);
    }

    public function testConstructorAcceptsConfig(): void
    {
        $config = new FlagsmithConfig('custom-key', 'https://custom.api.com');
        $mockClient = Mockery::mock(Flagsmith::class);
        $provider = new FlagsmithProvider($config, $mockClient);

        $this->assertInstanceOf(FlagsmithProvider::class, $provider);
        $metadata = $provider->getMetadata();
        $this->assertEquals('FlagsmithProvider', $metadata->getName());
    }
}
