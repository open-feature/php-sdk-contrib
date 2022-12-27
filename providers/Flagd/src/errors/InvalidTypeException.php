<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\errors;

use Exception;

class InvalidTypeException extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid Type');
    }
}
