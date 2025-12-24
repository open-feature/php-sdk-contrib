# OpenFeature Flagsmith Provider for PHP

[![a](https://img.shields.io/badge/slack-%40cncf%2Fopenfeature-brightgreen?style=flat&logo=slack)](https://cloud-native.slack.com/archives/C0344AANLA1)
[![Latest Stable Version](http://poser.pugx.org/open-feature/flagsmith-provider/v)](https://packagist.org/packages/open-feature/flagsmith-provider)
[![Total Downloads](http://poser.pugx.org/open-feature/flagsmith-provider/downloads)](https://packagist.org/packages/open-feature/flagsmith-provider)
![PHP 8.0+](https://img.shields.io/badge/php->=8.0-blue.svg)
[![License](http://poser.pugx.org/open-feature/flagsmith-provider/license)](https://packagist.org/packages/open-feature/flagsmith-provider)

## Overview

Flagsmith provides an all-in-one platform for developing, implementing, and managing your feature flags. This repository and package provides the client side code for interacting with it via the OpenFeature PHP SDK.

This package also builds on various PSRs (PHP Standards Recommendations) such as the Logger interfaces (PSR-3) and the Basic and Extended Coding Standards (PSR-1 and PSR-12).

## Installation

```sh
composer require open-feature/flagsmith-provider
```

## Usage

The `FlagsmithProvider` constructor takes a configured Flagsmith client as its only argument:

```php
$flagsmith = new Flagsmith\Flagsmith('YOUR_FLAGSMITH_API_KEY');

OpenFeatureAPI::setProvider(new FlagsmithProvider($flagsmith));
```

## Development

### PHP Versioning

This library targets PHP 8.1 and above. As long as you have a compatible version of PHP on your system, you should be able to utilize the OpenFeature SDK.

This package also has a `.tool-versions` file for use with PHP version managers like `asdf`.

### Installation and Dependencies

Install dependencies with `composer install`.

We value having as few runtime dependencies as possible. The addition of any dependencies requires careful consideration and review.

### Testing

Run tests with `composer run test`.
