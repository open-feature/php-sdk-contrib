<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\treatments;

use OpenFeature\Providers\Split\errors\InvalidTreatmentTypeException;
use OpenFeature\interfaces\flags\FlagValueType;

use function filter_var;
use function is_bool;
use function is_numeric;
use function json_decode;
use function json_last_error;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const JSON_ERROR_NONE;

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
                    filter_var($value, FILTER_NULL_ON_FAILURE | FILTER_VALIDATE_BOOLEAN),
                );
            case FlagValueType::FLOAT:
            case FlagValueType::INTEGER:
                return is_numeric($value);
            case FlagValueType::OBJECT:
                /**
                 * @psalm-suppress UnusedFunctionCall
                 */
                json_decode($value);

                return json_last_error() === JSON_ERROR_NONE;
            case FlagValueType::STRING:
                return true;
            default:
                throw new InvalidTreatmentTypeException();
        }
    }
}
