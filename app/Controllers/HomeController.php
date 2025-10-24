<?php
class HomeController extends Controller {
    public function index() {
        $token = $this->getBearerToken();
        if (!$token) return $this->json([], 'error', 'Thiếu token', 401);

        $payload = JWTHelper::validate($token);
        if (!$payload) return $this->json([], 'error', 'Token không hợp lệ hoặc đã hết hạn', 401);
        
        $users = Home::all();
        return $this->json([
            'status' => 'success',
            'message' => 'Load dữ liệu thành công',
            'data' => [$users]
        ]);
    }

    public function clearCache() {
        Cache::clear();
        $this->json(['status' => 'ok', 'message' => 'Đã xóa toàn bộ cache']);
    }
}
?>