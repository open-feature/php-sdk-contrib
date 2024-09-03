<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\Test\unit\controller;

use GuzzleHttp\Psr7\Response;
use OpenFeature\Providers\GoFeatureFlag\Test\TestCase;
use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\controller\OfrepApi;
use OpenFeature\Providers\GoFeatureFlag\exception\FlagNotFoundException;
use OpenFeature\Providers\GoFeatureFlag\exception\ParseException;
use OpenFeature\Providers\GoFeatureFlag\exception\RateLimitedException;
use OpenFeature\Providers\GoFeatureFlag\exception\UnauthorizedException;
use OpenFeature\Providers\GoFeatureFlag\exception\UnknownOfrepException;
use OpenFeature\Providers\GoFeatureFlag\model\OfrepApiErrorResponse;
use OpenFeature\Providers\GoFeatureFlag\model\OfrepApiSuccessResponse;
use OpenFeature\implementation\flags\MutableEvaluationContext;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Reason;
use Psr\Http\Client\ClientInterface;
use ReflectionClass;

use function gmdate;
use function json_encode;
use function time;
use function usleep;

class OfrepApiTest extends TestCase
{
    private EvaluationContext $defaultEvaluationContext;

    public function testShouldRaiseAnErrorIfRateLimited()
    {
        $this->expectException(RateLimitedException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(429, [], json_encode([]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldRaiseAnErrorIfNotAuthorized401()
    {
        $this->expectException(UnauthorizedException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(401, [], json_encode([]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldRaiseAnErrorIfNotAuthorized403()
    {
        $this->expectException(UnauthorizedException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(403, [], json_encode([]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldRaiseAnErrorIfFlagNotFound404()
    {
        $this->expectException(FlagNotFoundException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(404, [], json_encode([]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldRaiseAnErrorIfUnknownHttpCode500()
    {
        $this->expectException(UnknownOfrepException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(500, [], json_encode([]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldReturnAnErrorResponseIf400()
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(400, [], json_encode([
            'key' => 'flagKey',
            'errorCode' => 'TYPE_MISMATCH',
            'errorDetails' => 'The flag value is not of the expected type',
        ]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $got = $api->evaluate('flagKey', $this->defaultEvaluationContext);
        $this->assertInstanceOf(OfrepApiErrorResponse::class, $got);
        $this->assertEquals(Reason::ERROR, $got->getReason());
        $this->assertEquals(ErrorCode::TYPE_MISMATCH(), $got->getErrorCode());
        $this->assertEquals('The flag value is not of the expected type', $got->getErrorDetails());
    }

    public function testShouldReturnAValidResponseIf200()
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'flagKey',
            'value' => true,
            'reason' => Reason::TARGETING_MATCH,
            'variant' => 'default',
        ]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $got = $api->evaluate('flagKey', $this->defaultEvaluationContext);
        $this->assertInstanceOf(OfrepApiSuccessResponse::class, $got);
        $this->assertEquals(Reason::TARGETING_MATCH, $got->getReason());
        $this->assertEquals(true, $got->getValue());
    }

    public function testShouldRaiseAnErrorIf200AndJsonDoesNotContainTheRequiredKeysMissingValue()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'flagKey',
            'reason' => Reason::TARGETING_MATCH,
            'variant' => 'default',
        ]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldRaiseAnErrorIf200AndJsonDoesNotContainTheRequiredKeysMissingKey()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'value' => true,
            'reason' => Reason::TARGETING_MATCH,
            'variant' => 'default',
        ]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldRaiseAnErrorIf200AndJsonDoesNotContainTheRequiredKeysMissingReason()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'flagKey',
            'value' => true,
            'variant' => 'default',
        ]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldRaiseAnErrorIf200AndJsonDoesNotContainTheRequiredKeysMissingVariant()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'flagKey',
            'value' => true,
            'reason' => Reason::TARGETING_MATCH,
        ]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldRaiseAnErrorIf400AndJsonDoesNotContainTheRequiredKeysMissingKey()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(400, [], json_encode([
            'errorCode' => 'TYPE_MISMATCH',
            'errorDetails' => 'The flag value is not of the expected type',
        ]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldRaiseAnErrorIf400AndJsonDoesNotContainTheRequiredKeysMissingErrorCode()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(400, [], json_encode([
            'key' => 'flagKey',
            'errorDetails' => 'The flag value is not of the expected type',
        ]));
        $mockClient->method('sendRequest')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function testShouldNotBeAbleToCallTheApiAgainIfRateLimitedWithRetryAfterInt()
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(429, ['Retry-After' => '1'], json_encode([
            'key' => 'flagKey',
            'value' => true,
            'reason' => Reason::TARGETING_MATCH,
            'variant' => 'default',
        ]));
        $mockClient->expects($this->exactly(1))
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        try {
            $api->evaluate('flagKey', $this->defaultEvaluationContext);
        } catch (RateLimitedException $e) {
            $this->assertInstanceOf(RateLimitedException::class, $e);
        }

        try {
            $api->evaluate('another-flag', $this->defaultEvaluationContext);
        } catch (RateLimitedException $e) {
            $this->assertInstanceOf(RateLimitedException::class, $e);
        }
    }

    public function testShouldBeAbleToCallTheApiAgainIfWeWaitAfterTheRetryAfterAsInt()
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponseRateLimited = new Response(429, ['Retry-After' => '1'], json_encode([]));
        $mockResponseSuccess = new Response(200, [], json_encode([
            'key' => 'flagKey',
            'value' => true,
            'reason' => Reason::TARGETING_MATCH,
            'variant' => 'default',
        ]));
        $mockClient->method('sendRequest')->will($this->onConsecutiveCalls($mockResponseRateLimited, $mockResponseSuccess));

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        try {
            $api->evaluate('flagKey', $this->defaultEvaluationContext);
        } catch (RateLimitedException $e) {
            $this->assertInstanceOf(RateLimitedException::class, $e);
        }

        // Wait for 1.5 seconds
        usleep(1500000);

        $got = $api->evaluate('another-flag', $this->defaultEvaluationContext);
        $this->assertInstanceOf(OfrepApiSuccessResponse::class, $got);
    }

    public function testShouldNotBeAbleToCallTheApiAgainIfRateLimitedWithRetryAfterDate()
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(429, ['Retry-After' => gmdate('D, d M Y H:i:s \G\M\T', time() + 1)], json_encode([
            'key' => 'flagKey',
            'value' => true,
            'reason' => Reason::TARGETING_MATCH,
            'variant' => 'default',
        ]));
        $mockClient->expects($this->exactly(1))
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        try {
            $api->evaluate('flagKey', $this->defaultEvaluationContext);
        } catch (RateLimitedException $e) {
            $this->assertInstanceOf(RateLimitedException::class, $e);
        }

        try {
            $api->evaluate('another-flag', $this->defaultEvaluationContext);
        } catch (RateLimitedException $e) {
            $this->assertInstanceOf(RateLimitedException::class, $e);
        }
    }

    public function testShouldBeAbleToCallTheApiAgainIfWeWaitAfterTheRetryAfterAsDate()
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponseRateLimited = new Response(429, ['Retry-After' => gmdate('D, d M Y H:i:s \G\M\T', time() + 1)], json_encode([]));
        $mockResponseSuccess = new Response(200, [], json_encode([
            'key' => 'flagKey',
            'value' => true,
            'reason' => Reason::TARGETING_MATCH,
            'variant' => 'default',
        ]));
        $mockClient->method('sendRequest')->will($this->onConsecutiveCalls($mockResponseRateLimited, $mockResponseSuccess));

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        try {
            $api->evaluate('flagKey', $this->defaultEvaluationContext);
        } catch (RateLimitedException $e) {
            $this->assertInstanceOf(RateLimitedException::class, $e);
        }

        // Wait for 1.5 seconds
        usleep(1500000);

        $got = $api->evaluate('another-flag', $this->defaultEvaluationContext);
        $this->assertInstanceOf(OfrepApiSuccessResponse::class, $got);
    }

    public function testShouldHaveAuthorizationHeaderIfApiKeyInConfig()
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockResponse = new Response(200, [], json_encode([
            'key' => 'flagKey',
            'value' => true,
            'reason' => Reason::TARGETING_MATCH,
            'variant' => 'default',
        ]));

        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function ($req) use ($mockResponse) {
                $this->assertArrayHasKey('Authorization', $req->getHeaders());
                $this->assertEquals('Bearer your-secure-api-key', $req->getHeader('Authorization')[0]);

                return $mockResponse;
            });

        $api = new OfrepApi(new Config('https://gofeatureflag.org', apiKey: 'your-secure-api-key'));
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->defaultEvaluationContext = new MutableEvaluationContext('214b796a-807b-4697-b3a3-42de0ec10a37');
    }
}
