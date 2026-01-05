<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use OpenFeature\OpenFeatureAPI;
use OpenFeature\Providers\Flagsmith\config\FlagsmithConfig;
use OpenFeature\Providers\Flagsmith\FlagsmithProvider;

// Get API key from environment
$apiKey = getenv('FLAGSMITH_API_KEY');
if (!$apiKey) {
    die("ERROR: FLAGSMITH_API_KEY environment variable not set\n");
}

// Initialize provider
$config = new FlagsmithConfig($apiKey);
$provider = new FlagsmithProvider($config);

$api = OpenFeatureAPI::getInstance();
$api->setProvider($provider);

$client = $api->getClient();

// Boolean flag (environment)
$boolValue = $client->getBooleanValue('example-boolean-flag', false);
echo "Boolean (environment): " . ($boolValue ? 'true' : 'false') . "\n";

// String flag
$stringValue = $client->getStringValue('example-string-flag', 'default-value');
echo "String: $stringValue\n";

// Boolean with user context
$context = new EvaluationContext('user-123');
$details = $client->getBooleanDetails('example-boolean-flag', false, $context);
echo "Boolean (user-123): " . ($details->getValue() ? 'true' : 'false') . " [" . $details->getReason() . "]\n";

// With user traits
$contextWithTraits = new EvaluationContext(
    'user-456',
    new Attributes([
        'email' => 'premium-user@example.com',
        'plan' => 'premium',
    ])
);
$details = $client->getBooleanDetails('premium-feature', false, $contextWithTraits);
echo "Premium feature (user-456): " . ($details->getValue() ? 'true' : 'false') . " [" . $details->getReason() . "]\n";

// Integer flag
$intValue = $client->getIntegerValue('max-items', 10, $context);
echo "Integer: $intValue\n";

// Float flag
$floatValue = $client->getFloatValue('discount-rate', 0.0, $context);
echo "Float: $floatValue\n";

// Object flag
$objectValue = $client->getObjectValue('feature-config', ['enabled' => false], $context);
echo "Object: " . json_encode($objectValue) . "\n";

// Flag not found
$details = $client->getBooleanDetails('non-existent-flag', false);
echo "Non-existent: " . ($details->getValue() ? 'true' : 'false') . " [" . $details->getReason() . "]\n";
if ($details->getError()) {
    echo "  Error: " . $details->getError()->getResolutionErrorMessage() . "\n";
}
