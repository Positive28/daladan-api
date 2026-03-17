<?php

$allowedOrigins = array_filter(array_map(
    'trim',
    explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:3000,https://daladan.uz,https://www.daladan.uz'))
));

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'api/documentation',
        'docs',
        'docs/*',
    ],

    'allowed_methods' => ['*'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
