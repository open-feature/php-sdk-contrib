<?php

declare(strict_types=1);

namespace OpenFeature\Providers\CloudBees\Test\integration;

use OpenFeature\Providers\CloudBees\CloudBeesProvider;
use OpenFeature\Providers\CloudBees\Test\TestCase;
use OpenFeature\interfaces\provider\Provider;
use Rox\Core\Consts\Environment;
use Rox\Server\RoxOptions;
use Rox\Server\RoxOptionsBuilder;

use function putenv;

class CloudBeesProviderTest extends TestCase
{
    private const API_KEY = '012345678901234567890123';
    private Provider $instance;

    protected function setUp(): void
    {
        putenv(Environment::ENV_VAR_NAME . '=' . Environment::LOCAL);

        $apiKey = self::API_KEY;

        $instance = CloudBeesProvider::setup(
            $apiKey,
            new RoxOptions((new RoxOptionsBuilder())
                ->setRoxyURL('http://localhost:4444/')),
        );

        $this->instance = $instance;
    }

    public function testCanBeInstantiated(): void
    {
        // Given
        $instance = $this->instance;

        // Then
        $this->assertNotNull($instance);
        $this->assertInstanceOf(CloudBeesProvider::class, $instance);
        $this->assertInstanceOf(Provider::class, $instance);
    }

    public function testCanResolveBool(): void
    {
        // Given
        $flagName = 'dev.openfeature.bool_flag';
        $defaultValue = false;
        $expectedValue = true;

        // When
        $details = $this->instance->resolveBooleanValue($flagName, $defaultValue);
        $value = $details->getValue();

        // Then
        $this->assertNotEquals($value, $defaultValue);
        $this->assertEquals($value, $expectedValue);
    }

    public function testCanResolveInt(): void
    {
        // Given
        $flagName = 'dev.openfeature.int_flag';
        $defaultValue = 0;
        $expectedValue = 42;

        // When
        $details = $this->instance->resolveIntegerValue($flagName, $defaultValue);
        $value = $details->getValue();

        // Then
        $this->assertNotEquals($value, $defaultValue);
        $this->assertEquals($value, $expectedValue);
    }

    public function testCanResolveFloat(): void
    {
        // Given
        $flagName = 'dev.openfeature.float_flag';
        $defaultValue = 0.0;
        $expectedValue = 3.14;

        // When
        $details = $this->instance->resolveFloatValue($flagName, $defaultValue);
        $value = $details->getValue();

        // Then
        $this->assertNotEquals($value, $defaultValue);
        $this->assertEquals($value, $expectedValue);
    }

    public function testCanResolveString(): void
    {
        // Given
        $flagName = 'dev.openfeature.string_flag';
        $defaultValue = 'default';
        $expectedValue = 'string-value';

        // When
        $details = $this->instance->resolveStringValue($flagName, $defaultValue);
        $value = $details->getValue();

        // Then
        $this->assertNotEquals($value, $defaultValue);
        $this->assertEquals($value, $expectedValue);
    }

    public function testCanResolveObject(): void
    {
        // Given
        $flagName = 'dev.openfeature.object_flag';
        $defaultValue = ['anything' => 'at all'];
        $expectedValue = [];

        // When
        $details = $this->instance->resolveObjectValue($flagName, $defaultValue);
        $value = $details->getValue();

        // Then
        $this->assertNotEquals($value, $defaultValue);
        $this->assertEquals($value, $expectedValue);
    }
}
