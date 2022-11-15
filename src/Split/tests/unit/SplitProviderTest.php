<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\Test\unit;

use OpenFeature\Providers\Split\SplitProvider;
use OpenFeature\Providers\Split\Test\TestCase;
use OpenFeature\interfaces\provider\Provider;

class SplitProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        // Given
        $apiKey = 'localhost';
        $config = [];

        // When
        $instance = new SplitProvider($apiKey, $config);

        // Then
        $this->assertNotNull($instance);
        $this->assertInstanceOf(Provider::class, $instance);
    }
}
