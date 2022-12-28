<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\DDTrace\Test\integration;

use OpenFeature\Hooks\DDTrace\DDTraceHook;
use OpenFeature\Hooks\DDTrace\Test\TestCase;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\interfaces\hooks\Hook;

class DDTraceHookTest extends TestCase
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
        DDTraceHook::register();

        // Then
        $this->assertCount(1, $api->getHooks());
        $this->assertInstanceOf(Hook::class, $api->getHooks()[0]);
    }

    private function simulateAutoload(): void
    {
        require __DIR__ . '/../../src/_autoload.php';
    }
}
