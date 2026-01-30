<?php
class CategoriesController extends Controller{
    protected $categoriesModel; // khai báo su dung Model
    public function __construct(){
        $this->categoriesModel = new Categories();
    }

    function index(){
    }

    function combo(){
        $payload = $this->checkToken();
        $result = $this->categoriesModel->listCombo();
        return $this->json($result);
    }
}
?>