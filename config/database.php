<?php
return [
    'default' => env('DB_CONNECTION', 'pgsql'),
    'fetch' => PDO::FETCH_ASSOC,
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset' => env('DB_CHARSET')
        ],
    ]
];
