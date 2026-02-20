<?php
class Customer extends Model{
    protected static string $table = "tbl_customers"; //bảng khách hàng

    public static function listCustomer(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }
}
?>