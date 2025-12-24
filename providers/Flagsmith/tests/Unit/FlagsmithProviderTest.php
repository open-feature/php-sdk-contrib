<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\Test\Unit;

use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Flagsmith;
use OpenFeature\Providers\Flagsmith\FlagsmithProvider;
use OpenFeature\Providers\Flagsmith\Test\Fixtures\TestOfflineHandler;
use OpenFeature\Providers\Flagsmith\Test\TestCase;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\Reason;

use function file_get_contents;
use function json_decode;

class FlagsmithProviderTest extends TestCase
{
    protected function buildProvider(string $environmentModelPath): Provider
    {
        /** @var string $encoded */
        $encoded = file_get_contents($environmentModelPath);
        $modelData = json_decode($encoded);

        // @phpstan-ignore-next-line EnvironmentModel::build() type-hint is string but implementation expects object
        $offlineHandler = new TestOfflineHandler(EnvironmentModel::build($modelData));
        $flagsmith = new Flagsmith('dummy-key', offlineMode: true, offlineHandler: $offlineHandler);

        return new FlagsmithProvider($flagsmith);
    }

    public function testCanBeInstantiated(): void
    {
        // Given
        $flagsmith = new Flagsmith('dummy-key', offlineMode: true, offlineHandler: new TestOfflineHandler());

        // When
        $provider = new FlagsmithProvider($flagsmith);

        // Then
        $this->assertInstanceOf(Provider::class, $provider);
    }

    public function testBooleanResolutionWithEnabledFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/boolean.json');

        // When
        $resolutionDetails = $provider->resolveBooleanValue('some_feature', false);

        // Then
        $this->assertTrue($resolutionDetails->getValue());
    }

    public function testBooleanResolutionWithDisabledFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/boolean.json');

        // When
        $resolutionDetails = $provider->resolveBooleanValue('disabled_feature', true);

        // Then
        $this->assertFalse($resolutionDetails->getValue());
    }

    public function testBooleanResolutionWithDefaultValueFromFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/boolean.json');

        // When
        $resolutionDetails = $provider->resolveBooleanValue('missing_feature', false);

        // Then
        $this->assertFalse($resolutionDetails->getValue());
        $this->assertEquals(ErrorCode::GENERAL(), $resolutionDetails->getError()?->getResolutionErrorCode());
    }

    public function testStringResolutionWithEnabledFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/string.json');

        // When
        $resolutionDetails = $provider->resolveStringValue('string_feature', 'default value');

        // Then
        $this->assertEquals('flag value', $resolutionDetails->getValue());
    }

    public function testStringResolutionWithDisabledFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/string.json');

        // When
        $resolutionDetails = $provider->resolveStringValue('disabled_string_feature', 'default value');

        // Then
        $this->assertEquals('default value', $resolutionDetails->getValue());
    }

    public function testStringResolutionWithMissingFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/string.json');

        // When
        $resolutionDetails = $provider->resolveStringValue('missing_string_feature', 'default value');

        // Then
        $this->assertEquals('default value', $resolutionDetails->getValue());
        $this->assertEquals(ErrorCode::GENERAL(), $resolutionDetails->getError()?->getResolutionErrorCode());
        $this->assertEquals(Reason::ERROR, $resolutionDetails->getReason());
    }

    public function testIntegerResolutionWithEnabledFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/integer.json');

        // When
        $resolutionDetails = $provider->resolveIntegerValue('integer_feature', 1);

        // Then
        $this->assertEquals(2, $resolutionDetails->getValue());
    }

    public function testIntegerResolutionWithDisabledFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/integer.json');

        // When
        $resolutionDetails = $provider->resolveIntegerValue('disabled_integer_feature', 456);

        // Then
        $this->assertEquals(456, $resolutionDetails->getValue());
    }

    public function testIntegerResolutionWithMissingFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/integer.json');

        // When
        $resolutionDetails = $provider->resolveIntegerValue('missing_integer_feature', 123);

        // Then
        $this->assertEquals(123, $resolutionDetails->getValue());
        $this->assertEquals(ErrorCode::GENERAL(), $resolutionDetails->getError()?->getResolutionErrorCode());
        $this->assertEquals(Reason::ERROR, $resolutionDetails->getReason());
    }

    public function testFloatResolutionWithEnabledFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/float.json');

        // When
        $resolutionDetails = $provider->resolveFloatValue('float_feature', 1.0);

        // Then
        $this->assertEquals(2.345, $resolutionDetails->getValue());
    }

    public function testFloatResolutionWithDisabledFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/float.json');

        // When
        $resolutionDetails = $provider->resolveFloatValue('disabled_float_feature', 9.99);

        // Then
        $this->assertEquals(9.99, $resolutionDetails->getValue());
    }

    public function testFloatResolutionWithMissingFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/float.json');

        // When
        $resolutionDetails = $provider->resolveFloatValue('missing_float_feature', 0.123);

        // Then
        $this->assertEquals(0.123, $resolutionDetails->getValue());
        $this->assertEquals(ErrorCode::GENERAL(), $resolutionDetails->getError()?->getResolutionErrorCode());
        $this->assertEquals(Reason::ERROR, $resolutionDetails->getReason());
    }

    public function testObjectResolutionWithEnabledFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/object.json');

        // When
        $resolutionDetails = $provider->resolveObjectValue('object_feature', ['a' => 'b']);

        // Then
        $this->assertEquals(['key' => 'value'], $resolutionDetails->getValue());
    }

    public function testObjectResolutionWithDisabledFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/object.json');

        // When
        $resolutionDetails = $provider->resolveObjectValue('disabled_object_feature', ['a' => 'b']);

        // Then
        $this->assertEquals(['a' => 'b'], $resolutionDetails->getValue());
    }

    public function testObjectResolutionWithMissingFlag(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/object.json');

        // When
        $resolutionDetails = $provider->resolveObjectValue('missing_object_feature', ['c' => 3, 'p' => 'o']);

        // Then
        $this->assertEquals(['c' => 3, 'p' => 'o'], $resolutionDetails->getValue());
    }

    public function testObjectResolutionWithEnabledFlagWithInvalidValue(): void
    {
        // Given
        $provider = $this->buildProvider(__DIR__ . '/../Fixtures/environments/object.json');

        // When
        $resolutionDetails = $provider->resolveObjectValue('invalid_object_feature', ['default' => 'value']);

        // Then
        $this->assertEquals(['default' => 'value'], $resolutionDetails->getValue());
    }
}
