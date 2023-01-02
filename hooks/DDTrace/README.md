# OpenFeature DDTrace Hook

[![a](https://img.shields.io/badge/slack-%40cncf%2Fopenfeature-brightgreen?style=flat&logo=slack)](https://cloud-native.slack.com/archives/C0344AANLA1)
[![Latest Stable Version](http://poser.pugx.org/open-feature/dd-trace-hook/v)](https://packagist.org/packages/open-feature/dd-trace-hook)
[![Total Downloads](http://poser.pugx.org/open-feature/dd-trace-hook/downloads)](https://packagist.org/packages/open-feature/dd-trace-hook)
![PHP 7.4+](https://img.shields.io/badge/php->=7.4-blue.svg)
[![License](http://poser.pugx.org/open-feature/dd-trace-hook/license)](https://packagist.org/packages/open-feature/dd-trace-hook)

## Overview

`dd-trace` is the Datadog tracing library for PHP. It is built on the OpenTracing specification.

This package also builds on various PSRs (PHP Standards Recommendations) such as the Logger interfaces (PSR-3) and the Basic and Extended Coding Standards (PSR-1 and PSR-12).

### Design

OpenTracing is now an archived project of the CNCF, with suggestions to move towards OpenTelemetry. Feel free to check out our [OpenTelemetry hook for OpenFeature](../OpenTelemetry/README.md) as well. OpenTelemetry defines a semantic convention for feature flagging which is utilized in this hook to report flag evaluations, which is the basis for the log events being performed in this library for `dd-trace`.

### Autoloading

This package supports Composer autoloading. Thus, simply installing the package is all you need in order to immediately get started with Datadog's DDTrace for OpenFeature! Examples are provided that showcase the simple setup as well. Check out the **Usage** section for more info.

## Installation

```sh
composer require open-feature/dd-trace-hook   // installs the latest version
```

## Usage

The `DDTraceHook` should be registered to the OpenFeatureAPI globally for use across all evaluations.

It makes use of the `dd-trace` packages `Globals` utility for current span retrieval, thus has
no dependency on configuration or injection of tracers.

```php
use OpenFeature\Hooks\DDTrace\DDTraceHook;

DDTraceHook::register();
```

For more information on DDTrace, check out [their documentation](https://docs.datadoghq.com/tracing/trace_collection/dd_libraries/php?tab=containers).

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
