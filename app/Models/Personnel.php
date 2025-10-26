<?php
class Personnel extends Model{
    protected static string $table = "tbl_personnel";

    public static function listPersonnel(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }
}