<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

class FlagdResponseValidator
{
  /**
   * @param mixed[] $response
   */
  public static function isTypeMismatch(?array $response): bool
  {
      return is_null($response);
  }

  /**
   * @param mixed[] $response
   */
  public static function isErrorResponse(?array $response): bool
  {
      return !isset($response['value']);
  }
}
