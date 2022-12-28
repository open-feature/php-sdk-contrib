<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\OpenTelemetry\Test\integration;

use OpenFeature\Hooks\OpenTelemetry\OpenTelemetryHook;
use OpenFeature\Hooks\OpenTelemetry\Test\TestCase;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\interfaces\hooks\Hook;

class OpenTelemetryHookTest extends TestCase
{
    public function testAutoload(): void
    {
        // Given
        $api = OpenFeatureAPI::getInstance();
        $api->clearHooks();

        // When
        $this->simulateAutoload();

        // Then

        $this->assertCount(1, $api->getHooks());
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
        $this->assertCount(1, $api->getHooks());
        $this->assertInstanceOf(Hook::class, $api->getHooks()[0]);
    }

    private function simulateAutoload(): void
    {
        require __DIR__ . '/../../src/_autoload.php';
    }
}
