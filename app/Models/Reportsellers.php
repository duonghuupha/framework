<?php
/**
 * Model báo cáo bán hàng của IVYVET.
 * Chỉ đọc dữ liệu hiện có; không thêm, sửa hoặc xóa dữ liệu.
 */
class Reportsellers extends Model
{
    protected static string $table = 'sellers';

    /**
     * Tổng hợp danh sách hóa đơn theo điều kiện báo cáo.
     *
     * $params = [
     *   'page' => 1,
     *   'limit' => 20,
     *   'search' => [
     *      'code' => '', 'customer' => '', 'product' => '',
     *      'date_start' => 'YYYY-MM-DD', 'date_end' => 'YYYY-MM-DD'
     *   ]
     * ]
     *
     * Trả về cấu trúc: ['pagination' => [...], 'rows' => [...]]
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

}
