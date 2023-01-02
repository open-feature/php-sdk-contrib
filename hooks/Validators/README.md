# OpenFeature Validator Hooks

[![a](https://img.shields.io/badge/slack-%40cncf%2Fopenfeature-brightgreen?style=flat&logo=slack)](https://cloud-native.slack.com/archives/C0344AANLA1)
[![Latest Stable Version](http://poser.pugx.org/open-feature/validators-hook/v)](https://packagist.org/packages/open-feature/validators-hook)
[![Total Downloads](http://poser.pugx.org/open-feature/validators-hook/downloads)](https://packagist.org/packages/open-feature/validators-hook)
![PHP 7.4+](https://img.shields.io/badge/php->=7.4-blue.svg)
[![License](http://poser.pugx.org/open-feature/validators-hook/license)](https://packagist.org/packages/open-feature/validators-hook)

## Overview

Validator Hook constructs that provide means to execute validation against resolved feature flag values.

This package also builds on various PSRs (PHP Standards Recommendations) such as the Logger interfaces (PSR-3) and the Basic and Extended Coding Standards (PSR-1 and PSR-12).

## Installation

```sh
composer require open-feature/validators-hook
```

## Usage

The following validator hook constructs are available, but more are being worked on over time:

- `RegexpValidatorHook`


```php
use OpenFeature\Hooks\Validators\RegexpValidatorHook;

$alphanumericValidator = new RegexpValidatorHook('/^[A-Za-z0-9]+$/');
$hexadecimalValidator = new RegexpValidatorHook('/^[0-9a-f]+$/');
$asciiValidator = new RegexpValidatorHook('/^[ -~]$/');

// hooks can be applied to the global API, clients, providers, and resolution invocations

// all feature flag resolutions will use this validator
$api = OpenFeatureAPI::getInstance();
$api->addHooks($asciiValidator);

// invocations from this client will use this validator also
$client = $api->getClient('example');
$client->setHooks([$alphanumericValidator]);

// this specific invocation will use this validator also
$client->resolveBooleanValue('test-flag', 'deadbeef', null, new EvaluationOptions([$hexadecimalValidator]));
```

For more examples, see the [examples](./examples/).

## Development

### PHP Versioning

This library targets PHP version 7.4 and newer. As long as you have any compatible version of PHP on your system you should be able to utilize the OpenFeature SDK.

This package also has a `.tool-versions` file for use with PHP version managers like `asdf`.

### Installation and Dependencies

Install dependencies with `composer install`. `composer install` will update the `composer.lock` with the most recent compatible versions.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

### Testing

Run tests with `composer run test`.
