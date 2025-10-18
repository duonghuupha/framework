<?php
class Controller {
    public function view($view, $data = []) {
        $viewPath = __DIR__ . '/../app/Views/' . $view . '.php';
        if (file_exists($viewPath)) {
            extract($data);
            require $viewPath;
        } else {
            echo "Không tìm thấy view: $view";
        }
    }

    public function json($data, $status = 200) {
        header_remove();
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
