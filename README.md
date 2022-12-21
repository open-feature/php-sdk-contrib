# OpenFeature PHP SDK Contrib Library

[![a](https://img.shields.io/badge/slack-%40cncf%2Fopenfeature-brightgreen?style=flat&logo=slack)](https://cloud-native.slack.com/archives/C0344AANLA1)
![PHP 7.4+](https://img.shields.io/badge/php->=7.4-blue.svg)
![License](https://img.shields.io/github/license/open-feature/php-sdk-contrib)
![Experimental](https://img.shields.io/badge/Status-Experimental-yellow)

## Overview

The `php-contrib-sdk` repository is a monorepository containing various providers and hooks for OpenFeature's PHP SDK. Packages include:

- Providers
  - [Flagd](./providers/Flagd/README.md)
  - [Split](./providers/Split/README.md)
  - [CloudBees](./providers/CloudBees/README.md)
- Hooks
  - [OpenTelemetry](./hooks/OpenTelemetry/README.md)

### Status

This repository is marked as **experimental** since the repository structure itself could change. However, each the packages within the repository maintains its own release status.

## Development

### PHP Versioning

This library targets PHP version 7.4 and newer. As long as you have any compatible version of PHP on your system you should be able to utilize the OpenFeature SDK.

⚠️ **PHP 7.4 is EOL and support will be discontinued in these libraries soon.**

This package also has a `.tool-versions` file for use with PHP version managers like `asdf`.

### Installation and Dependencies

Install dependencies with `composer install`. `composer install` will update the `composer.lock` with the most recent compatible versions.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

### Testing

Each package implements its own test suite.

Run tests with `composer run test` in the package's directory.
