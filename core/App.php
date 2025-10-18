<?php
class App {
    protected $router;

    public function __construct() {
        require_once __DIR__ . '/Router.php';
        $this->router = new Router();

        $this->loadRoutes();

        $this->router->dispatch();
    }

    private function loadRoutes() {
        $routeFile = dirname(__DIR__) . '/routes/web.php';
        $cacheKey = 'framework_routes_cache';

        // Kiểm tra file route có tồn tại không
        if (!file_exists($routeFile)) {
            echo "Không tìm thấy file route: $routeFile";
            return;
        }

        // Kiểm tra cache
        $cache = Cache::get($cacheKey);
        $fileHash = md5_file($routeFile);

        if ($cache && isset($cache['hash']) && $cache['hash'] === $fileHash) {
            // Nạp route từ cache
            $routes = $cache['routes'];
            foreach ($routes as $method => $items) {
                foreach ($items as $pattern => $callback) {
                    $this->router->addRoute($method, $pattern, $callback);
                }
            }
            // echo "(Đã nạp route từ cache Redis)";
        } else {
            // Nạp route từ file
            $router = $this->router;
            require $routeFile;

            // Lưu lại route vào cache
            $routes = $this->router->getRoutes();
            Cache::set($cacheKey, [
                'hash' => $fileHash,
                'routes' => $routes
            ]);
            // echo "(Đã nạp route từ file & lưu vào cache)";
        }
    }
}
