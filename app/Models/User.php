<?php
class User extends Model{
    protected static $table = 'tbl_user';
    protected static $primaryKey = 'id';
    
    public function getUserByUsername($username){
        $users = self::where('username', $username);
        return $users ? $users[0] : null;
    }
}
