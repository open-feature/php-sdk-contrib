<?php

declare(strict_types=1);

namespace Examples\OpenFeature\Http;

require __DIR__ . '/../../../vendor/autoload.php';

use OpenFeature\OpenFeatureAPI;
use OpenFeature\Providers\Flagd\FlagdProvider;


// retrieve the OpenFeatureAPI instance
$api = OpenFeatureAPI::getInstance();

// configure a provider
$provider = new FlagdProvider([
  'host' => 'localhost',
  'port' => 8013,
  'secure' => false,
  'protocol' => 'http'
]);

$api->setProvider($provider);

// retrieve an OpenFeatureClient
$client = $api->getClient('http-example', '1.0');

$flagValue = $client->getBooleanDetails('dev.openfeature.example_flag', true, null, null);
