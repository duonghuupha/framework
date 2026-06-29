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
        $product = $params['search']['product'] ?? '';
        unset($params['search']['product']);
        if (!empty($product)) {
            $params['advanced'][] = [
                'type' => 'exists',
                'sql' => "
                    SELECT 1
                    FROM seller_items si
                    INNER JOIN products p
                        ON p.id = si.product_id
                    WHERE si.seller_id = v_sellers.id
                    AND (
                        p.name LIKE ?
                        OR p.code LIKE ?
                        OR p.barcode LIKE ?
                    )
                ",
                'params' => [
                    "%{$product}%",
                    "%{$product}%",
                    "%{$product}%"
                ]
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
    public static function detailSeller(int $sellerId): array|false{
        $sql = "
            SELECT si.*, p.code,
                p.barcode,
                p.name,
                p.stock,
                p.unit_name
            FROM seller_items si

            INNER JOIN products p
                ON p.id = si.product_id

            WHERE si.seller_id = ?

            ORDER BY si.id ASC
        ";

        return self::dynamicQuery(
            $sql,
            [
                $sellerId
            ]
        );
    }

        /* ==========================================================
     * TÍNH GIẢM GIÁ
     * <=100 : %
     * >100  : TIỀN
     * ========================================================== */
    private static function calculateDiscount(
        float $amount,
        float $discount
    ): float
    {
        if ($discount <= 0) {
            return $amount;
        }

        if ($discount <= 100) {
            return $amount - ($amount * $discount / 100);
        }

        return max(
            0,
            $amount - $discount
        );
    }

    /* ==========================================================
     * TÍNH TOÁN 1 DÒNG SẢN PHẨM
     * ========================================================== */
    private static function calculateItem(
        array $item
    ): array
    {
        $price = (float)$item['price'];
        $qty = (float)$item['quantity'];
        $discount = (float)($item['discount'] ?? 0);

        $originTotal = $price * $qty;

        $finalPrice = self::calculateDiscount(
            $price,
            $discount
        );

        $finalTotal = $finalPrice * $qty;

        $item['price'] = $price;
        $item['quantity'] = $qty;
        $item['discount'] = $discount;

        $item['origin_total'] = $originTotal;
        $item['final_price'] = $finalPrice;
        $item['total'] = $finalTotal;

        return $item;
    }

    /* ==========================================================
     * TÍNH TOÁN HÓA ĐƠN
     * ========================================================== */
    private static function calculateSummary(
        array $input
    ): array
    {
        $products = [];

        $totalAmount = 0;
        $discountAmount = 0;

        foreach ($input['products'] as $item) {

            $item = self::calculateItem($item);

            $products[] = $item;

            $totalAmount += $item['origin_total'];

            $discountAmount += (
                $item['origin_total'] - $item['total']
            );
        }

        $subTotal = $totalAmount - $discountAmount;

        $invoiceDiscount = (float)($input['discount'] ?? 0);

        $finalAmount = self::calculateDiscount(
            $subTotal,
            $invoiceDiscount
        );

        $discountAmount += (
            $subTotal - $finalAmount
        );

        $paidAmount = (float)($input['customer_pay'] ?? 0);

        $debtAmount = max(
            0,
            $finalAmount - $paidAmount
        );

        return [

            'products' => $products,

            'header' => [

                'total_amount' => $totalAmount,

                'discount_amount' => $discountAmount,

                'final_amount' => $finalAmount,

                'paid_amount' => $paidAmount,

                'debt_amount' => $debtAmount

            ]

        ];
    }

    /* ==========================================================
     * CHUYỂN PHƯƠNG THỨC THANH TOÁN
     * ========================================================== */
    private static function normalizePayment(
        int $payment
    ): string
    {
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
    private static function checkStocks(
        array $products
    ): array
    {
        if (self::ALLOW_NEGATIVE_STOCK) {
            return [];
        }

        $ids = array_column(
            $products,
            'id'
        );

        if (empty($ids)) {
            return [];
        }

        $placeholder = implode(
            ',',
            array_fill(
                0,
                count($ids),
                '?'
            )
        );

        $sql = "
            SELECT
                id,
                code,
                barcode,
                name,
                stock
            FROM products
            WHERE id IN ($placeholder)
        ";

        $rows = self::dynamicQuery(
            $sql,
            $ids
        );

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

            if (
                $stocks[$item['id']]['stock']
                < $item['quantity']
            ) {

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
    private static function insertHeader(
        array $header
    ): int
    {
        $id = self::insert(
            $header
        );

        if (!$id) {
            throw new Exception(
                "Không tạo được hóa đơn bán."
            );
        }

        return $id;
    }

    /* ==========================================================
     * THÊM CHI TIẾT HÓA ĐƠN
     * ========================================================== */
    private static function insertItems(
        int $sellerId,
        array $products
    ): void
    {

        foreach ($products as $item) {

            $detail = [

                'seller_id' => $sellerId,

                'product_id' => $item['id'],

                'quantity' => $item['quantity'],

                'price' => $item['price'],

                'discount' => $item['discount'],

                'final_price' => $item['final_price'],

                'total' => $item['total']

            ];

            $result = self::insertTo(
                static::$table_detail,
                $detail
            );

            if (!$result) {

                throw new Exception(
                    "Không lưu được chi tiết hóa đơn."
                );

            }

        }

    }

    /* ==========================================================
     * LƯU THANH TOÁN
     * ========================================================== */
    private static function insertPayment(
        int $sellerId,
        array $input,
        array $summary
    ): void
    {

        if (
            $summary['header']['paid_amount'] <= 0
        ) {
            return;
        }

        $payment = [

            'seller_id' => $sellerId,

            'method' => self::normalizePayment(
                (int)$input['payment']
            ),

            'amount' => $summary['header']['paid_amount']

        ];

        $result = self::insertTo(
            static::$table_payment,
            $payment
        );

        if (!$result) {

            throw new Exception(
                "Không lưu được thông tin thanh toán."
            );

        }

    }

        /* ==========================================================
     * CẬP NHẬT TỒN KHO
     * ========================================================== */
    private static function decreaseStocks(
        array $products
    ): void
    {
        foreach ($products as $item) {

            $sql = "
                UPDATE products
                SET stock = stock - ?
                WHERE id = ?
            ";

            $result = self::execQuery(
                $sql,
                [
                    $item['quantity'],
                    $item['id']
                ]
            );

            if (!$result) {
                throw new Exception(
                    "Không cập nhật được tồn kho."
                );
            }
        }
    }

    /* ==========================================================
     * TẠO HÓA ĐƠN BÁN
     * ========================================================== */
    public static function createSeller(
        array $input
    ): int
    {
        $duplicate = self::dupliObjSellers(
            $input['code']
        );

        if (!empty($duplicate)) {
            throw new Exception(
                "Mã hóa đơn đã tồn tại."
            );
        }

        $summary = self::calculateSummary(
            $input
        );

        self::checkStocks(
            $summary['products']
        );

        $header = [

            'code' => $input['code'],

            'customer_id' => $input['customer_id'] ?? null,

            'seller_date' => $input['seller_date'],

            'note' => $input['note'] ?? '',

            'discount' => $input['discount'] ?? 0,

            'total_amount' => $summary['header']['total_amount'],

            'discount_amount' => $summary['header']['discount_amount'],

            'final_amount' => $summary['header']['final_amount'],

            'paid_amount' => $summary['header']['paid_amount'],

            'debt_amount' => $summary['header']['debt_amount'],

            'status' => $summary['header']['debt_amount'] > 0
                ? 'debt'
                : 'completed'

        ];

        self::beginTransaction();

        try {

            $sellerId = self::insertHeader(
                $header
            );

            self::insertItems(
                $sellerId,
                $summary['products']
            );

            self::insertPayment(
                $sellerId,
                $input,
                $summary
            );

            self::decreaseStocks(
                $summary['products']
            );

            self::commit();

            return $sellerId;

        } catch (Exception $e) {

            self::rollback();

            throw $e;

        }
    }

}