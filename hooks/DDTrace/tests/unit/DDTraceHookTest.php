<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\DDTrace\Test\unit;

use DDTrace\Contracts\Span;
use DDTrace\Contracts\Tracer;
use DDTrace\GlobalTracer;
use DDTrace\NoopTracer;
use OpenFeature\Hooks\DDTrace\DDTraceHook;
use OpenFeature\Hooks\DDTrace\Test\TestCase;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\implementation\common\Metadata;
use OpenFeature\implementation\hooks\HookContextFactory;
use OpenFeature\implementation\hooks\HookHints;
use OpenFeature\implementation\provider\NoOpProvider;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\hooks\Hook;
use OpenFeature\interfaces\hooks\HookContext;
use OpenFeature\interfaces\provider\ResolutionDetails;

class DDTraceHookTest extends TestCase
{
    protected function tearDown(): void
    {
        GlobalTracer::set(new NoopTracer());

        parent::tearDown();
    }

    public function testCanBeRegistered(): void
    {
        // Given
        $api = OpenFeatureAPI::getInstance();

        // When
        DDTraceHook::register();

        // Then
        $this->assertNotEmpty($api->getHooks());
        $this->assertInstanceOf(Hook::class, $api->getHooks()[0]);
    }

    public function testAfterRecordsFlagDetailsOnChildSpan(): void
    {
        // Given
        OpenFeatureAPI::getInstance()->setProvider(new NoOpProvider());

        $parentSpan = $this->mockery(Span::class);
        $childSpan = $this->mockery(Span::class);
        $tracer = $this->mockery(Tracer::class);
        $tracer->shouldReceive('getActiveSpan')->andReturn($parentSpan);
        GlobalTracer::set($tracer);

        // Then
        $tracer->shouldReceive('startSpan')->once()->with('feature_flag.evaluation', [
            'child_of' => $parentSpan,
            'tags' => [
                'feature_flag.key' => 'my-flag',
                'feature_flag.provider_name' => 'NoOpProvider',
                'feature_flag.variant' => 'on',
            ],
        ])->andReturn($childSpan);
        $childSpan->shouldReceive('finish')->once();
        $parentSpan->shouldNotReceive('setTag');
        $parentSpan->shouldNotReceive('log');

        // When
        (new DDTraceHook())->after($this->createHookContext('my-flag'), $this->createDetails('on'), new HookHints());
    }

    public function testAfterRecordsEachEvaluationOnSharedSpanSeparately(): void
    {
        // Given
        OpenFeatureAPI::getInstance()->setProvider(new NoOpProvider());

        $parentSpan = $this->mockery(Span::class);
        $firstChildSpan = $this->mockery(Span::class);
        $secondChildSpan = $this->mockery(Span::class);
        $tracer = $this->mockery(Tracer::class);
        $tracer->shouldReceive('getActiveSpan')->andReturn($parentSpan);
        GlobalTracer::set($tracer);

        // Then
        $tracer->shouldReceive('startSpan')->once()->with('feature_flag.evaluation', [
            'child_of' => $parentSpan,
            'tags' => [
                'feature_flag.key' => 'first-flag',
                'feature_flag.provider_name' => 'NoOpProvider',
                'feature_flag.variant' => 'on',
            ],
        ])->andReturn($firstChildSpan);
        $tracer->shouldReceive('startSpan')->once()->with('feature_flag.evaluation', [
            'child_of' => $parentSpan,
            'tags' => [
                'feature_flag.key' => 'second-flag',
                'feature_flag.provider_name' => 'NoOpProvider',
                'feature_flag.variant' => 'off',
            ],
        ])->andReturn($secondChildSpan);
        $firstChildSpan->shouldReceive('finish')->once();
        $secondChildSpan->shouldReceive('finish')->once();
        $parentSpan->shouldNotReceive('setTag');

        // When
        $hook = new DDTraceHook();
        $hook->after($this->createHookContext('first-flag'), $this->createDetails('on'), new HookHints());
        $hook->after($this->createHookContext('second-flag'), $this->createDetails('off'), new HookHints());
    }

    private function createHookContext(string $flagKey): HookContext
    {
        return HookContextFactory::from(
            $flagKey,
            FlagValueType::BOOLEAN,
            false,
            null,
            new Metadata('client'),
            new Metadata('NoOpProvider'),
        );
    }

    private function createDetails(string $variant): ResolutionDetails
    {
        return (new ResolutionDetailsBuilder())->withValue(true)->withVariant($variant)->build();
    }
}
