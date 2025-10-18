<?php
return [
    // Cấu hình cơ bản
    'app' => [
        'base_url' => 'http://'.$_SERVER['HTTP_HOST'],
        'default_controller' => 'home',
        'default_method' => 'index',
        'timezone' => 'Asia/Ho_Chi_Minh',
    ],

    // Cấu hình database
    'database' => [
        'host' => 'localhost',
        'dbname' => 'edusoft',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],

    // Cấu hình cache
    'cache' => [
        'driver' => 'file', // có thể đổi sang 'redis'
        'path' => __DIR__ . '/../storage/cache/', // nơi lưu file cache
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
        ],
    ],
];
