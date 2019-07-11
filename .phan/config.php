<?php
declare(strict_types=1);

return [
    'target_php_version' => '7.3',
    'directory_list' => [
        'src',
        'vendor'
    ],
    'exclude_file_regex' => '@^vendor/.*/(tests?|Tests?)/@',
    'exclude_analysis_directory_list' => [
        'vendor/'
    ]
];