<?php
class AuthController extends Controller{
    protected $userModel;
    public function __construct(){
        $this->userModel = new User();
    }
    // Đăng ký người dùng
    public function register(){
        $input = Input::all();
        if (empty($input['username']) || empty($input['password']) || empty($input['email'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Thiếu thông tin đăng ký.'
            ]);
        }
        // Kiểm tra trùng username hoặc email
        if ($this->userModel->isExist('username', $input['username']) || $this->userModel->isExist('email', $input['email'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Tên đăng nhập hoặc email đã tồn tại.'
            ]);
        }
        $userId = $this->userModel->createUser($input);
        if ($userId) {
            return $this->json([
                'status' => 'success',
                'message' => 'Đăng ký thành công.',
                'data' => [
                    'user_id' => $userId,
                    'username' => $input['username'],
                    'email' => $input['email']
                ]
            ]);
        }
        return $this->json([
            'status' => 'error',
            'message' => 'Không thể đăng ký, vui lòng thử lại sau.'
        ]);
    }
    // Đăng nhập
    public function login(){
        $input = Input::all();
        if (empty($input['username']) || empty($input['password'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Thiếu thông tin đăng nhập.'
            ]);
        }
        // Kiểm tra cache trước
        $cacheKey = 'user_' . md5($input['username']);
        $user = Cache::get($cacheKey);
        if (!$user) {
            $user = $this->userModel->getUserByUsername($input['username']);
            if ($user) {
                // Lưu cache trong 10 phút
                Cache::set($cacheKey, $user, 600);
            }
        }
        if ($user && password_verify($input['password'], $user['password'])) {
            return $this->json([
                'status' => 'success',
                'message' => 'Đăng nhập thành công.',
                'data' => [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ]
            ]);
        }

        return $this->json([
            'status' => 'error',
            'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'
        ]);
    }
}
