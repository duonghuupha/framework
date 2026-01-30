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
                'title' => $input['search']['name'] ?? '',
                'code' => $input['search']['code'] ?? ''
            ],
            'order' => [
                'title' => 'ASC'
            ]
        ];
        $result = $this->productsModel->listProducts($params);
        return $this->json($result);
    }
}
?>