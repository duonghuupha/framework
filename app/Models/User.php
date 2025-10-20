<?php
class User extends Model {
    protected static $table = 'tbl_user'; // hoặc tbl_user nếu đúng tên bảng

    public static function createUser($username, $password) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        return parent::create([
            'username' => $username,
            'password' => $hashed,
            'code' => time(),
            'email' => 'abcd@gmail.com',
            'token' => '',
            'create_at' => date("Y-m-d H:i:s")
        ]);
    }

    public static function findByUsername($username) {
        $table = static::getTable();
        $db = parent::db();
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
