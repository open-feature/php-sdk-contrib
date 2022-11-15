<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\treatments;

use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\Providers\Flagd\errors\InvalidTypeException;

use function filter_var;
use function json_decode;
use function json_last_error;
use function is_bool;
use function is_numeric;
use function is_string;

class TreatmentValidator
{
    /**
     * @param string $flagType The type of flag value to validate as
     */
    public static function validate(string $flagType, string $value): bool
    {
        switch ($flagType) {
            case FlagValueType::BOOLEAN:
                return is_bool(
                    filter_var($value, FILTER_VALIDATE_BOOLEAN),
                );

            case FlagValueType::FLOAT:
            case FlagValueType::INTEGER:
                return is_numeric($value);

            case FlagValueType::OBJECT:
                if (!is_string($value)) {
                    return False;
                }

                json_decode($value);
                return json_last_error() === JSON_ERROR_NONE;

            case FlagValueType::STRING:
                return is_string($value);

            default:
                throw new InvalidTypeException();
        }
    }
}