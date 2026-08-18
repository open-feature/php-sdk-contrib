<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

use DateTime;
use Google\Protobuf\Struct;
use OpenFeature\Providers\Flagd\Schema\Grpc\Evaluation\V2\ResolveBooleanRequest;
use OpenFeature\Providers\Flagd\Schema\Grpc\Evaluation\V2\ResolveBooleanResponse;
use OpenFeature\Providers\Flagd\Schema\Grpc\Evaluation\V2\ResolveFloatRequest;
use OpenFeature\Providers\Flagd\Schema\Grpc\Evaluation\V2\ResolveFloatResponse;
use OpenFeature\Providers\Flagd\Schema\Grpc\Evaluation\V2\ResolveIntRequest;
use OpenFeature\Providers\Flagd\Schema\Grpc\Evaluation\V2\ResolveIntResponse;
use OpenFeature\Providers\Flagd\Schema\Grpc\Evaluation\V2\ResolveObjectRequest;
use OpenFeature\Providers\Flagd\Schema\Grpc\Evaluation\V2\ResolveObjectResponse;
use OpenFeature\Providers\Flagd\Schema\Grpc\Evaluation\V2\ResolveStringRequest;
use OpenFeature\Providers\Flagd\Schema\Grpc\Evaluation\V2\ResolveStringResponse;
use OpenFeature\Providers\Flagd\common\EvaluationContextArrayFactory;
use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\errors\InvalidConfigException;
use OpenFeature\Providers\Flagd\errors\RequestBuildException;
use OpenFeature\Providers\Flagd\service\ServiceInterface;
use OpenFeature\implementation\errors\FlagValueTypeError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function json_decode;
use function json_encode;
use function sprintf;

use const JSON_UNESCAPED_UNICODE;

class HttpService implements ServiceInterface
{
    private string $target;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    private const FLAGD_GRPC_WEB_HEADERS = [
        ['content-type', 'application/json'],
    ];

    public static function fromConfig(IConfig $config): HttpService
    {
        $protocol = $config->isSecure() ? 'https' : 'http';
        $host = $config->getHost();
        $port = $config->getPort();
        $target = sprintf('%s://%s:%d', $protocol, $host, $port);

        $http = $config->getHttpConfig();
        if (!$http) {
            throw new InvalidConfigException("'http' config property is required to use an HTTP service");
        }

        $client = $http->getClient();
        $requestFactory = $http->getRequestFactory();
        $streamFactory = $http->getStreamFactory();

        return new HttpService($target, $client, $requestFactory, $streamFactory);
    }

    public function __construct(string $target, ClientInterface $client, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory)
    {
        $this->target = $target;
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolveValue(string $flagKey, string $flagType, $defaultValue, ?EvaluationContext $context): ResolutionDetails
    {
        $path = $this->determinePathByFlagType($flagType);
        $body = $this->buildRequestBody($flagType, $flagKey, $context);

        $response = $this->sendRequest($path, $body);
        $payload = (string) $response->getBody();

        // Error envelopes aren't one of the generated message types, so they are still detected
        // from the decoded JSON and handled as before.
        /** @var mixed[]|null $errorCheck */
        $errorCheck = json_decode($payload, true);

        if (FlagdResponseValidator::isTypeMismatch($errorCheck)) {
            return FlagdResponseResolutionDetailsAdapter::forTypeMismatch($defaultValue);
        }

        if (FlagdResponseValidator::isErrorResponse($errorCheck)) {
            /** @var string[] $errorCheck */
            return FlagdResponseResolutionDetailsAdapter::forError($errorCheck, $defaultValue);
        }

        return $this->parseResponse($flagType, $payload, $defaultValue);
    }

    /**
     * @param mixed[]|bool|DateTime|float|int|string|null $defaultValue
     */
    private function parseResponse(string $flagType, string $payload, array | bool | DateTime | float | int | string | null $defaultValue): ResolutionDetails
    {
        $message = $this->createResponseMessage($flagType);
        $message->mergeFromJsonString($payload);

        // v2 declares `value` as an `optional` field, so an unset value is authoritative: it means
        // the flag resolved without one (a disabled flag, or a flag with no default variant).
        if (!$message->hasValue()) {
            return FlagdResponseResolutionDetailsAdapter::forAbsentValue(
                $defaultValue,
                $this->nonEmptyOrNull($message->getReason()),
            );
        }

        return FlagdResponseResolutionDetailsAdapter::forSuccess([
            'value' => $this->readValue($flagType, $message),
            'variant' => $message->hasVariant() ? $message->getVariant() : null,
            'reason' => $this->nonEmptyOrNull($message->getReason()),
        ]);
    }

    /**
     * @return mixed[]|bool|float|int|string|null
     */
    private function readValue(string $flagType, ResolveBooleanResponse | ResolveFloatResponse | ResolveIntResponse | ResolveObjectResponse | ResolveStringResponse $message)
    {
        if ($message instanceof ResolveObjectResponse) {
            $struct = $message->getValue();

            /** @var mixed[]|null $decoded */
            $decoded = $struct instanceof Struct ? json_decode($struct->serializeToJsonString(), true) : null;

            return $decoded;
        }

        if ($message instanceof ResolveIntResponse) {
            // v2 encodes 64-bit integers as JSON strings; the generated getter normalises them
            // back to a native int on 64-bit platforms and a numeric string otherwise.
            return (int) $message->getValue();
        }

        return $message->getValue();
    }

    private function buildRequestBody(string $flagType, string $flagKey, ?EvaluationContext $context): string
    {
        /**
         * The request is equivalent to:
         * curl -X POST http://localhost:8013/{path} \
         *      -H "Content-Type: application/json" \
         *      -d '{"flagKey": key, "context": evaluation_context}'
         */
        $message = match ($flagType) {
            FlagValueType::BOOLEAN => new ResolveBooleanRequest(),
            FlagValueType::FLOAT => new ResolveFloatRequest(),
            FlagValueType::INTEGER => new ResolveIntRequest(),
            FlagValueType::OBJECT => new ResolveObjectRequest(),
            FlagValueType::STRING => new ResolveStringRequest(),
            default => throw new FlagValueTypeError($flagType),
        };

        $message->setFlagKey($flagKey);

        $contextStruct = $this->buildContextStruct($context);
        if ($contextStruct !== null) {
            $message->setContext($contextStruct);
        }

        return $message->serializeToJsonString();
    }

    private function buildContextStruct(?EvaluationContext $context): ?Struct
    {
        $contextArray = EvaluationContextArrayFactory::build($context);
        if ($contextArray === null) {
            return null;
        }

        $encoded = json_encode($contextArray, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new RequestBuildException();
        }

        $struct = new Struct();
        $struct->mergeFromJsonString($encoded);

        return $struct;
    }

    private function buildRoute(string $path): string
    {
        return $this->target . '/' . $path;
    }

    private function sendRequest(string $path, string $body): ResponseInterface
    {
        $request = $this->requestFactory->createRequest(Method::POST, $this->buildRoute($path));

        foreach (self::FLAGD_GRPC_WEB_HEADERS as $headerInfo) {
            $request = $request->withHeader(...$headerInfo);
        }

        $bodyStream = $this->streamFactory->createStream($body);

        $request = $request->withBody($bodyStream);

        return $this->client->sendRequest($request);
    }

    /**
     * @return ResolveBooleanResponse|ResolveFloatResponse|ResolveIntResponse|ResolveObjectResponse|ResolveStringResponse
     */
    private function createResponseMessage(string $flagType)
    {
        return match ($flagType) {
            FlagValueType::BOOLEAN => new ResolveBooleanResponse(),
            FlagValueType::FLOAT => new ResolveFloatResponse(),
            FlagValueType::INTEGER => new ResolveIntResponse(),
            FlagValueType::OBJECT => new ResolveObjectResponse(),
            FlagValueType::STRING => new ResolveStringResponse(),
            default => throw new FlagValueTypeError($flagType),
        };
    }

    private function determinePathByFlagType(string $flagType): string
    {
        switch ($flagType) {
            case FlagValueType::BOOLEAN:
                return GrpcWebEndpoint::BOOLEAN;
            case FlagValueType::FLOAT:
                return GrpcWebEndpoint::FLOAT;
            case FlagValueType::INTEGER:
                return GrpcWebEndpoint::INTEGER;
            case FlagValueType::OBJECT:
                return GrpcWebEndpoint::OBJECT;
            case FlagValueType::STRING:
                return GrpcWebEndpoint::STRING;
            default:
                throw new FlagValueTypeError($flagType);
        }
    }

    private function nonEmptyOrNull(string $value): ?string
    {
        return $value !== '' ? $value : null;
    }
}
