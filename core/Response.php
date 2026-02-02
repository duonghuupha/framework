<?php
class Response{
    private static bool $debug = true; // false khi đưa lên production
    private static function output($status, $message, $code, $data){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        
        http_response_code($code);
        $options = JSON_UNESCAPED_UNICODE;
        if (self::$debug) {
            $options |= JSON_PRETTY_PRINT; // in ra nhiều dòng đẹp dễ đọc
        }

        echo json_encode([
            'status' => $status,
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $options);
        exit;
    }

    public static function success($message = 'Thành công', $data = [], $code = 200){
        self::output('success', $message, $code, $data);
    }

    public static function error($message = 'Lỗi xử lý', $code = 400, $data = []){
        self::output('error', $message, $code, $data);
    }
}
