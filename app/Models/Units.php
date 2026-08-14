<?php
class Units extends Model{
    protected static string $table = "dm_units"; // bảng don vị tính

    public static function listUnits(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function addUnits(array $data) : int|false{
        return self::insert($data);
    }

    public static function updateUnits(int $id, array $data) : int|false{
        return self::update($id, $data);
    }

    public function deleteUnits(int $id) : int|false{
        return self::delete($id);
    }

///////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function listCombo() : array{
        //return self::all();
        $sql = "SELECT id AS value, name AS label FROM " . static::$table;
        return self::dynamicQuery($sql);
    }
}
?>