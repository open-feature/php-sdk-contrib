<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\controller;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\exception\BaseOfrepException;
use OpenFeature\Providers\GoFeatureFlag\exception\FlagNotFoundException;
use OpenFeature\Providers\GoFeatureFlag\exception\ParseException;
use OpenFeature\Providers\GoFeatureFlag\exception\RateLimitedException;
use OpenFeature\Providers\GoFeatureFlag\exception\UnauthorizedException;
use OpenFeature\Providers\GoFeatureFlag\exception\UnknownOfrepException;
use OpenFeature\Providers\GoFeatureFlag\model\OfrepApiResponse;
use Psr\Http\Message\ResponseInterface;

class OfrepApi
{
    private ?int $retryAfter = null;
    private Config $options;
    private Client $client;

    public function __construct(Config $config)
    {
        $this->options = $config;
        $this->client = new Client([
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
    public function evaluate(string $flagKey, EvaluationContext $evaluationContext): OfrepApiResponse
    {
        try {
            if ($this->retryAfter !== null) {
                if (time() < $this->retryAfter) {
                    throw new RateLimitedException();
                } else {
                    $this->retryAfter = null;
                }
            }

            $base_uri = $this->options->getEndpoint();
            $evaluateApiPath = rtrim($base_uri, '/') . "/ofrep/v1/evaluate/flags/{$flagKey}";
            $headers = [
                'Content-Type' => 'application/json'
            ];

            if ($this->options->getCustomHeaders() !== null) {
                $headers = array_merge($headers, $this->options->getCustomHeaders());
            }

            $fields = array_merge(
                $evaluationContext->getAttributes()->toArray(),
                ['targetingKey' => $evaluationContext->getTargetingKey()]
            );

            $requestBody = json_encode(['context' => $fields]);
            $response = $this->client->post($evaluateApiPath, [
                'headers' => $headers,
                'body' => $requestBody
            ]);

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
        } catch (GuzzleException|Exception $e) {
            throw new UnknownOfrepException(null, $e);
        }
    }

    /**
     * @throws ParseException
     */
    private function parseSuccessResponse(ResponseInterface $response): OfrepApiResponse
    {
        $parsed = json_decode($response->getBody()->getContents(), true);
        return OfrepApiResponse::createSuccessResponse($parsed);
    }

    /**
     * @throws ParseException
     */
    private function parseErrorResponse(ResponseInterface $response): OfrepApiResponse
    {
        $parsed = json_decode($response->getBody()->getContents(), true);
        return OfrepApiResponse::createErrorResponse($parsed);
    }

    private function parseRetryLaterHeader(ResponseInterface $response): void
    {
        $retryAfterHeader = $response->getHeaderLine('Retry-After');
        if ($retryAfterHeader) {
            if (is_numeric($retryAfterHeader)) {
                // Retry-After is in seconds
                $this->retryAfter = time() + (int)$retryAfterHeader;
            } else {
                // Retry-After is in HTTP-date format
                $this->retryAfter = strtotime($retryAfterHeader);
            }
        }
    }
}
