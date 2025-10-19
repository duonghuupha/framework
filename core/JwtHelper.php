<?php
class JwtHelper {
    private static $secret_key = 'your_secret_key_here'; // Thay bằng key riêng
    private static $algo = 'HS256';
    private static $expire_time = 3600; // 1 giờ

    // Tạo JWT
    public static function createToken($payload = []) {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algo]);
        $payload['exp'] = time() + self::$expire_time;

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", self::$secret_key, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    // Kiểm tra và giải mã JWT
    public static function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        list($header, $payload, $signature) = $parts;
        $expectedSignature = self::base64UrlEncode(hash_hmac('sha256', "$header.$payload", self::$secret_key, true));

        if (!hash_equals($expectedSignature, $signature)) return false;

        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        if ($data['exp'] < time()) return false;

        return $data;
    }

    // Hỗ trợ
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
