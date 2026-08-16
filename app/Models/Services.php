<?php
class Services extends Model{
    protected static string $table = "services"; // bảng sản phẩm

    public static function listServices(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function dupliObjServices($code, $id) : array|false{
        if($id === 0){
            return self::where("code", $code);
        }else{
            $sql = "SELECT * FROM " . static::$table . " WHERE code = ? AND id != ?";
            $params = [$code, $id];
            return self::dynamicQuery($sql, $params);
        }
    }

    public static function addServices(array $data) : int|false{
        return self::insert($data);
    }

    public static function updateServices(int $id, array $data) : int|false{
        return self::update($id, $data);
    }

    public static function deleteServices(int $id) : int|false{
        return self::delete($id);
    }

    public static function listComboServices($name) : array|false{
        $sql = "SELECT id, code, name, type, price, duration, status, note 
                FROM " . static::$table . " WHERE (code LIKE '%$name%' OR name LIKE '%$name%') AND status = 1";
        return self::dynamicQuery($sql);
    }
}
?>