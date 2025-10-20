<?php
class AuthController extends Controller{
    public function __construct(){
        $userModel = new User();
    }
    public function register(){
        // Lấy toàn bộ dữ liệu từ form hoặc JSON
        $data = Input::all();
        if(!$data['username'] || !$data['password']) {
            return $this->json(['error' => 'Thiếu thông tin đăng nhập']);
        }
        $user = User::findByUsername($data['username']);
        if(!$user){
            $result = User::createUser($data['username'], $data['password']);
            return $this->json(['msg' => User::find($data['username'])]);
        }else{
            return $this->json(['error' => 'Thêm mới không thành công']);
        }
    }

    public function login(){
        $data = Input::all();
        if(!$data['username'] || !$data['password']) {
            return $this->json(['error' => 'Thiếu thông tin đăng nhập']);
        }
        $user = User::findByUsername($data['username']);
        if (!$user || !password_verify($data['password'], $user['password'])) {
            return $this->json(['error' => 'Sai tên đăng nhập hoặc mật khẩu']);
        }

        return $this->json(['message' => 'Đăng nhập thành công', 'user' => $user]);
        //return $this->json(['msg' => password_verify($data['password'], $user['password'])]);
    }
}
