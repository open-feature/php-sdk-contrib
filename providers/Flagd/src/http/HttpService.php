<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

use DateTime;
use OpenFeature\Providers\Flagd\common\EvaluationContextArrayFactory;
use OpenFeature\Providers\Flagd\config\Defaults;
use OpenFeature\Providers\Flagd\config\EvaluationApis;
use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\errors\InvalidConfigException;
use OpenFeature\Providers\Flagd\errors\InvalidTypeException;
use OpenFeature\Providers\Flagd\errors\RequestBuildException;
use OpenFeature\Providers\Flagd\service\ServiceInterface;
use OpenFeature\implementation\errors\FlagValueTypeError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function intval;
use function is_numeric;
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
    private string $evaluationApi;

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

        return new HttpService($target, $client, $requestFactory, $streamFactory, $config->getEvaluationApi());
    }

    public function __construct(string $target, ClientInterface $client, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory, ?string $evaluationApi = null)
    {
        $this->target = $target;
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->evaluationApi = $evaluationApi ?? Defaults::DEFAULT_EVALUATION_API;
    }

    /**
     * @inheritdoc
     */
    public function resolveValue(string $flagKey, string $flagType, $defaultValue, ?EvaluationContext $context): ResolutionDetails
    {
        $path = $this->determinePathByFlagType($flagType);

        $response = $this->sendRequest($path, $flagKey, $context);

        /** @var string[] $details */
        $details = json_decode((string) $response->getBody(), true);

        if (FlagdResponseValidator::isTypeMismatch($details)) {
            return FlagdResponseResolutionDetailsAdapter::forTypeMismatch($defaultValue);
        }

        if ($this->evaluationApi === EvaluationApis::V2) {
            if (FlagdResponseValidator::isErrorResponseV2($details)) {
                return FlagdResponseResolutionDetailsAdapter::forError($details, $defaultValue);
            }

            // v2 omits `value` entirely when the flag resolves without one, so presence of the
            // field is authoritative and no reason/variant heuristic is required.
            if (FlagdResponseValidator::hasNoValue($details)) {
                return FlagdResponseResolutionDetailsAdapter::forAbsentValue($defaultValue, $details['reason'] ?? null);
            }
        } else {
            if (FlagdResponseValidator::isErrorResponse($details)) {
                return FlagdResponseResolutionDetailsAdapter::forError($details, $defaultValue);
            }

            // v1 cannot represent an absent value, so a disabled flag arrives zero-filled and has
            // to be inferred from the reason plus an empty variant.
            if (($details['reason'] ?? null) === Reason::DISABLED && ($details['variant'] ?? '') === '') {
                return FlagdResponseResolutionDetailsAdapter::forDisabled($defaultValue);
            }
        }

        if ($flagType === FlagValueType::INTEGER) {
            $this->mapIntegerInResponse($details);
        }

        /** @var array{value: mixed[]|bool|DateTime|float|int|string|null, variant: ?string, reason: ?string} $validDetails */
        $validDetails = $details;

        return FlagdResponseResolutionDetailsAdapter::forSuccess($validDetails);
    }

    private function buildRoute(string $path): string
    {
        return $this->target . '/' . $path;
    }

    private function sendRequest(string $path, string $flagKey, ?EvaluationContext $context): ResponseInterface
    {
        /**
         * This method is equivalent to:
         * curl -X POST http://localhost:8013/{path} \
         *      -H "Content-Type: application/json" \
         *      -d '{"flag_key": key, "context": evaluation_context}'
         */

        $request = $this->requestFactory->createRequest(Method::POST, $this->buildRoute($path));

        foreach (self::FLAGD_GRPC_WEB_HEADERS as $headerInfo) {
            $request = $request->withHeader(...$headerInfo);
        }

        $contextArray = EvaluationContextArrayFactory::build($context);

        $bodyString = json_encode([
            'flag_key' => $flagKey,
            'context' => $contextArray,
        ], JSON_UNESCAPED_UNICODE);

        if ($bodyString === false) {
            throw new RequestBuildException();
        }

        $bodyStream = $this->streamFactory->createStream($bodyString);

        $request = $request->withBody($bodyStream);

        return $this->client->sendRequest($request);
    }

    private function determinePathByFlagType(string $flagType): string
    {
        $isV2 = $this->evaluationApi === EvaluationApis::V2;

        switch ($flagType) {
            case FlagValueType::BOOLEAN:
                return $isV2 ? GrpcWebEndpoint::BOOLEAN_V2 : GrpcWebEndpoint::BOOLEAN;
            case FlagValueType::FLOAT:
                return $isV2 ? GrpcWebEndpoint::FLOAT_V2 : GrpcWebEndpoint::FLOAT;
            case FlagValueType::INTEGER:
                return $isV2 ? GrpcWebEndpoint::INTEGER_V2 : GrpcWebEndpoint::INTEGER;
            case FlagValueType::OBJECT:
                return $isV2 ? GrpcWebEndpoint::OBJECT_V2 : GrpcWebEndpoint::OBJECT;
            case FlagValueType::STRING:
                return $isV2 ? GrpcWebEndpoint::STRING_V2 : GrpcWebEndpoint::STRING;
            default:
                throw new FlagValueTypeError($flagType);
        }
    }

    /**
     * @param mixed[] $details
     */
    private function mapIntegerInResponse(array &$details): void
    {
        $value = $details['value'];

        if (!is_numeric($value)) {
            throw new InvalidTypeException();
        }

        $details['value'] = intval($value);
    }
}
