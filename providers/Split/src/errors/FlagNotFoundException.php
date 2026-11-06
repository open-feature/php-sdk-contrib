<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Split\errors;

use Exception;
use OpenFeature\implementation\provider\ResolutionError as ProviderResolutionError;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\ResolutionError;
use OpenFeature\interfaces\provider\ThrowableWithResolutionError;

class FlagNotFoundException extends Exception implements ThrowableWithResolutionError
{
    private ResolutionError $resolutionError;

    public function __construct()
    {
        parent::__construct('Split returned the control treatment for the flag');
        $this->resolutionError = new ProviderResolutionError(ErrorCode::FLAG_NOT_FOUND(), 'The flag was not found or could not be evaluated by Split');
    }

    public function getResolutionError(): ResolutionError
    {
        return $this->resolutionError;
    }
}
