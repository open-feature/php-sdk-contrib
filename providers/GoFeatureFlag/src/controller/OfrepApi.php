<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\controller;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\exception\BaseOfrepException;
use OpenFeature\Providers\GoFeatureFlag\exception\FlagNotFoundException;
use OpenFeature\Providers\GoFeatureFlag\exception\ParseException;
use OpenFeature\Providers\GoFeatureFlag\exception\RateLimitedException;
use OpenFeature\Providers\GoFeatureFlag\exception\UnauthorizedException;
use OpenFeature\Providers\GoFeatureFlag\exception\UnknownOfrepException;
use OpenFeature\Providers\GoFeatureFlag\model\OfrepApiErrorResponse;
use OpenFeature\Providers\GoFeatureFlag\model\OfrepApiSuccessResponse;
use OpenFeature\Providers\GoFeatureFlag\util\Validator;
use OpenFeature\interfaces\flags\EvaluationContext;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function array_merge;
use function is_numeric;
use function json_decode;
use function json_encode;
use function rtrim;
use function strtotime;
use function time;

class OfrepApi
{
    private ?int $retryAfter = null;
    private Config $options;
    private ClientInterface $client;

    public function __construct(Config $config)
    {
        $this->options = $config;
        $this->client = $config->getHttpClient() ?? new Client([
            'base_uri' => $config->getEndpoint(),
        ]);
    }

    /**
     * @throws ParseException
     * @throws FlagNotFoundException
     * @throws RateLimitedException
     * @throws UnauthorizedException
     * @throws UnknownOfrepException
     * @throws BaseOfrepException
     */
    public function evaluate(string $flagKey, EvaluationContext $evaluationContext): OfrepApiSuccessResponse | OfrepApiErrorResponse
    {
        try {
            if ($this->retryAfter !== null) {
                if (time() < $this->retryAfter) {
                    throw new RateLimitedException();
                } else {
                    $this->retryAfter = null;
                }
            }

            $baseUri = $this->options->getEndpoint();
            $evaluateApiPath = rtrim($baseUri, '/') . "/ofrep/v1/evaluate/flags/{$flagKey}";
            $headers = array_merge(
                ['Content-Type' => 'application/json'],
                $this->options->getCustomHeaders(),
            );

            $fields = array_merge(
                $evaluationContext->getAttributes()->toArray(),
                ['targetingKey' => $evaluationContext->getTargetingKey()],
            );

            $requestBody = json_encode(['context' => $fields]);
            if ($requestBody === false) {
                throw new ParseException('failed to encode request body');
            }
            $req = new Request('POST', $evaluateApiPath, $headers, $requestBody);
            $response = $this->client->sendRequest($req);

            switch ($response->getStatusCode()) {
                case 200:
                    return $this->parseSuccessResponse($response);
                case 400:
                    return $this->parseErrorResponse($response);
                case 401:
                case 403:
                    throw new UnauthorizedException($response);
                case 404:
                    throw new FlagNotFoundException($flagKey, $response);
                case 429:
                    $this->parseRetryLaterHeader($response);

                    throw new RateLimitedException($response);
                default:
                    throw new UnknownOfrepException($response);
            }
        } catch (BaseOfrepException $e) {
            throw $e;
        } catch (GuzzleException | Throwable $e) {
            echo $e;

            throw new UnknownOfrepException(null, $e);
        }
    }

    /**
     * @throws ParseException
     */
    private function parseSuccessResponse(ResponseInterface $response): OfrepApiSuccessResponse
    {
        /** @var array<string, mixed> $parsed */
        $parsed = json_decode($response->getBody()->getContents(), true);
        $parsed = Validator::validateSuccessApiResponse($parsed);

        return new OfrepApiSuccessResponse($parsed);
    }

    /**
     * @throws ParseException
     */
    private function parseErrorResponse(ResponseInterface $response): OfrepApiErrorResponse
    {
        /** @var array<string, mixed> $parsed */
        $parsed = json_decode($response->getBody()->getContents(), true);
        $parsed = Validator::validateErrorApiResponse($parsed);

        return new OfrepApiErrorResponse($parsed);
    }

    private function parseRetryLaterHeader(ResponseInterface $response): void
    {
        $retryAfterHeader = $response->getHeaderLine('Retry-After');
        if ($retryAfterHeader) {
            if (is_numeric($retryAfterHeader)) {
                // Retry-After is in seconds
                $this->retryAfter = time() + (int) $retryAfterHeader;
            } else {
                // Retry-After is in HTTP-date format
                $retryTime = strtotime($retryAfterHeader);
                $this->retryAfter = $retryTime !== false ? $retryTime : null;
            }
        }
    }
}
