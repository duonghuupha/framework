<?php
/**
 * Controller báo cáo bán hàng của IVYVET.
 * Chỉ nhận điều kiện lọc và trả kết quả truy vấn; không ghi dữ liệu.
 */
class ReportsellersController extends Controller
{
    protected $reportSellersModel;

    public function __construct()
    {
        $this->reportSellersModel = new Reportsellers();
    }

    /**
     * Danh sách báo cáo bán hàng có lọc và phân trang.
     * Điều kiện ngày chấp nhận YYYY-MM-DD (input type=date) hoặc d/m/Y.
     */
    public function index()
    {
        try {
            $this->checkToken();
            $input = Input::all();
            $searchInput = $input['search'] ?? [];

            $dateStart = $this->normalizeReportDate($searchInput['date_start'] ?? '');
            $dateEnd = $this->normalizeReportDate($searchInput['date_end'] ?? '');

            if ($dateStart !== '' && $dateEnd !== '' && $dateStart > $dateEnd) {
                return $this->json([], 'error', 'Ngày bắt đầu không được lớn hơn ngày kết thúc.');
            }

            $params = [
                'page' => max(1, (int)($input['page'] ?? 1)),
                'limit' => max(1, min(200, (int)($input['limit'] ?? 20))),
                'search' => [
                    'code' => trim((string)($searchInput['code'] ?? '')),
                    'customer' => trim((string)($searchInput['customer'] ?? '')),
                    'product' => trim((string)($searchInput['product'] ?? '')),
                    'date_start' => $dateStart,
                    'date_end' => $dateEnd,
                ],
            ];

            $result = ReportSellers::reportSellers($params);
            return $this->json($result);
        } catch (Exception $e) {
            return $this->json([], 'error', $e->getMessage());
        }
    }

    /** Chuẩn hóa ngày về YYYY-MM-DD; chuỗi rỗng được giữ nguyên. */
    private function normalizeReportDate($value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $formats = ['!Y-m-d', '!d/m/Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $value);
            $errors = DateTime::getLastErrors();
            $valid = $date !== false
                && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));

            if ($valid) {
                $expectedFormat = $format === '!Y-m-d' ? 'Y-m-d' : 'd/m/Y';
                if ($date->format($expectedFormat) === $value) {
                    return $date->format('Y-m-d');
                }
            }
        }

        throw new InvalidArgumentException('Ngày lọc không hợp lệ: ' . $value);
    }


    /**
     * Danh sách hóa đơn theo ngày cho Dialog báo cáo
     */
    public function byDate($dateInput)
    {
        try {
            $this->checkToken();

            //$input = Input::all();

            $date = trim((string)($dateInput?? ''));

            if ($date === '') {
                throw new InvalidArgumentException(
                    'Vui lòng truyền ngày hóa đơn.'
                );
            }

            $params = [
                'page' => $input['page'] ?? 1,
                'limit' => $input['limit'] ?? 20
            ];

            $result = $this->sellerModel->listSellersByDate(
                $date,
                $params
            );

            return $this->json($result);
        } catch (Exception $e) {
            return $this->json([], 'error', $e->getMessage());
        }
    }

}
