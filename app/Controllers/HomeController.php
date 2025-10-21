<?php
class HomeController extends Controller {
    public function index() {
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