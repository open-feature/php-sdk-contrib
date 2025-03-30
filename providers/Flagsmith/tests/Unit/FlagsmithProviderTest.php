<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\Test\Unit;

use Flagsmith\Flagsmith;
use OpenFeature\Providers\Flagsmith\FlagsmithProvider;
use OpenFeature\Providers\Flagsmith\Test\Fixtures\TestOfflineHandler;
use OpenFeature\Providers\Flagsmith\Test\TestCase;
use OpenFeature\interfaces\provider\Provider;

class FlagsmithProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        // Given
        $flagsmith = new Flagsmith('dummy-key', offlineMode: true, offlineHandler: new TestOfflineHandler());

        // When
        $instance = new FlagsmithProvider($flagsmith);

        // Then
        $this->assertNotNull($instance);
        $this->assertInstanceOf(Provider::class, $instance);
    }
}
