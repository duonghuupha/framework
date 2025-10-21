<?php
/**
 * Class Database
 * Kết nối PDO MySQL dùng thông tin từ file .env
 * Tương thích autoload hiện tại (không dùng namespace)
 */

class Database{
    private static $instance = null;
    private $connection;

    private function __construct(){
        // Lấy thông tin từ biến môi trường (đã load trong index.php)
        $host = Config::get('DB_HOST', '127.0.0.1');
        $dbname = Config::get('DB_NAME', 'test');
        $username = Config::get('DB_USER', 'root');
        $password = Config::get('DB_PASS', '');
        $charset = 'utf8mb4';

        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => true, // Giữ kết nối liên tục
            ];
            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            die("❌ Kết nối cơ sở dữ liệu thất bại: " . $e->getMessage());
        }
    }

    /**
     * Lấy thể hiện duy nhất của Database
     */
    public static function getInstance(){
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Lấy kết nối PDO
     */
    public function getConnection(){
        return $this->connection;
    }

    // Chặn clone và unserialize để giữ Singleton
    private function __clone() {}
    public function __wakeup() {}
}
