<?php
abstract class Model {
    protected static $table = '';
    protected static $primaryKey = 'id';
    protected $attributes = [];

    public function __construct($data = []) {
        $this->attributes = $data;
    }

    public static function getTable() {
        return static::$table ?: strtolower(static::class) . 's';
    }

    public static function all() {
        $table = static::getTable();
        $rows = Database::query("SELECT * FROM {$table}");
        return array_map(fn($r) => new static($r), $rows);
    }

    public static function find($id) {
        $table = static::getTable();
        $pk = static::$primaryKey;
        $rows = Database::query("SELECT * FROM {$table} WHERE {$pk} = ?", [$id]);
        return $rows ? new static($rows[0]) : null;
    }

    public static function create($data) {
        $table = static::getTable();
        $fields = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO {$table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";

        $pdo = Database::connect();
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return static::find($pdo->lastInsertId());
    }

    public function update($data) {
        $table = static::getTable();
        $pk = static::$primaryKey;
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $this->attributes[$key] = $value;
        }
        $sql = "UPDATE {$table} SET " . implode(',', $fields) . " WHERE {$pk} = ?";
        $params = array_values($data);
        $params[] = $this->attributes[$pk];

        $pdo = Database::connect();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete() {
        $table = static::getTable();
        $pk = static::$primaryKey;
        $sql = "DELETE FROM {$table} WHERE {$pk} = ?";
        $pdo = Database::connect();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$this->attributes[$pk]]);
    }

    public function __get($key) {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value) {
        $this->attributes[$key] = $value;
    }

    public function toArray() {
        return $this->attributes;
    }
}
