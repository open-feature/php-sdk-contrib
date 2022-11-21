<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\Test\unit;

use OpenFeature\Providers\Split\SplitProvider;
use OpenFeature\Providers\Split\Test\TestCase;
use OpenFeature\interfaces\provider\Provider;
use org\bovigo\vfs\vfsStream;

class SplitProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        // Given
        $splitFile = $this->getPathToValidSplitFile();

        $apiKey = 'localhost';
        $config = [
            'splitFile' => $splitFile,
        ];

        // When
        $instance = new SplitProvider($apiKey, $config);

        // Then
        $this->assertNotNull($instance);
        $this->assertInstanceOf(Provider::class, $instance);
    }

    private function getPathToValidSplitFile(): string
    {
        $splitFs = vfsStream::setup('root', 0777, [
            '.split' => '',
        ]);
        $splitFile = $splitFs->getChild('.split')->url();

        return $splitFile;
    }
}
