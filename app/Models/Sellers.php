<?php
class Sellers extends Model{
    protected static string $table = "tbl_sellers"; //bảng bán hàng
    protected static string $table_detail = "tbl_sellers_detail";

    public static function listSellers(array $params = []) : array{
        return self::paginate(static::$table, $params);
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