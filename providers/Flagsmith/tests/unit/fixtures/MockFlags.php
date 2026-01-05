<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\Test\unit\fixtures;

/**
 * Mock flag configurations for testing.
 * Each flag has: value, enabled, isDefault properties.
 */
class MockFlags
{
    /**
     * @return array<string, array{value: mixed, enabled: bool, isDefault: bool}>
     */
    public static function getAll(): array
    {
        return [
            // ========================================
            // DEFAULT FLAG (for FLAG_NOT_FOUND tests)
            // ========================================
            'default_flag' => [
                'value' => null,
                'enabled' => false,
                'isDefault' => true,
            ],

            // ========================================
            // BOOLEAN FLAGS
            // ========================================
            'boolean_enabled_flag' => [
                'value' => true,
                'enabled' => true,
                'isDefault' => false,
            ],
            'boolean_disabled_flag' => [
                'value' => false,
                'enabled' => false,
                'isDefault' => false,
            ],
            'boolean_non_boolean_value_flag' => [
                'value' => 'not a boolean',
                'enabled' => true,
                'isDefault' => false,
            ],

            // ========================================
            // STRING FLAGS
            // ========================================
            'string_flag' => [
                'value' => 'test_string_value',
                'enabled' => true,
                'isDefault' => false,
            ],
            'string_empty_flag' => [
                'value' => '',
                'enabled' => true,
                'isDefault' => false,
            ],
            'string_disabled_flag' => [
                'value' => 'disabled_string',
                'enabled' => false,
                'isDefault' => false,
            ],

            // ========================================
            // INTEGER FLAGS
            // ========================================
            'integer_flag' => [
                'value' => 42,
                'enabled' => true,
                'isDefault' => false,
            ],
            'integer_zero_flag' => [
                'value' => 0,
                'enabled' => true,
                'isDefault' => false,
            ],
            'integer_negative_flag' => [
                'value' => -100,
                'enabled' => true,
                'isDefault' => false,
            ],
            'integer_disabled_flag' => [
                'value' => 777,
                'enabled' => false,
                'isDefault' => false,
            ],
            'float_when_int_expected' => [
                'value' => 3.14,
                'enabled' => true,
                'isDefault' => false,
            ],
            'string_float_when_int_expected' => [
                'value' => '3.14',
                'enabled' => true,
                'isDefault' => false,
            ],

            // ========================================
            // FLOAT FLAGS
            // ========================================
            'float_flag' => [
                'value' => 3.14159,
                'enabled' => true,
                'isDefault' => false,
            ],
            'float_zero_flag' => [
                'value' => 0.0,
                'enabled' => true,
                'isDefault' => false,
            ],
            'float_negative_flag' => [
                'value' => -99.99,
                'enabled' => true,
                'isDefault' => false,
            ],
            'float_disabled_flag' => [
                'value' => 8.88,
                'enabled' => false,
                'isDefault' => false,
            ],
            'int_for_float_flag' => [
                'value' => 42,
                'enabled' => true,
                'isDefault' => false,
            ],
            'string_when_float_expected' => [
                'value' => 'not a number',
                'enabled' => true,
                'isDefault' => false,
            ],

            // ========================================
            // OBJECT FLAGS
            // ========================================
            'object_json_string_flag' => [
                'value' => '{"name":"John","age":30}',
                'enabled' => true,
                'isDefault' => false,
            ],
            'object_already_parsed_flag' => [
                'value' => (object) ['status' => 'active', 'level' => 5],
                'enabled' => true,
                'isDefault' => false,
            ],
            'object_array_flag' => [
                'value' => ['item1', 'item2', 'item3'],
                'enabled' => true,
                'isDefault' => false,
            ],
            'object_empty_flag' => [
                'value' => '{}',
                'enabled' => true,
                'isDefault' => false,
            ],
            'object_disabled_flag' => [
                'value' => ['disabled' => true, 'reason' => 'maintenance'],
                'enabled' => false,
                'isDefault' => false,
            ],
            'object_invalid_json_flag' => [
                'value' => '{invalid json}',
                'enabled' => true,
                'isDefault' => false,
            ],
            'object_number_flag' => [
                'value' => 42,
                'enabled' => true,
                'isDefault' => false,
            ],
            'object_scalar_json_string_flag' => [
                'value' => '42',
                'enabled' => true,
                'isDefault' => false,
            ],
        ];
    }
}
