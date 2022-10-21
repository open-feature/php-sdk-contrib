<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\grpc;

use Exception;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenFeature\interfaces\provider\ThrowableWithResolutionError;
use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\grpc\ResponseResolutionDetailsAdapter;
use OpenFeature\Providers\Flagd\grpc\ResponseValidator;
use OpenFeature\Providers\Flagd\service\ServiceInterface;
use Schema\V1\ResolveBooleanRequest;
use Schema\V1\ResolveFloatRequest;
use Schema\V1\ResolveIntRequest;
use Schema\V1\ResolveObjectRequest;
use Schema\V1\ResolveStringRequest;
use Schema\V1\ServiceClient;

class GrpcService implements ServiceInterface
{
    public static function fromConfig(IConfig $config): GrpcService
    {
        $target = `{$config->getHost()}:{$config->getPort()}`;

        return new static($target);
    }

    private ServiceClient $client;

    private function __construct(string $hostname)
    {
        $this->service = new ServiceClient($hostname, [
            // todo: wire up credentials
            // 'credentials' => \Grpc\ChannelCredentials::createInsecure(),
        ]);
    }

    public function resolveValue(string $flagKey, string $flagType, $defaultValue, ?EvaluationContext $context): ResolutionDetails
    {
        try {
            $methodName = $this->getMethodName($flagType);
            $request = $this->getRequestInstance($flagType);

            $request->setFlagKey($flagKey);
            $request->setContext($context);

            [$response, $status] = $this->client->$methodName($request)->wait();

            // if (STATUS_OK !== $status) {
            //     // todo: handle unsuccessful evaluation
            // }

            if (!ResponseValidator::isResponse($response)) {
                // todo: handle invalid Response object
            }

            if (!ResponseValidator::isCorrectType($response, $flagType)) {
                // todo: handle type mismatch
            }

            return ResponseResolutionDetailsAdapter::fromResponse($response);
        } catch (Exception $err) {
            $resolutionError = ($err instanceof ThrowableWithResolutionError) ? $err->getResolutionError() : new ResolutionError(ErrorCode::GENERAL(), $err->getMessage);

            return (new ResolutionDetailsBuilder())->withError($resolutionError)->withValue($defaultValue)->build();
        }
    }

    private function getMethodName(string $flagType): string
    {
        switch ($flagType) {
            case FlagValueType::BOOLEAN:
                return "resolveBoolean";

            case FlagValueType::FLOAT:
                return "resolveFloat";

            case FlagValueType::INTEGER:
                return "resolveInteger";

            case FlagValueType::OBJECT:
                return "resolveObject";

            case FlagValueType::STRING:
                return "resolveString";
        }

        throw new ResolutionError(ErrorCode::GENERAL(), "Attempted to use invalid flag value type: " . $flagType);
    }

    private function getRequestInstance(string $flagType): mixed
    {
        switch ($flagType) {
            case FlagValueType::BOOLEAN:
                return new ResolveBooleanRequest();

            case FlagValueType::FLOAT:
                return new ResolveFloatRequest();

            case FlagValueType::INTEGER:
                return new ResolveIntRequest();

            case FlagValueType::OBJECT:
                return new ResolveObjectRequest();

            case FlagValueType::STRING:
                return new ResolveStringRequest();
        }

        throw new ResolutionError(ErrorCode::GENERAL(), "Attempted to use invalid flag value type: " . $flagType);
    }
}
