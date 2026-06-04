<?php
class ImportsController extends Controller{
    protected $importsModel; // Khai bao su dung Model
    public function __construct(){
        $this->importsModel = new Imports();
    }

    function index(){
        $payload = $this->checkToken();
        $input = Input::all();
        $created_at = $input['search']['created_at'] ?? '';
        $date = '';

        if (!empty($created_at)) {
            $objectDate = DateTime::createFromFormat('d/m/Y', $created_at);

            if ($objectDate) {
                $date = $objectDate->format('Y-m-d');
            }
        }
        $params = [
            'page' => $input['page'] ?? 1,
            'limit' => $input['limit'] ?? 20,
            'search' => [
                'supplier_id' => $input['search']['supplier_id'] ?? '',
                'created_at' => $date,
                'product' => $input['search']['product'] ?? ''
            ],
            'filters' => [],
            'order' => [
                'id' => 'DESC'
            ]
        ];
        $result = $this->importsModel->listImports($params);
        return $this->json($result);
    }

    function add(){
        $payload = $this->checkToken();
        $input = Input::all();
        if(count($this->importsModel->dupliObjImports($input['code'], 0)) > 0){
            return $this->json([], 'error', 'Mã phiếu nhập đã tồn tại');
        }else{
            // tinh total
            $totalAmount = 0;
            foreach($input['products'] as $row){
                $qty = (float)($row['quantity'] ?? 0);
                $price = (float)($row['price'] ?? 0);
                $totalAmount += $qty * $price;
            }
            $data = [
                'code' => $input['code'] ?? '',
                'supplier_id' => $input['supplier_id'] ?? '',
                'created_at' => $input['created_at'].' '.date("H:i:s") ?? date("Y-m-d H:i:s"),
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'debt_amount' => $totalAmount,
                'status' => 'debt',
                'note' => $input['ghi_chu'] ?? ''
            ];
            $newImports = $this->importsModel->addImports($data);

            if(!$newImports){
                return $this->json([], 'error', "Không tạo được phiếu nhập");
            }

            if(!empty($input['products'])){
                foreach($input['products'] as $row){
                    $qty = (float)$row['quantity'];
                    $price = (float)$row['price'];
                    $detail = [
                        'import_id' => $newImports,
                        'product_id' => $row['id'],
                        'qty' => $qty,
                        'price' => $price,
                        'total' => $qty * $price
                    ];
                    $res = $this->importsModel->addImportsDetail($detail);
                    if(!$res){
                        return $this->json([], 'error', "Lỗi thêm chi tiết phiếu nhập");
                    }
                }
            }
            return $this->json(['import_id' => $newImports]);
        }
    }
}
?>