<?php
class ManufacturerController extends Controller{
    protected $manuModel; // khai báo su dung Model
    public function __construct(){
        $this->manuModel = new Manufacturer();
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
        $result = $this->manuModel->listSupplier($params);
        return $this->json($result);
    }

    function add(){
        $payload = $this->checkToken();
        $input = Input::all();
        $data = [
            'name' => $input['name'] ?? '',
            'address' => $input['address'] ?? '',
            'phone' => $input['phone'] ?? ''
        ];
        $newCustomerId = $this->manuModel->addSupplier($data);
        return $this->json(['new_supplier_id' => $data]);
    }

    function update($id){
        $payload = $this->checkToken();
        $input = Input::all();
        $data = [
            'name' => $input['name'] ?? '',
            'address' => $input['address'] ?? '',
            'phone' => $input['phone'] ?? ''
        ];
        $updated = $this->manuModel->updateSupplier((int)$id, $data);
        return $this->json(['updated' => $updated]);
    }

    function delete($id){
        $payload = $this->checkToken();
        $deleted = $this->manuModel->deleteSupplier((int)$id);
        return $this->json(['deleted' => $deleted]);
    }
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    function combo(){
        $payload = $this->checkToken();
        $result = $this->manuModel->listCombo();
        return $this->json($result);
    }

    public function debt($id){
        $payload = $this->checkToken();
        if (empty($id)) {
            return $this->json([], 'error', 'Thiếu nhà cung cấp.');
        }
        $result = $this->manuModel->getDebtSupplier($id);
        return $this->json($result);
    }  
}
?>