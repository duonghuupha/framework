<?php
class SellersController extends Controller{
    protected $sellersModel; // Khai bao su dung Model
    public function __construct(){
        $this->sellersModel = new Sellers();
    }

    function index(){
        $payload = $this->checkToken();
        $input = Input::all();
        $params = [
            'page' => $input['page'] ?? 1,
            'limit' => $input['limit'] ?? 20,
            'search' => [
                'date_seller' => $input['search']['name'] ?? '',
            ],
            'filters' => [],
            'order' => [
                'id' => 'DESC'
            ]
        ];
        $result = $this->sellersModel->listSellers($params);
        return $this->json($result);
    }

    function add(){
        $payload = $this->checkToken();
        $input = Input::all();
        if(count($this->importsModel->dupliObjImports($input['code'], 0)) > 0){
            return $this->json([], 'error', 'Mã phiếu nhập đã tồn tại');
        }else{
            $data = [
                'code' => $input['code'] ?? '',
                'nhacungcap_id' => $input['nhacungcap_id'] ?? '',
                'date_import' => $input['date_import'] ?? '',
                'total_qty' => $input['total_qty'] ?? '',
                'total_price' => $input['total_price'] ?? '',
                'ghi_chu' => $input['ghi_chu'] ?? '',
                'status' => $input['status'] ?? ''
            ];
            $newImports = $this->importsModel->addImports($data);

            if(!$newImports){
                return $this->json([], 'error', "Không tạo được phiếu nhập");
            }

            if(!empty($input['products'])){
                foreach($input['products'] as $row){
                    $detail = [
                        'code' => $input['code'],
                        'id_product' => $row['id'],
                        'qty' => $row['quantity'],
                        'imp_price' => $row['price'],
                        'exp_price' => $row['exp_price']
                    ];
                    $this->importsModel->addImportsDetail($detail);
                }
            }

            return $this->json(['new_imports_id' => $newImports]);
        }
    }
}
?>