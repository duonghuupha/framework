<?php
class Cache {
    private static $driver;
    private static $redis;
    private static $cachePath;

    public static function init($driver = 'redis') {
        self::$driver = $driver;
        self::$cachePath = __DIR__ . '/../storage/cache/';

        if (!is_dir(self::$cachePath)) {
            mkdir(self::$cachePath, 0777, true);
        }

        if ($driver === 'redis') {
            try {
                self::$redis = new Redis();
                $host = Config::get('REDIS_HOST', '127.0.0.1');
                $port = Config::get('REDIS_PORT', 6379);
                self::$redis->connect($host, $port, 1);

                $password = Config::get('REDIS_PASSWORD', null);
                if ($password) {
                    self::$redis->auth($password);
                }

                $db = Config::get('REDIS_DB', 0);
                self::$redis->select($db);
            } catch (Exception $e) {
                // Nếu Redis lỗi → tự động dùng file
                self::$driver = 'file';
            }
        }
    }

    public static function set($key, $value, $ttl = 0) {
        $data = serialize(['value' => $value, 'expire' => $ttl ? time() + $ttl : 0]);

        if (self::$driver === 'redis' && self::$redis) {
            try {
                if ($ttl > 0) {
                    return self::$redis->setex($key, $ttl, serialize($value));
                } else {
                    return self::$redis->set($key, serialize($value));
                }
            } catch (Exception $e) {
                // fallback file
            }
        }

        // --- cache file ---
        $file = self::$cachePath . md5($key) . '.cache';
        file_put_contents($file, $data);
        return true;
    }

    public static function get($key) {
        if (self::$driver === 'redis' && self::$redis) {
            try {
                $data = self::$redis->get($key);
                return $data ? unserialize($data) : null;
            } catch (Exception $e) {
                // fallback file
            }
        }

        // --- cache file ---
        $file = self::$cachePath . md5($key) . '.cache';
        if (!file_exists($file)) return null;

        $data = unserialize(file_get_contents($file));
        if ($data['expire'] && $data['expire'] < time()) {
            unlink($file);
            return null;
        }
        return $data['value'];
    }

    public static function forget($key) {
        if (self::$driver === 'redis' && self::$redis) {
            try {
                return self::$redis->del($key);
            } catch (Exception $e) {}
        }

        $file = self::$cachePath . md5($key) . '.cache';
        if (file_exists($file)) unlink($file);
        return true;
    }

    public static function clear() {
        if (self::$driver === 'redis' && self::$redis) {
            try {
                self::$redis->flushDB();
            } catch (Exception $e) {}
        }

        $files = glob(self::$cachePath . '*.cache');
        foreach ($files as $file) unlink($file);
        return true;
    }

    public static function remember($key, $ttl, $callback) {
        $cached = self::get($key);
        if ($cached !== null) return $cached;

        $data = $callback();
        self::set($key, $data, $ttl);
        return $data;
    }
}
