<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\treatments;

use OpenFeature\Providers\Split\errors\InvalidTreatmentTypeException;
use OpenFeature\interfaces\flags\FlagValueType;

use function filter_var;
use function json_decode;

use const FILTER_VALIDATE_BOOLEAN;

class TreatmentParser
{
    /**
     * @param string $flagType The type of flag value to parse as
     *
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
                /** @var mixed[] $object */
                $object = json_decode($value, true);

                return $object;
            case FlagValueType::STRING:
                return $value;
            default:
                throw new InvalidTreatmentTypeException();
        }
    }
}
