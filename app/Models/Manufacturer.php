<?php
class Manufacturer extends Model{
    protected static string $table = "tbldm_nhacungcap"; // bảng nhà cung cấp

    public static function listProducts(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function listCombo() : array{
        return self::all();
    }
}
?>