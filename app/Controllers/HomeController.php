<?php
class HomeController extends Controller {
    public function index() {
        $input = Input::all(); $password = $input['password'] ?? ''; $id = $input['id'] ?? 0;
        if ($id <= 0 || $password === '') {
            return $this->json([
                'status' => 'error',
                'message' => 'Thiếu thông tin cập nhật.'
            ]);
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $data = ['password' => $hashedPassword];
        $updated = Home::updateUser($id, $data);
        if ($updated === false) {
            return $this->json([
                'status' => 'error',
                'message' => 'Cập nhật mật khẩu thất bại.'
            ]);
        }else{
            return $this->json([
                'status' => 'success',
                'message' => 'Cập nhật mật khẩu thành công.'
            ]);
        }
    }

    public function clearCache() {
        Cache::clearAll();
        $this->json(['status' => 'success', 'message' => 'Đã xóa toàn bộ cache']);
    }
}
?>