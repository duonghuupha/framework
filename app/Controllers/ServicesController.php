<?php
class ServicesController extends Controller{
    protected $servicesModel; // khai báo su dung Model
    public function __construct(){
        $this->servicesModel = new Services();
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
        $result = $this->servicesModel->listServices($params);
        return $this->json($result);
    }

    function add(){
        $payload = $this->checkToken();
        $input = Input::all();
        if(count($this->servicesModel->dupliObjServices($input['code'], 0)) > 0){
            return $this->json([], 'error', 'Mã dịch vụ đã tồn tại');
        }else{
            $data = [
                'code' => $input['code'] ?? '',
                'name' => $input['name'] ?? '',
                'type' => $input['type'] ?? '',
                'price' => $input['price'] ?? 0,
                'duration' => $input['duration'] ?? 0,
                'status' => 1,
                'note' => $input['note'] ?? ''
            ];
            $newServicesId = $this->servicesModel->addServices($data);
            return $this->json(['new_service_id' => $data]);
        }
    }

    function update($id){
        $payload = $this->checkToken();
        $input = Input::all();
        if(count($this->servicesModel->dupliObjServices($input['code'], (int)$id)) > 0){
            return $this->json([], 'error', 'Mã dịch vụ đã tồn tại');
        }else{
            $data = [
                'code' => $input['code'] ?? '',
                'name' => $input['name'] ?? '',
                'type' => $input['type'] ?? '',
                'price' => $input['price'] ?? 0,
                'duration' => $input['duration'] ?? 0,
                'note' => $input['note'] ?? ''
            ];
            $updated = $this->servicesModel->updateServices((int)$id, $data);
            return $this->json(['updated' => $updated]);
        }
    }

    function delete($id){
        $payload = $this->checkToken();
        $deleted = $this->servicesModel->deleteServices((int)$id);
        return $this->json(['deleted' => $deleted]);
    }

    function combo(){
        $payload = $this->checkToken();
        $input = Input::all();
        $result = $this->servicesModel->listComboServices($input['search']['name']);
        return $this->json($result);
    }

    function data_list(){
        $payload = $this->checkToken();
        $result = $this->servicesModel->listDataService('medical');
        return $this->json($result);
    }
}
?>