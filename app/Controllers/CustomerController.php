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
                'name' => $input['search']['name'] ?? '',
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
                'name' => $input['name'] ?? '',
                'address' => $input['address'] ?? '',
                'phone' => $input['phone'] ?? ''
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
                'name' => $input['name'] ?? '',
                'address' => $input['address'] ?? '',
                'phone' => $input['phone'] ?? ''
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

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    function combo(){
        $payload = $this->checkToken();
        $input = Input::all();
        $result = $this->customerModel->listComboCustomer($input['search']['name']);
        return $this->json($result);
    }

    function debt($id){
        $payload = $this->checkToken();
        $total_s = 0; $total_t = 0;
        $total_seller = $this->customerModel->getDebtCustomer((int)$id);
        if(!empty($total_seller) && isset($total_seller[0]['total'])){
            $total_s = (float)$total_seller[0]['total'];
        }
        $total_thu = $this->customerModel->getSellerCustomer((int)$id);
        if(!empty($total_thu) && isset($total_thu[0]['total'])){
            $total_t = (float)$total_thu[0]['total'];
        }
        $debt = $total_t - $total_s;
        $data = [
            "customer_id" => $id,
            "debt" => $debt
        ];
        return $this->json(['debt' => $data]);
    }   
}
?>