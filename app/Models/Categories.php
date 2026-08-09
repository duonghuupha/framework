<?php
class Categories extends Model{
    protected static string $table = "dm_categories"; // bảng danh mục

    public static function listCategories(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function addCategory(array $data) : int|false{
        return self::insert($data);
    }

    public static function updateCategory(int $id, array $data) : int|false{
        return self::update($id, $data);
    }

    public static function deleteCategory(int $id) : int|false{
        return self::delete($id);
    }

    public static function listCombo() : array{
        //return self::all();
        $sql = "SELECT id AS value, name AS label FROM " . static::$table;
        return self::dynamicQuery($sql);
    }
}
?>