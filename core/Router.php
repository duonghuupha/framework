<?php
class Router {
    private $routes = [];
    private $notFound;

    public function get($uri, $action, $options = []) {
        $this->addRoute('GET', $uri, $action, $options);
    }

    public function post($uri, $action, $options = []) {
        $this->addRoute('POST', $uri, $action, $options);
    }

    public function put($uri, $action, $options = []) {
        $this->addRoute('PUT', $uri, $action, $options);
    }

    public function delete($uri, $action, $options = []) {
        $this->addRoute('DELETE', $uri, $action, $options);
    }

    private function addRoute($method, $uri, $action, $options = []) {
        $uri = trim($uri, '/');
        $this->routes[$method][$uri] = [
            'action' => $action,
            'options' => $options
        ];
    }

    public function setNotFound($callback) {
        $this->notFound = $callback;
    }

    public function dispatch() {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $method = $_SERVER['REQUEST_METHOD'];

        // Xử lý override method cho PUT / DELETE qua _method trong form hoặc header
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        if (!isset($this->routes[$method])) {
            return $this->handleNotFound();
        }

        foreach ($this->routes[$method] as $route => $data) {
            if ($uri === $route) {
                return $this->runAction($data['action'], $data['options'], $uri, $method);
            }
        }

        return $this->handleNotFound();
    }

    private function runAction($action, $options, $uri, $method) {
        // Cache key: method + uri
        $cacheKey = "route_cache_{$method}_" . md5($uri);

        // --- Nếu route có cache ---
        if (isset($options['cache']) && $options['cache'] > 0) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                header('Content-Type: application/json');
                echo $cached;
                return;
            }
        }

        // --- Chạy controller/action ---
        if (is_callable($action)) {
            $response = call_user_func($action);
        } elseif (is_string($action) && strpos($action, '@') !== false) {
            list($controller, $methodName) = explode('@', $action);
            if (!class_exists($controller)) {
                echo json_encode(['error' => "Controller $controller không tồn tại"]);
                return;
            }
            $controllerInstance = new $controller;
            if (!method_exists($controllerInstance, $methodName)) {
                echo json_encode(['error' => "Phương thức $methodName không tồn tại trong $controller"]);
                return;
            }
            $response = call_user_func([$controllerInstance, $methodName]);
        } else {
            echo json_encode(['error' => "Cấu hình route không hợp lệ"]);
            return;
        }

        // --- Cache kết quả nếu có yêu cầu ---
        if (isset($options['cache']) && $options['cache'] > 0) {
            if (is_array($response) || is_object($response)) {
                $json = json_encode($response, JSON_UNESCAPED_UNICODE);
                Cache::set($cacheKey, $json, $options['cache']);
                header('Content-Type: application/json');
                echo $json;
            } else {
                Cache::set($cacheKey, $response, $options['cache']);
                echo $response;
            }
        } else {
            if (is_array($response) || is_object($response)) {
                header('Content-Type: application/json');
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                echo $response;
            }
        }
    }

    private function handleNotFound() {
        http_response_code(404);
        if ($this->notFound) {
            call_user_func($this->notFound);
        } else {
            echo json_encode(['error' => '404 Not Found']);
        }
    }
}
