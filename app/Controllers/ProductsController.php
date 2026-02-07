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
                'title' => $input['search']['name'] ?? ''
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
                'title' => $input['title'] ?? '',
                'donvitinh_id' => $input['donvitinh_id'] ?? 0,
                'loaisanpham_id' => $input['loaisanpham_id'] ?? 0,
                'imp_price' => $input['imp_price'] ?? 0,
                'exp_price' => $input['exp_price'] ?? 0,
                'stock' => $input['stock'] ?? 0,
                'is_vacxin' => $input['is_vacxin'] ?? 0,
                'active' => 1,
                'image' => ''
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
                'title' => $input['title'] ?? '',
                'donvitinh_id' => $input['donvitinh_id'] ?? 0,
                'loaisanpham_id' => $input['loaisanpham_id'] ?? 0,
                'imp_price' => $input['imp_price'] ?? 0,
                'exp_price' => $input['exp_price'] ?? 0,
                'stock' => $input['stock'] ?? 0,
                'is_vacxin' => $input['is_vacxin'] ?? 0,
                'image' => ''
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
}
?>