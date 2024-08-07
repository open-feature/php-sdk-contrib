<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\exception;

use OpenFeature\interfaces\provider\ErrorCode;
use Psr\Http\Message\ResponseInterface;

abstract class BaseGoffException extends \Exception
{
    private string $customMessage;
    private ?ResponseInterface $response;
    private ErrorCode $errorCode;

    public function __construct(string $message, ErrorCode $errorCode, ?ResponseInterface $response, int $code = 0, \Exception $previous = null)
    {
        $this->customMessage = $message;
        $this->response = $response;
        $this->errorCode = $errorCode;
        parent::__construct($message, $code, $previous);
    }

    public function getCustomMessage(): string
    {
        return $this->customMessage;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getErrorCode(): ErrorCode
    {
        return $this->errorCode;
    }
}