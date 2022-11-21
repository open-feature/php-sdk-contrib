<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\errors;

use Exception;

class SplitFactoryCreationException extends Exception
{
    public function __construct()
    {
        parent::__construct('Failed to create Split Factory. Was it already instantiated elsewhere?');
    }
}
