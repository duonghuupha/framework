<?php
class Units extends Model{
    protected static string $table = "tbldm_donvitinh"; // bảng don vị tính

    public static function listProducts(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function listCombo($)
}
?>