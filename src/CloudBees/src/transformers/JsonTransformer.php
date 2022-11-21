<?php

declare(strict_types=1);

namespace OpenFeature\Providers\CloudBees\transformers;

use OpenFeature\Providers\CloudBees\errors\InvalidJsonTypeException;
use OpenFeature\Providers\CloudBees\errors\JsonParseException;

use function is_array;
use function json_decode;
use function json_last_error;

use const JSON_ERROR_NONE;

class JsonTransformer
{
    /**
     * @return mixed[]
     */
    public function __invoke(string $x)
    {
        $value = json_decode($x, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonParseException();
        }

        if (!is_array($value)) {
            throw new InvalidJsonTypeException();
        }

        return $value;
    }
}
