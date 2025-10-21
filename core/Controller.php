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

    protected function json($data = [], $status = 'success', $message = '', $code = 200) {
        // Nếu $data có chứa status/message thì không cần bọc lại nữa
        if (is_array($data) && isset($data['status']) && isset($data['message'])) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        // Gọi tới lớp Response để thống nhất định dạng
        if ($status === 'error') {
            Response::error($message ?: 'Lỗi xử lý', $code, $data);
        } else {
            Response::success($message ?: 'Thành công', $data, $code);
        }
    }
}
?>