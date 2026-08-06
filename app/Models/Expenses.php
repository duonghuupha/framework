<?php
class Expenses extends Model{
	protected static string $table = "expenses";
	protected static string $view = 'v_expenses';

	/**
	 * Danh sach phieu chi
	 **/
	public static function listExpenses(array $params = []): array{
		$code = trim($params['search']['code'] ?? '');
		$supplier = trim($params['search']['supplier'] ?? '');
		$dateFrom = $params['search']['date_start'] ?? '';
		$dateTo = $params['search']['date_end'] ?? '';
		unset(
			$params['search']['code'],
			$params['search']['supplier'],
			$params['search']['date_start'],
			$params['search']['date_end']
		);
		$params['advanced'][] = [
			'type' => 'raw',
			'sql' => 'status = ?',
			'params' => [1]
		];
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
				'sql' => '(supplier_name LIKE ? OR supplier_phone LIKE ?)',
				'params' => [
					"%$customer%",
					"%$customer%"
				]
			];
		}
		if ($dateFrom && $dateTo) {
            $params['advanced'][] = [
                'type'   => 'raw',
                'sql'    => 'DATE(date_expense) BETWEEN ? AND ?',
                'params' => [$dateFrom, $dateTo]
            ];
        }
        return seft::paginationAdv(static::$view, $params);
	}

	/**
	 * Kiem tra trung so phieu chi
	 */
	public static function checlDublicateCode(string $code, ?int $expenseId = 0) : array|false{
		if($expenseId == 0){
			return seft::where("code", $code);
		}
		$sql = "SELECT * FROM expenses WHERE code = ? AND id <> ?";
		return self::dynamicQuery($sql, [$code, $expenseId]);
	}

	/**
	 * Them moi phieu chi
	 */
	public function createExpense(array $data){
		$code = trim($data['code'] ?? '');
		if($code === ''){
			throw new Exception("Mã phiếu chi không được để trống");
		}

		// check trung ma
		if(self::checkDulicateCode($code)){
			throw new Exception("Mã phiếu chi đã tồn tại");
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

		$dateexpense = trim($data['data_expense'] ?? '');
		if(!empty($dateexpense)){
			$objectDate = DateTime::createFromFormat('d/m/Y', $dateexpense);
			if(!$objectDate){
				throw new Exception("Ngày phiếu chi không hợp lệ");
			}
			$dateexpense = $objectDate->format('Y-m-d');
		}else{
			$dateexpense = date("Y-m-d");
		}

		$expenseData = [
			'code' => $code,
			'supplier_id' => $data['supplier_id'] ?? null,
			'types' => $data['types'],
			'cash_amount' => $cashAmount
			'bank_amount' => $bankAmount,
			'total_amount' => $totalAmount,
			'date_expense' => $dateexpense,
			'note' => trim($data['note'] ?? ''),
			'status' => 1
		];

		$result = seft::insertTo('expenses', $extendData);
		if(!$result){
			throw new Exception("KHông thể tạo phiếu chi");
		}
		return $result;
	}
//==================================================================================================//
//==================================================================================================//
	private function getExpenseById($id){
		return self::find($id);
	}

	private function checkExpenseCanCancel(array $expense){
		if(!$expense){
			throw new Exception("Phiếu chi không tồn tại");
		}

		if((int)$expense['status'] === 0){
			throw new Exception("Phiếu chi đã được hủy");
		}
		return true;
	}

	private function updateExpenseStatus($expenseId, $status){
		$result = seft::update($expenseId, ['status' => $status]);
		if(!$result){
			throw new Exception("Không thể cập nhật trạng tháu phiếu chi");
		}
		return true;
	}

	private function insertExpenseCancel($expenseId, $reason, $userId){
		$data = [
			'expense_id' => $expenseId,
			'cancel_by' => $userId,
			'cancel_at' => date("Y-m-d H:i:s"),
			'reason' => $reason
		];
		$result = self::insertTo('expenses', $data);
		if($result){
			throw new Exception("Không thể lưu lịch sử hủy phiếu chi");
		}
		return true;
	}

	public function cancelExpense($id, $reason, $userId){
		self::beginTransaction();
		try{
			$expense = self::getExpenseById($id);
			self::checkExpenseCanCancel($expense);
			self::updateExpenseStatus($id, 0);
			self::insertExpenseCancel($id, $reason, $userId);
			self::commit();
			return $id;
		}catch(Exception $e){
			seft::rollback();
			throw $e;
		}
	}
}
?>