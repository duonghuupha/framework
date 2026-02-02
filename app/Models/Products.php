<?php
class Products extends Model{
    protected static string $view_product = "v_products"; // view sản phẩm
    protected static string $table = "tbl_sanpham"; // bảng sản phẩm

    public static function listProducts(array $params = []) : array{
        return self::paginate(static::$view_product, $params);
    }

    public static function dupliObjProduct($code, $id) : array|false{
        if($id === 0){
            return self::where("code", $code);
        }else{
            $sql = "SELECT * FROM " . static::$table . " WHERE code = ? AND id != ?";
            $params = [$code, $id];
            return self::dynamicQuery($sql, $params);
        }
    }

    public static function addProduct(array $data) : int|false{
        return self::insert($data);
    }

    public static function updateProduct(int $id, array $data) : int|false{
        return self::update($id, $data);
    }

    public static function deleteProduct(int $id) : int|false{
        return self::delete($id);
    }
}
?>