<?php

declare(strict_types=1);

namespace Examples\OpenFeature\Http;

require __DIR__ . '/../../../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\Providers\Flagd\FlagdProvider;

// Retrieve the OpenFeatureAPI instance
$api = OpenFeatureAPI::getInstance();

// Create a PSR-3 logger to use for OpenFeature
$logger = new Logger('openfeature-logger');
$logger->pushHandler(new StreamHandler('logs/openfeature.log', Logger::WARNING));

$api->setLogger($logger);

// Configure a flagd provider
$provider = new FlagdProvider([
  'host' => 'localhost',
  'port' => 8013,
  'secure' => false,
  'protocol' => 'grpc'
]);

$api->setProvider($provider);

// Retrieve an OpenFeatureClient
$client = $api->getClient('grpc-example', '1.0');

// Resolve a value
$flagValue = $client->getBooleanDetails('dev.openfeature.example_flag', true, null, null);

$logger->info("Resolved the boolean value: " . ($flagValue ? 'true' : 'false'));