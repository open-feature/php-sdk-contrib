<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\Test\unit\service;

use Exception;
use Flagsmith\Flagsmith;
use Flagsmith\Models\BaseFlag;
use Flagsmith\Models\Flags;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\Providers\Flagsmith\service\FlagEvaluator;
use OpenFeature\Providers\Flagsmith\Test\unit\fixtures\MockFlags;
use PHPUnit\Framework\TestCase;

class FlagEvaluatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private FlagEvaluator $evaluator;
    private Flagsmith $flagsmithClient;
    private Flags $mockFlags;

    /**
     * Create a mock BaseFlag from configuration.
     *
     * @param array{value: mixed, enabled: bool, isDefault: bool} $config
     */
    private function createMockFlag(array $config): BaseFlag
    {
        $flag = Mockery::mock(BaseFlag::class);
        $flag->shouldReceive('getValue')->andReturn($config['value']);
        $flag->shouldReceive('getEnabled')->andReturn($config['enabled']);
        $flag->shouldReceive('getIsDefault')->andReturn($config['isDefault']);
        return $flag;
    }

    protected function setUp(): void
    {
        $this->flagsmithClient = Mockery::mock(Flagsmith::class);
        $this->evaluator = new FlagEvaluator($this->flagsmithClient);

        // Set up mock flags collection
        $this->mockFlags = Mockery::mock(Flags::class);

        // Load all mock flags from MockFlags fixture
        $mockFlagsData = MockFlags::getAll();
        foreach ($mockFlagsData as $flagKey => $config) {
            $mockFlag = $this->createMockFlag($config);
            $this->mockFlags->shouldReceive('getFlag')
                ->with($flagKey)
                ->andReturn($mockFlag);
        }
    }

    // Boolean Resolver Tests
    public function testEvaluateBooleanWithEnabledFlag(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateBoolean('boolean_enabled_flag', false, null, null);

        $this->assertTrue($result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateBooleanWithDisabledFlag(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateBoolean('boolean_disabled_flag', true, null, null);

        $this->assertFalse($result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('DISABLED', $result->getReason());
    }

    public function testEvaluateBooleanWithIdentifierUsesTargetingMatch(): void
    {
        $traits = (object) ['plan' => 'premium'];

        $this->flagsmithClient->shouldReceive('getIdentityFlags')
            ->with('user-123', $traits)
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateBoolean('boolean_enabled_flag', false, 'user-123', $traits);

        $this->assertTrue($result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('TARGETING_MATCH', $result->getReason());
    }

    public function testEvaluateBooleanWithIdentifierButNoTraits(): void
    {
        $this->flagsmithClient->shouldReceive('getIdentityFlags')
            ->with('user-456', null)
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateBoolean('boolean_enabled_flag', false, 'user-456', null);

        $this->assertTrue($result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('TARGETING_MATCH', $result->getReason());
    }

    public function testEvaluateBooleanReturnsDefaultWhenExceptionThrown(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andThrow(new Exception('Network error'));

        $result = $this->evaluator->evaluateBoolean('any_flag', true, null, null);

        // Should return default value
        $this->assertTrue($result->getValue());
        // Should have error
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::GENERAL(), $result->getError()->getResolutionErrorCode());
        $this->assertStringContainsString('Network error', $result->getError()->getResolutionErrorMessage());
        // Reason should be ERROR
        $this->assertEquals('ERROR', $result->getReason());
    }

    public function testEvaluateBooleanWithIdentifierReturnsDefaultWhenExceptionThrown(): void
    {
        $this->flagsmithClient->shouldReceive('getIdentityFlags')
            ->with('user-error', null)
            ->once()
            ->andThrow(new Exception('API error'));

        $result = $this->evaluator->evaluateBoolean('any_flag', false, 'user-error', null);

        // Should return default value
        $this->assertFalse($result->getValue());
        // Should have error
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::GENERAL(), $result->getError()->getResolutionErrorCode());
        $this->assertEquals('ERROR', $result->getReason());
    }

    // String Resolver Tests
    public function testEvaluateStringReturnsValue(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateString('string_flag', 'default', null, null);

        $this->assertSame('test_string_value', $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateStringReturnsEmptyString(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateString('string_empty_flag', 'default', null, null);

        $this->assertSame('', $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateStringWithIdentifierUsesTargetingMatch(): void
    {
        $traits = (object) ['country' => 'US'];

        $this->flagsmithClient->shouldReceive('getIdentityFlags')
            ->with('user-789', $traits)
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateString('string_flag', 'default', 'user-789', $traits);

        $this->assertSame('test_string_value', $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('TARGETING_MATCH', $result->getReason());
    }

    public function testEvaluateStringWithIdentifierButNoTraits(): void
    {
        $this->flagsmithClient->shouldReceive('getIdentityFlags')
            ->with('user-999', null)
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateString('string_flag', 'default', 'user-999', null);

        $this->assertSame('test_string_value', $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('TARGETING_MATCH', $result->getReason());
    }

    public function testEvaluateStringReturnsDefaultWhenExceptionThrown(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andThrow(new Exception('Connection failed'));

        $result = $this->evaluator->evaluateString('any_flag', 'fallback_value', null, null);

        // Should return default value
        $this->assertSame('fallback_value', $result->getValue());
        // Should have error
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::GENERAL(), $result->getError()->getResolutionErrorCode());
        $this->assertStringContainsString('Connection failed', $result->getError()->getResolutionErrorMessage());
        // Reason should be ERROR
        $this->assertEquals('ERROR', $result->getReason());
    }

    public function testEvaluateStringWithIdentifierReturnsDefaultWhenExceptionThrown(): void
    {
        $this->flagsmithClient->shouldReceive('getIdentityFlags')
            ->with('error-user', null)
            ->once()
            ->andThrow(new Exception('Auth failed'));

        $result = $this->evaluator->evaluateString('any_flag', 'default_string', 'error-user', null);

        // Should return default value
        $this->assertSame('default_string', $result->getValue());
        // Should have error
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::GENERAL(), $result->getError()->getResolutionErrorCode());
        $this->assertEquals('ERROR', $result->getReason());
    }

    // Integer Resolver Tests
    public function testEvaluateIntegerReturnsValue(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateInteger('integer_flag', 0, null, null);

        $this->assertSame(42, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateIntegerReturnsZero(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateInteger('integer_zero_flag', 999, null, null);

        $this->assertSame(0, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateIntegerReturnsNegativeValue(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateInteger('integer_negative_flag', 0, null, null);

        $this->assertSame(-100, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateIntegerWithIdentifierUsesTargetingMatch(): void
    {
        $traits = (object) ['tier' => 'gold'];

        $this->flagsmithClient->shouldReceive('getIdentityFlags')
            ->with('user-int', $traits)
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateInteger('integer_flag', 0, 'user-int', $traits);

        $this->assertSame(42, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('TARGETING_MATCH', $result->getReason());
    }

    public function testEvaluateIntegerReturnsDefaultWhenExceptionThrown(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andThrow(new Exception('Network failure'));

        $result = $this->evaluator->evaluateInteger('any_flag', 123, null, null);

        $this->assertSame(123, $result->getValue());
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::GENERAL(), $result->getError()->getResolutionErrorCode());
        $this->assertEquals('ERROR', $result->getReason());
    }

    public function testEvaluateIntegerReturnsDefaultWhenFloatReceived(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateInteger('float_when_int_expected', 100, null, null);

        // Should return default due to type mismatch
        $this->assertSame(100, $result->getValue());
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::TYPE_MISMATCH(), $result->getError()->getResolutionErrorCode());
        $this->assertStringContainsString('Expected integer', $result->getError()->getResolutionErrorMessage());
        $this->assertEquals('ERROR', $result->getReason());
    }

    // Float Resolver Tests
    public function testEvaluateFloatReturnsValue(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateFloat('float_flag', 0.0, null, null);

        $this->assertSame(3.14159, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateFloatReturnsZero(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateFloat('float_zero_flag', 99.9, null, null);

        $this->assertSame(0.0, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateFloatReturnsNegativeValue(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateFloat('float_negative_flag', 0.0, null, null);

        $this->assertSame(-99.99, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateFloatWithIntegerValueCastsToFloat(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateFloat('int_for_float_flag', 0.0, null, null);

        // Integer 42 should be cast to float 42.0
        $this->assertSame(42.0, $result->getValue());
        $this->assertIsFloat($result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateFloatWithIdentifierUsesTargetingMatch(): void
    {
        $traits = (object) ['tier' => 'premium'];

        $this->flagsmithClient->shouldReceive('getIdentityFlags')
            ->with('user-float', $traits)
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateFloat('float_flag', 0.0, 'user-float', $traits);

        $this->assertSame(3.14159, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('TARGETING_MATCH', $result->getReason());
    }

    public function testEvaluateFloatReturnsDefaultWhenExceptionThrown(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andThrow(new Exception('Service unavailable'));

        $result = $this->evaluator->evaluateFloat('any_flag', 1.23, null, null);

        $this->assertSame(1.23, $result->getValue());
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::GENERAL(), $result->getError()->getResolutionErrorCode());
        $this->assertEquals('ERROR', $result->getReason());
    }

    public function testEvaluateFloatReturnsDefaultWhenStringReceived(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $result = $this->evaluator->evaluateFloat('string_when_float_expected', 5.55, null, null);

        // Should return default due to type mismatch
        $this->assertSame(5.55, $result->getValue());
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::TYPE_MISMATCH(), $result->getError()->getResolutionErrorCode());
        $this->assertStringContainsString('Expected float', $result->getError()->getResolutionErrorMessage());
        $this->assertEquals('ERROR', $result->getReason());
    }

    // Object Resolver Tests
    public function testEvaluateObjectWithJsonString(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $defaultValue = ['default' => true];
        $result = $this->evaluator->evaluateObject('object_json_string_flag', $defaultValue, null, null);

        // JSON string should be parsed to associative array
        $expected = ['name' => 'John', 'age' => 30];
        $this->assertEquals($expected, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateObjectWithAlreadyParsedObject(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $defaultValue = ['default' => true];
        $result = $this->evaluator->evaluateObject('object_already_parsed_flag', $defaultValue, null, null);

        // Object should be converted to associative array
        $expected = ['status' => 'active', 'level' => 5];
        $this->assertEquals($expected, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateObjectWithArray(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $defaultValue = [];
        $result = $this->evaluator->evaluateObject('object_array_flag', $defaultValue, null, null);

        // Array should be returned as-is
        $expected = ['item1', 'item2', 'item3'];
        $this->assertEquals($expected, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateObjectWithEmptyObject(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $defaultValue = ['has' => 'value'];
        $result = $this->evaluator->evaluateObject('object_empty_flag', $defaultValue, null, null);

        // Empty JSON object should parse to empty array
        $this->assertEquals([], $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('STATIC', $result->getReason());
    }

    public function testEvaluateObjectWithIdentifierUsesTargetingMatch(): void
    {
        $traits = (object) ['role' => 'admin'];

        $this->flagsmithClient->shouldReceive('getIdentityFlags')
            ->with('user-obj', $traits)
            ->once()
            ->andReturn($this->mockFlags);

        $defaultValue = [];
        $result = $this->evaluator->evaluateObject('object_json_string_flag', $defaultValue, 'user-obj', $traits);

        $expected = ['name' => 'John', 'age' => 30];
        $this->assertEquals($expected, $result->getValue());
        $this->assertNull($result->getError());
        $this->assertEquals('TARGETING_MATCH', $result->getReason());
    }

    public function testEvaluateObjectReturnsDefaultWhenExceptionThrown(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andThrow(new Exception('Connection timeout'));

        $defaultValue = ['fallback' => 'data'];
        $result = $this->evaluator->evaluateObject('any_flag', $defaultValue, null, null);

        $this->assertSame($defaultValue, $result->getValue());
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::GENERAL(), $result->getError()->getResolutionErrorCode());
        $this->assertEquals('ERROR', $result->getReason());
    }

    public function testEvaluateObjectReturnsDefaultWhenInvalidJson(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $defaultValue = ['safe' => 'default'];
        $result = $this->evaluator->evaluateObject('object_invalid_json_flag', $defaultValue, null, null);

        // Should return default due to invalid JSON
        $this->assertSame($defaultValue, $result->getValue());
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::PARSE_ERROR(), $result->getError()->getResolutionErrorCode());
        $this->assertStringContainsString('JSON', $result->getError()->getResolutionErrorMessage());
        $this->assertEquals('ERROR', $result->getReason());
    }

    public function testEvaluateObjectReturnsDefaultWhenNumberReceived(): void
    {
        $this->flagsmithClient->shouldReceive('getEnvironmentFlags')
            ->once()
            ->andReturn($this->mockFlags);

        $defaultValue = ['default' => 'value'];
        $result = $this->evaluator->evaluateObject('object_number_flag', $defaultValue, null, null);

        // Should return default due to type mismatch
        $this->assertSame($defaultValue, $result->getValue());
        $this->assertNotNull($result->getError());
        $this->assertEquals(ErrorCode::TYPE_MISMATCH(), $result->getError()->getResolutionErrorCode());
        $this->assertStringContainsString('Expected object', $result->getError()->getResolutionErrorMessage());
        $this->assertEquals('ERROR', $result->getReason());
    }
}
