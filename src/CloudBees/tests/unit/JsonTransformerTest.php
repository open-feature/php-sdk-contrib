<?php

declare(strict_types=1);

namespace OpenFeature\Providers\CloudBees\Test\unit;

use OpenFeature\Providers\CloudBees\Test\TestCase;
use OpenFeature\Providers\CloudBees\transformers\JsonTransformer;

class JsonTransformerTest extends TestCase
{
    public function testCanDeserializeJsonObject(): void
    {
        // Given
        $name = 'OpenFeature';
        $version = '1.0.0';
        $stringValue = '{"name":"' . $name . '","version":"' . $version . '"}';

        $transformer = new JsonTransformer();

        // When
        $decodedValue = $transformer($stringValue);

        // Then
        $this->assertNotNull($decodedValue);
        $this->assertIsArray($decodedValue);
        $this->assertEquals($decodedValue['name'], $name);
        $this->assertEquals($decodedValue['version'], $version);
    }
}
