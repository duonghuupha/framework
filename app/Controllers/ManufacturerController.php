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