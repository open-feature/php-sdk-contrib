<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\errors;

use Exception;
use OpenFeature\implementation\provider\ResolutionError as ProviderResolutionError;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\ResolutionError;
use OpenFeature\interfaces\provider\ThrowableWithResolutionError;

class TargetingKeyMissingException extends Exception implements ThrowableWithResolutionError
{
    private ResolutionError $resolutionError;

    public function __construct()
    {
        parent::__construct('The targeting key was not included in the evaluation context');
        $this->resolutionError = new ProviderResolutionError(ErrorCode::TARGETING_KEY_MISSING(), 'The targeting key is required for Split');
    }

    public function getResolutionError(): ResolutionError
    {
        return $this->resolutionError;
    }
}
