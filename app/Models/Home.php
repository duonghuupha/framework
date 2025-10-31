<?php
class Home extends Model {
    protected static string $table = 'tbl_users';
    protected static string $primaryKey = 'id';

    public static function updateUser($id, $data) {
        return self::update($id, $data);
    }
}
