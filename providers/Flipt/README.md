# FliptProvider for OpenFeature PHP SDK

The `FliptProvider` is an integration for the [OpenFeature](https://github.com/open-feature/php-sdk) PHP SDK, allowing you to use [Flipt](https://flipt.io) as a feature flagging service.

## Installation

Before using the `FliptProvider`, you must have the OpenFeature PHP SDK installed. If you have not already installed the SDK, you can do so with Composer:

```bash
composer require open-feature/php-sdk
```

Next, include the FliptProvider in your project:

```bash
composer require open-feature/flipt-provider
```

## Usage
To use the FliptProvider, you'll need to create an instance of it by passing your Flipt host, API token, and namespace (if applicable). Then, set this provider for OpenFeature.

Here's a quick example:

```php

use OpenFeature\OpenFeature;
use OpenFeature\Providers\Flipt\FliptProvider;

// Replace these with your actual Flipt host and API token
$host = 'http://your-flipt-instance';
$apiToken = 'your-api-token';
$namespace = 'namespace';

$provider = new FliptProvider($host, $apiToken, $namespace);

OpenFeature::setProvider($provider);

// Now you can evaluate your feature flags as follows
$booleanFlagValue = OpenFeatureAPI::getInstance()->getClient()->getBooleanValue('your-boolean-flag-key', false);
$stringFlagValue = OpenFeatureAPI::getInstance()->getClient()->getStringValue('your-string-flag-key', 'default-value');
$integerFlagValue = OpenFeatureAPI::getInstance()->getClient()->getIntegerValue('your-integer-flag-key', 0);
$floatFlagValue = OpenFeatureAPI::getInstance()->getClient()->getFloatValue('your-float-flag-key', 0.0);
$objectFlagValue = OpenFeatureAPI::getInstance()->getClient()->getObjectValue('your-object-flag-key', ['default' => 'value']);
```

### Caching

If you like to cache the feature flag results you can pass a [PSR-16](https://www.php-fig.org/psr/psr-16/) compatible cache storage into the provider constructor like this:

```php

$cache = '<your psr-16 compatible cache storage>';
$provider = new FliptProvider($host, $apiToken, $namespace, $cache);

OpenFeature::setProvider($provider);


// to clear the cache you can call
$provider->cacheClear();
```
