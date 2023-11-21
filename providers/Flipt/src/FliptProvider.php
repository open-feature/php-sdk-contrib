<?php

namespace OpenFeature\Providers\Flipt;

use Flipt\Client\FliptClient;
use Flipt\Models\BooleanEvaluationResult;
use Flipt\Models\VariantEvaluationResult;
use OpenFeature\implementation\provider\AbstractProvider;
use OpenFeature\implementation\provider\ResolutionDetailsBuilder;
use OpenFeature\implementation\provider\ResolutionDetailsFactory;
use OpenFeature\implementation\provider\ResolutionError;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\flags\FlagValueType;
use OpenFeature\interfaces\provider\ErrorCode;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Psr\SimpleCache\CacheInterface;

class FliptProvider extends AbstractProvider implements Provider
{
    protected const NAME = 'FliptProvider';

    protected $client;
    protected Cache $cache;

    public function __construct( mixed $hostOrClient, string $apiToken = '', string $namespace = '', CacheInterface $cache = null ) {
        $this->client = ( is_string( $hostOrClient ) ) ? new FliptClient( $hostOrClient, $apiToken, $namespace ) : $hostOrClient;
        $this->cache = new Cache( $cache );
    }


    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::BOOLEAN, $defaultValue, $context);
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::STRING, $defaultValue, $context);
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::INTEGER, $defaultValue, $context);
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::FLOAT, $defaultValue, $context);
    }

    /**
     * @param mixed[] $defaultValue
     */
    public function resolveObjectValue(string $flagKey, array $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->resolveValue($flagKey, FlagValueType::OBJECT, $defaultValue, $context);
    }


    /**
     * Clears the cache of all requests
     */
    public function clearCache() {
        $this->cache->clear();
    }

    /**
     * @param bool|string|int|float|mixed[] $defaultValue
     */
    private function resolveValue(string $flagKey, string $flagType, mixed $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {

        // check if cache has already result
        $cacheKey = $this->cache->key( [ 'flag' => $flagKey, 'type' => $flagType, 'default' => $defaultValue, 'context' => $context->getAttributes()->toArray(), 'key' => $context->getTargetingKey() ] );
        $cached = $this->cache->get( $cacheKey );
        if( isset( $cached ) ) return $cached;


        // booleans need a dedicated function
        if( $flagType == FlagValueType::BOOLEAN ) {
            $result = $this->client->boolean( $flagKey, $context->getAttributes()->toArray(), $context->getTargetingKey() );
        } else {
            $result = $this->client->variant( $flagKey, $context->getAttributes()->toArray(), $context->getTargetingKey() );
        }

        
        // there is a match
        // not sure yet as the variant result has a getMatch() but not the boolean result.
        if( $result->getReason() == 'MATCH_EVALUATION_REASON' || $result->getReason() == "DEFAULT_EVALUATION_REASON" ) {
            $result = ResolutionDetailsFactory::fromSuccess( $this->castResult( $result, $flagType ) );
        } else {
            $result = (new ResolutionDetailsBuilder())
                    ->withValue( $defaultValue )
                    ->withError(
                        // not sure if thie reason to error mapping is correct
                        new ResolutionError(ErrorCode::GENERAL(), $result->getReason() ),
                    )
                    ->build();
        }

        // write result into cache
        $this->cache->set( $cacheKey, $result ); 

        return $result;
    }



    private function castResult( VariantEvaluationResult|BooleanEvaluationResult $result, string $type ) {
        switch ($type) {
            case FlagValueType::BOOLEAN:
                return filter_var($result->getEnabled(), FILTER_VALIDATE_BOOLEAN);
            case FlagValueType::FLOAT:
                return (float) $result->getVariantKey();
            case FlagValueType::INTEGER:
                return (int) $result->getVariantKey();
            case FlagValueType::OBJECT:
                return json_decode( $result->getVariantAttachment(), true);
            case FlagValueType::STRING:
                return $result->getVariantKey();
            default:
                return null;
        }
    }

}