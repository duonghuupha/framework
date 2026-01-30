<?php
class Products extends Model{
    protected static string $view_product = "v_products"; // view sản phẩm
    protected static string $table = "tbl_sanpham"; // bảng sản phẩm

    public static function listProducts(array $params = []) : array{
        return self::paginate(static::$view_product, $params);
    }

    public static function addProduct(array $data) : int{
        return self::insert(static::$table, $data);
    }
}
?>