<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\exception;

use Exception;
use Throwable;

class InvalidConfigException extends Exception
{
    private string $customMessage;

    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        $this->customMessage = $message;
        parent::__construct($message, $code, $previous);
    }

    public function getCustomMessage(): string
    {
        return $this->customMessage;
    }
}
