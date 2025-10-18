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
}
