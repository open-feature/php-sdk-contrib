<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\Test\unit;

use OpenFeature\Providers\Flagd\FlagdProvider;
use OpenFeature\Providers\Flagd\Test\TestCase;
use OpenFeature\Providers\Flagd\config\ConfigFactory;
use OpenFeature\Providers\Flagd\config\Defaults;
use OpenFeature\Providers\Flagd\config\EvaluationApis;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Reason;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Coverage for the opt-in `flagd.evaluation.v2` API.
 *
 * Every response body below was captured from a real flagd v0.16.1 instance rather than
 * hand-written, so the fixtures match the wire format exactly. The defining difference from v1
 * is that `value` is declared `optional` in the v2 protobuf, so it is omitted from the payload
 * entirely when the flag resolves without one instead of being zero-filled.
 */
class FlagdProviderEvaluationV2Test extends TestCase
{
    // ---------------------------------------------------------------- config

    public function testDefaultsToV1WhenUnset(): void
    {
        $config = ConfigFactory::fromArray([]);

        $this->assertEquals(EvaluationApis::V1, $config->getEvaluationApi());
        $this->assertEquals(EvaluationApis::V1, Defaults::DEFAULT_EVALUATION_API);
    }

    public function testAcceptsV2(): void
    {
        $config = ConfigFactory::fromArray(['evaluationApi' => EvaluationApis::V2]);

        $this->assertEquals(EvaluationApis::V2, $config->getEvaluationApi());
    }

    public function testFallsBackToV1OnUnknownValue(): void
    {
        $config = ConfigFactory::fromArray(['evaluationApi' => 'v99']);

        $this->assertEquals(EvaluationApis::V1, $config->getEvaluationApi());
    }

    // --------------------------------------------------------------- routing

    public function testV2RoutesToEvaluationV2Service(): void
    {
        $uri = null;
        $provider = $this->provider(
            '{"value":true,"reason":"STATIC","variant":"on","metadata":{}}',
            EvaluationApis::V2,
            $uri,
        );

        $provider->resolveBooleanValue('any-key', false, null);

        $this->assertIsString($uri);
        $this->assertStringContainsString('flagd.evaluation.v2.Service/ResolveBoolean', $uri);
    }

    public function testV1StillRoutesToSchemaV1Service(): void
    {
        $uri = null;
        $provider = $this->provider(
            '{"value":true,"reason":"STATIC","variant":"on","metadata":{}}',
            EvaluationApis::V1,
            $uri,
        );

        $provider->resolveBooleanValue('any-key', false, null);

        $this->assertIsString($uri);
        $this->assertStringContainsString('schema.v1.Service/ResolveBoolean', $uri);
    }

    // --------------------------------------------------------------- success

    public function testResolvesEnabledBooleanFlag(): void
    {
        $provider = $this->v2Provider('{"value":true,"reason":"STATIC","variant":"on","metadata":{}}');

        $details = $provider->resolveBooleanValue('any-key', false, null);

        $this->assertTrue($details->getValue());
        $this->assertEquals('on', $details->getVariant());
        $this->assertEquals('STATIC', $details->getReason());
    }

    public function testResolvesEnabledIntegerFlagFromStringEncodedValue(): void
    {
        // v2 keeps the JSON string encoding for 64-bit integers, exactly as v1 does.
        $provider = $this->v2Provider('{"value":"42","reason":"STATIC","variant":"high","metadata":{}}');

        $details = $provider->resolveIntegerValue('any-key', 1, null);

        $this->assertSame(42, $details->getValue());
        $this->assertEquals('high', $details->getVariant());
    }

    // -------------------------------------------------- disabled (no `value`)

    public function testDisabledBooleanFlagFallsBackToCallerDefault(): void
    {
        $provider = $this->v2Provider('{"reason":"DISABLED","metadata":{}}');

        $details = $provider->resolveBooleanValue('any-key', true, null);

        $this->assertTrue($details->getValue());
        $this->assertNull($details->getVariant());
        $this->assertEquals(Reason::DISABLED, $details->getReason());
        $this->assertNull($details->getError());
    }

    public function testDisabledStringFlagFallsBackToCallerDefault(): void
    {
        $provider = $this->v2Provider('{"reason":"DISABLED","metadata":{}}');

        $details = $provider->resolveStringValue('any-key', 'fallback', null);

        $this->assertEquals('fallback', $details->getValue());
        $this->assertEquals(Reason::DISABLED, $details->getReason());
    }

    public function testDisabledIntegerFlagFallsBackToCallerDefault(): void
    {
        $provider = $this->v2Provider('{"reason":"DISABLED","metadata":{}}');

        $details = $provider->resolveIntegerValue('any-key', 42, null);

        $this->assertSame(42, $details->getValue());
        $this->assertEquals(Reason::DISABLED, $details->getReason());
    }

    public function testDisabledFloatFlagFallsBackToCallerDefault(): void
    {
        $provider = $this->v2Provider('{"reason":"DISABLED","metadata":{}}');

        $details = $provider->resolveFloatValue('any-key', 1.5, null);

        $this->assertEquals(1.5, $details->getValue());
        $this->assertEquals(Reason::DISABLED, $details->getReason());
    }

    public function testDisabledObjectFlagFallsBackToCallerDefault(): void
    {
        $provider = $this->v2Provider('{"reason":"DISABLED","metadata":{}}');
        $default = ['a' => 1];

        $details = $provider->resolveObjectValue('any-key', $default, null);

        $this->assertEquals($default, $details->getValue());
        $this->assertEquals(Reason::DISABLED, $details->getReason());
    }

    /**
     * A flag with no default variant resolves to the code default. On v1 this arrives as a
     * zero-filled value with reason FALLBACK, which is indistinguishable from a real resolution.
     * On v2 the value is absent and the reason is DEFAULT.
     */
    public function testFlagWithoutDefaultVariantFallsBackToCallerDefault(): void
    {
        $provider = $this->v2Provider('{"reason":"DEFAULT","metadata":{}}');

        $details = $provider->resolveBooleanValue('any-key', true, null);

        $this->assertTrue($details->getValue());
        $this->assertEquals('DEFAULT', $details->getReason());
        $this->assertNull($details->getError());
    }

    /**
     * The v1 heuristic keys on an empty variant. A disabled flag on v2 carries no variant at all,
     * so a legitimately resolved falsy value must not be mistaken for one.
     */
    public function testFalsyResolvedValueIsNotTreatedAsDisabled(): void
    {
        $provider = $this->v2Provider('{"value":false,"reason":"TARGETING_MATCH","variant":"off","metadata":{}}');

        $details = $provider->resolveBooleanValue('any-key', true, null);

        $this->assertFalse($details->getValue());
        $this->assertEquals('off', $details->getVariant());
        $this->assertEquals('TARGETING_MATCH', $details->getReason());
    }

    // ----------------------------------------------------------------- errors

    public function testMissingFlagReturnsFlagNotFoundError(): void
    {
        $provider = $this->v2Provider('{"code":"not_found","message":"Flag not found"}');

        $details = $provider->resolveBooleanValue('any-key', true, null);

        $this->assertTrue($details->getValue());
        $this->assertNotNull($details->getError());
        $this->assertEquals(
            ErrorCode::FLAG_NOT_FOUND(),
            $details->getError()->getResolutionErrorCode(),
        );
    }

    public function testTypeMismatchReturnsTypeMismatchError(): void
    {
        $provider = $this->v2Provider('{"code":"invalid_argument","message":"Type mismatch error"}');

        $details = $provider->resolveStringValue('any-key', 'fallback', null);

        $this->assertEquals('fallback', $details->getValue());
        $this->assertNotNull($details->getError());
        $this->assertEquals(
            ErrorCode::TYPE_MISMATCH(),
            $details->getError()->getResolutionErrorCode(),
        );
    }

    /**
     * Against a flagd server older than v0.14.0, the v2 endpoint doesn't exist and the server
     * returns a plain-text 404 rather than a JSON body. `json_decode` then yields null, which
     * must not leak through as the resolved value in place of the caller's default.
     */
    public function testNonJsonResponseFallsBackToCallerDefault(): void
    {
        $provider = $this->v2Provider('404 page not found');

        $details = $provider->resolveBooleanValue('any-key', true, null);

        $this->assertTrue($details->getValue());
        $this->assertNotNull($details->getError());
    }

    // ---------------------------------------------------------------- helpers

    private function v2Provider(string $body): FlagdProvider
    {
        $uri = null;

        return $this->provider($body, EvaluationApis::V2, $uri);
    }

    private function provider(string $body, string $evaluationApi, ?string &$capturedUri): FlagdProvider
    {
        $mockRequest = $this->mockery(RequestInterface::class);
        $mockRequest->shouldReceive('withHeader')->andReturn($mockRequest);
        $mockRequest->shouldReceive('withBody')->andReturn($mockRequest);

        $mockRequestFactory = $this->mockery(RequestFactoryInterface::class);
        $mockRequestFactory
            ->shouldReceive('createRequest')
            ->andReturnUsing(function (string $method, string $uri) use ($mockRequest, &$capturedUri) {
                $capturedUri = $uri;

                return $mockRequest;
            });

        $mockStream = $this->mockery(StreamInterface::class);

        $mockStreamFactory = $this->mockery(StreamFactoryInterface::class);
        $mockStreamFactory->shouldReceive('createStream')->andReturn($mockStream);

        $mockResponse = $this->mockery(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody->__toString')->andReturn($body);

        $mockClient = $this->mockery(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')->with($mockRequest)->andReturn($mockResponse);

        /** @var ClientInterface $client */
        $client = $mockClient;
        /** @var RequestFactoryInterface $requestFactory */
        $requestFactory = $mockRequestFactory;
        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $mockStreamFactory;

        return new FlagdProvider([
            'evaluationApi' => $evaluationApi,
            'httpConfig' => [
                'client' => $client,
                'requestFactory' => $requestFactory,
                'streamFactory' => $streamFactory,
            ],
        ]);
    }
}
