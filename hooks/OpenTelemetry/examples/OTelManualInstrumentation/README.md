# OpenFeature OpenTelemetry Hook example

This example provides an example of bootstrapping and using the OpenTelemetry hook for OpenFeature.

It showcases how you can manually configure the OTel SDK for metrics, tracing, and more. This is then utilized under the hood by OpenFeature within its hook lifecycles to report the feature flag semantic events via the tracer. 