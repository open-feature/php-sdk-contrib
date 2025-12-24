<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\Test\unit\service;

use OpenFeature\Providers\Flagsmith\service\ContextMapper;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use PHPUnit\Framework\TestCase;

class ContextMapperTest extends TestCase
{
    private ContextMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ContextMapper();
    }

    public function testMapWithTargetingKeyAndAttributes(): void
    {
        $context = new EvaluationContext(
            'user-123',
            new Attributes(['email' => 'user@example.com', 'plan' => 'premium']),
        );

        $result = $this->mapper->map($context);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('identifier', $result);
        $this->assertArrayHasKey('traits', $result);
        $this->assertSame('user-123', $result['identifier']);
        $this->assertIsObject($result['traits']);
        $this->assertEquals((object) ['email' => 'user@example.com', 'plan' => 'premium'], $result['traits']);
    }

    public function testMapWithTargetingKeyOnly(): void
    {
        $context = new EvaluationContext('user-456');

        $result = $this->mapper->map($context);

        $this->assertSame('user-456', $result['identifier']);
        $this->assertNull($result['traits']);
    }

    public function testMapWithNullContext(): void
    {
        $result = $this->mapper->map(null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('identifier', $result);
        $this->assertArrayHasKey('traits', $result);
        $this->assertNull($result['identifier']);
        $this->assertNull($result['traits']);
    }

    public function testMapWithEmptyAttributes(): void
    {
        $context = new EvaluationContext('user-789', new Attributes([]));

        $result = $this->mapper->map($context);

        $this->assertSame('user-789', $result['identifier']);
        $this->assertNull($result['traits']);
    }

    public function testMapWithNoTargetingKeyButWithAttributes(): void
    {
        $context = new EvaluationContext(null, new Attributes(['env' => 'production']));

        $result = $this->mapper->map($context);

        $this->assertNull($result['identifier']);
        $this->assertIsObject($result['traits']);
        $this->assertEquals((object) ['env' => 'production'], $result['traits']);
    }
}
