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

    public function testAfterSetsFlagDetailsAsSpanTags(): void
    {
        // Given
        OpenFeatureAPI::getInstance()->setProvider(new NoOpProvider());

        $context = HookContextFactory::from(
            'my-flag',
            FlagValueType::BOOLEAN,
            false,
            null,
            new Metadata('client'),
            new Metadata('NoOpProvider'),
        );
        $details = (new ResolutionDetailsBuilder())->withValue(true)->withVariant('on')->build();

        $span = $this->mockery(Span::class);
        $tracer = $this->mockery(Tracer::class);
        $tracer->shouldReceive('getActiveSpan')->andReturn($span);
        GlobalTracer::set($tracer);

        // Then
        $span->shouldReceive('setTag')->once()->with('feature_flag.key', 'my-flag');
        $span->shouldReceive('setTag')->once()->with('feature_flag.provider_name', 'NoOpProvider');
        $span->shouldReceive('setTag')->once()->with('feature_flag.variant', 'on');
        $span->shouldNotReceive('log');

        // When
        (new DDTraceHook())->after($context, $details, new HookHints());
    }
}
