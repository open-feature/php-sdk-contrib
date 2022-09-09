<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\Test\unit;

use OpenFeature\Providers\Flagd\FlagdProvider;
use OpenFeature\Test\TestCase;
use OpenFeature\interfaces\provider\Provider;

class FlagdProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new FlagdProvider();

        $this->assertNotNull($instance);
        $this->assertInstanceOf(Provider::class, $instance);
    }
}
