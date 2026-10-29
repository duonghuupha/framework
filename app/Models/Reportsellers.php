
<?php

class ReportSellers extends Model
{
    protected static string $table = "sellers";
    protected static string $view = "v_sellers";

    /**
     * Báo cáo bán hàng.
     * Chỉ đọc dữ liệu, không thay đổi hóa đơn.
     */
    public static function reportSellers(array $params = []): array
    {
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

        // Chỉ lấy hóa đơn chưa bị hủy.
        $params['advanced'][] = [
            'type' => 'raw',
            'sql' => 'active = ?',
            'params' => [0]
        ];

        // Lọc theo khách hàng.
        if ($customer !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => '(customer_name LIKE ? OR customer_phone LIKE ?)',
                'params' => [
                    "%{$customer}%",
                    "%{$customer}%"
                ]
            ];
        }

        // Lọc theo sản phẩm có trong hóa đơn.
        if ($product !== '') {
            $params['advanced'][] = [
                'type' => 'exists',
                'sql' => '
                    SELECT 1
                    FROM seller_items si
                    INNER JOIN products p
                        ON p.id = si.product_id
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

        // Lọc ngày bắt đầu và ngày kết thúc.
        if ($dateFrom !== '' && $dateTo !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 'DATE(created_at) BETWEEN ? AND ?',
                'params' => [$dateFrom, $dateTo]
            ];
        } elseif ($dateFrom !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 'DATE(created_at) >= ?',
                'params' => [$dateFrom]
            ];
        } elseif ($dateTo !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 'DATE(created_at) <= ?',
                'params' => [$dateTo]
            ];
        }

        return self::paginateAdv(static::$view, $params);
    }
}
