# OpenFeature SplitIO Provider for PHP

![Experimental](https://img.shields.io/badge/experimental-breaking%20changes%20allowed-yellow)
![Alpha](https://img.shields.io/badge/alpha-release-red)

## Disclaimer

:warning: **Work in Progress** :warning:

_I'm throwing this project together as a potential demo-phase of OpenFeature for PHP utilizing the Split SDK. It is not complete and is very much work in progress._

## Overview

Split is a feature delivery platform that powers feature flag management, software experimentation, and continuous delivery. This repository and package provides the client side code for interacting with it via the OpenFeature PHP SDK.

This package also builds on various PSRs (PHP Standards Recommendations) such as the Logger interfaces (PSR-3) and the Basic and Extended Coding Standards (PSR-1 and PSR-12).

## Installation

```
$ composer require open-feature/split-provider   // installs the latest version
```

## Usage

The `SplitProvider` client constructor takes a single optional argument with 3 fields, their default values correspond to the default arguments supplied to the flagd server:

```php
$splitConfig = [
    'cache' => [
      'adapter' => 'predis',
      'parameters' => [
        'scheme' => 'tcp',
        'host' => getenv('REDIS_HOST'),
        'port' => getenv('REDIS_PORT'),
        'timeout' => 881,
      ],
      'options' => [
        'prefix' => '',
      ],
    ],
];

$splitApiKey = getenv('SPLIT_API_KEY');

$provider = new SplitProvider($splitApiKey, $splitConfig);
```

For more information on the configuration options, please see the Split PHP SDK documentation on [Configuration](https://help.split.io/hc/en-us/articles/360020350372-PHP-SDK#configuration).

Resolving values requires the use of the `EvaluationContext, where you can provide the `targetingKey` for the evaluation (the identifier which represents the user/account/etc.)

```php
$client = $api->getClient('split-example', '1.0.0');

$featureEnabled = $client->getBooleanDetails('dev.openfeature.example_flag', false, new EvaluationContext('user-id'), null);

if ($featureEnabled) {
  // do new logic here
} else {
  // do old logic here
}
```

You can provide more elaborate attributes to resolve values, but the values must conform to the requirements of the Split SDK. Information on what attributes are allowed can be found in the [Attributes section](https://help.split.io/hc/en-us/articles/360020350372-PHP-SDK#attribute-syntax) of the PHP SDK documentation.

```php
$client = $api->getClient('split-example', '1.0.0');

$featureEnabled = $client->getBooleanDetails('dev.openfeature.example_flag', false, new EvaluationContext('user-id', [
  'plan_type' => 'growth',
  'registered_date' => (new DateTime('now', new DateTimeZone('UTC')))->getTimestamp(),
  'deal_size' => 10000,
  'paying_customer' => True,
  'permissions' => ['gold','silver','platinum'],
]), null);

if ($featureEnabled) {
  // do new logic here
} else {
  // do old logic here
}
```

## Development

### PHP Versioning

This library targets PHP version 7.4 and newer. As long as you have any compatible version of PHP on your system you should be able to utilize the OpenFeature SDK.

This package also has a `.tool-versions` file for use with PHP version managers like `asdf`.

### Installation and Dependencies

Install dependencies with `composer install`. `composer install` will update the `composer.lock` with the most recent compatible versions.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

### Testing

Run tests with `composer run test`.