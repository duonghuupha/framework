<?php

class Imports extends Model
{
    protected static string $table = "imports";
    protected static string $table_detail = "import_items";
    protected static string $view_import = "v_imports";

    /* ==========================================================
     * DANH SÁCH PHIẾU NHẬP
     * ========================================================== */
    public static function listImports(array $params = []): array
    {
        $product = $params['search']['product'] ?? '';

        unset($params['search']['product']);

        if (!empty($product)) {

            $params['advanced'][] = [
                'type' => 'exists',
                'sql' => "
                    SELECT 1
                    FROM import_items d
                    INNER JOIN products p
                        ON p.id = d.product_id
                    WHERE d.import_id = v_imports.id
                    AND p.name LIKE ?
                ",
                'params' => [
                    "%{$product}%"
                ]
            ];
        }

        return self::paginateAdv(
            static::$view_import,
            $params
        );
    }

    /* ==========================================================
     * KIỂM TRA TRÙNG MÃ
     * ========================================================== */
    public static function dupliObjImports($code, $id): array|false
    {
        if ($id == 0) {
            return self::where("code", $code);
        }

        $sql = "
            SELECT *
            FROM " . static::$table . "
            WHERE code = ?
            AND id != ?
        ";

        return self::dynamicQuery($sql, [
            $code,
            $id
        ]);
    }

    /* ==========================================================
     * TẠO PHIẾU NHẬP
     * ========================================================== */
    public static function createImport(array $input): int
    {
        if (count(self::dupliObjImports($input['code'], 0)) > 0) {
            throw new Exception("Mã phiếu nhập đã tồn tại.");
        }

        $totalAmount = self::calculateTotal($input['products']);

        $header = [
            'code'         => $input['code'],
            'supplier_id'  => $input['supplier_id'],
            'created_at'   => $input['created_at'] . ' ' . date('H:i:s'),
            'total_amount' => $totalAmount,
            'paid_amount'  => 0,
            'debt_amount'  => $totalAmount,
            'status'       => 'debt',
            'note'         => $input['ghi_chu'] ?? ''
        ];

        self::beginTransaction();

        try {

            $importId = self::insertHeader($header);

            self::insertItems(
                $importId,
                $input['products']
            );

            self::updateProductStocks(
                $input['products']
            );

            self::commit();

            static::bumpTableCacheVersion();

            return $importId;

        } catch (Exception $e) {

            self::rollBack();

            throw $e;

        }
    }

    /* ==========================================================
     * TÍNH TỔNG TIỀN
     * ========================================================== */
    private static function calculateTotal(array $products): float
    {
        $total = 0;

        foreach ($products as $item) {

            $total += ($item['quantity'] * $item['price']);

        }

        return $total;
    }

    /* ==========================================================
     * THÊM HEADER
     * ========================================================== */
    private static function insertHeader(array $header): int
    {
        $id = self::insert($header);

        if (!$id) {
            throw new Exception("Không tạo được phiếu nhập.");
        }

        return $id;
    }

    /* ==========================================================
     * THÊM CHI TIẾT
     * ========================================================== */
    private static function insertItems(
        int $importId,
        array $products
    ): void
    {
        foreach ($products as $item) {

            $detail = [

                'import_id' => $importId,

                'product_id' => $item['id'],

                'qty' => $item['quantity'],

                'price' => $item['price'],

                'total' => $item['quantity'] * $item['price']

            ];

            $result = self::insertTo(
                static::$table_detail,
                $detail
            );

            if (!$result) {

                throw new Exception(
                    "Không lưu được chi tiết phiếu nhập."
                );

            }

        }
    }

    /* ==========================================================
     * CẬP NHẬT TỒN KHO
     * ========================================================== */
    private static function updateProductStocks(array $products): void
    {
        foreach ($products as $item) {

            self::increaseStock(
                $item['id'],
                $item['quantity'],
                $item['price']
            );

        }
    }

    /* ==========================================================
     * CỘNG TỒN + CẬP NHẬT GIÁ NHẬP
     * ========================================================== */
    private static function increaseStock(
        int $productId,
        float $qty,
        float $importPrice
    ): void
    {
        $sql = "
            UPDATE products
            SET
                stock = stock + ?,
                import_price = ?
            WHERE id = ?
        ";

        $result = self::execQuery(
            $sql,
            [
                $qty,
                $importPrice,
                $productId
            ]
        );

        if (!$result) {

            throw new Exception(
                "Không cập nhật được tồn kho."
            );

        }
    }

    /* ==========================================================
     * CHI TIẾT PHIẾU NHẬP
     * ========================================================== */
    public static function getImportItems($id)
    {
        $sql = "
            SELECT
                ii.*,
                p.code product_code,
                p.name product_name,
                u.name unit_name
            FROM import_items ii
            LEFT JOIN products p
                ON p.id = ii.product_id
            LEFT JOIN dm_units u
                ON u.id = p.unit_id
            WHERE ii.import_id = ?
            ORDER BY ii.id ASC
        ";

        return self::dynamicQuery(
            $sql,
            [$id]
        );
    }
}