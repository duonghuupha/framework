<?php
class Personnel extends Model{
    protected static string $table = "tbl_sanpham";

    public static function listPersonnel(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }
}