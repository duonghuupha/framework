<?php
class Cache {
    protected static $enabled = false; // bật/tắt cache Redis
    protected static $redis = null;    // đối tượng Redis
    protected static $path;            // đường dẫn lưu file cache
    protected static $prefix = 'fw_';  // tiền tố cho key cache

    /**
     * Khởi tạo cache
     * @param bool $useRedis true nếu dùng Redis
     */
    public static function init($useRedis = false) {
        self::$path = __DIR__ . '/../storage/cache';

        // Nếu thư mục cache chưa có → tạo
        if (!file_exists(self::$path)) {
            mkdir(self::$path, 0777, true);
        }

        if ($useRedis) {
            try {
                self::$redis = new Redis();
                self::$redis->connect('127.0.0.1', 6379);
                self::$enabled = true;
            } catch (Exception $e) {
                self::$enabled = false; // fallback về file
            }
        }
    }

    /**
     * Đặt giá trị vào cache
     */
    public static function set($key, $value, $ttl = 300) {
        $cacheKey = self::$prefix . $key;

        if (self::$enabled && self::$redis) {
            self::$redis->setex($cacheKey, $ttl, serialize($value));
        } else {
            file_put_contents(self::$path . '/' . md5($cacheKey) . '.cache', serialize($value));
        }
    }

    /**
     * Lấy giá trị từ cache
     */
    public static function get($key) {
        $cacheKey = self::$prefix . $key;

        if (self::$enabled && self::$redis) {
            $cached = self::$redis->get($cacheKey);
            return $cached ? unserialize($cached) : null;
        }

        $cacheFile = self::$path . '/' . md5($cacheKey) . '.cache';
        if (file_exists($cacheFile)) {
            return unserialize(file_get_contents($cacheFile));
        }

        return null;
    }

    /**
     * Xóa cache theo key
     */
    public static function delete($key) {
        $cacheKey = self::$prefix . $key;

        if (self::$enabled && self::$redis) {
            self::$redis->del($cacheKey);
        } else {
            $cacheFile = self::$path . '/' . md5($cacheKey) . '.cache';
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
        }
    }

    /**
     * Hàm remember() — cache helper thông minh
     * Lưu cache nếu chưa có, tự động lấy lại nếu có sẵn
     */
    public static function remember($key, $ttl, callable $callback) {
        $cacheKey = self::$prefix . $key;

        // 1️⃣ Kiểm tra cache sẵn có
        if (self::$enabled && self::$redis) {
            $cached = self::$redis->get($cacheKey);
            if ($cached !== false && $cached !== null) {
                return unserialize($cached);
            }
        } else {
            $cacheFile = self::$path . '/' . md5($cacheKey) . '.cache';
            if (file_exists($cacheFile) && (filemtime($cacheFile) + $ttl) > time()) {
                return unserialize(file_get_contents($cacheFile));
            }
        }

        // 2️⃣ Nếu chưa có cache → lấy dữ liệu mới
        $value = call_user_func($callback);

        // 3️⃣ Lưu cache
        self::set($key, $value, $ttl);

        return $value;
    }
}
