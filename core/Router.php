<?php
class Router {
    private $routes = [];
    private $currentMiddleware = null;

    /**
     * Gán middleware cho route kế tiếp
     */
    public function middleware($middleware) {
        $this->currentMiddleware = $middleware;
        return $this;
    }

    /**
     * Đăng ký GET route
     */
    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }

    /**
     * Đăng ký POST route
     */
    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }

    /**
     * Thêm route vào danh sách
     */
    private function addRoute($method, $path, $callback) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback,
            'middleware' => $this->currentMiddleware
        ];

        // reset middleware để không áp dụng cho route tiếp theo
        $this->currentMiddleware = null;
    }

    /**
     * Dispatch router
     */
    public function dispatch() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/'); // loại bỏ dấu / cuối
        if ($uri === '') $uri = '/';

        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                // ✅ Xử lý middleware
                if (!empty($route['middleware'])) {
                    if (!class_exists($route['middleware'])) {
                        throw new Exception("Middleware {$route['middleware']} không tồn tại.");
                    }

                    $middlewareClass = $route['middleware'];
                    $middleware = new $middlewareClass();
                    $middleware->handle();
                }

                // ✅ Gọi Controller hoặc Callback
                if (is_callable($route['callback'])) {
                    return call_user_func($route['callback']);
                }

                if (is_string($route['callback'])) {
                    list($controllerName, $actionName) = explode('@', $route['callback']);
                    $controllerFile = BASE_PATH . '/app/Controllers/' . $controllerName . '.php';

                    if (!file_exists($controllerFile)) {
                        throw new Exception("Không tìm thấy controller: $controllerFile");
                    }

                    require_once $controllerFile;
                    $controller = new $controllerName();

                    if (!method_exists($controller, $actionName)) {
                        throw new Exception("Không tồn tại action '$actionName' trong controller '$controllerName'");
                    }

                    return call_user_func([$controller, $actionName]);
                }
            }
        }

        // ❌ Không tìm thấy route
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => "Không tìm thấy route tương ứng với $method $uri"
        ]);
    }
}
