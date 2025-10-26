<?php
class Input {
    private static array $data = [];

    public static function all(): array {
        if (empty(self::$data)) self::parse();
        return self::$data;
    }

    public static function get(string $key, $default = null) {
        if (empty(self::$data)) self::parse();
        return self::$data[$key] ?? $default;
    }

    private static function parse(): void {
        // Bắt đầu với GET + POST
        self::$data = array_merge($_GET ?? [], $_POST ?? []);

        // Thêm JSON body nếu có
        $raw = file_get_contents('php://input');
        if ($raw) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                self::$data = array_merge(self::$data, $json);
            }
        }

        // Thêm file upload
        if (!empty($_FILES)) {
            self::$data['_files'] = $_FILES;
        }
    }
}
