<?php

declare(strict_types=1);

namespace Examples\OpenFeature\CloudBees;

require __DIR__ . '/vendor/autoload.php';

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\Cache\FilesystemCache;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\Providers\CloudBees\CloudBeesProvider;
use Rox\Core\Consts\Environment;
use Rox\Core\Logging\LoggerFactory;
use Rox\Core\Logging\MonologLoggerFactory;
use Rox\Server\RoxOptions;
use Rox\Server\RoxOptionsBuilder;

use const FILTER_VALIDATE_BOOLEAN;


// retrieve the OpenFeatureAPI instance
$api = OpenFeatureAPI::getInstance();

// setup the Rox provider
function isEnabled(string $envVar)
{
    return filter_var($_ENV[$envVar], FILTER_VALIDATE_BOOLEAN);
}

const DEFAULT_API_KEY = '5e6a3533d3319d76d1ca33fd';
const DEFAULT_DEV_MODE_KEY = '297c23e7fcb68e54c513dcca';

if (!isset($_ENV[Environment::ENV_VAR_NAME])) {
    $_ENV[Environment::ENV_VAR_NAME] = Environment::QA;
}

$apiKey = isset($_ENV['ROLLOUT_API_KEY'])
    ? $_ENV['ROLLOUT_API_KEY']
    : DEFAULT_API_KEY;

$devModeKey = isset($_ENV['ROLLOUT_DEV_MODE_KEY'])
    ? $_ENV['ROLLOUT_DEV_MODE_KEY']
    : DEFAULT_DEV_MODE_KEY;

$roxOptionsBuilder = (new RoxOptionsBuilder())
    ->setDevModeKey($devModeKey);

if (isEnabled('ROLLOUT_LOGGING')) {
    $logFile = join(DIRECTORY_SEPARATOR, [
        sys_get_temp_dir(),
        'rollout',
        'logs',
        'demo.log'
    ]);

    LoggerFactory::setup((new MonologLoggerFactory())
        ->setDefaultHandlers([
            new StreamHandler($logFile, Logger::DEBUG)
        ]));
}

if (isEnabled('ROLLOUT_CACHE')) {
    $roxOptionsBuilder
        ->setCacheStorage(new DoctrineCacheStorage(
            new ChainCache([
                new ArrayCache(),
                new FilesystemCache('/tmp/rollout/cache'),
            ])
        ))
        ->setLogCacheHitsAndMisses(true)
        ->setConfigFetchIntervalInSeconds(30);
}

$provider = CloudBeesProvider::setup($apiKey, new RoxOptions($roxOptionsBuilder));

$api->setProvider($provider);

// retrieve an OpenFeatureClient
$client = $api->getClient('cloudbees-example', '1.0');

$flagValue = $client->getBooleanDetails('dev.openfeature.example_flag', true, null, null);

// make sure to shutdown the CloudBees provider
CloudBeesProvider::shutdown();
