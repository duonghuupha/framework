<?php
class Customer extends Model{
    protected static string $table = "customers"; //bảng khách hàng
    //protected static string $table_debt = "tbl_thu"; // bảng thu
    //protected static string $table_sellers = "tbl_sellers"; // bảng bán hàng

    public static function listCustomer(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function dupliObjCustomer($code, $id) : array|false{
        if($id === 0){
            return self::where("code", $code);
        }else{
            $sql = "SELECT * FROM " . static::$table . " WHERE code = ? AND id != ?";
            $params = [$code, $id];
            return self::dynamicQuery($sql, $params);
        }
    }

    public static function addCustomer(array $data) : int|false{
        return self::insert($data);
    }

    public static function updateCustomer(int $id, array $data) : int|false{
        return self::update($id, $data);
    }

    public static function deleteCustomer(int $id) : int|false{
        return self::delete($id);
    }

    public static function listComboCustomer($name) : array|false{
        $sql = "SELECT id, code, name, address, phone, is_default FROM " . static::$table . " WHERE name LIKE ? OR phone LIKE ?";
        $params = ["%$name%", "%$name%"];
        return self::dynamicQuery($sql, $params);
    }

    public static function getDebtCustomer(int $customerId): array{
        // tong cong no phat sinh
        $sqlSeller = "SELECT COALESCE(SUM(debt_amount), 0) total FROM sellers WHERE customer_id = ?";
        $seller = self::dynamicQuery($sqlSeller, [$customerId]);
        $sellerDebt = (float)($seller[0]['total'] ?? 0);

        // tong tien da thu cong no
        $sqlReceipt = "SELECT COALESCE(SUM(total_amount), 0) total FROM receipts WHERE customer_id = ? AND types = 'debt'";
        $receipt = self::dynamicQuery($sqlReceipt, [$customerId]);
        $receiptAmount = (float)($receipt[0]['total'] ?? 0);

        return [
            'seller_debt' => $sellerDebt,
            'receipt_amount' => $receiptAmount,
            'current_debt' => $sellerDebt - $receiptAmount
        ];
    }
}
?>