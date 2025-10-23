<?php
require_once __DIR__ . '/JwtHelper.php';

class AuthMiddleware {
    public function handle() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Thiếu token xác thực']);
            exit;
        }

        $token = substr($authHeader, 7);
        $payload = JwtHelper::validate($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Token không hợp lệ hoặc đã hết hạn']);
            exit;
        }

        // Gán thông tin user hiện tại cho request
        $_REQUEST['user_id'] = $payload['user_id'] ?? null;
    }
}
