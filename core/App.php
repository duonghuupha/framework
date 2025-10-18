<?php
class App {
    protected $router;
    public static $config;

    public function __construct() {
        // Nạp config
        self::$config = require __DIR__ . '/../config/config.php';

        // Set timezone
        date_default_timezone_set(self::$config['app']['timezone']);

        // Tạo router và dispatch
        $this->router = new Router();
        $this->router->dispatch();
    }

    public static function config($key) {
        $keys = explode('.', $key);
        $value = self::$config;
        foreach ($keys as $k) {
            if (isset($value[$k])) $value = $value[$k];
            else return null;
        }
        return $value;
    }
}
