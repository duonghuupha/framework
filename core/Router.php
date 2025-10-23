<?php
// core/Router.php

require_once __DIR__ . '/Middleware.php';

class Router
{
    private $routes = [];
    private $currentMiddleware = null;

    /**
     * Gắn middleware cho route tiếp theo (ví dụ: ->middleware('AuthMiddleware'))
     */
    public function middleware($middleware)
    {
        $this->currentMiddleware = $middleware;
        return $this; // Cho phép chain tiếp, ví dụ: ->get()
    }

    /**
     * Đăng ký route GET
     */
    public function get($path, $callback)
    {
        $this->addRoute('GET', $path, $callback);
    }

    /**
     * Đăng ký route POST
     */
    public function post($path, $callback)
    {
        $this->addRoute('POST', $path, $callback);
    }

    /**
     * Hàm nội bộ thêm route vào danh sách
     */
    private function addRoute($method, $path, $callback)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'callback' => $callback,
            'middleware' => $this->currentMiddleware
        ];

        // Reset middleware sau khi dùng để tránh ảnh hưởng route khác
        $this->currentMiddleware = null;
    }

    /**
     * Hàm chuẩn hóa path (thêm / đầu và bỏ / cuối)
     */
    private function normalizePath($path)
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    /**
     * Chạy router
     */
    public function dispatch()
    {
        // Lấy URI hiện tại
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // Loại bỏ thư mục gốc nếu có (ví dụ /framework/)
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        // Chuẩn hóa URI
        $uri = $this->normalizePath($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                // Nếu có middleware thì gọi nó
                if (!empty($route['middleware'])) {
                    Middleware::handle($route['middleware']);
                }

                // Gọi callback hoặc controller
                return $this->executeCallback($route['callback']);
            }
        }

        // Nếu không có route nào khớp
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy route: ' . $uri]);
    }

    /**
     * Gọi callback hoặc controller@action
     */
    private function executeCallback($callback)
    {
        if (is_callable($callback)) {
            return call_user_func($callback);
        }

        if (is_string($callback) && strpos($callback, '@') !== false) {
            list($controllerName, $actionName) = explode('@', $callback);
            $controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

            if (!file_exists($controllerFile)) {
                throw new Exception("Không tìm thấy file controller: $controllerFile");
            }

            require_once $controllerFile;
            $controller = new $controllerName();

            if (!method_exists($controller, $actionName)) {
                throw new Exception("Không tìm thấy action '$actionName' trong controller '$controllerName'");
            }

            return call_user_func([$controller, $actionName]);
        }

        throw new Exception("Callback không hợp lệ cho route");
    }
}
