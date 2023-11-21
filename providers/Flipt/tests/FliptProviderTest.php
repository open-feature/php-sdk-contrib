<?php

namespace Tests;

use Flipt\Models\DefaultBooleanEvaluationResult;
use Flipt\Models\DefaultVariantEvaluationResult;
use Mockery;
use Mockery\MockInterface;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use OpenFeature\implementation\provider\ResolutionDetails;
use OpenFeature\Providers\Flipt\FliptProvider;
use PHPUnit\Framework\TestCase;

class FliptProviderTest extends TestCase
{

    protected MockInterface $mockClient;
    protected FliptProvider $provider;

    protected function setUp(): void
    {
        $this->mockClient = Mockery::mock();
        $this->provider = new FliptProvider( $this->mockClient );
    }


    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testBoolean() 
    {
        $this->mockClient->shouldReceive( 'boolean')
            ->withArgs( function( $flag, $context, $entityId ) {
                $this->assertEquals( $flag, 'flag' );
                $this->assertEquals( $context, [ 'context' => 'demo' ] );
                $this->assertEquals( $entityId, 'id' );
                return true;
            })
            ->andReturn( new DefaultBooleanEvaluationResult( true, 'MATCH_EVALUATION_REASON', 0.1, 'rid', '13245' ) );

        $result = $this->provider->resolveBooleanValue( 'flag', false, new EvaluationContext( 'id', new Attributes( [ 'context' => 'demo' ] ) ) );

        $this->assertInstanceOf( ResolutionDetails::class, $result );
        $this->assertEquals( $result->getValue(), true );

    }

    public function testInteger() 
    {
        $this->mockClient->shouldReceive( 'variant')
            ->withArgs( function( $flag, $contextRecv, $entityId ) {
                $this->assertEquals( $flag, 'flag' );
                $this->assertEquals( $contextRecv, [ 'context' => 'demo' ] );
                $this->assertEquals( $entityId, 'id' );
                return true;
            })
            ->andReturn( new DefaultVariantEvaluationResult( true, 'MATCH_EVALUATION_REASON', 0.1, 'rid', '13245', [], '20', '{"json":1}' ) );

        $result = $this->provider->resolveIntegerValue( 'flag', 10, new EvaluationContext( 'id', new Attributes( [ 'context' => 'demo' ] ) ) );

        $this->assertInstanceOf( ResolutionDetails::class, $result );
        $this->assertEquals( $result->getValue(), 20 );

    }

    public function testFloat() 
    {
        $this->mockClient->shouldReceive( 'variant')
            ->withArgs( function( $flag, $contextRecv, $entityId ) {
                $this->assertEquals( $flag, 'flag' );
                $this->assertEquals( $contextRecv, [ 'context' => 'demo' ] );
                $this->assertEquals( $entityId, 'id' );
                return true;
            })
            ->andReturn( new DefaultVariantEvaluationResult( true, 'MATCH_EVALUATION_REASON', 0.1, 'rid', '13245', [], '0.2345', '{"json":1}' ) );

        $result = $this->provider->resolveFloatValue( 'flag', 0.1111, new EvaluationContext( 'id', new Attributes( [ 'context' => 'demo' ] ) ) );

        $this->assertInstanceOf( ResolutionDetails::class, $result );
        $this->assertEquals( $result->getValue(), 0.2345 );

    }

    public function testString() 
    {
        $this->mockClient->shouldReceive( 'variant')
            ->withArgs( function( $flag, $contextRecv, $entityId ) {
                $this->assertEquals( $flag, 'flag' );
                $this->assertEquals( $contextRecv, [ 'context' => 'demo' ] );
                $this->assertEquals( $entityId, 'id' );
                return true;
            })
            ->andReturn( new DefaultVariantEvaluationResult( true, 'MATCH_EVALUATION_REASON', 0.1, 'rid', '13245', [], 'My string', '{"json":1}' ) );

        $result = $this->provider->resolveStringValue( 'flag', 'base', new EvaluationContext( 'id', new Attributes( [ 'context' => 'demo' ] ) ) );

        $this->assertInstanceOf( ResolutionDetails::class, $result );
        $this->assertEquals( $result->getValue(), 'My string' );

    }


    public function testObject() 
    {
        $this->mockClient->shouldReceive( 'variant')
            ->withArgs( function( $flag, $contextRecv, $entityId ) {
                $this->assertEquals( $flag, 'flag' );
                $this->assertEquals( $contextRecv, [ 'context' => 'demo' ] );
                $this->assertEquals( $entityId, 'id' );
                return true;
            })
            ->andReturn( new DefaultVariantEvaluationResult( true, 'MATCH_EVALUATION_REASON', 0.1, 'rid', '13245', [], 'My string', '{"json":1}' ) );

        $result = $this->provider->resolveObjectValue( 'flag', [], new EvaluationContext( 'id', new Attributes( [ 'context' => 'demo' ] ) ) );

        $this->assertInstanceOf( ResolutionDetails::class, $result );
        $this->assertEquals( $result->getValue(), [ "json" => 1 ] );

    }

    
}
