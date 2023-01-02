<?php

declare(strict_types=1);

namespace Examples\OpenFeature\Http;

require __DIR__ . '/../../../vendor/autoload.php';

use OpenFeature\Hooks\Validators\Regexp\RegexpValidatorHook;
use OpenFeature\OpenFeatureAPI;


// retrieve the OpenFeatureAPI instance
$api = OpenFeatureAPI::getInstance();

// retrieve an OpenFeatureClient
$client = $api->getClient('split-example', '1.0');

// create some example hook validators

$alphanumericValidator = new RegexpValidatorHook('/^[A-Za-z0-9]+$/');
$hexadecimalValidator = new RegexpValidatorHook('/^[0-9a-f]+$/');
$asciiValidator = new RegexpValidatorHook('/^[ -~]$/');

$client->setHooks([
  $alphanumericValidator,
  $hexadecimalValidator,
  $asciiValidator
]);

$flagValue = $client->getBooleanDetails('dev.openfeature.example_flag', true, null, null);
