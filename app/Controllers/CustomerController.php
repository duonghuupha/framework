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
                'title' => $input['search']['name'] ?? '',
                'phone' => $input['search']['name'] ?? '',
            ],
            'filters' => [],
            'order' => [
                'id' => 'DESC'
            ]
        ];
        $result = $this->customerModel->listCustomer($params);
        return $this->json($result);
    }

    function add(){
        $payload = $this->checkToken();
        $input = Input::all();
        if(count($this->customerModel->dupliObjCustomer($input['code'], 0)) > 0){
            return $this->json([], 'error', 'Mã khách hàng đã tồn tại');
        }else{
            $data = [
                'code' => $input['code'] ?? '',
                'title' => $input['title'] ?? '',
                'address' => $input['address'] ?? '',
                'phone' => $input['phone'] ?? '',
                'ghi_chu' => $input['ghi_chu'] ?? ''
            ];
            $newCustomerId = $this->customerModel->addCustomer($data);
            return $this->json(['new_customer_id' => $data]);
        }
    }

    function update($id){
        $payload = $this->checkToken();
        $input = Input::all();
        if(count($this->customerModel->dupliObjCustomer($input['code'], (int)$id)) > 0){
            return $this->json([], 'error', 'Mã khách hàng đã tồn tại');
        }else{
            $data = [
                'code' => $input['code'] ?? '',
                'title' => $input['title'] ?? '',
                'address' => $input['address'] ?? '',
                'phone' => $input['phone'] ?? '',
                'ghi_chu' => $input['ghi_chu'] ?? ''
            ];
            $updated = $this->customerModel->updateCustomer((int)$id, $data);
            return $this->json(['updated' => $updated]);
        }
    }

    function delete($id){
        $payload = $this->checkToken();
        $deleted = $this->customerModel->deleteCustomer((int)$id);
        return $this->json(['deleted' => $deleted]);
    }
}
?>