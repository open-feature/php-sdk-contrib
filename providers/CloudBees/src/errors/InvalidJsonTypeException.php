<?php

declare(strict_types=1);

namespace OpenFeature\Providers\CloudBees\errors;

use Exception;
use OpenFeature\implementation\provider\ResolutionError as ResolutionErrorImpl;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\ResolutionError;
use OpenFeature\interfaces\provider\ThrowableWithResolutionError;

class InvalidJsonTypeException extends Exception implements ThrowableWithResolutionError
{
    private ResolutionError $resolutionError;

    public function __construct()
    {
        parent::__construct('The JSON type was invalid');
        $this->resolutionError = new ResolutionErrorImpl(ErrorCode::PARSE_ERROR(), 'An error occurred while parsing the JSON');
    }

    public function getResolutionError(): ResolutionError
    {
        return $this->resolutionError;
    }
}
