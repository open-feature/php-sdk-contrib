<?php

namespace OpenFeature\Providers\GoFeatureFlag\exception;

class InvalidConfigException extends \Exception
{
    private string $customMessage;

    public function __construct(string $message, int $code = 0, \Exception $previous = null)
    {
        $this->customMessage = $message;
        parent::__construct($message, $code, $previous);
    }

    public function getCustomMessage(): string
    {
        return $this->customMessage;
    }
}