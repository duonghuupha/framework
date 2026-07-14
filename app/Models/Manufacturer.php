<?php
class Manufacturer extends Model{
    protected static string $table = "dm_suppliers"; // bảng nhà cung cấp

    public static function listProducts(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function listCombo() : array{
        //return self::all();
        $sql = "SELECT id AS value, name AS label FROM " . static::$table;
        return self::dynamicQuery($sql);
    }
}
?>