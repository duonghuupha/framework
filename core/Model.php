<?php
class Model {
    protected static $table = '';
    protected static $db = null;

    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    // Kết nối database một lần duy nhất
    protected static function db() {
        if (self::$db === null) {
            self::$db = Database::connect();
        }
        return self::$db;
    }

    // Lấy tên bảng
    protected static function getTable() {
        return static::$table ?: strtolower(static::class);
    }

    // Lấy tất cả bản ghi
    public static function all() {
        $table = static::getTable();
        $db = self::db();
        $stmt = $db->prepare("SELECT * FROM {$table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tìm theo ID
    public static function find($id) {
        $table = static::getTable();
        $db = self::db();
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Tạo mới bản ghi
    public static function create($data) {
        $table = static::getTable();
        $db = self::db();
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = ':' . implode(',:', $keys);

        $stmt = $db->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})");
        $stmt->execute($data);
        return $db->lastInsertId();
    }

    // Cập nhật bản ghi
    public static function update($id, $data) {
        $table = static::getTable();
        $db = self::db();
        $setStr = implode(',', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $data['id'] = $id;

        $stmt = $db->prepare("UPDATE {$table} SET {$setStr} WHERE id = :id");
        return $stmt->execute($data);
    }

    // Xóa bản ghi
    public static function delete($id) {
        $table = static::getTable();
        $db = self::db();
        $stmt = $db->prepare("DELETE FROM {$table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
