<?php
// core/Cache.php
// Hỗ trợ Redis (nếu extension Redis có sẵn) hoặc file fallback.
// File cache lưu JSON: { "key": "...", "expires": 1234567890, "value": ... }

class Cache{
    private static $useRedis = false;
    private static $redis = null;
    private static $cacheDir = __DIR__ . '/../storage/cache/';
    private static $prefix = 'fw_';

    // Tùy bạn: có thể gọi Cache::init() ở index.php; nếu không, class tự init khi cần.
    public static function init(){
        // nếu đã init thì bỏ qua
        if (self::$redis !== null || is_dir(self::$cacheDir)) {
            // try to init redis if not attempted yet
        }

        // Thử Redis (đọc host/port từ env nếu có)
        if (class_exists('Redis')) {
            try {
                $host = (function_exists('getenv') ? getenv('REDIS_HOST') : false) ?: '127.0.0.1';
                $port = (function_exists('getenv') ? getenv('REDIS_PORT') : false) ?: 6379;
                $r = new Redis();
                $r->connect($host, (int)$port, 2.5);
                self::$redis = $r;
                self::$useRedis = true;
            } catch (Exception $e) {
                self::$useRedis = false;
                self::$redis = null;
            }
        }

        // đảm bảo folder cache file tồn tại
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0777, true);
        }
    }

    private static function ensureInit(){
        if (self::$redis === null && !is_dir(self::$cacheDir)) {
            self::init();
        }
    }

    private static function rawKey($key){
        return self::$prefix . $key;
    }

    private static function filePath($key){
        // sử dụng sha1 để tránh tên file quá dài / ký tự lạ
        return self::$cacheDir . sha1(self::rawKey($key)) . '.json';
    }

    public static function set($key, $value, $ttl = 3600){
        self::ensureInit();
        $k = self::rawKey($key);

        if (self::$useRedis && self::$redis) {
            $payload = json_encode(['key' => $key, 'value' => $value]);
            return self::$redis->setex($k, (int)$ttl, $payload);
        }

        $file = self::filePath($key);
        $data = [
            'key'     => $key,
            'expires' => time() + (int)$ttl,
            'value'   => $value
        ];
        return @file_put_contents($file, json_encode($data));
    }

    public static function get($key){
        self::ensureInit();
        $k = self::rawKey($key);

        if (self::$useRedis && self::$redis) {
            $raw = self::$redis->get($k);
            if ($raw === false || $raw === null) return null;
            $obj = json_decode($raw, true);
            return $obj['value'] ?? null;
        }

        $file = self::filePath($key);
        if (!file_exists($file)) return null;
        $raw = @file_get_contents($file);
        if (!$raw) return null;
        $obj = json_decode($raw, true);
        if (!isset($obj['expires'])) { @unlink($file); return null; }
        if ($obj['expires'] < time()) { @unlink($file); return null; }
        return $obj['value'] ?? null;
    }

    public static function remember($key, $ttlOrCallback, $maybeCallback = null){
        self::ensureInit();

        // Nếu dữ liệu đã có trong cache, trả về luôn
        $val = self::get($key);
        if ($val !== null) return $val;

        // Xác định TTL và callback (cho phép gọi kiểu 2 hoặc 3 tham số)
        if (is_callable($ttlOrCallback)) {
            $ttl = 300; // TTL mặc định 5 phút
            $callback = $ttlOrCallback;
        } else {
            $ttl = (int)$ttlOrCallback;
            $callback = $maybeCallback;
        }

        // Lấy dữ liệu mới từ callback
        $val = call_user_func($callback);

        // Lưu vào cache
        self::set($key, $val, $ttl);

        return $val;
    }


    public static function delete($key){
        self::ensureInit();
        $k = self::rawKey($key);

        if (self::$useRedis && self::$redis) {
            return (bool) self::$redis->del($k);
        }

        $file = self::filePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
        return true;
    }

    public static function clearAll(){
        self::ensureInit();
        if (self::$useRedis && self::$redis) {
            $pattern = self::$prefix . '*';
            $keys = self::$redis->keys($pattern);
            if (is_array($keys)) {
                foreach ($keys as $k) self::$redis->del($k);
            }
            return true;
        }

        $files = glob(self::$cacheDir . '*.json');
        foreach ($files as $f) @unlink($f);
        return true;
    }

    // Xóa cache theo prefix key (ví dụ 'model_users_') — hoạt động cho Redis & file
    public static function clearByPrefix($prefix){
        self::ensureInit();
        $realPrefix = self::rawKey($prefix);
        if (self::$useRedis && self::$redis) {
            $keys = self::$redis->keys($realPrefix . '*');
            if (is_array($keys)) {
                foreach ($keys as $k) self::$redis->del($k);
            }
            return true;
        }

        // file mode: quét files, đọc JSON và so sánh key field
        $files = glob(self::$cacheDir . '*.json');
        foreach ($files as $file) {
            $raw = @file_get_contents($file);
            if (!$raw) continue;
            $obj = @json_decode($raw, true);
            if (!is_array($obj) || !isset($obj['key'])) continue;
            if (strpos(self::rawKey($obj['key']), $realPrefix) === 0) {
                @unlink($file);
            }
        }
        return true;
    }
}
?>