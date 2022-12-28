<?php

declare(strict_types=1);

use OpenFeature\OpenFeatureAPI;

echo 'autoloading SDK example starting...' . PHP_EOL;

// Composer autoloader will execute SDK/_autoload.php which will register global instrumentation from environment configuration
require dirname(__DIR__) . '/vendor/autoload.php';

$client = OpenFeatureAPI::getInstance()->getClient('dev.openfeature.contrib.php.demo', '1.0.0');

$version = $client->getStringValue('dev.openfeature.contrib.php.version-value', 'unknown');

echo 'Version is ' . $version;