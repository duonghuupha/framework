<?php
class Imports extends Model{
    protected static string $table = "tbl_imports"; //bảng nhập kho
    protected static string $table_detail = "tbl_imports_detail";

    public static function listImports(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function dupliObjImports($code, $id) : array|false{
        if($id === 0){
            return self::where("code", $code);
        }else{
            $sql = "SELECT * FROM " . static::$table . " WHERE code = ? AND id != ?";
            $params = [$code, $id];
            return self::dynamicQuery($sql, $params);
        }
    }

    public static function addImports(array $data) : int|false{
        return self::insert($data);
    }

    public static function addImportsDetail(array $data) : int|false{
        return self::insertTo(static::$table_detail, $data);
    }
}
?>