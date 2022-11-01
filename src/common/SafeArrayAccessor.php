<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\common;

class SafeArrayAccessor
{
    /** @var mixed[] $arr */
    private array $arr;

    /**
     * @param mixed[] $arr
     */
    public function __construct(array $arr)
    {
        $this->arr = $arr;
    }

    /**
     * @param mixed[] $arr
     */
    public static function with(array $arr): SafeArrayAccessor
    {
        return new SafeArrayAccessor($arr);
    }

    /**
     * @param mixed[] $arr
     *
     * @return mixed
     */
    public static function getKeyFromArray(array $arr, string $key)
    {
        return $arr[$key] ?? null;
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        return self::getKeyFromArray($this->arr, $key);
    }
}
