<?php
class Customer extends Model{
    protected static string $table = "customers"; //bảng khách hàng
    //protected static string $table_debt = "tbl_thu"; // bảng thu
    //protected static string $table_sellers = "tbl_sellers"; // bảng bán hàng

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

    public static function listComboCustomer($name) : array|false{
        $sql = "SELECT id, code, name, address, phone, is_default FROM " . static::$table . " WHERE name LIKE ? OR phone LIKE ?";
        $params = ["%$name%", "%$name%"];
        return self::dynamicQuery($sql, $params);
    }

    public static function getDebtCustomer(int $customerId): float{
        $customer = self::find($customerId);
        if (empty($customer)) {
            return 0;
        }
        return (float)($customer['debt_total'] ?? 0);
    }
}
?>