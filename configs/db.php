<?php


return [
    'pool' => [
        'maxConnections' => 10,
        'maxRetries' => 10,
        'retryDelay' => 1000,
    ],

    'connections' => [
        'default' => [
            'databaseName' => 'your_db_name',
            'host' => 'localhost',
            'username' => 'root',
            'password' => ''
        ]
    ]
];
