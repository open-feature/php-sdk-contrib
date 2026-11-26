<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\treatments;

use OpenFeature\Providers\Split\errors\InvalidTreatmentTypeException;
use OpenFeature\interfaces\flags\FlagValueType;

use function filter_var;
use function is_array;
use function is_bool;
use function is_numeric;
use function json_decode;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_INT;

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
                    filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                );
            case FlagValueType::FLOAT:
                return is_numeric($value);
            case FlagValueType::INTEGER:
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            case FlagValueType::OBJECT:
                // Valid JSON scalars such as "42" or "null" are not objects
                return is_array(json_decode($value, true));
            case FlagValueType::STRING:
                return true;
            default:
                throw new InvalidTreatmentTypeException();
        }
    }
}
