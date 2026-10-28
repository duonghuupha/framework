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
        $page  = max(1, (int)($params['page'] ?? 1));
        $limit = max(1, min(200, (int)($params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $search = $params['search'] ?? [];
        $code = trim((string)($search['code'] ?? ''));
        $customer = trim((string)($search['customer'] ?? ''));
        $product = trim((string)($search['product'] ?? ''));
        $dateStart = trim((string)($search['date_start'] ?? ''));
        $dateEnd = trim((string)($search['date_end'] ?? ''));

        // Báo cáo chỉ lấy hóa đơn còn hiệu lực; active = 1 là đã hủy.
        $where = ['vs.active = ?'];
        $bind = [0];

        if ($code !== '') {
            $where[] = 'vs.code LIKE ?';
            $bind[] = '%' . $code . '%';
        }

        if ($customer !== '') {
            $where[] = '(vs.customer_name LIKE ? OR vs.customer_phone LIKE ?)';
            $bind[] = '%' . $customer . '%';
            $bind[] = '%' . $customer . '%';
        }

        if ($product !== '') {
            $where[] = 'EXISTS (
                SELECT 1
                FROM seller_items si
                INNER JOIN products p ON p.id = si.product_id
                WHERE si.seller_id = vs.id
                  AND (p.name LIKE ? OR p.code LIKE ?)
            )';
            $bind[] = '%' . $product . '%';
            $bind[] = '%' . $product . '%';
        }

        if ($dateStart !== '') {
            $where[] = 'DATE(vs.created_at) >= ?';
            $bind[] = $dateStart;
        }
        if ($dateEnd !== '') {
            $where[] = 'DATE(vs.created_at) <= ?';
            $bind[] = $dateEnd;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        // Đếm số hóa đơn khớp điều kiện, không thay đổi dữ liệu.
        $countSql = "
            SELECT COUNT(*) AS total
            FROM v_sellers vs
            {$whereSql}
        ";
        $countRows = self::dynamicQuery($countSql, $bind, 0);
        $total = (int)($countRows[0]['total'] ?? 0);

        // Lấy dữ liệu trang hiện tại.
        $dataSql = "
            SELECT
                vs.id,
                vs.code,
                vs.created_at,
                vs.customer_name,
                vs.total_amount,
                vs.discount_amount,
                vs.final_amount
            FROM v_sellers vs
            {$whereSql}
            ORDER BY vs.created_at DESC, vs.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $rows = self::dynamicQuery($dataSql, $bind, 0);

        return [
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => (int)ceil($total / $limit),
            ],
            'rows' => $rows,
        ];
    }
}
