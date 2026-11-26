<?php

namespace OpenFeature\Providers\Flipt;

use Flipt\Client\FliptClient;
use Flipt\Models\BooleanEvaluationResult;
use Flipt\Models\ResponseReasons;
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

class FliptProvider extends AbstractProvider implements Provider
{
    protected const NAME = 'FliptProvider';

    protected $client;

    public function __construct( string|FliptClient $hostOrClient, string $apiToken = '', string $namespace = '' ) {
        $this->client = ( is_string( $hostOrClient ) ) ? new FliptClient( $hostOrClient, $apiToken, $namespace ) : $hostOrClient;
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
     * @param bool|string|int|float|mixed[] $defaultValue
     */
    private function resolveValue(string $flagKey, string $flagType, mixed $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {

        // check null context
        if( empty( $context ) ) {
            $attributes = [];
            $id = null;
        } else {
            $attributes = $context->getAttributes()->toArray();
            $id = $context->getTargetingKey();
        }

        // booleans need a dedicated function
        if( $flagType == FlagValueType::BOOLEAN ) {
            $result = $this->client->boolean( $flagKey, $attributes, $id );
        } else {
            $result = $this->client->variant( $flagKey, $attributes, $id );
        }

        
        // there is a match
        // not sure yet as the variant result has a getMatch() but not the boolean result.
        if( $result->getReason() == ResponseReasons::MATCH_EVALUATION_REASON || $result->getReason() == ResponseReasons::DEFAULT_EVALUATION_REASON ) {
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