<?php

declare(strict_types=1);

use OpenFeature\Hooks\DDTrace\DDTraceHook;

// automatically registers the DDTraceHook for OpenFeature
DDTraceHook::register();
