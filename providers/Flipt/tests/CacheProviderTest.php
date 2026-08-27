<?php

namespace Tests;

use Flipt\Client\FliptClient;
use Flipt\Models\DefaultBooleanEvaluationResult;
use Mockery;
use Mockery\MockInterface;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use OpenFeature\implementation\provider\ResolutionDetails;
use OpenFeature\implementation\provider\ResolutionDetailsFactory;
use OpenFeature\Providers\Flipt\CacheProvider;
use OpenFeature\Providers\Flipt\FliptProvider;
use OpenFeature\Providers\Flipt\ResponseReasons;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class CacheProviderTest extends TestCase
{

    protected FliptProvider&MockInterface $mockProvider;
    protected CacheInterface&MockInterface $storage;
    protected CacheProvider $provider;


    protected function setUp(): void
    {
        $this->mockProvider = Mockery::mock( FliptProvider::class );
        $this->storage = Mockery::mock( CacheInterface::class );
        $this->provider = new CacheProvider( $this->mockProvider, $this->storage );
    }


    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSet()
    {

        $context = new EvaluationContext( 'id', new Attributes( [ 'context' => 'demo' ] ) );

        $this->storage->shouldReceive( 'get')->with( 'open-feature', [] )->andReturn( [] );
        $this->storage->shouldReceive( 'set')->withArgs( function( $key, $content ) {
            $this->assertEquals( $key, 'open-feature' );
            $this->assertArrayHasKey( 'fa89ea8c3b16386166360dd163f10e6d', $content );
            $this->assertInstanceOf( ResolutionDetails::class, $content['fa89ea8c3b16386166360dd163f10e6d' ]);
            return true;
        });
        $this->mockProvider->shouldReceive( 'resolveBooleanValue')
            ->with( 'flag', true, $context )
            ->andReturn(  ResolutionDetailsFactory::fromSuccess( true ) );

        $result = $this->provider->resolveBooleanValue( 'flag', true, $context );

        $this->assertEquals( $result->getValue(), true );
    }
}