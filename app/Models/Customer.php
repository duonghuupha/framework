<?php
class Customer extends Model{
    protected static string $table = "customers"; //bảng khách hàng
    protected static string $seller = "sellers"; //bảng hóa đơn

    /*public static function listCustomer(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }*/
<<<<<<< HEAD

    public static function listCustomer(array $params = []): array
    {
        // 1. Lấy điều kiện tìm kiếm
        $name  = trim($params['search']['name'] ?? '');
        $phone = trim($params['search']['phone'] ?? '');

        // 2. Loại bỏ các trường sẽ xử lý bằng advanced
=======
    public static function listCustomer(array $params = []): array
    {
        // Tìm kiếm khách hàng
        $name = trim($params['search']['name'] ?? '');
        $phone = trim($params['search']['phone'] ?? '');

>>>>>>> 22b24ca (105)
        unset(
            $params['search']['name'],
            $params['search']['phone']
        );

<<<<<<< HEAD
        // 3. Tìm kiếm theo tên hoặc số điện thoại
        if ($name !== '') {
            $params['advanced'][] = [
                'type'   => 'raw',
                'sql'    => 'name LIKE ?',
=======
        // Truy vấn khách hàng kèm công nợ
        $table = "(
            SELECT
                c.*,
                COALESCE(s.seller_debt, 0) AS seller_debt,
                COALESCE(r.receipt_amount, 0) AS receipt_amount,
                (
                    COALESCE(s.seller_debt, 0)
                    - COALESCE(r.receipt_amount, 0)
                ) AS current_debt
            FROM customers c

            LEFT JOIN (
                SELECT customer_id, SUM(debt_amount) AS seller_debt
                FROM sellers
                GROUP BY customer_id
            ) s ON s.customer_id = c.id

            LEFT JOIN (
                SELECT customer_id, SUM(total_amount) AS receipt_amount
                FROM receipts
                WHERE types = 'debt' AND status = 1
                GROUP BY customer_id
            ) r ON r.customer_id = c.id
        ) AS customer_debt";

        // Tìm kiếm tên và số điện thoại
        if ($name !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 'name LIKE ?',
>>>>>>> 22b24ca (105)
                'params' => ["%{$name}%"]
            ];
        }

        if ($phone !== '') {
            $params['advanced'][] = [
<<<<<<< HEAD
                'type'   => 'raw',
                'sql'    => 'phone LIKE ?',
=======
                'type' => 'raw',
                'sql' => 'phone LIKE ?',
>>>>>>> 22b24ca (105)
                'params' => ["%{$phone}%"]
            ];
        }

<<<<<<< HEAD
        // 4. Truy vấn khách hàng và tính công nợ
        $table = "(
            SELECT
                c.*,

                COALESCE((
                    SELECT SUM(s.debt_amount)
                    FROM sellers s
                    WHERE s.customer_id = c.id
                    AND s.active = 0
                    AND s.status = 'debt'
                ), 0) AS seller_debt,

                COALESCE((
                    SELECT SUM(r.total_amount)
                    FROM receipts r
                    WHERE r.customer_id = c.id
                    AND r.types = 'debt'
                    AND r.status = 1
                ), 0) AS receipt_amount

            FROM " . static::$table . " c
        ) AS customer_summary";

        // 5. Phân trang theo Model cha
=======
>>>>>>> 22b24ca (105)
        return self::paginateAdv($table, $params);
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

    public function getCustomerHistory(int $id, array $params = []) : array{
        $code = trim($params['search']['code'] ?? '');
        $dateFrom = $params['search']['date_start'] ?? '';
        $dateTo = $params['search']['date_end'] ?? '';
        unset(
            $params['search']['code'],
            $params['search']['date_start'],
            $params['search']['date_end']
        );

        $params['advanced'][] = [
            'type' => 'raw',
            'sql' => 'customer_id = ?',
            'params' => [$id]
        ];

        if($code !== ''){
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 'code LIKE ?',
                'params' => ["{$code}"]
            ];
        }

        if($dateFrom && $dateTo){
            $params['advanced'][] =[
                'type' => 'raw',
                'sql' => 'DATE(created_at) BETWEEN ? AND ?',
                'params' => [$dateFrom, $dateTo]
            ];
        }
        return self::paginateAdv(static::$seller, $params);
    }
}
?>