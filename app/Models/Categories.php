<?php
class Categories extends Model{
    protected static string $table = "dm_categories"; // bảng danh mục

    public static function listCategories(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function listCombo() : array{
        return self::all();
    }
}
?>