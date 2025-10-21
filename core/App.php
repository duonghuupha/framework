<?php
class App {
    protected $router;
    public function __construct() {
        // Khởi tạo hệ thống cache (file hoặc redis)
        Cache::init('file'); // hoặc 'redis' nếu bạn dùng Redis

        // 🔹 Khởi tạo router (Router tự nạp routes hoặc từ cache)
        $this->router = new Router();

        // 🔹 Thực thi điều hướng
        $this->router->dispatch();
    }

    /**
     * Tải router từ cache nếu hợp lệ
     */
    private function loadRouterFromCache() {
        $cacheKey = 'router_cache';
        $cacheTimeKey = 'router_cache_time';
        $routesFile = BASE_PATH . '/routes/web.php';
        $lastModified = file_exists($routesFile) ? filemtime($routesFile) : 0;

        $cachedRoutes = Cache::get($cacheKey);
        $cachedTime = Cache::get($cacheTimeKey);

        $router = new Router();

        if (is_array($cachedRoutes) && $cachedTime == $lastModified) {
            // Nạp routes từ cache
            $ref = new ReflectionClass($router);
            $prop = $ref->getProperty('routes');
            $prop->setAccessible(true);
            $prop->setValue($router, $cachedRoutes);
        } else {
            // Cache không hợp lệ → xóa
            Cache::delete($cacheKey);
            Cache::delete($cacheTimeKey);
        }

        return $router;
    }

    /**
     * Nạp file routes/web.php
     */
    private function loadRoutes() {
        $routeFile = BASE_PATH . '/routes/web.php';
        if (!file_exists($routeFile)) {
            echo json_encode(['error' => "Không tìm thấy file route: $routeFile"]);
            exit;
        }

        // Nạp routes (file web.php sẽ dùng biến $router)
        $router = $this->router;
        require $routeFile;

        // Lưu lại cache mới
        $router->saveCache();
    }
}
