<?php

declare(strict_types=1);

return [
    'target_php_version' => '8.2',
    'minimum_target_php_version' => '8.2',
    'directory_list' => [
        'src',
        'proto/php',
        'vendor',
    ],
    'exclude_analysis_directory_list' => [
        'proto/php/',
        'vendor/',
    ],
];
