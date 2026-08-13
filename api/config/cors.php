<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET'],

    'allowed_origins' => [env('APP_URL', 'http://localhost:8000')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
