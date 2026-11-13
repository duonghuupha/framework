<?php
class Customer extends Model{
    protected static string $table = "customers"; //bảng khách hàng
    protected static string $seller = "sellers"; //bảng hóa đơn

    public static function listCustomer(array $params = []): array{
        // Tìm kiếm khách hàng
        $name = trim($params['search']['name'] ?? '');

        unset($params['search']['name']);

        // Truy vấn khách hàng kèm công nợ
        $sql = "(SELECT c.*,
                    COALESCE((SELECT SUM(s.debt_amount) FROM sellers s WHERE s.customer_id = c.id AND s.active = 0 AND s.status = 'debt'), 0) AS seller_amount,
                    COALESCE((SELECT SUM(r.total_amount) FROM receipts r WHERE r.customer_id = c.id AND r.status = 1 AND r.types = 'debt'), 0) AS receipt_amount
                    FROM customers c
                ) AS customer_summary";

        // Tìm kiếm tên và số điện thoại
        if ($name !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => '(name LIKE ? OR phone LIKE ? OR code LIKE ?)',
                'params' => [
                    "%{$name}%",
                    "%{$name}%",
                    "%{$name}%"
                ]
            ];
        }
        return self::paginateAdv($sql, $params);
    }

    public static function dupliObjCustomer($code, $id) : array|false{
        if($id === 0){
            return self::where("code", $code);
        }else{
            $sql = "SELECT * FROM " . static::$table . " WHERE code = ? AND id != ?";
            $params = [$code, $id];
            return self::dynamicQuery($sql, $params);
        }
    }

    public static function addCustomer(array $data) : int|false{
        return self::insert($data);
    }

    public static function updateCustomer(int $id, array $data) : int|false{
        return self::update($id, $data);
    }

    public static function deleteCustomer(int $id) : int|false{
        return self::delete($id);
    }

    public static function listComboCustomer($name) : array|false{
        $sql = "SELECT id, code, name, address, phone, is_default FROM " . static::$table . " WHERE name LIKE ? OR phone LIKE ?";
        $params = ["%$name%", "%$name%"];
        return self::dynamicQuery($sql, $params);
    }

    public static function getDebtCustomer(int $customerId): array{
        // tong cong no phat sinh
        $sqlSeller = "SELECT COALESCE(SUM(debt_amount), 0) total FROM sellers WHERE customer_id = ?";
        $seller = self::dynamicQuery($sqlSeller, [$customerId]);
        $sellerDebt = (float)($seller[0]['total'] ?? 0);

        // tong tien da thu cong no
        $sqlReceipt = "SELECT COALESCE(SUM(total_amount), 0) total FROM receipts WHERE customer_id = ? AND types = 'debt' AND status = 1";
        $receipt = self::dynamicQuery($sqlReceipt, [$customerId]);
        $receiptAmount = (float)($receipt[0]['total'] ?? 0);

        return [
            'seller_debt' => $sellerDebt,
            'receipt_amount' => $receiptAmount,
            'current_debt' => $sellerDebt - $receiptAmount
        ];
    }

    public function getCustomerInfo($id) : array{
        return self::find($id);
    }

    public function getCustomerHistory(int $id, array $params = []): array
    {
        // Lấy điều kiện tìm kiếm
        $code = trim($params['search']['code'] ?? '');
        $dateFrom = $params['search']['date_start'] ?? '';
        $dateTo = $params['search']['date_end'] ?? '';

        unset(
            $params['search']['code'],
            $params['search']['date_start'],
            $params['search']['date_end']
        );

        // Đảm bảo ID là số nguyên trước khi đưa vào SQL
        $customerId = (int)$id;

        // Hợp nhất lịch sử hóa đơn và phiếu thu
        $table = "(
            SELECT
                id,
                code,
                created_at,
                'seller' AS transaction_type,
                final_amount AS incurred_amount,
                paid_amount AS paid_amount
            FROM sellers
            WHERE active = 0
                AND customer_id = {$customerId}

            UNION ALL

            SELECT
                id,
                code,
                created_at,
                'receipt' AS transaction_type,
                0 AS incurred_amount,
                total_amount AS paid_amount
            FROM receipts
            WHERE status = 1
                AND customer_id = {$customerId}
                AND types = 'debt'
        ) AS customer_history";

        // Tìm kiếm theo mã hóa đơn hoặc mã phiếu thu
        if ($code !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 'code LIKE ?',
                'params' => ["%{$code}%"]
            ];
        }

        // Lọc từ ngày
        if ($dateFrom !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 'DATE(created_at) >= ?',
                'params' => [$dateFrom]
            ];
        }

        // Lọc đến ngày
        if ($dateTo !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 'DATE(created_at) <= ?',
                'params' => [$dateTo]
            ];
        }

        // Sắp xếp giao dịch mới nhất trước
        $params['order'] = [
            'created_at' => 'DESC'
        ];

        return self::paginateAdv($table, $params);
    }
}
?>