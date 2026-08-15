<?php
class Petbreed extends Model{
    protected static string $table = "pet_breeds"; // bảng danh mục

    public static function listPetbreed(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function addPetbreed(array $data) : int|false{
        return self::insert($data);
    }

    public static function updatePetbreed(int $id, array $data) : int|false{
        return self::update($id, $data);
    }

    public static function deletePetbreed(int $id) : int|false{
        return self::delete($id);
    }

    public static function listCombo() : array{
        //return self::all();
        $sql = "SELECT id AS value, name AS label FROM " . static::$table;
        return self::dynamicQuery($sql);
    }
}
?>