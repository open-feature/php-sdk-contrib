<?php

declare(strict_types=1);

namespace OpenFeature\Providers\GoFeatureFlag\Test\unit\controller;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use OpenFeature\implementation\flags\MutableEvaluationContext;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Reason;
use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\controller\OfrepApi;
use OpenFeature\Providers\GoFeatureFlag\exception\FlagNotFoundException;
use OpenFeature\Providers\GoFeatureFlag\exception\ParseException;
use OpenFeature\Providers\GoFeatureFlag\exception\RateLimitedException;
use OpenFeature\Providers\GoFeatureFlag\exception\UnauthorizedException;
use OpenFeature\Providers\GoFeatureFlag\exception\UnknownOfrepException;
use OpenFeature\Providers\GoFeatureFlag\model\OfrepApiResponse;
use OpenFeature\Providers\GoFeatureFlag\Test\TestCase;

class OfrepApiTest extends TestCase
{
    private EvaluationContext $defaultEvaluationContext;

    public function test_should_raise_an_error_if_rate_limited()
    {
        $this->expectException(RateLimitedException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(429, [], json_encode([]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_raise_an_error_if_not_authorized_401()
    {
        $this->expectException(UnauthorizedException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(401, [], json_encode([]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_raise_an_error_if_not_authorized_403()
    {
        $this->expectException(UnauthorizedException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(403, [], json_encode([]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_raise_an_error_if_flag_not_found_404()
    {
        $this->expectException(FlagNotFoundException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(404, [], json_encode([]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_raise_an_error_if_unknown_http_code_500()
    {
        $this->expectException(UnknownOfrepException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(500, [], json_encode([]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_return_an_error_response_if_400()
    {
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(400, [], json_encode([
            "key" => "flagKey",
            "errorCode" => "TYPE_MISMATCH",
            "errorDetails" => "The flag value is not of the expected type"
        ]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $got = $api->evaluate('flagKey', $this->defaultEvaluationContext);
        $this->assertInstanceOf(OfrepApiResponse::class, $got);
        $this->assertEquals("flagKey", $got->getKey());
        $this->assertEquals(Reason::ERROR, $got->getReason());
        $this->assertEquals(ErrorCode::TYPE_MISMATCH(), $got->getErrorCode());
        $this->assertEquals("The flag value is not of the expected type", $got->getErrorDetails());
    }

    public function test_should_return_a_valid_response_if_200()
    {
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(200, [], json_encode([
            "key" => "flagKey",
            "value" => true,
            "reason" => Reason::TARGETING_MATCH,
            "variant" => "default"
        ]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $got = $api->evaluate('flagKey', $this->defaultEvaluationContext);
        $this->assertInstanceOf(OfrepApiResponse::class, $got);
        $this->assertEquals("flagKey", $got->getKey());
        $this->assertEquals(Reason::TARGETING_MATCH, $got->getReason());
        $this->assertNull($got->getErrorDetails());
        $this->assertNull($got->getErrorCode());
        $this->assertEquals(true, $got->getValue());
    }

    public function test_should_raise_an_error_if_200_and_json_does_not_contains_the_required_keys_missing_value()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(200, [], json_encode([
            "key" => "flagKey",
            "reason" => Reason::TARGETING_MATCH,
            "variant" => "default"
        ]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_raise_an_error_if_200_and_json_does_not_contains_the_required_keys_missing_key()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(200, [], json_encode([
            "value" => true,
            "reason" => Reason::TARGETING_MATCH,
            "variant" => "default"
        ]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_raise_an_error_if_200_and_json_does_not_contains_the_required_keys_missing_reason()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(200, [], json_encode([
            "key" => "flagKey",
            "value" => true,
            "variant" => "default"
        ]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_raise_an_error_if_200_and_json_does_not_contains_the_required_keys_missing_variant()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(200, [], json_encode([
            "key" => "flagKey",
            "value" => true,
            "reason" => Reason::TARGETING_MATCH,
        ]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_raise_an_error_if_400_and_json_does_not_contains_the_required_keys_missing_key()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(400, [], json_encode([
            "errorCode" => "TYPE_MISMATCH",
            "errorDetails" => "The flag value is not of the expected type"
        ]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_raise_an_error_if_400_and_json_does_not_contains_the_required_keys_missing_error_code()
    {
        $this->expectException(ParseException::class);
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(400, [], json_encode([
            "key" => "flagKey",
            "errorDetails" => "The flag value is not of the expected type"
        ]));
        $mockClient->method('post')->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    public function test_should_not_be_able_to_call_the_API_again_if_rate_limited_with_retry_after_int()
    {
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(429, ["Retry-After" => "1"], json_encode([
            "key" => "flagKey",
            "value" => true,
            "reason" => Reason::TARGETING_MATCH,
            "variant" => "default"
        ]));
        $mockClient->expects($this->exactly(1))
            ->method('post')
            ->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
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

    public function test_should_be_able_to_call_the_API_again_if_we_wait_after_the_retry_after_as_int()
    {
        $mockClient = $this->createMock(Client::class);
        $mockResponseRateLimited = new Response(429, ["Retry-After" => "1"], json_encode([]));
        $mockResponseSuccess = new Response(200, [], json_encode([
            "key" => "flagKey",
            "value" => true,
            "reason" => Reason::TARGETING_MATCH,
            "variant" => "default"
        ]));
        $mockClient->method('post')->will($this->onConsecutiveCalls($mockResponseRateLimited, $mockResponseSuccess));


        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
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
        $this->assertInstanceOf(OfrepApiResponse::class, $got);
    }

    public function test_should_not_be_able_to_call_the_API_again_if_rate_limited_with_retry_after_date()
    {
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(429, ["Retry-After" => gmdate('D, d M Y H:i:s \G\M\T', time() + 1)], json_encode([
            "key" => "flagKey",
            "value" => true,
            "reason" => Reason::TARGETING_MATCH,
            "variant" => "default"
        ]));
        $mockClient->expects($this->exactly(1))
            ->method('post')
            ->willReturn($mockResponse);

        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
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

    public function test_should_be_able_to_call_the_API_again_if_we_wait_after_the_retry_after_as_date()
    {
        $mockClient = $this->createMock(Client::class);
        $mockResponseRateLimited = new Response(429, ["Retry-After" => gmdate('D, d M Y H:i:s \G\M\T', time() + 1)], json_encode([]));
        $mockResponseSuccess = new Response(200, [], json_encode([
            "key" => "flagKey",
            "value" => true,
            "reason" => Reason::TARGETING_MATCH,
            "variant" => "default"
        ]));
        $mockClient->method('post')->will($this->onConsecutiveCalls($mockResponseRateLimited, $mockResponseSuccess));


        $api = new OfrepApi(new Config('https://gofeatureflag.org'));
        $reflection = new \ReflectionClass($api);
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
        $this->assertInstanceOf(OfrepApiResponse::class, $got);
    }

    public function test_should_have_autorization_header_if_api_key_in_config()
    {
        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(200, [], json_encode([
            "key" => "flagKey",
            "value" => true,
            "reason" => Reason::TARGETING_MATCH,
            "variant" => "default"
        ]));

        $mockClient->expects($this->once())
            ->method('post')
            ->willReturnCallback(function ($uri, $options) use ($mockResponse) {
                // Check headers here
                echo sizeof($options['headers']);
                $this->assertArrayHasKey('headers', $options);
                $this->assertArrayHasKey('Authorization', $options['headers']);
                $this->assertEquals('Bearer your-secure-api-key', $options['headers']['Authorization']);
                return $mockResponse;
            });


        $api = new OfrepApi(new Config('https://gofeatureflag.org', apiKey: "your-secure-api-key"));
        $reflection = new \ReflectionClass($api);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($api, $mockClient);

        $api->evaluate('flagKey', $this->defaultEvaluationContext);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->defaultEvaluationContext = new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37");
    }
}