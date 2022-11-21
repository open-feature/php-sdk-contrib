<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\Test\integration;

use OpenFeature\implementation\flags\EvaluationContext;
use OpenFeature\Providers\Split\SplitProvider;
use OpenFeature\Providers\Split\Test\TestCase;
use OpenFeature\interfaces\provider\Provider;

class SplitProviderTest extends TestCase
{
    private SplitProvider $provider;
    private EvaluationContext $evaluationContext;

    public function setUp(): void
    {
        $apiKey = 'localhost';
        $config = [
            'splitFile' => __DIR__ . '/files/splits.yml',
        ];

        $this->provider = new SplitProvider($apiKey, $config);

        $this->evaluationContext = new EvaluationContext('test_uid');
    }

    public function testCanBeInstantiated(): void
    {
        // Given
        $provider = $this->provider;

        // Then
        $this->assertNotNull($provider);
        $this->assertInstanceOf(Provider::class, $provider);
    }    

    public function testCanResolveBoolean(): void
    {
        // Given
        $expectedValue = true;
        
        // When
        $actualDetails = $this->provider->resolveBooleanValue('dev.openfeature.bool_flag', false, $this->evaluationContext);
        $actualValue = $actualDetails->getValue();

        // Then
        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testCanResolveFloat(): void
    {
        // Given
        $expectedValue = 3.14;
                
        // When
        $actualDetails = $this->provider->resolveFloatValue('dev.openfeature.float_flag', 0.0, $this->evaluationContext);
        $actualValue = $actualDetails->getValue();

        // Then
        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testCanResolveInteger(): void
    {
        // Given
        $expectedValue = 42;
        
        // When
        $actualDetails = $this->provider->resolveIntegerValue('dev.openfeature.int_flag', 0, $this->evaluationContext);
        $actualValue = $actualDetails->getValue();

        // Then
        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testCanResolveObject(): void
    {
        // Given
        $expectedValue = ["name"=>"OpenFeature","version"=>"1.0.0"];
        
        // When
        $actualDetails = $this->provider->resolveObjectValue('dev.openfeature.object_flag', [], $this->evaluationContext);
        $actualValue = $actualDetails->getValue();

        // Then
        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testCanResolveString(): void
    {
        // Given
        $expectedValue = 'string-value';
        
        // When
        $actualDetails = $this->provider->resolveStringValue('dev.openfeature.string_flag', 'not-the-string-value', $this->evaluationContext);
        $actualValue = $actualDetails->getValue();

        // Then
        $this->assertEquals($expectedValue, $actualValue);
    }
}
