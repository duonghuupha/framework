<?php
class PersonnelController extends Controller{
    protected $personnelModel; // khai báo su dung Model
    public function __construct(){
        $this->personnelModel = new Personnel();
    }

    function index(){
        $payload = $this->checkToken();
        $input = Input::all();
        $params = [
            'page' => $input['page'] ?? 1,
            'limit' => $input['limit'] ?? 20,
            'search' => [
                'fullname' => $input['search']['fullname'] ?? '',
                'code' => $input['search']['code'] ?? ''
            ],
            'filters' => [
                'active' => $input['filters']['active'] ?? 1
            ],
            'order' => [
                'id' => $input['id'] ?? ''
            ]
        ];
        $result = $this->personnelModel->listPersonnel($params);
        return $this->json($result);
    }
}