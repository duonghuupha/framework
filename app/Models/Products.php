<?php
class Products extends Model{
    protected static string $view_product = "v_products"; // view sản phẩm
    protected static string $table = "products"; // bảng sản phẩm

    public static function listProducts(array $params = []) : array{
        return self::paginate(static::$view_product, $params);
    }

    public static function dupliObjProduct($code, $id) : array|false{
        if($id === 0){
            return self::where("code", $code);
        }else{
            $sql = "SELECT * FROM " . static::$table . " WHERE code = ? AND id != ?";
            $params = [$code, $id];
            return self::dynamicQuery($sql, $params);
        }
    }

    public static function addProduct(array $data) : int|false{
        return self::insert($data);
    }

    public static function createProduct(array $data) : int|false{
        self::beginTransaction();
        try{
            // tao san pham
            $productId = self::addProduct($data);
            if(!$productId){
                self::rollback();
                return false;
            }
            // tao base Uom
            $baseUomId = ProductUoms::createBase(
                (int)$productId, (int)$data['unit_id'], (float)$data['sell_price'], (int)$data['is_active']
            );
            if(!$baseUomId){
                self::rollback();
                return false;
            }
            // hoan tat transaction
            self::commit();
            return (int)$productId;
        }catch(Exception $e){
            self::rollback();
            throw $e;
        }
    }

    public static function updateProduct(int $id, array $data) : int|false{
        return self::update($id, $data);
    }

    public static function deleteProduct(int $id) : int|false{
        return self::delete($id);
    }

    public static function listComboProduct($name) : array|false{
        $sql = "SELECT id, code, name, import_price, sell_price, stock, (SELECT dm_units.name FROM dm_units WHERE dm_units.id = unit_id) AS unit 
                FROM " . static::$table . " WHERE (code LIKE '%$name%' OR name LIKE '%$name%') AND is_active = 1";
        return self::dynamicQuery($sql);
    }
}
?>