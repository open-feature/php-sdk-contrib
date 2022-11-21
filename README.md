# OpenFeature PHP SDK Contrib Library

![Experimental](https://img.shields.io/badge/experimental-breaking%20changes%20allowed-yellow)
![Alpha](https://img.shields.io/badge/alpha-release-red)

## Disclaimer

:warning: **Work in Progress** :warning:

## Overview

The `php-contrib-sdk` repository is a monorepository containing various providers and hooks for OpenFeature's PHP SDK. Packages include:

- [Flagd](./src/Flagd/README.md)
- [Split](./src/Split/README.md)
- [CloudBees](./src/CloudBees/README.md)

## Development

### PHP Versioning

This library targets PHP version 7.4 and newer. As long as you have any compatible version of PHP on your system you should be able to utilize the OpenFeature SDK.

This package also has a `.tool-versions` file for use with PHP version managers like `asdf`.

### Installation and Dependencies

Install dependencies with `composer install`. `composer install` will update the `composer.lock` with the most recent compatible versions.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

### Testing

Each package implements its own test suite.

Run tests with `composer run test` in the package's directory.
