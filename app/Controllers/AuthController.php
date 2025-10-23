<?php
class AuthController extends Controller{
    protected $userModel; // khai báo su dung Model
    protected $tokenTTL = 86400; // khai bao thoi gian hieu luc cua token
    public function __construct(){
        $this->userModel = new User();
    }
    
    // Đăng nhập
    public function login(){
        $input = Input::all(); $username = trim($input['username'] ?? ''); $password = $input['password'] ?? '';
        if ($username === '' || $password === '') {
            return $this->json([
                'status' => 'error',
                'message' => 'Thiếu thông tin đăng nhập.'
            ]);
        }
        // Goi model de kiem tra
        $user = $this->userModel->getUserByUsername($username);
        if(!$user && !password_verify($password, $user['password'])){
            return $this->json([
                'status' => 'error',
                'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'
            ]);
        }

        // Sinh JWT va luu vao cache (JWTHelper::issueAndStore tu luu)
        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username']
        ];
        $token = JWTHelper::issueAndStore($payload); // dung TTL mac dinh
        $data =[
            'token' => $token,
            'expires_in' => JWTHelper::ttl(),
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ];
        return $this->json([
            'status' => "success",
            'message' => "Đăng nhập thành công",
            'data' => $data
        ]);
    }

    // POST /logout
    public function logout(){
        $token = $this->getBearerToken();
        if (!$token) {
            return $this->json([], 'error', 'Thiếu token', 400);
        }

        $payload = JWTHelper::decode($token);
        if ($payload && isset($payload['user_id'])) {
            JWTHelper::revokeByUserId($payload['user_id']);
            return $this->json([], 'success', 'Đã đăng xuất', 200);
        }

        // nếu decode fail, vẫn xóa key md5 nếu có
        JWTHelper::revokeToken($token);
        return $this->json([], 'success', 'Đã đăng xuất', 200);
    }

    // GET /info
    public function info(){
        $token = $this->getBearerToken();
        if (!$token) return $this->json([], 'error', 'Thiếu token', 401);

        $payload = JWTHelper::validate($token);
        if (!$payload) return $this->json([], 'error', 'Token không hợp lệ hoặc đã hết hạn', 401);

        // Lấy user từ model
        $user = $this->userModel->getUserById($payload['user_id']);
        if (!$user) return $this->json([], 'error', 'Không tìm thấy người dùng', 404);

        return $this->json(['user' => $user], 'success', '', 200);
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
