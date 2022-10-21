<?php

declare(strict_types=1);

namespace OpenFeature\Providers\Flagd\http;

use OpenFeature\implementation\provider\ResolutionDetailsFactory;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenFeature\Providers\Flagd\config\IConfig;
use OpenFeature\Providers\Flagd\service\ServiceInterface;

class HttpService implements ServiceInterface
{
    public static function fromConfig(IConfig $config): HttpService
    {
        $target = `{$config->getHost()}:{$config->getPort()}`;

        return new static($target);
    }

    private function __construct()
    {

    }

    public function resolveValue(string $flagKey, string $flagType, $defaultValue, ?EvaluationContext $context): ResolutionDetails
    {
        // todo: implement
        return ResolutionDetailsFactory::fromSuccess($defaultValue);
    }
}