<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\treatments;

use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\Providers\Flagd\errors\InvalidTypeException;

use function filter_var;
use function json_decode;

class TreatmentParser
{
    /**
     * @param string $flagType The type of flag value to parse as
     * @return bool|string|int|float|mixed[]
     */
    public static function parse(string $flagType, string $value)
    {
        switch ($flagType) {
            case FlagValueType::BOOLEAN:
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case FlagValueType::FLOAT:
                return (float) $value;

            case FlagValueType::INTEGER:
                return (int) $value;

            case FlagValueType::OBJECT:
                return json_decode($value);

            case FlagValueType::STRING:
                return $value;

            default:
                throw new InvalidTypeException();
        }
    }
}