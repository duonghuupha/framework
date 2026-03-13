<?php
class ImportsController extends Controller{
    protected $importsModel; // Khai bao su dung Model
    public function __construct(){
        $this->importsModel = new Imports();
    }

    function index(){
        $payload = $this->checkToken();
        $input = Input::all();
        $params = [
            'page' => $input['page'] ?? 1,
            'limit' => $input['limit'] ?? 20,
            'search' => [
                'date_import' => $input['search']['name'] ?? '',
            ],
            'filters' => [],
            'order' => [
                'id' => 'DESC'
            ]
        ];
        $result = $this->importsModel->listImports($params);
        return $this->json($result);
    }
}
?>