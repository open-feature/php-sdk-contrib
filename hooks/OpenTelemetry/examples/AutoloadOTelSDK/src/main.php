<?php

declare(strict_types=1);

use OpenFeature\OpenFeatureAPI;

putenv('OTEL_PHP_AUTOLOAD_ENABLED=true');
putenv('OTEL_TRACES_EXPORTER=otlp');
putenv('OTEL_EXPORTER_OTLP_PROTOCOL=grpc');
putenv('OTEL_METRICS_EXPORTER=otlp');
putenv('OTEL_EXPORTER_OTLP_METRICS_PROTOCOL=grpc');
putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://collector:4317');
putenv('OTEL_PHP_TRACES_PROCESSOR=batch');
putenv('OTEL_PROPAGATORS=b3,baggage,tracecontext');

echo 'autoloading SDK example starting...' . PHP_EOL;

// Composer autoloader will execute SDK/_autoload.php which will register global instrumentation from environment configuration
require dirname(__DIR__) . '/vendor/autoload.php';

$client = OpenFeatureAPI::getInstance()->getClient('dev.openfeature.contrib.php.demo', '1.0.0');

$version = $client->getStringValue('dev.openfeature.contrib.php.version-value', 'unknown');

echo 'Version is ' . $version;