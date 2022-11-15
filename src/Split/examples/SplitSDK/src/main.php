<?php

declare(strict_types=1);

namespace Examples\OpenFeature\Http;

require __DIR__ . '/../../../vendor/autoload.php';

use OpenFeature\OpenFeatureAPI;
use OpenFeature\Providers\Split\SplitProvider;


// retrieve the OpenFeatureAPI instance
$api = OpenFeatureAPI::getInstance();

// build configuration options for Split SDK
$parameters = [
  'scheme' => 'tcp',
  'host' => getenv('REDIS_HOST'),
  'port' => getenv('REDIS_PORT'),
  'timeout' => 881,
];

$options = ['prefix' => ''];

$splitConfig = [
  'cache' => [
    'adapter' => 'predis', 
    'parameters' => $parameters, 
    'options' => $options,
  ],
];

$splitApiKey = getenv('SPLIT_API_KEY');

$provider = new SplitProvider($splitApiKey, $splitConfig);

$api->setProvider($provider);

// retrieve an OpenFeatureClient
$client = $api->getClient('split-example', '1.0');

$flagValue = $client->getBooleanDetails('dev.openfeature.example_flag', true, null, null);
