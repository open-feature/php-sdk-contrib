<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagsmith\Test\Fixtures;

use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Offline\IOfflineHandler;

class TestOfflineHandler implements IOfflineHandler
{
    public function __construct(
        private ?EnvironmentModel $environmentModel = null,
    ) {

    }

    public function getEnvironment(): ?EnvironmentModel
    {
        return $this->environmentModel;
    }
}
