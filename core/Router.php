<?php
class Router {
    public function dispatch() {
        $url = $_GET['url'] ?? '';
        $url = trim($url, '/');
        $parts = explode('/', $url);

        if (empty($parts[0])) {
            $parts[0] = 'home'; // Controller mặc định
        }
        if (empty($parts[1])) {
            $parts[1] = 'index'; // Method mặc định
        }

        $controllerName = ucfirst($parts[0] ?? 'home') . 'Controller';
        $methodName = $parts[1] ?? 'index';

        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            if (method_exists($controller, $methodName)) {
                $controller->$methodName();
            } else {
                echo "Không tìm thấy phương thức <b>$methodName</b>";
            }
        } else {
            echo "Không tìm thấy controller <b>$controllerName</b>";
        }
    }
}
