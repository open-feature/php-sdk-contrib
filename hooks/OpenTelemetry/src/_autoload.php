<?php

declare(strict_types=1);

use OpenFeature\Hooks\OpenTelemetry\OpenTelemetryHook;

// automatically registers the OTel hook for OpenFeature
OpenTelemetryHook::register();
