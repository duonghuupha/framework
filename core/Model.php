<?php
/**
 * Class Model (PHP 8.3+)
 * Lớp cha cho tất cả các model trong hệ thống.
 * - Chuẩn hóa cú pháp (PDO typed, return type)
 * - Thêm hỗ trợ phân trang + tìm kiếm linh hoạt
 * - Giữ nguyên khả năng cache
 */

class Model{
    protected static string $table = '';             // Tên bảng
    protected static string $primaryKey = 'id';      // Khóa chính

    protected static function getDB(): PDO{
        return Database::getInstance()->getConnection();
    }

    protected static function getTableCacheVersion() : int{
        $key = static::$table . '_cache_version';
        return Cache::remember($key, function() {
            return time();
        });
    }

    protected static function bumpTableCacheVersion() : void{
        $key = static::$table . '_cache_version';
        Cache::set($key, time());
    }

    /**
     * Thực thi truy vấn SQL có tham số (tránh SQL injection)
     */
    protected static function execQuery(string $sql, array $params = []): PDOStatement{
        $db = self::getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Lấy toàn bộ dữ liệu (có cache)
     */
    public static function all(): array{
        $version = self::getTableCacheVersion();
        $cacheKey = 'table_all_' . static::$table . '_v' . $version;
        return Cache::remember($cacheKey, function () {
            $stmt = self::execQuery("SELECT * FROM " . static::$table);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Tìm 1 bản ghi theo id
     */
    public static function find(int|string $id): ?array{
        $version = self::getTableCacheVersion();
        $cacheKey = 'record_' . static::$table . '_' . $id . '_v' . $version;
        return Cache::remember($cacheKey, function () use ($id) {
            $sql = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
            $stmt = self::execQuery($sql, [$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        });
    }

    /**
     * Tìm theo điều kiện đơn giản (column = value)
     */
    public static function where(string $column, mixed $value): array{
        $version = self::getTableCacheVersion();
        $cacheKey = 'where_' . static::$table . '_' . $column . '_' . md5($value) . '_v' . $version;
        return Cache::remember($cacheKey, function () use ($column, $value) {
            $sql = "SELECT * FROM " . static::$table . " WHERE {$column} = ?";
            $stmt = self::execQuery($sql, [$value]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Truy vấn phức tạp, có cache TTL (giây)
     */
    public static function dynamicQuery(string $sql, array $params = [], int $ttl = 30): array{
        $version = self::getTableCacheVersion();
        $cacheKey = 'dynamic_' . md5($sql . json_encode($params)) . '_v' . $version;
        return Cache::remember($cacheKey, $ttl, function () use ($sql, $params) {
            $stmt = self::execQuery($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Thêm dữ liệu mới
     */
    public static function insert(array $data): int|false{
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        $sql = "INSERT INTO " . static::$table . " ($fields) VALUES ($placeholders)";
        $stmt = self::execQuery($sql, array_values($data));

        //Cache::clearByPrefix('table_all_' . static::$table);
        self::bumpTableCacheVersion();
        return (int) self::getDB()->lastInsertId();
    }

    /**
     * Cập nhật bản ghi theo id
     */
    public static function update(int|string $id, array $data): bool{
        $setParts = array_map(fn($key) => "{$key} = ?", array_keys($data));
        $sql = "UPDATE " . static::$table . " SET " . implode(',', $setParts)
             . " WHERE " . static::$primaryKey . " = ?";
        $params = [...array_values($data), $id];
        self::execQuery($sql, $params);

        //Cache::clearByPrefix('table_all_' . static::$table);
        self::bumpTableCacheVersion();
        return true;
    }

    /**
     * Xóa bản ghi theo id
     */
    public static function delete(int|string $id): bool{
        $sql = "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        self::execQuery($sql, [$id]);

        //Cache::clearByPrefix('table_all_' . static::$table);
        self::bumpTableCacheVersion();
        return true;
    }

    // ================================================================
    // 🔹 PHÂN TRANG & TÌM KIẾM NÂNG CAO
    // ================================================================

    /**
     * Phân trang + tìm kiếm + lọc + sắp xếp (có cache)
     */
    public static function paginate(string $table, array $params = [], int $ttl = 30): array {
        $page   = max(1, (int)($params['page'] ?? 1));
        $limit  = max(1, (int)($params['limit'] ?? 10));
        $offset = ($page - 1) * $limit;
        $order  = $params['order'] ?? ['id' => 'DESC'];
        $search = $params['search'] ?? [];
        $filters = $params['filters'] ?? [];

        [$whereSQL, $queryParams] = self::buildWhere($filters, $search);

        // ORDER BY
        $orderSQL = '';
        if (!empty($order)) {
            $orderParts = [];
            foreach ($order as $col => $dir) {
                $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
                $orderParts[] = "{$col} {$dir}";
            }
            $orderSQL = " ORDER BY " . implode(', ', $orderParts);
        }
        // ✅ Tạo key cache duy nhất dựa trên điều kiện
        $version = self::getTableCacheVersion();
        $cacheKey = sprintf(
            "paginate_%s_%s_%s",
            $table,
            $version,
            md5(json_encode([
                'page' => $page,
                'limit' => $limit,
                'order' => $order,
                'filters' => $filters,
                'search' => $search
            ]))
        );

        // ✅ Lấy dữ liệu từ cache nếu có
        return Cache::remember($cacheKey, $ttl, function() use ($table, $whereSQL, $queryParams, $orderSQL, $limit, $offset, $page) {
            // COUNT tổng
            $countSQL = "SELECT COUNT(*) AS total FROM {$table} {$whereSQL}";
            $countStmt = self::execQuery($countSQL, $queryParams);
            $total = (int)$countStmt->fetchColumn();

            // Lấy dữ liệu trang hiện tại
            $sql = "SELECT * FROM {$table} {$whereSQL} {$orderSQL} LIMIT {$limit} OFFSET {$offset}";
            $stmt = self::execQuery($sql, $queryParams);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'rows' => $data
            ];
        });
    }


    /**
     * Tạo câu WHERE linh hoạt từ filters + search
     * - filters: so sánh bằng
     * - search: LIKE (có thể nhiều cột)
     */
    protected static function buildWhere(array $filters = [], array $search = []): array{
        $conditions = [];
        $params = [];

        // Lọc chính xác
        foreach ($filters as $col => $val) {
            if ($val === '' || $val === null) continue;
            $conditions[] = "{$col} = ?";
            $params[] = $val;
        }

        // Tìm kiếm mờ (LIKE)
        foreach ($search as $col => $val) {
            if ($val === '' || $val === null) continue;
            $conditions[] = "{$col} LIKE ?";
            $params[] = '%' . $val . '%';
        }

        $whereSQL = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$whereSQL, $params];
    }
}
