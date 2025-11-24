<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\Test\unit\config;

use InvalidArgumentException;
use OpenFeature\Providers\Flagsmith\config\FlagsmithConfig;
use PHPUnit\Framework\TestCase;

class FlagsmithConfigTest extends TestCase
{
    public function testConstructorWithValidApiKey(): void
    {
        $apiKey = 'ser.test_api_key_12345';

        $config = new FlagsmithConfig($apiKey);

        $this->assertInstanceOf(FlagsmithConfig::class, $config);
        $this->assertSame($apiKey, $config->getApiKey());
    }

    public function testConstructorThrowsExceptionWhenApiKeyIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API key cannot be empty');

        new FlagsmithConfig('');
    }

    public function testConstructorThrowsExceptionWhenApiKeyIsWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API key cannot be empty');

        new FlagsmithConfig('   ');
    }
}
