<?php
class AuthController extends Controller{
    public function __construct(){
        $userModel = new User();
    }
    public function register(){
        // Lấy toàn bộ dữ liệu từ form hoặc JSON
        $data = Input::all();
        $files = Input::files();

        // Gộp dữ liệu upload vào
        if (!empty($files)) {
            $data = array_merge($data, $files);
        }

        // Gọi Model xử lý
        $result = User::register($data);

        // Trả JSON phản hồi
        return $this->json($result);
    }

    public function login(){
        $data = Input::all();

        if (!$data['username'] || !$data['password']) {
            return $this->json(['error' => 'Thiếu thông tin đăng nhập']);
        }

        $user = User::findByUsername($data['username']);
        if (!$user || !password_verify(sha1($data['username']), $user['password'])) {
            return $this->json(['error' => 'Sai tên đăng nhập hoặc mật khẩu']);
        }

        return $this->json(['message' => 'Đăng nhập thành công', 'user' => $user]);
    }
}
