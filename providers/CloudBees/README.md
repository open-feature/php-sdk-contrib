# OpenFeature CloudBees Provider for PHP

[![a](https://img.shields.io/badge/slack-%40cncf%2Fopenfeature-brightgreen?style=flat&logo=slack)](https://cloud-native.slack.com/archives/C0344AANLA1)
[![Latest Stable Version](http://poser.pugx.org/open-feature/cloudbees-provider/v)](https://packagist.org/packages/open-feature/cloudbees-provider)
[![Total Downloads](http://poser.pugx.org/open-feature/cloudbees-provider/downloads)](https://packagist.org/packages/open-feature/cloudbees-provider)
![PHP 7.4+](https://img.shields.io/badge/php->=7.4-blue.svg)
[![License](http://poser.pugx.org/open-feature/cloudbees-provider/license)](https://packagist.org/packages/open-feature/cloudbees-provider)

## Overview

CloudBees Feature Management is designed to release, control, and measure features at scale. This repository and package provides the client side code for interacting with it via the OpenFeature PHP SDK.

This package also builds on various PSRs (PHP Standards Recommendations) such as the Logger interfaces (PSR-3) and the Basic and Extended Coding Standards (PSR-1 and PSR-12).

### Limitations

There is [an open issue](https://github.com/rollout/rox-php/issues/37) with object deserialization in the upstream library used for Rollout, CloudBees Feature Management SDK for PHP. Objects are also not first-class citizens of the feature management system, as in there is no direct "object" retrieval. Instead the OpenFeature provider builds upon the string retrieval with JSON as the expected format. This doesn't _really_ work though, due to the open issue above. Once that is resolved, JSON objects saved in CloudBees Feature Management system will simply be accessible.

## Installation

```sh
composer require open-feature/cloudbees-provider
```

## Usage

The `CloudBeesProvider` can be created with the static `setup` method. This works in much the same way as the `Rox::setup` method, so you can refer to the Rollout documentation for PHP [here](https://docs.cloudbees.com/docs/cloudbees-feature-management/latest/getting-started/php-sdk) for more information.

```php
// retrieve the OpenFeatureAPI instance
$api = OpenFeatureAPI::getInstance();

// setup the CloudBeesProvider with the default settings
$provider = CloudBeesProvider::setup($apiKey);

// set the OpenFeature provider
$api->setProvider($provider);

// retrieve an OpenFeatureClient
$client = $api->getClient('cloudbees-example', '1.0');

$flagValue = $client->getBooleanDetails('dev.openfeature.example_flag', true, null, null);

// ... do work with the $flagValue

// IMPORTANT! make sure to shutdown the CloudBees provider
CloudBeesProvider::shutdown();

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

#### Integration tests

The integration test suite utilizes a locally available mock server for Rollout called Roxy.

The docker image is published under `rollout/roxy`.

For more information on Roxy, see [the documentation](https://docs.cloudbees.com/docs/cloudbees-feature-management/latest/debugging/microservices-automated-testing-and-local-development#_running_roxy).
