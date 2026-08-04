<?php
class Receipts extends Model{
	protected static string $table = "receipts";
	protected static string $view = "v_receipts";


	/**
	 * Danh sach phieu thu
	 */
	public static function listReceipts(array $params = []): array {
		$code = trim($params['search']['code'] ?? '');
		$customer = trim($params['search']['code'] ?? '');
		$dateFrom = $params['search']['date_start'] ?? '';
		$dateTo = $params['search']['date_end'] ?? '';
		unset(
			$params['search']['code'],
			$params['search']['customer'],	
			$params['search']['date_start'],
			$params['search']['date_end']
		);
		if($code !== ''){
			$params['advanced'][] = [
				'type' => 'raw',
				'sql' => 'code LIKE ?',
				'params' => ["%$code%"]
			];
		}
		if($customer !== ''){
			$params['advanced'][] = [
				'type' => 'raw',
				'sql' => '(customer_name LIKE ? OR customer_phone LIKE ?)',
				'params' => [
					"%$customer%",
					"%$customer%"
				]
			];
		}
		if ($dateFrom && $dateTo) {
            $params['advanced'][] = [
                'type'   => 'raw',
                'sql'    => 'DATE(date_receipt) BETWEEN ? AND ?',
                'params' => [$dateFrom, $dateTo]
            ];
        }
		return self::paginateAdv(static::$view, $params);
	}

	/**
	 * Kiem tra trung ma hoa don
	 */
	public static function checkDulicateCode(string $code, ?int $receiptsId = 0): array|false{
		if($receiptsId == 0){
			return self::where("code", $code);
		}
		$sql = "SELECT* FROM receipts WHERE code = ? AND id <> ?";
		return self::dynamicQuery($sql, [$code, $receiptsId]);
	}

	/**
	 * Them moi phieu thu
	 **/
	public static function createReceipt(array $data){
		$code = trim($data['code'] ?? '');
		if($code === ''){
			throw new Exception('Mã phiếu thu không được để trống');
		}

		// check trung ma
		if(self::checkDulicateCode($code)){
			throw new Exception("Mã phiếu thu đã tồn tại");
		}

		$cashAmount = (float) ($data['cash_amount'] ?? 0);
		$bankAmount = (float) ($data['bank_amount'] ?? 0);

		if($cashAmount < 0 || $bankAmount < 0){
			throw new Exception("Số tiền thu không hợp lệ");
		}

		$totalAmount = $cashAmount + $bankAmount;
		if($totalAmount <= 0){
			throw new Exception("Tổng tiền thu phải lớn hơn 0");
		}

		$datereceipt = trim($data['date_receipt'] ?? '');
		if(!empty($datereceipt)){
			$objectDate = DateTime::createFromFormat('d/m/Y', $datereceipt);
			if(!$objectDate){
				throw new Exception("Ngày phiếu thu không hợp lệ");
			}
			$datereceipt = $objectDate->format('Y-m-d');
		}else{
			$datereceipt = date("Y-m-d");
		}

		$receiptData = [
			'code' => $code,
			'customer_id' => $data['customer_id'] ?? null,
			'types' => $data['types'] ?? 'debt',
			'cash_amount' => $cashAmount,
			'bank_amount' => $bankAmount,
			'total_amount' => $totalAmount,
			'date_receipt' => $datereceipt,
			'note' => trim($data['note'] ?? '') 
		];

		$result = self::insertTo('receipts', $receiptData);
		if(!$result){
			throw new Exception("Không thể tạo phiếu thu");
		}
		return $result;
	}

	/**
	 * Cap nhat phieu thu
	 */
	public static function updateReceipt(int $id, array $data){
		if($id <= 0){
			throw new Exception("Phiếu thu không hợp lệ");
		}

		$code = trim($data['code'] ?? '');
		if($code === ''){
			throw new Exception("Mã phiếu không thể để trống");
		}

		// CHeck trung nhung bỏ qua chinh phieu dang sửa
		if(self::checkDulicateCode($code, $id)){
			throw new Exception("Mã phiếu thu đã rồn tại");
		}

		$cashAmount = (float) ($data['cash_amount'] ?? 0);
		$bankAmount = (float) ($data['bank_amount'] ?? 0);

		if($cashAmount < 0 || $bankAmount < 0){
			throw new Exception("SỐ tiền thu không hợp lý");
		}

		$totalAmount = $cashAmount + $bankAmount;
		if($totalAmount <= 0){
			throw new Exception("TỔng tiền phải lớn hơn 0");
		}

		$datereceipt = trim($data['date_receipt'] ?? '');
		if(!empty($datereceipt)){
			$objectDate = DateTime::createFromFormat('d/m/Y', $datereceipt);
			if(!$objectDate){
				throw new Exception("Ngày phiếu thu không hợp lệ");
			}
			$datereceipt = $objectDate->format('Y-m-d');
		}else{
			$datereceipt = date("Y-m-d");
		}

		$receiptData = [
			'code'         => $code,
	        'customer_id'  => $data['customer_id'] ?? null,
	        'types'         => $data['type'] ?? 'debt',

	        'cash_amount'  => $cashAmount,
	        'bank_amount'  => $bankAmount,
	        'total_amount' => $totalAmount,

	        'date_receipt' => $datereceipt,
	        'note'         => trim($data['note'] ?? ''),
		];

		$result = self::update($id, $receiptData);
		if(!$result){
			throw new Exception("Không thẻ cập nhật phiếu thu");
		}

		return $result;
	}
}
?>