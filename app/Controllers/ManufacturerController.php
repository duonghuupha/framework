<?php
class ManufacturerController extends Controller{
    protected $manuModel; // khai báo su dung Model
    public function __construct(){
        $this->manuModel = new Manufacturer();
    }

    function index(){
    }

    function combo(){
        $payload = $this->checkToken();
        $result = $this->manuModel->listCombo();
        return $this->json($result);
    }
}
?>