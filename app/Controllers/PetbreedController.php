<?php
class PetbreedController extends Controller{
    protected $petbreedModel; // khai báo su dung Model
    public function __construct(){
        $this->petbreedModel = new Petbreed();
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
            'order' => ['id' => 'DESC']
        ];
        $result = $this->petbreedModel->listPetbreed($params);
        return $this->json($result);
    }

    function add(){
        $payload = $this->checkToken();
        $input = Input::all();
        $data = [
            'name' => $input['name'] ?? '',
            'species' => $input['species'] ?? ''
        ];
        $newUnits = $this->petbreedModel->addPetbreed($data);
        return $this->json(['new_petbreeds_id' => $data]);
    }

    function update($id){
        $payload = $this->checkToken();
        $input = Input::all();
        $data = [
            'name' => $input['name'] ?? '',
            'species' => $input['species'] ?? ''
        ];
        $updated = $this->petbreedModel->updatePetbreed((int)$id, $data);
        return $this->json(['updated' => $updated]);
    }

    function delete($id){
        $payload = $this->checkToken();
        $deleted = $this->petbreedModel->deletePetbreed((int)$id);
        return $this->json(['deleted' => $deleted]);
    }

    function combo(){
        $payload = $this->checkToken();
        $result = $this->categoriesModel->listCombo();
        return $this->json($result);
    }
}
?>