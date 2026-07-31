<?php
class Sellers extends Model{
    protected static string $table = "sellers";
    protected static string $table_detail = "seller_items";
    protected static string $table_payment = "seller_payments";
    protected static string $view = "v_sellers";

    /**
     * true  : Cho phép bán âm
     * false : Không cho phép bán âm
     * Sau này sẽ lấy từ bảng settings
     */
    private const ALLOW_NEGATIVE_STOCK = true;

    /* ==========================================================
     * DANH SÁCH HÓA ĐƠN
     * ========================================================== */
    public static function listSellers(array $params = []): array{
        $customer = trim($params['search']['customer'] ?? '');
        $product  = trim($params['search']['product'] ?? '');
        $dateFrom = $params['search']['date_start'] ?? '';
        $dateTo   = $params['search']['date_end'] ?? '';
        unset(
            $params['search']['customer'],
            $params['search']['product'],
            $params['search']['date_start'],
            $params['search']['date_end']
        );
        if ($customer !== '') {
            $params['advanced'][] = [
                'type'   => 'raw',
                'sql'    => '(customer_name LIKE ? OR customer_phone LIKE ?)',
                'params' => [
                    "%{$customer}%",
                    "%{$customer}%"
                ]
            ];
        }
        if ($product !== '') {
            $params['advanced'][] = [
                'type' => 'exists',
                'sql'  => '
                    SELECT 1
                    FROM seller_items si
                    INNER JOIN products p ON p.id = si.product_id
                    WHERE si.seller_id = v_sellers.id
                      AND (
                            p.name LIKE ?
                         OR p.code LIKE ?
                      )
                ',
                'params' => [
                    "%{$product}%",
                    "%{$product}%"
                ]
            ];
        }

        if ($dateFrom && $dateTo) {
            $params['advanced'][] = [
                'type'   => 'raw',
                'sql'    => 'DATE(created_at) BETWEEN ? AND ?',
                'params' => [$dateFrom, $dateTo]
            ];
        }
        return self::paginateAdv(static::$view, $params);
    }

    /* ==========================================================
     * KIỂM TRA TRÙNG MÃ
     * ========================================================== */
    public static function dupliObjSellers(string $code, int $id = 0): array|false{
        if ($id == 0) {
            return self::where("code", $code);
        }
        $sql = "SELECT * FROM sellers WHERE code = ? AND id <> ?";
        return self::dynamicQuery($sql,[$code, $id]);
    }

    /* ==========================================================
     * CHI TIẾT HÓA ĐƠN
     * ========================================================== */
    public static function getSellerItems($id){
        $sql = "
            SELECT
                si.product_id,
                p.code  AS product_code,
                p.name  AS product_name,
                u.name  AS unit_name,
                si.qty,
                si.price,
                si.discount,
                si.final_price,
                si.total
            FROM seller_items si
            INNER JOIN products p
                ON p.id = si.product_id
            LEFT JOIN dm_units u
                ON u.id = p.unit_id
            WHERE si.seller_id = ?
            ORDER BY si.id ASC
        ";

        return self::dynamicQuery($sql,[$id]);
    }

    /* ==========================================================
     * CHI TIẾT HÓA ĐƠN
     * ========================================================== */
    public static function getPayments($sellerId){
        $sql = "SELECT * FROM seller_payments WHERE seller_id = ?";
        return self::dynamicQuery($sql,[$sellerId]);
    }

    /* ==========================================================
     * TÍNH GIẢM GIÁ
     * <=100 : %
     * >100  : TIỀN
     * ========================================================== */
    private static function calculateDiscount(float $amount, float $discount): float{
        if ($discount <= 0) {
            return $amount;
        }
        if ($discount <= 100) {
            return $amount - ($amount * $discount / 100);
        }
        return max(0, $amount - $discount);
    }

    /* ==========================================================
     * TÍNH TOÁN 1 DÒNG SẢN PHẨM
     * ========================================================== */
    private static function calculateItem(array $item): array{
        $price = (float)$item['price']; // don gia
        $qty = (float)$item['quantity']; // so luong
        $discount = (float)($item['discount'] ?? 0); // giam gia
        $originTotal = $price * $qty; // tien trước giam
        $finalPrice = self::calculateDiscount($price,$discount); // don gia sau guiam
        $finalTotal = $finalPrice * $qty; // tong tien sau giam
        $item['price'] = $price; // don gia
        $item['quantity'] = $qty; // so luong
        $item['discount'] = $discount; // giam gia
        $item['origin_total'] = $originTotal; // tong tien trươc giam
        $item['final_price'] = $finalPrice; // don gia sau giam
        $item['total'] = $finalTotal; // tong tien sau giam
        return $item;
    }

    /* ==========================================================
     * TÍNH TOÁN HÓA ĐƠN
     * ========================================================== */
    private static function calculateSummary(array $input): array{
        $products = [];
        $totalAmount = 0;
        $discountAmount = 0;
        foreach ($input['products'] as $item) {
            $item = self::calculateItem($item);
            $products[] = $item;
            $totalAmount += $item['origin_total']; // tong tien san pham
            $discountAmount += ($item['origin_total'] - $item['total']); // giam gia
        }
        $subTotal = $totalAmount - $discountAmount;
        $invoiceDiscount = (float)($input['discount'] ?? 0);
        $finalAmount = self::calculateDiscount($subTotal, $invoiceDiscount);
        $discountAmount += ($subTotal - $finalAmount);
        $paidAmount = (float)($input['customer_pay'] ?? 0);
        $debtAmount = max(0, $finalAmount - $paidAmount);
        return [
            'products' => $products,
            'header' => [
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'paid_amount' => $paidAmount,
                'debt_amount' => $debtAmount
            ],
            'payment' => [
                'cash_amount' => $input['cash_amount'],
                'bank_amount' => $input['bank_amount'],
                'customer_pay' => $input['customer_pay']
            ]
        ];
    }

    /* ==========================================================
     * CHUYỂN PHƯƠNG THỨC THANH TOÁN
     * ========================================================== */
    private static function normalizePayment(int $payment): string{
        return match ($payment) {
            1 => 'cash',
            2 => 'bank',
            3 => 'cash+bank',
            default => throw new Exception(
                'Phương thức thanh toán không hợp lệ.'
            )
        };
    }

    /* ==========================================================
     * KIỂM TRA TỒN KHO
     * ========================================================== */
    private static function checkStocks(array $products): array{
        if (self::ALLOW_NEGATIVE_STOCK) {
            return [];
        }
        $ids = array_column($products, 'id');
        if (empty($ids)) {
            return [];
        }
        $placeholder = implode(',', array_fill(0, count($ids), '?'));
        $sql = " SELECT id, code, name, stock FROM products WHERE id IN ($placeholder)";
        $rows = self::dynamicQuery($sql, $ids);
        $stocks = [];
        foreach ($rows as $row) {
            $stocks[$row['id']] = $row;
        }
        foreach ($products as $item) {
            if (!isset($stocks[$item['id']])) {
                throw new Exception(
                    "Không tìm thấy sản phẩm."
                );
            }
            if ($stocks[$item['id']]['stock'] < $item['quantity']) {
                throw new Exception(
                    "Sản phẩm {$stocks[$item['id']]['code']} chỉ còn {$stocks[$item['id']]['stock']}."
                );
            }
        }
        return $stocks;
    }

    /* ==========================================================
     * THÊM HEADER
     * ========================================================== */
    private static function insertHeader(array $header): int{
        $id = self::insert($header);
        if (!$id) {
            throw new Exception("Không tạo được hóa đơn bán.");
        }
        return $id;
    }

    /* ==========================================================
     * THÊM CHI TIẾT HÓA ĐƠN
     * ========================================================== */
    private static function insertItems(int $sellerId, array $products): void{
        foreach ($products as $item) {
            $detail = [
                'seller_id' => $sellerId,
                'product_id' => $item['id'],
                'qty' => $item['quantity'],
                'price' => $item['price'],
                'discount' => $item['discount'],
                'final_price' => $item['final_price'],
                'total' => $item['total']
            ];
            $result = self::insertTo(static::$table_detail, $detail);
            if (!$result) {
                throw new Exception("Không lưu được chi tiết hóa đơn.");
            }
        }
    }

    /* ==========================================================
     * LƯU THANH TOÁN
     * ========================================================== */
    private static function insertPayment(int $sellerId, array $summary): void{
        if ($summary['header']['paid_amount'] <= 0) {
            return;
        }
        if(($summary['payment']['cash_amount'] ?? 0) > 0){
            $payment = [
                'seller_id' => $sellerId,
                'method' => self::normalizePayment(1),
                'amount' => $summary['payment']['cash_amount']
            ];
            $result = self::insertTo(static::$table_payment, $payment);
            if (!$result) {
                throw new Exception("Không lưu được thông tin thanh toán.");
            }
        }
        if(($summary['payment']['bank_amount'] ?? 0) > 0){
            $payment = [
                'seller_id' => $sellerId,
                'method' => self::normalizePayment(2),
                'amount' => $summary['payment']['bank_amount']
            ];
            $result = self::insertTo(static::$table_payment, $payment);
            if (!$result) {
                throw new Exception("Không lưu được thông tin thanh toán.");
            }
        }
    }

    /* ==========================================================
     * CẬP NHẬT TỒN KHO
     * ========================================================== */
    private static function decreaseStocks(array $products): void{
        foreach ($products as $item) {
            $sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $result = self::execQuery($sql, [$item['quantity'], $item['id']]);
            if (!$result) {
                throw new Exception("Không cập nhật được tồn kho.");
            }
        }
    }

    /* ==========================================================
     * TẠO HÓA ĐƠN BÁN
     * ========================================================== */
    public static function createSeller(array $input): int{
        $duplicate = self::dupliObjSellers($input['code']);
        if (!empty($duplicate)) {
            throw new Exception("Mã hóa đơn đã tồn tại.");
        }
        $summary = self::calculateSummary($input); // tinh tien hoa dơn
        self::checkStocks($summary['products']);
        $header = [
            'code' => $input['code'],
            'customer_id' => $input['customer_id'] ?? null,
            'note' => $input['note'] ?? '',
            'total_amount' => $summary['header']['total_amount'], // tong tien truoc giam
            'discount_amount' => $summary['header']['discount_amount'], // giam gia
            'final_amount' => $summary['header']['final_amount'], // tong tien sau giam
            'paid_amount' => $summary['header']['paid_amount'], // so tien thanh toan
            'debt_amount' => $summary['header']['debt_amount'], // cong no cua hoa don
            'status' => $summary['header']['debt_amount'] > 0 ? 'debt' : 'completed'// trang thai cua hoa don no hay khong no
        ];
        self::beginTransaction();
        try {
            $sellerId = self::insertHeader($header);
            self::insertItems($sellerId,$summary['products']);
            self::insertPayment($sellerId, $summary);
            self::decreaseStocks($summary['products']);
            self::commit();
            return $sellerId;
        } catch (Exception $e) {
            self::rollback();
            throw $e;
        }
    }
}