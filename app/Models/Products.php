<?php
class Products extends Model{
    protected static string $table = "v_products"; // bảng sản phẩm

    public static function listProducts(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }
}
?>