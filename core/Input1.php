<?
class Input{
    private static $data = null;

    // Nạp dữ liệu từ request chỉ 1 lần
    private static function load(){
        if (self::$data !== null) {
            return;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $rawData = file_get_contents('php://input');

        // Nếu là JSON (application/json)
        if (stripos($contentType, 'application/json') !== false) {
            $json = json_decode($rawData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                self::$data = $json;
            } else {
                self::$data = [];
            }
        } else {
            // Nếu là form thường hoặc query string
            self::$data = array_merge($_GET, $_POST);
        }
    }

    // Lấy giá trị theo key
    public static function get($key, $default = null){
        self::load();
        return self::$data[$key] ?? $default;
    }

    // Lấy toàn bộ dữ liệu
    public static function all(){
        self::load();
        return self::$data;
    }

    // Lấy file upload
    public static function file($key){
        return $_FILES[$key] ?? null;
    }

    // Kiểm tra tồn tại key
    public static function has($key){
        self::load();
        return isset(self::$data[$key]);
    }
}
