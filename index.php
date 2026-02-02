<?php
// ✅ Bật CORS toàn cục
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Bật hiển thị lỗi (debug)
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Định nghĩa đường dẫn gốc của project
define('BASE_PATH', __DIR__);

// Nạp autoload
require_once __DIR__ . '/core/Autoload.php';

require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Cache.php';
require_once __DIR__ . '/core/Database.php';

// Nạp cấu hình .env
Config::load(__DIR__ . '/.env');

// Khởi tạo cache Cache::init('redis');
// gọi init tự động (hoặc bạn có thể gọi Cache::init() ở index.php)
Cache::init();
Cache::delete('router_cache');
Cache::delete('router_cache_time');
// Khởi tạo App
new App();
