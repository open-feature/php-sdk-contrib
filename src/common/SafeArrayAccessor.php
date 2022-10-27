<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\common;

class SafeArrayAccessor
{
  private array $arr;

  public function __construct(array $arr)
  {
    $this->arr = $arr;
  }

  public static function with(array $arr): SafeArrayAccessor
  {
    return new static($arr);
  }

  public static function getKeyFromArray(array $arr, string $key): mixed
  {
    return isset($arr[$key]) ? $arr[$key] : null;
  }

  public function get(string $key): mixed
  {
    return self::getKeyFromArray($this->arr, $key);
  }
}