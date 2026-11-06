
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
        $dateFrom = $params['search']['date_start'] ?? '';
        $dateTo   = $params['search']['date_end'] ?? '';
        $customer = trim($params['search']['customer'] ?? '');
        $product  = trim($params['search']['product'] ?? '');
        $code     = trim($params['search']['code'] ?? '');

        // Không đưa các bộ lọc đặc biệt vào SEARCH mặc định.
        unset(
            $params['search']['date_start'],
            $params['search']['date_end'],
            $params['search']['customer'],
            $params['search']['product'],
            $params['search']['code']
        );

        $params['advanced'] = $params['advanced'] ?? [];

        // Chỉ lấy hóa đơn chưa hủy.
        $params['advanced'][] = [
            'type' => 'raw',
            'sql' => 's.active = ?',
            'params' => [0]
        ];

        // Lọc mã hóa đơn trước khi tổng hợp.
        if ($code !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 's.code LIKE ?',
                'params' => ["%{$code}%"]
            ];
        }

        // Lọc khách hàng trước khi tổng hợp.
        if ($customer !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => '(s.customer_name LIKE ? OR s.customer_phone LIKE ?)',
                'params' => ["%{$customer}%", "%{$customer}%"]
            ];
        }

        // Chỉ tổng hợp các hóa đơn có sản phẩm phù hợp.
        if ($product !== '') {
            $params['advanced'][] = [
                'type' => 'exists',
                'sql' => '
                    SELECT 1
                    FROM seller_items si
                    INNER JOIN products p ON p.id = si.product_id
                    WHERE si.seller_id = s.id
                      AND (p.name LIKE ? OR p.code LIKE ?)
                ',
                'params' => ["%{$product}%", "%{$product}%"]
            ];
        }

        // Lọc khoảng ngày trước khi nhóm.
        if ($dateFrom !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 's.created_at >= ?',
                'params' => [$dateFrom . ' 00:00:00']
            ];
        }

        if ($dateTo !== '') {
            $params['advanced'][] = [
                'type' => 'raw',
                'sql' => 's.created_at < ?',
                'params' => [
                    date('Y-m-d', strtotime($dateTo . ' +1 day')) . ' 00:00:00'
                ]
            ];
        }

        $params['select'] = '
            DATE(s.created_at) AS report_date,
            COUNT(s.id) AS invoice_count,
            SUM(s.total_amount) AS total_amount,
            SUM(s.discount_amount) AS discount_amount,
            SUM(s.final_amount) AS final_amount
        ';

        $params['group_by'] = ['DATE(s.created_at)'];
        $params['order'] = ['report_date' => 'DESC'];

        return self::paginateGroupByAdv(
            'v_sellers s',
            $params
        );
    }


    /* ==========================================================
     * DANH SÁCH HÓA ĐƠN THEO NGÀY
     * Dùng cho Dialog báo cáo bán hàng
     * Chỉ nhận ngày và tham số phân trang
     * ========================================================== */
    public static function listSellersByDate(
        string $date,
        array $params = []
    ): array {
        // Kiểm tra định dạng ngày Y-m-d.
        $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

        if (
            !$dateObject ||
            $dateObject->format('Y-m-d') !== $date
        ) {
            throw new InvalidArgumentException(
                'Ngày hóa đơn không hợp lệ. Định dạng yêu cầu: Y-m-d.'
            );
        }

        $dateStart = $dateObject->format('Y-m-d 00:00:00');

        $dateEnd = (clone $dateObject)
            ->modify('+1 day')
            ->format('Y-m-d 00:00:00');

        // Chỉ giữ tham số phân trang.
        $params = [
            'page' => max(1, (int)($params['page'] ?? 1)),
            'limit' => max(1, min(200, (int)($params['limit'] ?? 20))),
            'filters' => [],
            'search' => [],
            'advanced' => [
                [
                    'type' => 'raw',
                    'sql' => 'active = ?',
                    'params' => [0]
                ],
                [
                    'type' => 'raw',
                    'sql' => 'created_at >= ? AND created_at < ?',
                    'params' => [$dateStart, $dateEnd]
                ]
            ],
            'order' => [
                'created_at' => 'DESC',
                'id' => 'DESC'
            ]
        ];

        return self::paginateAdv(static::$view, $params);
    }

}
