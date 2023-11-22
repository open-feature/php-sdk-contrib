<?php

namespace OpenFeature\Providers\Flipt;

use Psr\SimpleCache\CacheInterface;

class Cache {

    protected const CACHE_KEY = 'openfeature-flipt';

    protected CacheInterface|null $cache;



    public function __construct( CacheInterface $cache = null ) {
        $this->cache = $cache;
    }



    /**
     * Retrievies a value from the cache
     */
    public function get( $key ) {
        
        if( empty( $this->cache ) ) return null;


        $entries = $this->cache->get( self::CACHE_KEY, [] );

        if( array_key_exists( $key, $entries ) ) return $entries[ $key ];

        return null;
    }


    /**
     * Sets the $value into the cache
     */
    public function set( $key, $value ) {

        if( empty( $this->cache ) ) return;

        $entries = $this->cache->get( self::CACHE_KEY, []);
        $entries[ $key ] = $value;

        $this->cache->set( self::CACHE_KEY, $entries );
    }


    /**
     * Clears the cached records
     */
    public function clear() {
        if( empty( $this->cache ) ) return;
        $this->cache->delete( self::CACHE_KEY );
    }


    public function key( array $params ) {
        return md5( json_encode( $params ) );
    }
}

