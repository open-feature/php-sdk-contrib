<?php

declare(strict_types=1);

namespace OpenFeature\Hooks\DDTrace\Test\unit;

use OpenFeature\Hooks\DDTrace\DDTraceHook;
use OpenFeature\Hooks\DDTrace\Test\TestCase;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\interfaces\hooks\Hook;

class DDTraceHookTest extends TestCase
{
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
}
