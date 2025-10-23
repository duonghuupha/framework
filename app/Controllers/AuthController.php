<?php
class AuthController extends Controller{
    protected $userModel; // khai báo su dung Model
    protected $tokenTTL = 86400; // khai bao thoi gian hieu luc cua token
    public function __construct(){
        $this->userModel = new User();
    }

    public function info(){
        $userId = $this->checkTokenReturnUserId();
        if (!$userId) {
            return $this->json([
                'status' => 'error',
                'message' => 'Token không hợp lệ hoặc đã hết hạn.'
            ]);
        }

        // Gọi model để lấy thông tin người dùng
        $user = $this->userModel->getUserById($userId);
        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Không tìm thấy người dùng.'
            ]);
        }

        return $this->json([
            'status' => 'success',
            'message' => 'Lấy thông tin người dùng thành công',
            'data' => $user
        ]);
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
        try{
            $token = bin2hex(random_bytes(32)); // 64 hex chars
        }catch(Exception $e){
            $token = sha1($user['id'].microtime(true).rand());
        }

        // luu token vao redis (key: auth_token:<token> => user_id) voi TTL
        $cacheKey = "auth_token:{$token}";
        Cache::set($cacheKey, ["user_id" => $user['id'], "create_at" => time()], $this->tokenTTL);

        // tuy chon luu nguoc: luu token hien tai cho user de de invalid sau nay
        $userTokenKey = "user_token:{$user['id']}";
        Cache::set($userTokenKey, $token, $this->tokenTTL);

        // tra token cho frontend
        $data = [
            "token" => $token,
            "expires_in" => $this->tokenTTL,
            "user" => [
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

    // POST /logout  (yêu cầu header Authorization: Bearer <token>)
    public function logout(){
        $token = $this->getBearerToken();
        if (!$token) {
            return $this->json([], 'error', 'Thiếu token', 400);
        }

        $cacheKey = "auth_token:{$token}";
        $info = Cache::get($cacheKey);
        if ($info && isset($info['user_id'])) {
            // xóa key token và user->token
            Cache::delete($cacheKey);
            Cache::delete("user_token:{$info['user_id']}");
        } else {
            // vẫn trả success (idempotent)
        }

        return $this->json([], 'success', 'Đã đăng xuất', 200);
    }

    // Hàm helper lấy token từ header Authorization: Bearer <token>
    protected function getBearerToken(){
        // getallheaders() có thể không tồn tại trên một số môi trường CLI, fallback:
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

    // Kiểm tra token và trả user id (dùng trong controller khác nếu cần)
    public static function checkTokenReturnUserId(){
        // Vì là static, dùng getallheaders tương tự
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $auth = $headers['Authorization'] ?? ($headers['authorization'] ?? null);
        } else {
            $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null);
        }
        if (!$auth || stripos($auth, 'Bearer ') !== 0) return null;
        $token = trim(substr($auth, 7));
        $info = Cache::get("auth_token:{$token}");
        if (!$info) return null;
        // optional: refresh TTL if muốn "sliding session"
        return $info['user_id'] ?? null;
    }
}
