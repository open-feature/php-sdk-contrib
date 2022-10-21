# OpenFeature Flagd Provider for PHP

![Experimental](https://img.shields.io/badge/experimental-breaking%20changes%20allowed-yellow)
![Alpha](https://img.shields.io/badge/alpha-release-red)

## Disclaimer

_I'm throwing this project together as a potential demo-phase of OpenFeature for PHP, with future work surrounding a Split PHP provider (wrapping their existing package). It is not complete and is very much work in progress._

## Overview

Flagd is a simple command line tool for fetching and presenting feature flags to services. It is designed to conform to OpenFeature schema for flag definitions. This repository and package provides the client side code for interacting with it via the OpenFeature PHP SDK.

This package also builds on various PSRs (PHP Standards Recommendations) such as the Logger interfaces (PSR-3) and the Basic and Extended Coding Standards (PSR-1 and PSR-12).

## Installation

```
$ composer require 0xc/openfeature-flagd-provider   // installs the latest version
```

## Usage

The `FlagdProvider` client constructor takes a single optional argument with 3 fields, their default values correspond to the default arguments supplied to the flagd server:

```
  OpenFeatureAPI::setProvider(new FlagdProvider([
      'service' => 'grpc',
      'host' => 'localhost',
      'port' => 8013,
  ]))
```

**service**: "http" | "grpc" _(defaults to http)_  
**host**: string _(defaults to "localhost")_  
**port**: number _(defaults to 8013)_  
**protocol**: "http" | "https" _(defaults to http - only active for http service)_

## Development

### PHP Versioning

This library targets PHP version 7.4 and newer. As long as you have any compatible version of PHP on your system you should be able to utilize the OpenFeature SDK.

This package also has a `.tool-versions` file for use with PHP version managers like `asdf`.

### Installation and Dependencies

Install dependencies with `composer install`. `composer install` will update the `composer.lock` with the most recent compatible versions.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

### Testing

Run tests with `composer run test`.
