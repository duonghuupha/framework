<?php
class JWTHelper {
    protected static function secret(): string {
        $s = getenv('JWT_SECRET');
        if (!$s) $s = 'change_this_secret_immediately';
        return $s;
    }

    public static function ttl(): int {
        $t = getenv('JWT_TTL');
        return $t ? (int)$t : 3600;
    }

    protected static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected static function base64UrlDecode(string $data): string {
        $remainder = strlen($data) % 4;
        if ($remainder) $data .= str_repeat('=', 4 - $remainder);
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode(array $payload, ?int $ttl = null): string {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $iat = time();
        $exp = $iat + ($ttl ?? self::ttl());
        $payload['iat'] = $iat;
        $payload['exp'] = $exp;

        $header_b64 = self::base64UrlEncode(json_encode($header));
        $payload_b64 = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$header_b64.$payload_b64", self::secret(), true);
        $signature_b64 = self::base64UrlEncode($signature);

        return "$header_b64.$payload_b64.$signature_b64";
    }

    public static function decode(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$h, $p, $s] = $parts;

        $header = json_decode(self::base64UrlDecode($h), true);
        $payload = json_decode(self::base64UrlDecode($p), true);
        $signature = self::base64UrlDecode($s);

        if (!$header || !$payload) return null;
        $expected = hash_hmac('sha256', "$h.$p", self::secret(), true);
        if (!hash_equals($expected, $signature)) return null;
        if (isset($payload['exp']) && time() > $payload['exp']) return null;

        return $payload;
    }

    public static function issueAndStore(array $payload, ?int $ttl = null): string {
        $token = self::encode($payload, $ttl);
        if (isset($payload['user_id'])) {
            Cache::set('jwt_user_' . $payload['user_id'], $token, $ttl ?? self::ttl());
        } else {
            Cache::set('jwt:' . md5($token), $token, $ttl ?? self::ttl());
        }
        return $token;
    }

    public static function validate(string $token): ?array {
        $payload = self::decode($token);
        if (!$payload) return null;

        if (isset($payload['user_id'])) {
            $stored = Cache::get('jwt_user_' . $payload['user_id']);
            if (!$stored || !hash_equals($stored, $token)) return null;
        } else {
            $stored = Cache::get('jwt:' . md5($token));
            if (!$stored || !hash_equals($stored, $token)) return null;
        }

        return $payload;
    }

    public static function revokeByUserId($userId) {
        Cache::delete('jwt_user_' . $userId);
    }

    public static function revokeToken($token) {
        Cache::delete('jwt:' . md5($token));
    }
}