# OpenFeature OpenTelemetry Hook

[![a](https://img.shields.io/badge/slack-%40cncf%2Fopenfeature-brightgreen?style=flat&logo=slack)](https://cloud-native.slack.com/archives/C0344AANLA1)
[![Latest Stable Version](http://poser.pugx.org/open-feature/otel-hook/v)](https://packagist.org/packages/open-feature/otel-hook)
[![Total Downloads](http://poser.pugx.org/open-feature/otel-hook/downloads)](https://packagist.org/packages/open-feature/otel-hook)
![PHP 7.4+](https://img.shields.io/badge/php->=7.4-blue.svg)
[![License](http://poser.pugx.org/open-feature/otel-hook/license)](https://packagist.org/packages/open-feature/otel-hook)

## Overview

OpenTelemetry is an open specification for distributed tracing, metrics, and logging. It defines a semantic convention for feature flagging which is utilized in this hook to report flag evaluations.

This package also builds on various PSRs (PHP Standards Recommendations) such as the Logger interfaces (PSR-3) and the Basic and Extended Coding Standards (PSR-1 and PSR-12).

### Autoloading

This package supports Composer autoloading. Thus, simply installing the package is all you need in order to immediately get started with OpenTracing for OpenFeature! Examples are provided that showcase the simple setup as well. Check out the Usage section for more info.

### OpenTelemetry Package Status

The OpenTelemetry package for PHP is still in beta, so there could be changes required. However, it exposes global primitives for span retrieval that should not require any configuration upfront for the provider to just work.

## Installation

```sh
composer require open-feature/otel-hook   // installs the latest version
```

## Usage

The `OpenTelemetryHook` should be registered to the OpenFeatureAPI globally for use across all evaluations.

It makes use of the `open-telemetry/api` packages `Globals` utility for current span retrieval, thus has
no dependency on configuration or injection of tracers.

```php
use OpenFeature\Hooks\OpenTelemetry\OpenTelemetryHook;

OpenTelemetryHook::register();
```

For more information on OpenTelemetry, check out [their documentation](https://opentelemetry.io/docs/instrumentation/php/).

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
