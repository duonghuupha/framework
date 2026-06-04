<?php
class Imports extends Model{
    protected static string $table = "imports"; //bảng nhập kho
    protected static string $table_detail = "import_items"; //bảng nhập khi chi tiết
    protected static string $view_import = "v_imports"; // view hiển thị danh sách nhập kho

    public static function listImports(array $params = []) : array{
        //return self::paginate(static::$view_import, $params);
        $product = $params['search']['product'] ?? '';

        unset($params['search']['product']);

        if(!empty($product)){

            $params['exists'][] = [

                'sql' => "
                    SELECT 1
                    FROM import_items d
                    INNER JOIN products p
                        ON p.id = d.product_id
                    WHERE d.import_id = v_imports.id
                    AND p.name LIKE ?
                ",

                'params' => [
                    "%{$product}%"
                ]
            ];
        }

        return self::paginateAdv(
            static::$view_import,
            $params
        );
    }

    public static function dupliObjImports($code, $id) : array|false{
        if($id = 0){
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