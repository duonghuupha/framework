<?php
class Products extends Model{
    protected static string $table = "tbl_sanpham";

    public static function listProducts(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }
}
?>