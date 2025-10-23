<?php
// core/AuthMiddleware.php

// không require Middleware ở đây (Middleware dispatcher sẽ require file này khi cần)
// nhưng bạn có thể require JWTHelper / Cache nếu dùng:
require_once __DIR__ . '/JwtHelper.php';
require_once __DIR__ . '/Cache.php'; // nếu Cache chưa autoload

class AuthMiddleware
{
    // phương thức static, KHÔNG nhận tham số
    public static function handle()
    {
        // Lấy header Authorization
        $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
        $auth = $headers['Authorization'] ?? ($headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? null));

        if (!$auth || stripos($auth, 'Bearer ') !== 0) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Thiếu token xác thực']);
            exit;
        }

        $token = trim(substr($auth, 7));

        // Kiểm tra JWT (dùng JWTHelper bạn tự viết)
        $payload = JWTHelper::validate($token); // hoặc decode/validate tùy tên hàm của bạn
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Token không hợp lệ hoặc đã hết hạn']);
            exit;
        }

        // Kiểm tra token có tồn tại trong Cache/Redis (nếu bạn lưu)
        if (isset($payload['user_id'])) {
            $key = 'jwt_user_' . $payload['user_id'];
            $stored = Cache::get($key);
            if (!$stored || !hash_equals($stored, $token)) {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Token đã bị thu hồi hoặc không hợp lệ']);
                exit;
            }
            // gán user id cho controller dùng
            $_SERVER['user_id'] = $payload['user_id'];
        }

        // Trả về true để biểu thị middleware OK (không bắt buộc)
        return true;
    }
}
