<?php
class User extends Model{
    protected static string $table = 'tbl_users';
    protected static string $primaryKey = 'id';
    
    public static function getUserByUsername($username){
        $users = self::where('username', $username);
        if(!$users) return false;
        // neu where tra ve array of rows
        if(isset($users[0]) && is_array($users[0])){
            return $users[0];
        }
        // neu whewre tra 1 ban ghi dangj assoc
        return $users;
    }

    public static function getUserById($id){
        $users = self::where('id', $id);
        if(isset($users[0]) && is_array($users[0])){
            return $users[0];
        }
        return $users;
    }

    public static function storeTokenForUser($userId, $token, $ttl = 86400){
        $key = "auth_token:{$token}";
        Cache::set($key, ['user_id' => $userId, 'created_at' => time()], $ttl);
        Cache::set("user_token:{$userId}", $token, $ttl);
    }
}
