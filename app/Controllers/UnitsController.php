<?php
class UnitsController extends Controller{
    protected $unitsModel; // khai báo su dung Model
    public function __construct(){
        $this->unitsModel = new Units();
    }

    function index(){
        $payload = $this->checkToken();
        $input = Input::all();
        $params = [
            'page' => $input['page'] ?? 1,
            'limit' => $input['limit'] ?? 20,
            'filters' => [],
            'order' => ['id' => 'DESC']
        ];
        $result = $this->unitsModel->listUnits($params);
        return $this->json($result);
    }

    function add(){
        $payload = $this->checkToken();
        $input = Input::all();
        $data = [
            'name' => $input['name'] ?? ''
        ];
        $newUnits = $this->unitsModel->addUnits($data);
        return $this->json(['new_units_id'] => $data);
    }

    function update($id){
        $payload = $this->checkToken();
        $input = Input::all();
        $data = [
            'name' => $input['name'] ?? ''
        ];
        $updated = $this->unitsModel->updateUnits((int)$id, $data);
        return $this->json(['updated'] => $updated);
    }

    function delete($id){
        $payload = $this->checkToken();
        $deleted = $this->unitsModel->deleteUnits((int)$id);
        return $this->json(['deleted' => $deleted]);
    }

    function combo(){
        $payload = $this->checkToken();
        $result = $this->unitsModel->listCombo();
        return $this->json($result);
    }
}
?>