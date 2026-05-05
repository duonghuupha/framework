<?php
class ProductsController extends Controller{
    protected $productsModel; // khai báo su dung Model
    public function __construct(){
        $this->productsModel = new Products();
    }

    function index(){
        $payload = $this->checkToken();
        $input = Input::all();
        $params = [
            'page' => $input['page'] ?? 1,
            'limit' => $input['limit'] ?? 20,
            'search' => [
                'name' => $input['search']['name'] ?? ''
            ],
            'filters' => [],
            'order' => [
                'id' => 'DESC'
            ]
        ];
        $result = $this->productsModel->listProducts($params);
        return $this->json($result);
    }

    function add(){
        $payload = $this->checkToken();
        $input = Input::all();
        if(count($this->productsModel->dupliObjProduct($input['code'], 0)) > 0){
            return $this->json([], 'error', 'Mã sản phẩm đã tồn tại');
        }else{
            $data = [
                'code' => $input['code'] ?? '',
                'name' => $input['name'] ?? '',
                'unit_id' => $input['unit_id'] ?? 0,
                'category_id' => $input['category_id'] ?? 0,
                'import_price' => $input['import_price'] ?? 0,
                'sell_price' => $input['sell_price'] ?? 0,
                'stock' => $input['stock'] ?? 0,
                'is_active' => 1,
            ];
            $newProductId = $this->productsModel->addProduct($data);
            return $this->json(['new_product_id' => $data]);
        }
    }

    function update($id){
        $payload = $this->checkToken();
        $input = Input::all();
        if(count($this->productsModel->dupliObjProduct($input['code'], (int)$id)) > 0){
            return $this->json([], 'error', 'Mã sản phẩm đã tồn tại');
        }else{
            $data = [
                'code' => $input['code'] ?? '',
                'name' => $input['name'] ?? '',
                'unit_id' => $input['unit_id'] ?? 0,
                'category_id' => $input['category_id'] ?? 0,
                'import_price' => $input['import_price'] ?? 0,
                'sell_price' => $input['sell_price'] ?? 0,
                'stock' => $input['stock'] ?? 0,
                'is_active' => 1,
            ];
            $updated = $this->productsModel->updateProduct((int)$id, $data);
            return $this->json(['updated' => $updated]);
        }
    }

    function delete($id){
        $payload = $this->checkToken();
        $deleted = $this->productsModel->deleteProduct((int)$id);
        return $this->json(['deleted' => $deleted]);
    }

    function combo(){
        $payload = $this->checkToken();
        $input = Input::all();
        $result = $this->productsModel->listComboProduct($input['search']['name']);
        return $this->json($result);
    }
}
?>