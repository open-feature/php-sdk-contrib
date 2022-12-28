<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\OpenTelemetry\Test\integration;

use OpenFeature\Hooks\OpenTelemetry\OpenTelemetryHook;
use OpenFeature\Hooks\OpenTelemetry\Test\TestCase;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\interfaces\hooks\Hook;

class OpenTelemetryHookTest extends TestCase
{
    public function testIsRegisteredAutomatically(): void
    {
        // Given
        $api = OpenFeatureAPI::getInstance();
        $api->clearHooks();

        // When
        // simulates the composer autoload
        require_once __DIR__ . '/../../src/_autoload.php';

        // Then
        $this->assertNotEmpty($api->getHooks());
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
}
