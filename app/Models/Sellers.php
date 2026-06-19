<?php
class Sellers extends Model{
    protected static string $v_table = "v_sellers"; // view danh sach hoa don
    protected static string $table = "sellers"; //bảng bán hàng
    protected static string $table_detail = "seller_items";

    public static function listSellers(array $params = []) : array{
        return self::paginate(static::$v_table, $params);
    }

    public static function dupliObjSellers($code, $id) : array|false{
        if($id === 0){
            return self::where("code", $code);
        }else{
            $sql = "SELECT * FROM " . static::$table . " WHERE code = ? AND id != ?";
            $params = [$code, $id];
            return self::dynamicQuery($sql, $params);
        }
    }

    public static function addSellers(array $data) : int|false{
        return self::insert($data);
    }

    public static function addSellersDetail(array $data) : int|false{
        return self::insertTo(static::$table_detail, $data);
    }
}
?>