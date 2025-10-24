<?php
// core/Router.php

class Router
{
    private $routes = [];
    private $currentMiddleware = null;

    public function middleware($middleware)
    {
        $this->currentMiddleware = $middleware;
        return $this; // cho phép chain như ->get() hoặc ->post()
    }

    public function get($path, $callback)
    {
        $this->addRoute('GET', $path, $callback);
    }

    public function post($path, $callback)
    {
        $this->addRoute('POST', $path, $callback);
    }

    private function addRoute($method, $path, $callback)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback,
            'middleware' => $this->currentMiddleware
        ];

        // reset middleware cho route sau
        $this->currentMiddleware = null;
    }

    public function dispatch()
{

    header('Content-Type: application/json');

    /*$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];

    // ⚠️ Debug tạm thời để xem router nhận được gì
    echo json_encode([
        'debug_uri' => $uri,
        'debug_method' => $method,
        'script_name' => $_SERVER['SCRIPT_NAME'],
        'base_path' => dirname($_SERVER['SCRIPT_NAME'])
    ]);
    exit;*/

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if ($scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
        $uri = substr($uri, strlen($scriptDir));
    }
    $uri = '/' . ltrim($uri, '/');
    $method = $_SERVER['REQUEST_METHOD'];

    // Lấy thư mục gốc ứng dụng (ví dụ: /framework)
    $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
        // Loại bỏ /framework khỏi URI
        $uri = substr($uri, strlen($basePath));
    }

    // Chuẩn hóa lại URI
    $uri = rtrim($uri, '/') ?: '/';

    foreach ($this->routes as $route) {
        $normalizedRoute = rtrim($route['path'], '/');
        $normalizedUri   = rtrim($uri, '/');

        if ($route['method'] === $method && $normalizedRoute === $normalizedUri) {

            // Middleware
            if (!empty($route['middleware'])) {
                $middlewareClass = $route['middleware'];
                $middlewareFile = __DIR__ . '/' . $middlewareClass . '.php';

                if (file_exists($middlewareFile)) {
                    require_once $middlewareFile;
                    $middleware = new $middlewareClass();
                    if (method_exists($middleware, 'handle')) {
                        $middleware->handle();
                    }
                }
            }

            // Gọi controller hoặc callback
            if (is_callable($route['callback'])) {
                return call_user_func($route['callback']);
            }

            if (is_string($route['callback'])) {
                list($controllerName, $actionName) = explode('@', $route['callback']);
                $controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

                if (!file_exists($controllerFile)) {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => "Không tìm thấy controller: $controllerFile"]);
                    return;
                }

                require_once $controllerFile;
                $controller = new $controllerName();

                if (!method_exists($controller, $actionName)) {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => "Không tìm thấy action '$actionName' trong '$controllerName'"]);
                    return;
                }

                return call_user_func([$controller, $actionName]);
            }
        }
    }

    // Nếu không có route khớp
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => "Không tìm thấy route tương ứng với $method $uri"]);
}

}
