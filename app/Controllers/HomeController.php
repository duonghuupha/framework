<?php
class HomeController extends Controller {
    public function index() {
        $users = Cache::remember('users_all', 300, function() {
            return Home::all();
        });

        $this->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function clearCache() {
        Cache::clear();
        $this->json(['status' => 'ok', 'message' => 'Đã xóa toàn bộ cache']);
    }
}
?>