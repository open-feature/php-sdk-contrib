# OpenFeature Flagd Provider for PHP

[![a](https://img.shields.io/badge/slack-%40cncf%2Fopenfeature-brightgreen?style=flat&logo=slack)](https://cloud-native.slack.com/archives/C0344AANLA1)
[![Latest Stable Version](http://poser.pugx.org/open-feature/flagd-provider/v)](https://packagist.org/packages/open-feature/flagd-provider)
[![Total Downloads](http://poser.pugx.org/open-feature/flagd-provider/downloads)](https://packagist.org/packages/open-feature/flagd-provider)
![PHP 8.0+](https://img.shields.io/badge/php->=8.0-blue.svg)
[![License](http://poser.pugx.org/open-feature/flagd-provider/license)](https://packagist.org/packages/open-feature/flagd-provider)

## Overview

Flagd is a simple command line tool for fetching and presenting feature flags to services. It is designed to conform to OpenFeature schema for flag definitions. This repository and package provides the client side code for interacting with it via the OpenFeature PHP SDK.

This package also builds on various PSRs (PHP Standards Recommendations) such as the Logger interfaces (PSR-3) and the Basic and Extended Coding Standards (PSR-1 and PSR-12).

## Installation

```sh
composer require open-feature/flagd-provider
```

## Usage

The `FlagdProvider` client constructor takes a single optional argument with 3 fields, their default values correspond to the default arguments supplied to the flagd server:

```php
/** @var \Psr\Http\Client\ClientInterface $client */
$client;

/** @var Psr\Http\Message\RequestFactoryInterface $requestFactory */
$requestFactory;

/** @var Psr\Http\Message\StreamFactoryInterface $streamFactory */
$streamFactory;

OpenFeatureAPI::setProvider(new FlagdProvider([
    'protocol' => 'http',
    'host' => 'localhost',
    'port' => 8013,
    'secure' => true,
    'httpConfig' => [
      'client' => $client,
      'requestFactory' => $requestFactory,
      'streamFactory' => $streamFactory,
    ],
]));
```

- **protocol**: "http" _(defaults to http)_
- **host**: string _(defaults to "localhost")_
- **port**: number _(defaults to 8013)_
- **secure**: true | false _(defaults to false)_
- **httpConfig**: An array or `HttpConfig` object, providing implementations for PSR interfaces
    - **client**: a `ClientInterface` implementation
    - **requestFactory**: a `RequestFactoryInterface` implementation
    - **streamFactory**: a `StreamFactoryInterface` implementation

### gRPC vs HTTP

The Flagd server is gRPC but offers gRPC Web endpoints that can be accessed over HTTP. The latter is used by the current implementation of the Flagd provider, with future development planned to implement a gRPC native provider option. There are certain flexibilities around HTTP with PHP available, whereas gRPC is an opinionated code-generation strategy, but they are both useful and gRPC native may provide better performance over certain sync/async scenarios. An additional goal will be to provide benchmarking of the Flagd provider's protocol for various scenarios so this decision can be made more easily by consumers of the provider.

## Development

### PHP Versioning

This library targets PHP version and newer. As long as you have any compatible version of PHP on your system you should be able to utilize the OpenFeature SDK.

This package also has a `.tool-versions` file for use with PHP version managers like `asdf`.

### Installation and Dependencies

Install dependencies with `composer install`. `composer install` will update the `composer.lock` with the most recent compatible versions.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

### Testing

Run tests with `composer run test`.
