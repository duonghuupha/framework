<?php
/**
 * Class Model
 * Lớp cha cho tất cả các model trong hệ thống.
 * Hỗ trợ cache và thao tác DB chuẩn.
 */

class Model
{
    protected static $table = '';       // Tên bảng
    protected static $primaryKey = 'id'; // Khóa chính

    protected static function getDB()
    {
        return Database::getInstance()->getConnection();
    }

    /**
     * Thực thi truy vấn SQL có tham số
     */
    protected static function execQuery($sql, $params = [])
    {
        $db = self::getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Lấy toàn bộ dữ liệu (có cache)
     */
    public static function all()
    {
        $cacheKey = 'table_all_' . static::$table;
        return Cache::remember($cacheKey, function () {
            $stmt = self::execQuery("SELECT * FROM " . static::$table);
            return $stmt->fetchAll();
        });
    }

    /**
     * Tìm 1 bản ghi theo id
     */
    public static function find($id)
    {
        $cacheKey = 'record_' . static::$table . '_' . $id;
        return Cache::remember($cacheKey, function () use ($id) {
            $sql = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
            $stmt = self::execQuery($sql, [$id]);
            return $stmt->fetch();
        });
    }

    /**
     * Tìm theo điều kiện (trả về mảng)
     */
    public static function where($column, $value)
    {
        $cacheKey = 'where_' . static::$table . '_' . $column . '_' . md5($value);
        return Cache::remember($cacheKey, function () use ($column, $value) {
            $sql = "SELECT * FROM " . static::$table . " WHERE {$column} = ?";
            $stmt = self::execQuery($sql, [$value]);
            return $stmt->fetchAll();
        });
    }

    /**
     * Thêm dữ liệu mới
     */
    public static function insert($data)
    {
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $sql = "INSERT INTO " . static::$table . " ($fields) VALUES ($placeholders)";
        $stmt = self::execQuery($sql, array_values($data));

        // Xóa cache liên quan
        Cache::forgetPrefix('table_all_' . static::$table);
        return self::getDB()->lastInsertId();
    }

    /**
     * Cập nhật bản ghi theo id
     */
    public static function update($id, $data)
    {
        $setParts = [];
        foreach ($data as $key => $val) {
            $setParts[] = "{$key} = ?";
        }
        $setStr = implode(',', $setParts);
        $sql = "UPDATE " . static::$table . " SET {$setStr} WHERE " . static::$primaryKey . " = ?";
        $params = array_values($data);
        $params[] = $id;

        self::execQuery($sql, $params);

        // Xóa cache bản ghi cũ
        Cache::forget('record_' . static::$table . '_' . $id);
        Cache::forgetPrefix('table_all_' . static::$table);
        return true;
    }

    /**
     * Xóa bản ghi theo id
     */
    public static function delete($id)
    {
        $sql = "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        self::execQuery($sql, [$id]);

        Cache::forget('record_' . static::$table . '_' . $id);
        Cache::forgetPrefix('table_all_' . static::$table);
        return true;
    }
}
