<?php

namespace OpenFeature\Providers\Flipt;

use Closure;
use OpenFeature\implementation\provider\AbstractProvider;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Psr\SimpleCache\CacheInterface;

class CacheProvider extends AbstractProvider implements Provider
{

    protected const NAME = 'CacheProvider';

    protected Provider $provider;
    protected CacheInterface $storage;
    protected string $key;

    public function __construct(Provider $provider, CacheInterface $storage, string $key = 'open-feature')
    {
        $this->provider = $provider;
        $this->storage = $storage;
        $this->key = $key;
    }


    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->cacheCheck(
            $this->hash($flagKey, $defaultValue, $context),
            fn () => $this->provider->resolveBooleanValue($flagKey, $defaultValue, $context)
        );
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->cacheCheck(
            $this->hash($flagKey, $defaultValue, $context),
            fn () => $this->provider->resolveStringValue($flagKey, $defaultValue, $context)
        );
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->cacheCheck(
            $this->hash($flagKey, $defaultValue, $context),
            fn () => $this->provider->resolveIntegerValue($flagKey, $defaultValue, $context)
        );
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->cacheCheck(
            $this->hash($flagKey, $defaultValue, $context),
            fn () => $this->provider->resolveFloatValue($flagKey, $defaultValue, $context)
        );
    }

    public function resolveObjectValue(string $flagKey, array $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->cacheCheck(
            $this->hash($flagKey, $defaultValue, $context),
            fn () => $this->provider->resolveObjectValue($flagKey, $defaultValue, $context)
        );
    }


    protected function cacheCheck(string $key, Closure $next)
    {

        $cached = $this->get($key);
        if (isset($cached)) return $cached;

        $result = $next();

        $this->set($key, $result);

        return $result;
    }


    /**
     * Retrievies a value from the cache
     */
    protected function get($key)
    {

        $entries = $this->storage->get($this->key, []);

        if (array_key_exists($key, $entries)) return $entries[$key];

        return null;
    }


    /**
     * Sets the $value into the cache
     */
    protected function set($key, $value)
    {


        $entries = $this->storage->get($this->key, []);
        $entries[$key] = $value;

        $this->storage->set($this->key, $entries);
    }



    /**
     * Clears the cached records
     */
    public function clear()
    {
        $this->storage->delete($this->key);
    }



    protected function hash(string $flag, mixed $default, ?EvaluationContext $context)
    {

        // check null context
        if (empty($context)) {
            $attributes = [];
            $id = null;
        } else {
            $attributes = $context->getAttributes()->toArray();
            $id = $context->getTargetingKey();
        }

        // generate hash on request arguments
        return md5(json_encode([
            'flag' => $flag,
            'default' => $default,
            'id' => $id, 'context' => $this->sortArray($attributes)
        ]));
    }



    protected function sortArray(array $array)
    {
        $sortedArray = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Recursively sort nested arrays
                $value = $this->sortArray($value);
            }
            $sortedArray[$key] = $value;
        }

        // Sort the array by keys
        ksort($sortedArray);

        return $sortedArray;
    }
}
