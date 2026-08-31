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
            return $this->json(['new_customer_id' => $newCustomerId]);
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

    public function debt($id){
        $payload = $this->checkToken();
        if (empty($id)) {
            return $this->json([], 'error', 'Thiếu khách hàng.');
        }
        $result = $this->customerModel->getDebtCustomer($id);
        return $this->json($result);
    }  

    public function info($id){
        $payload = $this->checkToken();
        $result = $this->customerModel->getCustomerInfo($id);
        return $this->json($result);
    }

    public function history($id){
        $payload = $this->checkToken();
        $input = Input::all();
        $date_start = $input['search']['date_start'] ?? '';
        $date_end = $input['search']['date_end'] ?? '';

        $date_from = '';
        if(!empty($date_start)){
            $objectDate = DateTime::createFromFormat('d/m/Y', $date_start);
            if($objectDate){
                $date_from = $objectDate->format('Y-m-d');
            }
        }
        $date_to = '';
        if(!empty($date_end)){
            $objectDate = DateTime::createFromFormat('d/m/Y', $date_end);
            if($objectDate){
                $date_to = $objectDate->format('Y-m-d');
            }
        }

        $params = [
            'page' => $input['page'] ?? 1,
            'limit' => $input['limit'] ?? 20,
            'search' => [
                'code' => $input['search']['code'] ?? '',
                'date_start' => $date_from,
                'date_end' => $date_to
            ],
            'filters' => [],
            'order' => ['id' => 'DESC']
        ];
        $result = $this->customerModel->getCustomerHistory($id, $params);
        return $this->json($result);
    }
}
?>