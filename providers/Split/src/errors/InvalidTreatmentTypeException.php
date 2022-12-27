<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\errors;

use Exception;
use OpenFeature\implementation\provider\ResolutionError as ProviderResolutionError;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\ResolutionError;
use OpenFeature\interfaces\provider\ThrowableWithResolutionError;

class InvalidTreatmentTypeException extends Exception implements ThrowableWithResolutionError
{
    private ResolutionError $resolutionError;

    public function __construct()
    {
        parent::__construct('The treatment value does not match the expected type');
        $this->resolutionError = new ProviderResolutionError(ErrorCode::TYPE_MISMATCH(), 'Treatment value does not match the expected type');
    }

    public function getResolutionError(): ResolutionError
    {
        return $this->resolutionError;
    }
}
