<p align="center">
  <img width="400" src="https://raw.githubusercontent.com/thomaspoignant/go-feature-flag/main/gofeatureflag.svg" alt="go-feature-flag logo" />

</p>

# GO Feature Flag - OpenFeature PHP provider
<p align="center">
  <a href="https://packagist.org/packages/open-feature/go-feature-flag"><img src="https://img.shields.io/packagist/v/open-feature/go-feature-flag-provider?color=blue&logo=php" /></a>
  <a href="https://packagist.org/packages/open-feature/go-feature-flag"><img src="https://img.shields.io/packagist/dt/open-feature/go-feature-flag-provider?logo=php" /></a>
  <img alt="Packagist Version" src="https://img.shields.io/packagist/v/open-feature/go-feature-flag-provider?logo=php&color=blue">
  <a href="https://gofeatureflag.org/"><img src="https://img.shields.io/badge/%F0%9F%93%92-Website-blue" alt="Documentation"></a>
  <a href="https://github.com/thomaspoignant/go-feature-flag/issues"><img src="https://img.shields.io/badge/%E2%9C%8F%EF%B8%8F-issues-red" alt="Issues"></a>
  <a href="https://gofeatureflag.org/slack"><img src="https://img.shields.io/badge/join-us%20on%20slack-gray.svg?longCache=true&logo=slack&colorB=green" alt="Join us on slack"></a>
</p>

This repository contains the official PHP OpenFeature provider for accessing your feature flags with [GO Feature Flag](https://gofeatureflag.org).

In conjunction with the [OpenFeature SDK](https://openfeature.dev/docs/reference/concepts/provider) you will be able
to evaluate your feature flags in your Ruby applications.

For documentation related to flags management in GO Feature Flag,
refer to the [GO Feature Flag documentation website](https://gofeatureflag.org/docs).

### Functionalities:
- Manage the integration of the OpenFeature PHP SDK and GO Feature Flag relay-proxy.

## Dependency Setup

### Composer

```shell
composer require open-feature/go-feature-flag-provider
```
## Getting started

### Initialize the provider

The `GoFeatureFlagProvider` takes a config object as parameter to be initialized.

The constructor of the config object has the following options:

| **Option**      | **Description**                                                                                                  |
|-----------------|------------------------------------------------------------------------------------------------------------------|
| `endpoint`      | **(mandatory)** The URL to access to the relay-proxy.<br />*(example: `https://relay.proxy.gofeatureflag.org/`)* |
| `apiKey`        | The token used to call the relay proxy.                                                                          |
| `customHeaders` | Any headers you want to add to call the relay-proxy.                                                             |
| `httpclient`    | The HTTP Client to use (if you want to use a custom one). _It has to be a `PSR-7` compliant implementation._     |

The only required option to create a `GoFeatureFlagProvider` is the URL _(`endpoint`)_ to your GO Feature Flag relay-proxy instance.

```php
use OpenFeature\Providers\GoFeatureFlag\config\Config;
use OpenFeature\Providers\GoFeatureFlag\GoFeatureFlagProvider;
use OpenFeature\implementation\flags\MutableEvaluationContext;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\OpenFeatureAPI;

$config = new Config('http://gofeatureflag.org', 'my-api-key');
$provider = new GoFeatureFlagProvider($config);

$api = OpenFeatureAPI::getInstance();
$api->setProvider($provider);
$client = $api->getClient();
$evaluationContext = new MutableEvaluationContext(
      "214b796a-807b-4697-b3a3-42de0ec10a37", 
      new Attributes(["email" => 'contact@gofeatureflag.org'])
  );

$value = $client->getBooleanDetails('integer_key', false, $evaluationContext);
if ($value) {
    echo "The flag is enabled";
} else {
    echo "The flag is disabled";
}
```

The evaluation context is the way for the client to specify contextual data that GO Feature Flag uses to evaluate the feature flags, it allows to define rules on the flag.

The `targeting_key` is mandatory for GO Feature Flag to evaluate the feature flag, it could be the id of a user, a session ID or anything you find relevant to use as identifier during the evaluation.


### Evaluate a feature flag
The client is used to retrieve values for the current `EvaluationContext`.
For example, retrieving a boolean value for the flag **"my-flag"**:

```php
$value = $client->getBooleanDetails('integer_key', false, $evaluationContext);
if ($value) {
  echo "The flag is enabled";
} else {
  echo "The flag is disabled";
}
```

GO Feature Flag supports different all OpenFeature supported types of feature flags, it means that you can use all the accessor directly
```php
// Bool
$client->getBooleanDetails('my-flag-key', false, new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37"));
$client->getBooleanValue('my-flag-key', false, new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37"));

// String
$client->getStringDetails('my-flag-key', "default", new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37"));
$client->getStringValue('my-flag-key', "default", new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37"));

// Integer
$client->getIntegerDetails('my-flag-key', 1, new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37"));
$client->getIntegerValue('my-flag-key', 1, new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37"));

// Float
$client->getFloatDetails('my-flag-key', 1.1, new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37"));
$client->getFloatValue('my-flag-key', 1.1, new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37"));

// Object
$client->getObjectDetails('my-flag-key', ["default" => true], new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37"));
$client->getObjectValue('my-flag-key', ["default" => true], new MutableEvaluationContext("214b796a-807b-4697-b3a3-42de0ec10a37"));
```

## Features status

| Status | Feature         | Description                                                                |
|-------|-----------------|----------------------------------------------------------------------------|
| ✅     | Flag evaluation | It is possible to evaluate all the type of flags                           |
| ❌     | Caching         | Mechanism is in place to refresh the cache in case of configuration change |
| ❌     | Event Streaming | Not supported by the SDK                                                   |
| ❌     | Logging         | Not supported by the SDK                                                   |
| ❌     | Flag Metadata   | Not supported by the SDK                                                   |


<sub>**Implemented**: ✅ | In-progress: ⚠️ | Not implemented yet: ❌</sub>

## Contributing
This project welcomes contributions from the community.
If you're interested in contributing, see the [contributors' guide](https://github.com/thomaspoignant/go-feature-flag/blob/main/CONTRIBUTING.md) for some helpful tips.

### PHP Versioning
This library targets PHP version 8.0 and newer. As long as you have any compatible version of PHP on your system you should be able to utilize the OpenFeature SDK.

This package also has a .tool-versions file for use with PHP version managers like asdf.

### Installation and Dependencies
Install dependencies with `composer install`, it will update the `composer.lock` with the most recent compatible versions.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

