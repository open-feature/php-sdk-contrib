<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\OpenTelemetry\Test\integration;

use OpenFeature\Hooks\OpenTelemetry\OpenTelemetryHook;
use OpenFeature\Hooks\OpenTelemetry\Test\TestCase;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\interfaces\hooks\Hook;

use function phpversion;
use function preg_match;

class OpenTelemetryHookTest extends TestCase
{
    public function testIsRegisteredAutomatically(): void
    {
        // Given
        $api = OpenFeatureAPI::getInstance();
        $api->clearHooks();

        // When
        $this->simulateAutoload();

        // Then

        $this->assertCount($this->isAutoloadSupported() ? 1 : 0, $api->getHooks());
        $this->assertInstanceOf(Hook::class, $api->getHooks()[0]);
    }

    public function testCanBeRegistered(): void
    {
        // Given
        $api = OpenFeatureAPI::getInstance();
        $api->clearHooks();

        // When
        OpenTelemetryHook::register();

        // Then
        $this->assertNotEmpty($api->getHooks());
        $this->assertInstanceOf(Hook::class, $api->getHooks()[0]);
    }

    private function simulateAutoload(): void
    {
        require_once __DIR__ . '/../../src/_autoload.php';
    }

    private function isAutoloadSupported(): bool
    {
        $version = phpversion();

        if (!$version) {
            return false;
        }

        return preg_match('/8\..+/', $version) === 1;
    }
}
