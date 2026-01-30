<?php
class UnitsController extends Controller{
    protected $unitsModel; // khai báo su dung Model
    public function __construct(){
        $this->unitsModel = new Units();
    }

    function index(){
    }

    function combo(){
        $payload = $this->checkToken();
        $result = $this->unitsModel->listCombo();
        return $this->json($result);
    }
}
?>