# OpenFeature Flagsmith Provider for PHP

[![a](https://img.shields.io/badge/slack-%40cncf%2Fopenfeature-brightgreen?style=flat&logo=slack)](https://cloud-native.slack.com/archives/C0344AANLA1)
[![Latest Stable Version](http://poser.pugx.org/open-feature/flagsmith-provider/v)](https://packagist.org/packages/open-feature/flagsmith-provider)
[![Total Downloads](http://poser.pugx.org/open-feature/flagsmith-provider/downloads)](https://packagist.org/packages/open-feature/flagsmith-provider)
![PHP 8.0+](https://img.shields.io/badge/php->=8.0-blue.svg)
[![License](http://poser.pugx.org/open-feature/flagsmith-provider/license)](https://packagist.org/packages/open-feature/flagsmith-provider)

## Overview

Flagsmith is an open-source feature flag and remote configuration service. This repository and package provides the client side code for interacting with it via the OpenFeature PHP SDK.

This package also builds on various PSRs (PHP Standards Recommendations) such as the Logger interfaces (PSR-3) and the Basic and Extended Coding Standards (PSR-1 and PSR-12).

## Installation

```sh
composer require open-feature/flagsmith-provider
```

## Usage

### Basic Setup

The `FlagsmithProvider` requires a `FlagsmithConfig` object with your Flagsmith environment API key:

```php
use OpenFeature\Providers\Flagsmith\FlagsmithProvider;
use OpenFeature\Providers\Flagsmith\config\FlagsmithConfig;
use OpenFeature\OpenFeatureAPI;

// Create config with your Flagsmith API key
$config = new FlagsmithConfig('your-environment-api-key');

// Create and set the provider
$provider = new FlagsmithProvider($config);
$api = OpenFeatureAPI::getInstance();
$api->setProvider($provider);
```

### Advanced Configuration

You can optionally configure the API URL, custom headers, and request timeout:

```php
$config = new FlagsmithConfig(
    apiKey: 'your-environment-api-key',
    apiUrl: 'https://custom.flagsmith.com/api/v1/',  // Optional: custom API URL
    customHeaders: (object)['X-Custom-Header' => 'value'],  // Optional: custom headers
    requestTimeout: 5000  // Optional: request timeout in milliseconds
);

$provider = new FlagsmithProvider($config);
```

### Evaluating Feature Flags

#### Without Targeting (Environment Flags)

```php
$client = $api->getClient();

$featureEnabled = $client->getBooleanValue('new-feature', false);

if ($featureEnabled) {
    // New feature logic
} else {
    // Old feature logic
}
```

#### With Targeting (Identity Flags)

Provide a `targetingKey` to evaluate flags for a specific user:

```php
use OpenFeature\implementation\flags\EvaluationContext;

$context = new EvaluationContext('user-123');

$featureEnabled = $client->getBooleanValue('new-feature', false, $context);
```

#### With User Traits

Include user attributes for advanced targeting:

```php
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;

$context = new EvaluationContext(
    'user-123',
    new Attributes([
        'email' => 'user@example.com',
        'plan' => 'premium',
        'signup_date' => '2024-01-01',
    ])
);

$featureEnabled = $client->getBooleanValue('premium-feature', false, $context);
```

### Supported Value Types

The provider supports all OpenFeature value types:

```php
// Boolean
$boolValue = $client->getBooleanValue('boolean-flag', false, $context);

// String
$stringValue = $client->getStringValue('string-flag', 'default', $context);

// Integer
$intValue = $client->getIntegerValue('int-flag', 0, $context);

// Float
$floatValue = $client->getFloatValue('float-flag', 0.0, $context);

// Object (array in PHP)
$objectValue = $client->getObjectValue('object-flag', [], $context);
```

### Getting Evaluation Details

Access additional evaluation metadata:

```php
$details = $client->getBooleanDetails('feature-flag', false, $context);

echo $details->getValue();    // The flag value
echo $details->getReason();   // Reason code: STATIC, TARGETING_MATCH, DISABLED, ERROR
$error = $details->getError(); // Error details if evaluation failed
```

#### Reason Codes

- `STATIC` - Flag evaluated using environment defaults (no targeting)
- `TARGETING_MATCH` - Flag evaluated with user targeting/identity
- `DISABLED` - Flag is disabled but value returned
- `ERROR` - Evaluation failed (flag not found, type mismatch, etc.)

## Examples

Run the example script from the repository root:

```sh
export FLAGSMITH_API_KEY='your-environment-key'
php providers/Flagsmith/examples/Flagsmith/main.php
```

Or in one line:

```sh
FLAGSMITH_API_KEY='your-key' php providers/Flagsmith/examples/Flagsmith/main.php
```

## Development

### PHP Versioning

This library targets PHP version 8.0 and newer. As long as you have any compatible version of PHP on your system you should be able to utilize the OpenFeature SDK.

This package also has a `.tool-versions` file for use with PHP version managers like `asdf`.

### Installation and Dependencies

Install dependencies with `composer install`. `composer install` will update the `composer.lock` with the most recent compatible versions.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

### Testing

Run tests with `composer run test`.

```sh
composer run test
```

### Linting and Standards

Run linting and code standards checks:

```sh
composer run dev:lint
```

## Contributing

Contributions are welcome! Please see the [OpenFeature Contributor Guide](https://openfeature.dev/community) for more information.

## License

Apache 2.0 - See [LICENSE](./LICENSE) for more information.
