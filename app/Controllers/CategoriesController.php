<?php
class CategoriesController extends Controller{
    protected $categoriesModel; // khai báo su dung Model
    public function __construct(){
        $this->categoriesModel = new Categories();
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
        $result = $this->categoriesModel->listCategory($params);
        return $this->json($result);
    }

    function add(){
        $payload = $this->checkToken();
        $input = Input::all();
        $data = [
            'name' => $input['name'] ?? ''
        ];
        $newUnits = $this->categoriesModel->addCategory($data);
        return $this->json(['new_category_id' => $data]);
    }

    function update($id){
        $payload = $this->checkToken();
        $input = Input::all();
        $data = [
            'name' => $input['name'] ?? ''
        ];
        $updated = $this->categoriesModel->updateCategory((int)$id, $data);
        return $this->json(['updated' => $updated]);
    }

    function delete($id){
        $payload = $this->checkToken();
        $deleted = $this->categoriesModel->deleteCategory((int)$id);
        return $this->json(['deleted' => $deleted]);
    }

    function combo(){
        $payload = $this->checkToken();
        $result = $this->categoriesModel->listCombo();
        return $this->json($result);
    }
}
?>