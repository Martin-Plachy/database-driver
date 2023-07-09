<?php

define('DEV_MODE','Development Mode');
define('PROD_MODE','Development Mode');

return [
    DEV_MODE => [
        'DB_DSN' => 'mysql:host=',
        'DB_SERVERNAME' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
        'DB_INIT_OPTIONS' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]   
    ]
];
