<?php
class HomeController extends Controller {
    public function index() {
        $users = Home::all();
        echo "<pre>";
        print_r($users);
    }

    public function testCreate() {
        $user = Home::create([
            'code' => time(),
            'username' => 'Nguyen Van Test',
            'password' => sha1(123456),
            'email' => 'test@example.com',
            'create_at' => date("Y-m-d H:i:s")
        ]);
        echo "Đã tạo user mới: ";
        print_r($user->toArray());
    }
}
