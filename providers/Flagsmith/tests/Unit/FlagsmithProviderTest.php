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
}
