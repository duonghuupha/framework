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
            return $this->json([], 'error', "Thiếu thông tin đăng nhập", 400);
        }
        // Goi model de kiem tra
        $user = $this->userModel->getUserByUsername($username);
        if(!$user){
            return $this->json([], 'error', "Tên đăng nhập không đúng.", 400);
        }

        if(!password_verify($password, $user['password'])){
            return $this->json([], 'error', "Mật khẩu không đúng.", 400);
        }

        // Sinh JWT va luu vao cache (JWTHelper::issueAndStore tu luu)
        $payload = ['user_id' => $user['id'], 'username' => $user['username']];
        $token = JWTHelper::issueAndStore($payload); // dung TTL mac dinh
        $data =[
            'token' => $token,
            'expires_in' => JWTHelper::ttl(),
            'user_id' => $user['id'],
            'username' => $user['username'],
            'fullname' => $user['fullname'] ?? '',
            'roles' => ["ADMIN"],
            'permissions' => ["dashboard", "products.view", "products.create", "products.edit", "products.delete", "user.view"],
        ];
        return $this->json($data, 'success', 'Đăng nhập thành công', 200);
    }

    // POST /logout
    public function logout(){
        $token = $this->getBearerToken();
        //return $this->json([], 'error', $token, 400);
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
        $payload = $this->checkToken();
        // Lấy user từ model
        $user = $this->userModel->getUserById($payload['user_id']);
        if (!$user) return $this->json([], 'error', 'Không tìm thấy người dùng', 404);

        return $this->json(['user' => $user], 'success', '', 200);
    }
}