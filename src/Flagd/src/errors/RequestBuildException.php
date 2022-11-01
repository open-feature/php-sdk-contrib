<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\errors;

use Exception;

class RequestBuildException extends Exception
{
    public function __construct()
    {
        parent::__construct('Failed to create JSON payload');
    }
}
