<?php

class Router {
    private $routes = [];
    private $cacheKey = 'router_cache';
    private $cacheTime = 3600; // 1 giờ

    public function __construct() {
        $routesFile = BASE_PATH . '/routes/web.php';
        $lastModified = file_exists($routesFile) ? filemtime($routesFile) : 0;

        // Lấy cache
        $cachedData = Cache::get($this->cacheKey);
        $cachedTime = Cache::get($this->cacheKey . '_time');

        // Kiểm tra hợp lệ
        if (is_array($cachedData) && $cachedTime && $cachedTime == $lastModified) {
            $this->routes = $cachedData;
        } else {
            // Xóa cache cũ để tránh dữ liệu lỗi
            Cache::delete($this->cacheKey);
            Cache::delete($this->cacheKey . '_time');
        }
    }

    public function get($path, $callback) {
        $this->routes['GET'][$this->normalize($path)] = $callback;
    }

    public function post($path, $callback) {
        $this->routes['POST'][$this->normalize($path)] = $callback;
    }

    public function put($path, $callback) {
        $this->routes['PUT'][$this->normalize($path)] = $callback;
    }

    public function delete($path, $callback) {
        $this->routes['DELETE'][$this->normalize($path)] = $callback;
    }

    public function setNotFound($callback) {
        $this->notFound = $callback;
    }

    private function normalize($path) {
        return '/' . trim($path, '/');
    }

    public function dispatch() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->normalize(str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $uri));

        if (!isset($this->routes[$method])) {
            echo json_encode(['error' => 'Cấu hình route không hợp lệ (method)']);
            return;
        }

        foreach ($this->routes[$method] as $route => $callback) {
            if ($route === $path) {
                if (is_callable($callback)) {
                    call_user_func($callback);
                    return;
                } elseif (is_string($callback)) {
                    $this->callController($callback);
                    return;
                }
            }
        }

        // Nếu không có route khớp
        echo json_encode(['error' => 'Không tìm thấy route tương ứng']);
    }

    private function callController($callback) {
        list($controllerName, $methodName) = explode('@', $callback);
        $controllerFile = BASE_PATH . '/app/controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            echo json_encode(['error' => "Không tìm thấy controller: $controllerName"]);
            return;
        }

        require_once $controllerFile;
        if (!class_exists($controllerName)) {
            echo json_encode(['error' => "Không tồn tại lớp: $controllerName"]);
            return;
        }

        $controller = new $controllerName();
        if (!method_exists($controller, $methodName)) {
            echo json_encode(['error' => "Không tồn tại phương thức: $methodName"]);
            return;
        }

        call_user_func([$controller, $methodName]);
    }

    public function saveCache() {
        $routesFile = BASE_PATH . '/routes/web.php';
        $lastModified = file_exists($routesFile) ? filemtime($routesFile) : 0;

        Cache::set($this->cacheKey, $this->routes, $this->cacheTime);
        Cache::set($this->cacheKey . '_time', $lastModified, $this->cacheTime);
    }

    public function clearCache() {
        Cache::delete($this->cacheKey);
        Cache::delete($this->cacheKey . '_time');
    }
}
