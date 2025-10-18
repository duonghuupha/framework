<?php
class Config {
    protected static $data = [];

    // Đọc file .env
    public static function load($path) {
        if (!file_exists($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($key, $value) = array_map('trim', explode('=', $line, 2));
            self::$data[$key] = $value;
        }
    }

    // Lấy giá trị config
    public static function get($key, $default = null) {
        return self::$data[$key] ?? $default;
    }
}
