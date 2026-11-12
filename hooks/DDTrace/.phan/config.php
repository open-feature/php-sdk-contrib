<?php

declare(strict_types=1);

return [
    'target_php_version' => '8.2',
    'minimum_target_php_version' => '8.2',
    'directory_list' => [
        'src',
        'vendor',
    ],
    'exclude_analysis_directory_list' => [
        'vendor/',
    ],
    // dd-trace ships both userland API classes and extension stubs declaring the same classes
    'exclude_file_list' => [
        'vendor/datadog/dd-trace/src/ddtrace_php_api.stubs.php',
    ],
];
