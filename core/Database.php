<?php
class Database {
    protected static $pdo;

    public static function connect() {
        if (self::$pdo) return self::$pdo;

        $host = Config::get('DB_HOST');
        $port = Config::get('DB_PORT');
        $dbname = Config::get('DB_NAME');
        $user = Config::get('DB_USER');
        $pass = Config::get('DB_PASS');

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Kết nối database lỗi: " . $e->getMessage());
        }

        return self::$pdo;
    }

    // Thực thi query có cache
    public static function query($sql, $params = [], $ttl = 60) {
        $cacheKey = 'db:' . md5($sql . serialize($params));
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;

        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();

        Cache::set($cacheKey, $result, $ttl);
        return $result;
    }
}
