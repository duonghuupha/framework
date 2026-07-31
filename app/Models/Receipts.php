<?php
class Receipts extends Model{
	protected static string $table = "receipts";


	/**
	 * Danh sach phieu thu
	 */
	public static function listReceipts(array $params = []): array {

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

		$receiptData = [
			'code' => $code,
			'customer_id' => $data['customer_id'] ?? null,
			'types' => $data['types'] ?? 'debt',
			'cash_amount' => $cashAmount,
			'bank_amount' => $bankAmount,
			'total_amount' => $totalAmount,
			'date_receipt' => $data['date_receipt'] ?? date('Y-m-d'),
			'note' => trim($data['note'] ?? '') 
		];

		$result = self::insertTo('receipts', $receiptData);
		if(!result){
			throw new Exception("Không thể tạo phiếu thu");
		}
		return $result;
	}
}
?>