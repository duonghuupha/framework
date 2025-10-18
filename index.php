<?php
// Bật hiển thị lỗi (debug)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Nạp autoload
require_once __DIR__ . '/core/Autoload.php';

require_once __DIR__ . '/Core/Config.php';
require_once __DIR__ . '/Core/Cache.php';
require_once __DIR__ . '/Core/Database.php';

// Nạp cấu hình .env
Config::load(__DIR__ . '/.env');

// Khởi tạo cache
Cache::init('redis');
Cache::set('test_key', 'Xin chào Redis!', 30);
echo Cache::get('test_key');

// Khởi tạo App
new App();
