<?php
class Customer extends Model{
    protected static string $table = "tbl_customers"; //bảng khách hàng

    public static function listCustomer(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function dupliObjCustomer($code, $id) : array|false{
        if($id === 0){
            return self::where("code", $code);
        }else{
            $sql = "SELECT * FROM " . static::$table . " WHERE code = ? AND id != ?";
            $params = [$code, $id];
            return self::dynamicQuery($sql, $params);
        }
    }

    public static function addCustomer(array $data) : int|false{
        return self::insert($data);
    }

    public static function updateCustomer(int $id, array $data) : int|false{
        return self::update($id, $data);
    }

    public static function deleteCustomer(int $id) : int|false{
        return self::delete($id);
    }
}
?>