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

    protected function checkToken(){
        $token = $this->getBearerToken();
        if (!$token) {
            $this->json([], 'error', 'Thiếu token', 401);
        }

        $payload = JWTHelper::validate($token);
        if (!$payload) {
            $this->json([], 'error', 'Token không hợp lệ hoặc đã hết hạn', 401);
        }

        return $payload;
    }

    // helper lấy Bearer token từ header
    protected function getBearerToken(){
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $auth = $headers['Authorization'] ?? ($headers['authorization'] ?? null);
        } else {
            $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null);
        }

        if (!$auth) return null;
        if (stripos($auth, 'Bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        return null;
    }
}
?>