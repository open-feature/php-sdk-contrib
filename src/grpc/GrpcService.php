<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\grpc;

use Google\Protobuf\Struct;
use Grpc;
use Grpc\ChannelCredentials;
use Grpc\UnaryCall;
use OpenFeature\Providers\Flagd\common\EvaluationContextArrayFactory;
use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\service\ServiceInterface;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Schema\V1\ResolveBooleanRequest;
use Schema\V1\ResolveBooleanResponse;
use Schema\V1\ResolveFloatRequest;
use Schema\V1\ResolveFloatResponse;
use Schema\V1\ResolveIntRequest;
use Schema\V1\ResolveIntResponse;
use Schema\V1\ResolveObjectRequest;
use Schema\V1\ResolveObjectResponse;
use Schema\V1\ResolveStringRequest;
use Schema\V1\ResolveStringResponse;
use Schema\V1\ServiceClient;

use function sprintf;

class GrpcService implements ServiceInterface
{
    public static function fromConfig(IConfig $config): GrpcService
    {
        $target = sprintf('%s:%d', $config->getHost(), $config->getPort());
        $secure = $config->isSecure();

        return new GrpcService($target, $secure);
    }

    private ServiceClient $client;

    private function __construct(string $hostname, bool $secure)
    {
        /**
         * @psalm-suppress UndefinedClass
         * @var ChannelCredentials $credentials
         */
        $credentials = $secure ? ChannelCredentials::createSsl() : ChannelCredentials::createInsecure();

        $this->client = new ServiceClient($hostname, [
            'credentials' => $credentials,
        ]);
    }

    /**
     * @param mixed $defaultValue
     */
    public function resolveValue(string $flagKey, string $flagType, $defaultValue, ?EvaluationContext $context): ResolutionDetails
    {
        $methodName = $this->getMethodName($flagType);
        $request = $this->getRequestInstance($flagType);

        $request->setFlagKey($flagKey);
        $request->setContext($this->buildContextAsStruct($context));

        /** @var UnaryCall $clientCall */
        $clientCall = $this->client->$methodName($request);

        /** @var mixed $maybeResponse */
        /** @var mixed $status */
        [$maybeResponse, $status] = $clientCall->wait();

        if (!$this->isSuccessStatus($status)) {
            $this->throwForStatus($status);
        }

        if (!ResponseValidator::isResponse($maybeResponse)) {
            throw new ResolutionError(ErrorCode::PARSE_ERROR(), 'The response type could not be parsed');
        }

        /** @var ResolveBooleanResponse|ResolveFloatResponse|ResolveIntResponse|ResolveObjectResponse|ResolveStringResponse $response */
        $response = $maybeResponse;

        if (!ResponseValidator::isCorrectType($response, $flagType)) {
            throw new ResolutionError(ErrorCode::TYPE_MISMATCH(), 'The resolution type is incorrect');
        }

        return ResponseResolutionDetailsAdapter::fromResponse($response);
    }

    private function getMethodName(string $flagType): string
    {
        switch ($flagType) {
            case FlagValueType::BOOLEAN:
                return 'resolveBoolean';
            case FlagValueType::FLOAT:
                return 'resolveFloat';
            case FlagValueType::INTEGER:
                return 'resolveInteger';
            case FlagValueType::OBJECT:
                return 'resolveObject';
            case FlagValueType::STRING:
                return 'resolveString';
        }

        throw new ResolutionError(ErrorCode::GENERAL(), 'Attempted to use invalid flag value type: ' . $flagType);
    }

    /**
     * @return ResolveBooleanRequest|ResolveFloatRequest|ResolveIntRequest|ResolveObjectRequest|ResolveStringRequest
     */
    private function getRequestInstance(string $flagType)
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

        throw new ResolutionError(ErrorCode::GENERAL(), 'Attempted to use invalid flag value type: ' . $flagType);
    }

    /**
     * @param mixed $status
     */
    private function isSuccessStatus($status): bool
    {
        /**
         * @psalm-suppress UndefinedConstant
         */
        return $status === Grpc\STATUS_OK;
    }

    /**
     * @param mixed $status
     */
    private function throwForStatus($status): void
    {
        switch ($status) {
            default:
                throw new ResolutionError(ErrorCode::GENERAL(), 'Error occurred in gRPC call');
        }
    }

    private function buildContextAsStruct(?EvaluationContext $context): Struct
    {
        $contextArray = EvaluationContextArrayFactory::build($context);

        return new Struct($contextArray);
    }
}
