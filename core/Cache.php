<?php
class Cache {
    protected static $driver = 'file';   // redis | file
    protected static $redis = null;
    protected static $path = __DIR__ . '/../storage/cache/';
    protected static $useJson = true; // true => dùng json_encode/decode, false => serialize/unserialize

    /**
     * Khởi tạo cache driver
     * @param string $driver 'redis' hoặc 'file'
     */
    public static function init($driver = 'file') {
        self::$driver = $driver;

        // Kiểm tra quyền ghi cho cache folder
        if (!file_exists(self::$path)) {
            mkdir(self::$path, 0777, true);
        }
        if (!is_writable(self::$path)) {
            throw new Exception("Cache path " . self::$path . " is not writable.");
        }

        // Nếu chọn Redis
        if ($driver === 'redis') {
            try {
                $redis = new Redis();

                $host = getenv('REDIS_HOST') ?: '127.0.0.1';
                $port = getenv('REDIS_PORT') ?: 6379;
                $password = getenv('REDIS_PASSWORD') ?: null;

                $redis->connect($host, $port);
                if ($password) {
                    $redis->auth($password);
                }

                // Test ping để chắc chắn Redis hoạt động
                $redis->ping();

                self::$redis = $redis;
                self::$driver = 'redis';
            } catch (Exception $e) {
                // Ghi log và fallback sang file cache
                error_log("Redis connection failed: " . $e->getMessage());
                self::$driver = 'file';
            }
        }
    }

    /**
     * Lưu dữ liệu vào cache
     */
    public static function set($key, $value, $ttl = 3600) {
        if (self::$driver === 'redis' && self::$redis) {
            $data = self::$useJson ? json_encode($value) : serialize($value);
            if ($ttl > 0) {
                return self::$redis->setex($key, $ttl, $data);
            } else {
                return self::$redis->set($key, $data);
            }
        } else {
            $file = self::$path . md5($key) . '.cache';
            $data = [
                'expires' => time() + $ttl,
                'value' => $value
            ];
            return file_put_contents($file, serialize($data)) !== false;
        }
    }

    /**
     * Lấy dữ liệu từ cache
     */
    public static function get($key) {
        if (self::$driver === 'redis' && self::$redis) {
            $data = self::$redis->get($key);
            if (!$data) return null;
            return self::$useJson ? json_decode($data, true) : unserialize($data);
        } else {
            $file = self::$path . md5($key) . '.cache';
            if (!file_exists($file)) return null;

            $data = unserialize(file_get_contents($file));
            if ($data['expires'] < time()) {
                unlink($file);
                return null;
            }
            return $data['value'];
        }
    }

    /**
     * Xóa 1 key cache
     */
    public static function delete($key) {
        if (self::$driver === 'redis' && self::$redis) {
            return self::$redis->del($key);
        } else {
            $file = self::$path . md5($key) . '.cache';
            if (file_exists($file)) unlink($file);
        }
    }

    /**
     * Xóa toàn bộ cache
     */
    public static function clear() {
        if (self::$driver === 'redis' && self::$redis) {
            return self::$redis->flushAll();
        } else {
            $files = glob(self::$path . '*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Kiểm tra cache có tồn tại không
     */
    public static function has($key) {
        if (self::$driver === 'redis' && self::$redis) {
            return self::$redis->exists($key);
        } else {
            $file = self::$path . md5($key) . '.cache';
            if (!file_exists($file)) return false;

            $data = unserialize(file_get_contents($file));
            if ($data['expires'] < time()) {
                unlink($file);
                return false;
            }
            return true;
        }
    }

    /**
     * Dọn rác cache hết hạn (chỉ cho file cache)
     */
    public static function gc() {
        if (self::$driver === 'file') {
            foreach (glob(self::$path . '*.cache') as $file) {
                $data = unserialize(file_get_contents($file));
                if ($data['expires'] < time()) unlink($file);
            }
        }
    }
}
