<?php
class CustomerController extends Controller{
    protected $customerModel; // Khai bao su dung Model
    public function __construct(){
        $this->customerModel = new Customer();
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
        $result = $this->customerModel->listCustomer($params);
        return $this->json($result);
    }
}
?>