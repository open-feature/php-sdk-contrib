# OpenFeature PHP SDK Contrib Library

[![a](https://img.shields.io/badge/slack-%40cncf%2Fopenfeature-brightgreen?style=flat&logo=slack)](https://cloud-native.slack.com/archives/C0344AANLA1)
[![codecov](https://codecov.io/gh/open-feature/php-sdk-contrib/branch/main/graph/badge.svg?token=3DC5XOEHMY)](https://codecov.io/gh/open-feature/php-sdk-contrib)
![PHP 8.0+](https://img.shields.io/badge/php->=8.0-blue.svg)
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
  - [Datadog](./hooks/DDTrace/README.md)
  - [Validators](./hooks/Validators/README.md)

### Status

This repository is marked as **experimental** since the repository structure itself could change. However, each the packages within the repository maintains its own release status.

## Development

### PHP Versioning

This library targets PHP version 8.0 and newer. As long as you have any compatible version of PHP on your system you should be able to utilize the OpenFeature SDK.

This package also has a `.tool-versions` file for use with PHP version managers like `asdf`.

### Installation and Dependencies

Install dependencies with `composer install`. `composer install` will update the `composer.lock` with the most recent compatible versions.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

### Testing

Each package implements its own test suite.

Run tests with `composer run test` in the package's directory.
