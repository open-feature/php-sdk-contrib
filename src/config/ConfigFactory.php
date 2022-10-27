<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\config;

use OpenFeature\Providers\Flagd\common\SafeArrayAccessor;

class ConfigFactory
{
  public function fromOptions(?string $host = null, ?int $port = null, ?string $protocol = null, ?bool $secure = null): IConfig
  {
    return new Config($host, $port, $protocol, $secure);
  }

  public function fromArray(array $options): IConfig
  {
    $accessor = SafeArrayAccessor::with($options);

    return new Config(
      $accessor->get('host'),
      $accessor->get('port'),
      $accessor->get('protocol'),
      $accessor->get('secure'),
    );
  }
}
