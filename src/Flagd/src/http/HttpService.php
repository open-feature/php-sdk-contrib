<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

use OpenFeature\Providers\Flagd\common\EvaluationContextArrayFactory;
use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\errors\InvalidConfigException;
use OpenFeature\Providers\Flagd\errors\RequestBuildException;
use OpenFeature\Providers\Flagd\service\ServiceInterface;
use OpenFeature\implementation\errors\FlagValueTypeError;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
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

class HttpService implements ServiceInterface
{
    private string $target;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    private const FLAGD_GRPC_WEB_HEADERS = [
        ['Content-Type', 'application/json'],
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

        $client = $http->getClient(); // TODO: Support $http->getAsyncClient();
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
     * @param mixed $defaultValue
     */
    public function resolveValue(string $flagKey, string $flagType, $defaultValue, ?EvaluationContext $context): ResolutionDetails
    {
        $path = $this->determinePathByFlagType($flagType);

        $response = $this->sendRequest($path, $flagKey, $context);

        /** @var string[] $details */
        $details = json_decode((string) $response->getBody(), true);

        // @todo: validate

        return (new ResolutionDetailsBuilder())
            ->withValue($details['value'])
            ->withVariant($details['variant'])
            ->withReason($details['reason'])
            ->build();
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
         *      -d '{"flagKey": key, "context": evaluation_context}'
         */

        $request = $this->requestFactory->createRequest(Method::POST, $this->buildRoute($path));

        foreach (self::FLAGD_GRPC_WEB_HEADERS as $headerInfo) {
            $request = $request->withHeader(...$headerInfo);
        }

        $contextArray = EvaluationContextArrayFactory::build($context);

        $bodyString = json_encode([
            'flagKey' => $flagKey,
            'context' => $contextArray,
        ]);

        if ($bodyString === false) {
            throw new RequestBuildException();
        }

        $bodyStream = $this->streamFactory->createStream($bodyString);

        $request->withBody($bodyStream);

        return $this->client->sendRequest($request);
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

    // TODO: Coerce the type if it's a string due to gRPC handling of integers
    // private function coerceTypeIfNecessary()
}
