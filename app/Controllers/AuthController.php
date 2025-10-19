<?php
class AuthController extends Controller {
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $user = Database::query("SELECT * FROM tbl_user WHERE username = ? AND password = ?", [$username, sha1($password)]);

        if (!$user) {
            return $this->json([
                'status' => "error",
                'message' => "Sai tài khoản hoặc mật khẩu"
            ]);
        }

        $user = $user[0];
        $token = JwtHelper::createToken(['user_id' => $user['id'], 'username' => $user['username']]);

        // Lưu token vào Redis
        $cache = new Cache();
        $cache->set("jwt_token_" . $user['id'], $token, 3600);

        return $this->json(['token' => $token, 'user' => $user]);
    }

    public function info() {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? null;

        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Thiếu token']);
            return;
        }

        $data = JwtHelper::verifyToken($token);
        if (!$data) {
            http_response_code(403);
            //echo json_encode(['error' => 'Token không hợp lệ hoặc đã hết hạn']);
            //return;
            return $this->json([
                'status' => "error",
                'message' => "Token không hợp lệ  hoặc hết hạn"
            ]);
        }
        //echo json_encode(['user' => $data]);
        return $this->json([
                'status' => "success",
                'user' => $data
            ]);
    }
}
