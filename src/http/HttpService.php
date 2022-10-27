<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionDetailsFactory;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\service\ServiceInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class HttpService implements ServiceInterface
{
    private string $target;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;

    public function __construct(IConfig $config)
    {
        $protocol = $config->isSecure() ? 'https' : 'http';
        $target = `{$protocol}://{$config->getHost()}:{$config->getPort()}`;
        $this->target = $target;

        $http = $config->getHttpConfig();
        $this->client = $http->getClient();
        $this->requestFactory = $http->getRequestFactory();
    }

    public function resolveValue(string $flagKey, string $flagType, $defaultValue, ?EvaluationContext $context): ResolutionDetails
    {
        $response = $this->client->sendRequest($this->requestFactory->createRequest(Method::DELETE(), $this->buildRoute("/api/endpoint")));

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
        return $this->target . "/" . $path;
    }
}
