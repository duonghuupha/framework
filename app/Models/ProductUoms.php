<?php
class ProductUoms extends Model{
	protected static string $table = "product_uoms";

	/**
	 *  Lấy base Uom cua san pham
	 **/
	public static function getBaseUom(int $productId) : array|false{
		$sql = "SELECT * FROM product_uoms WHERE product_id = ? AND is_base = 1 LIMIT 1";
		return self::dynamicQuery($sql, [$productId]);
	}

	/**
	 * Tao base Uom cho san pham
	 **/
	public static function createBase(int $productId, int $unitId, float $salePrice, int $isActive = 1) : int|false{
		$data = [
			'product_id' => $productId,
			'unit_id' => $unitId,
			'conversion_factor' => 1,
			'sale_price' => $salePrice,
			'is_base' => 1,
			'is_active' => $isActive 
		];
		return self::insert($data);
	}
}
?>