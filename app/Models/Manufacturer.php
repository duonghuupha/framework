<?php
class Manufacturer extends Model{
    protected static string $table = "dm_suppliers"; // bảng nhà cung cấp

    public static function listSupplier(array $params = []) : array{
        return self::paginate(static::$table, $params);
    }

    public static function addSupplier(array $data) : int|false{
        return self::insert($data);
    }

    public static function updateSupplier(int $id, array $data) : int|false{
        return self::update($id, $data);
    }

    public static function deleteSupplier(int $id) : int|false{
        return self::delete($id);
    }s

    public static function listCombo() : array{
        $sql = "SELECT id AS value, name AS label FROM " . static::$table;
        return self::dynamicQuery($sql);
    }

    public static function getDebtSupplier(int $supplierId): array{
        // tong cong no phat sinh
        $sqlImport = "SELECT COALESCE(SUM(debt_amount), 0) total FROM imports WHERE supplier_id = ?";
        $import = self::dynamicQuery($sqlImport, [$supplierId]);
        $importDebt = (float)($import[0]['total'] ?? 0);

        // tong tien da tra cong no
        $sqlExpense = "SELECT COALESCE(SUM(total_amount), 0) total FROM expenses WHERE supplier_id = ? AND types = 'debt' AND status = 1";
        $expense = self::dynamicQuery($sqlExpense, [$supplierId]);
        $expenseAmount = (float)($expense[0]['total'] ?? 0);

        return [
            'import_debt' => $importDebt,
            'expense_amount' => $expenseAmount,
            'current_debt' => $importDebt - $expenseAmount
        ];
    }
}
?>